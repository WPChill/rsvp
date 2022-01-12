<?php
/**
 * RSVP Attendees list table
 *
 * @version 2.7.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) :
	exit;
endif;


class RSVP_Attendees_List_Table extends RSVP_List_Table {

	public function __construct( $args = array() ) {
		parent::__construct(
			array(
				'plural'   => 'attendees',
				'singular' => 'attendee',
				'ajax'     => false,
				'screen'   => null,
			)
		);
	}

	/**
	 * Message to be displayed when there are no items
	 *
	 * @since  2.7.2
	 * @access public
	 */
	public function no_items() {
		esc_html_e( 'No attendees found.', 'rsvp' );
	}

	public function prepare_items( $data = array() ) {

		global $wpdb;

		$sql      = 'SELECT id, firstName, lastName, rsvpStatus, note, kidsMeal, additionalAttendee, veggieMeal, personalGreeting, passcode, email, rsvpDate FROM ' . ATTENDEES_TABLE;
		$values   = array();
		$order    = ' ORDER BY lastName, firstName ASC';
		$operator = 'WHERE';

		if ( isset( $_GET['s'] ) ) {
			$sql     .= " WHERE (firstName LIKE '%%%s%%'  OR  lastName LIKE '%%%s%%')";
			$values[] = sanitize_text_field( wp_unslash( $_GET['s'] ) );
			$values[] = sanitize_text_field( wp_unslash( $_GET['s'] ) );
			$operator = 'AND';
		}

		if ( isset( $_GET['event_list'] ) ) {

			switch ( $_GET['event_list'] ) {
				case 'yes_count':
					$sql .= " $operator rsvpStatus = 'YES'";
					break;
				case 'no_count':
					$sql .= " $operator rsvpStatus = 'NO'";
					break;
				case 'no_response_count':
					$sql .= " $operator rsvpStatus = 'NoResponse'";
					break;
				case 'kids_meal_count':
					$sql .= " $operator kidsMeal = 'Y'";
					break;
				case 'veggie_meal_count':
					$sql .= " $operator veggieMeal = 'Y'";
					break;
			}
		}

		if ( ! empty( $values ) ) {
			$data = $wpdb->get_results( $wpdb->prepare( $sql, $values ) );
		} else {
			$data = $wpdb->get_results( $sql );

		}

		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		// Sort the list
		if ( isset( $_GET['orderby'] ) ) {
			usort( $data, array( &$this, 'usort_reorder' ) );
		}
		$data = apply_filters( 'rsvp_list_views', $data );

		$this->items = $this->prepare_attendees( $data );
	}

	public function get_columns() {
		$columns = array(
			'cb'                   => __( 'ID', 'rsvp' ),
			'attendee'             => __( 'Attendee', 'rsvp' ),
			'rsvpStatus'           => __( 'RSVP Status', 'rsvp' ),
			'rsvpDate'             => __( 'RSVP Date', 'rsvp' ),
			'kidsMeal'             => __( 'Kids Meal', 'rsvp' ),
			'additionalAttendee'   => __( 'Additional Attendee', 'rsvp' ),
			'veggieMeal'           => __( 'Vegetarian', 'rsvp' ),
			'personalGreeting'     => __( 'Note', 'rsvp' ),
			'associated_attendees' => __( 'Associated Attendees', 'rsvp' ),
		);

		return $columns;
	}

	public function get_hidden_columns() {
		return array();
	}

	public function get_sortable_columns() {
		return array(
			'attendee'           => array( 'attendee', false ),
			'rsvpStatus'         => array( 'rsvpStatus', false ),
			'rsvpDate'           => array( 'rsvpDate', false ),
			'kidsMeal'           => array( 'kidsMeal', false ),
			'additionalAttendee' => array( 'additionalAttendee', false ),
			'veggieMeal'         => array( 'veggieMeal', false ),
		);
	}

	/**
	 * Sorting function
	 *
	 * @param $a
	 * @param $b
	 *
	 * @return int
	 * @since 2.7.2
	 */
	public function usort_reorder( $a, $b ) {
		// If no sort, default to name
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? sanitize_sql_orderby( wp_unslash( $_GET['orderby'] ) ) : 'attendee';

		// If no order, default to asc
		$order = ( ! empty( $_GET['order'] ) ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'asc';

		// Determine sort order
		if ( 'attendee' == $orderby ) {
			$result = $this->attendee_orderby( $a, $b );
		} else {
			$result = strcasecmp( $a->$orderby, $b->$orderby );
		}

		// Send final sort direction to usort
		return ( $order === 'asc' ) ? $result : - $result;
	}

	/**
	 * Sorting function
	 *
	 * @param $a
	 * @param $b
	 *
	 * @return int
	 * @since 2.7.2
	 */
	public function attendee_orderby( $a, $b ) {
		if ( $a->firstName == $b->firstName ) {
			return strcmp( $a->lastName, $b->lastName );
		} else {
			return strcmp( $a->firstName, $b->firstName );
		}

	}

	/**
	 * The Attendee column output
	 *
	 * @param $item
	 *
	 * @since 2.7.2
	 */
	public function column_attendee( $item ) {

		// Edit link
		$edit_link = add_query_arg(
			array(
				'page' => 'rsvp-admin-guest',
				'id'   => $item['id'],
			),
			admin_url( 'admin.php' )
		);

		// Delete link
		$delete_link = add_query_arg(
			array(
				'action' => 'delete-rsvp-attendee',
				'id'     => absint( $item['id'] ),
			),
			admin_url( 'admin.php' )
		);

		echo '<a class="row-title" href="' . esc_url( $edit_link ) . '">' . esc_html( $item['firstName'] . ' ' . $item['lastName'] ) . '</a>';

		// Assemble links

		$actions           = array();
		$actions['edit']   = '<a href="' . esc_url( $edit_link ) . '">' . esc_html__( 'Edit', 'rsvp' ) . '</a>';
		$actions['delete'] = "<a class='submitdelete' href='" . wp_nonce_url( $delete_link, 'delete-rsvp-attendee_' . absint( $item['id'] ) ) . "' onclick=\"if ( confirm( '" . esc_js( sprintf( __( 'Delete "%s"?', 'rsvp' ), esc_html( $item['firstName'] . ' ' . $item['lastName'] ) ) ) . "' ) ) { return true;} return false;\">" . __( 'Delete', 'rsvp' ) . '</a>';

		$actions = apply_filters( 'rsvp_attendees_actions', $actions, $item );

		echo wp_kses_post( $this->row_actions( $actions ) );
	}

	public function column_default( $item, $column_name ) {

		switch ( $column_name ) {

			case 'attendee':
				$text = esc_html( $item['firstName'] . ' ' . $item['lastName'] );
				break;
			case 'rsvpStatus':
				$text = ( isset( $item[ $column_name ] ) && $item[ $column_name ] && 'NoResponse' != $item[ $column_name ] ) ? $item[ $column_name ] : esc_html__( 'No response', 'rsvp' );
				break;
			case 'rsvpDate':
				if ( isset( $item[ $column_name ] ) && $item[ $column_name ] ) {
					$text = date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item[ $column_name ] ) );
				} else {
					$text = esc_html__( 'No date set', 'rsvp' );
				}
				break;
				break;
			case 'additionalAttendee':
				$text = ( isset( $item[ $column_name ] ) && $item[ $column_name ] && 'Y' == $item[ $column_name ] ) ? esc_html__( 'Yes', 'rsvp' ) : esc_html__( 'No', 'rsvp' );
				break;
			case 'veggieMeal':
				$text = ( isset( $item[ $column_name ] ) && $item[ $column_name ] && 'Y' == $item[ $column_name ] ) ? esc_html__( 'Yes', 'rsvp' ) : esc_html__( 'No', 'rsvp' );
				break;
			case 'kidsMeal':
				$text = ( isset( $item[ $column_name ] ) && $item[ $column_name ] && 'Y' == $item[ $column_name ] ) ? esc_html__( 'Yes', 'rsvp' ) : esc_html__( 'No', 'rsvp' );
				break;
			case 'associated_attendees':
				$rsvp_helper  = RSVP_Helper::get_instance();
				$associations = $rsvp_helper->get_associated_attendees( $item['id'] );
				$text         = '';
				foreach ( $associations as $a ) {
					$text .= htmlspecialchars( stripslashes( $a->firstName . ' ' . $a->lastName ) ) . '<br />';
				}
				break;
			default:
				$text = esc_html( $item[ $column_name ] );
		}

		return apply_filters( "rsvp_attendee_list_column_$column_name", $text, $item );
	}


	/**
	 * Display the table
	 *
	 * @since  2.7.2
	 * @access public
	 */
	public function display() {

		$singular = $this->_args['singular'];
		$this->prepare_items();
		$screen_options = get_user_meta( get_current_user_id(), 'rsvp_screen_options' );

		if ( $screen_options && isset( $screen_options[0]['pagesize'] ) ) {
			$pagesize = $screen_options[0]['pagesize'];
		} else {
			$pagesize = 25;
		}

		?>

		<div class="clear"></div>
		<!--</div>-->
		<form id="posts-filter" method="get">
			<p class="search-box">
				<label class="screen-reader-text"
					   for="post-search-input"><?php esc_html_e( 'Search', 'rsvp' ); ?></label>
				<input type="search" id="post-search-input" name="s"
					   value="<?php echo( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) : '' ); ?>">
				<input type="hidden" name="page" value="rsvp-top-level">
				<input type="hidden" id="post-pagesize" name="pagesize"
					   value="<?php echo( isset( $_GET['pagesize'] ) && ! empty( $_GET['pagesize'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['pagesize'] ) ) ) : esc_attr( $pagesize ) ); ?>">
				<input type="submit" id="search-submit" class="button"
					   value="<?php esc_html_e( 'Search attendee', 'rsvp' ); ?>">
			</p>
			<?php
			$this->display_tablenav( 'top' );
			?>
			<table class="wp-list-table <?php echo esc_attr( implode( ' ', $this->get_table_classes() ) ); ?>">
				<thead>
				<tr>
					<?php $this->print_column_headers(); ?>
				</tr>
				</thead>

				<tbody id="the-list"
				<?php
				if ( $singular ) {
					echo " data-wp-lists='list:".esc_attr($singular)."'";
				}
				?>
				>
				<?php $this->display_rows_or_placeholder(); ?>
				</tbody>

				<tfoot>
				<tr>
					<?php $this->print_column_headers( false ); ?>
				</tr>
				</tfoot>

			</table>
			<?php
			$this->display_tablenav( 'bottom' );
			?>
		</form>
		<?php
	}

	/**
	 * @param $attendees
	 *
	 * @return mixed
	 */
	function prepare_attendees( $attendees ) {

		$return = array();

		foreach ( $attendees as $view ) {

			$return[ $view->id ] = array(
				'id'                   => $view->id,
				'firstName'            => $view->firstName,
				'lastName'             => $view->lastName,
				'rsvpStatus'           => $view->rsvpStatus,
				'rsvpDate'             => $view->rsvpDate,
				'kidsMeal'             => $view->kidsMeal,
				'additionalAttendee'   => $view->additionalAttendee,
				'veggieMeal'           => $view->veggieMeal,
				'personalGreeting'     => $view->personalGreeting,
				'associated_attendees' => $view->email,
			);
		}

		return $return;
	}

	/**
	 * Handles the checkbox column output.
	 *
	 * @param $item
	 *
	 * @since 2.7.2
	 */
	public function column_cb( $item ) {
		?>
		<input id="cb-select-<?php echo absint( $item['id'] ); ?>" type="checkbox" name="attendee[]"
			   value="<?php echo absint( $item['id'] ); ?>"/>
		<div class="locked-indicator">
			<span class="locked-indicator-icon" aria-hidden="true"></span>
		</div>
		<?php

	}

	/**
	 * Set bulk actions
	 *
	 * @return array
	 * @Since 2.7.2
	 */
	public function get_bulk_actions() {

		$actions = array(
			'delete' => __( 'Delete', 'rsvp' ),
		);

		return $actions;
	}

	/**
	 * Get an associative array ( id => link ) with the list
	 * of views available on this table.
	 *
	 * @return array
	 * @since  2.7.2
	 * @access protected
	 *
	 */
	protected function get_views() {
		global $wpdb;
		$yesResults        = $wpdb->get_results( 'SELECT COUNT(*) AS yesCount FROM ' . ATTENDEES_TABLE . " WHERE rsvpStatus = 'Yes'" );
		$noResults         = $wpdb->get_results( 'SELECT COUNT(*) AS noCount FROM ' . ATTENDEES_TABLE . " WHERE rsvpStatus = 'No'" );
		$noResponseResults = $wpdb->get_results( 'SELECT COUNT(*) AS noResponseCount FROM ' . ATTENDEES_TABLE . " WHERE rsvpStatus = 'NoResponse'" );
		$kidsMeals         = $wpdb->get_results( 'SELECT COUNT(*) AS kidsMealCount FROM ' . ATTENDEES_TABLE . " WHERE kidsMeal = 'Y'" );
		$veggieMeals       = $wpdb->get_results( 'SELECT COUNT(*) AS veggieMealCount FROM ' . ATTENDEES_TABLE . " WHERE veggieMeal = 'Y'" );
		$all               = $wpdb->get_results( 'SELECT COUNT(*) AS allCount FROM ' . ATTENDEES_TABLE );

		if ( isset( $_GET['event_list'] ) && '' != sanitize_text_field( wp_unslash( $_GET['event_list'] ) ) ) {
			$class = sanitize_text_field( wp_unslash( $_GET['event_list'] ) );
		} else {
			$class = 'all';
		}

		return array(
			'all'               => '<a
		href="' . admin_url( 'admin.php?page=rsvp-top-level' ) . '"
		class="' . ( ( 'all' == $class ) ? 'current' : '' ) . '">' . esc_html__( 'All', 'rsvp' ) . ' <span class="count">(' . intval( $all[0]->allCount ) . ')</span></a>',
			'yes_count'         => '<a
		href="' . admin_url( 'admin.php?page=rsvp-top-level' ) . '&event_list=yes_count"
		class="' . ( ( 'yes_count' == $class ) ? 'current' : '' ) . '">' . esc_html__( 'Yes', 'rsvp' ) . ' <span class="count">(' . intval( $yesResults[0]->yesCount ) . ')</a>',
			'no_count'          => '<a
		href="' . admin_url( 'admin.php?page=rsvp-top-level' ) . '&event_list=no_count"
		class="' . ( ( 'no_count' == $class ) ? 'current' : '' ) . '">' . esc_html__( 'No', 'rsvp' ) . ' <span class="count">(' . intval( $noResults[0]->noCount ) . ')</span></a>',
			'no_response_count' => '<a
		href="' . admin_url( 'admin.php?page=rsvp-top-level' ) . '&event_list=no_response_count"
		class="' . ( ( 'no_response_count' == $class ) ? 'current' : '' ) . '">' . esc_html__( 'No response', 'rsvp' ) . ' <span class="count">(' . intval( $noResponseResults[0]->noResponseCount ) . ')</span></a>',
			'kids_meal'         => '<a
		href="' . admin_url( 'admin.php?page=rsvp-top-level' ) . '&event_list=kids_meal_count"
		class="' . ( ( 'kids_meal_count' == $class ) ? 'current' : '' ) . '">' . esc_html__( 'Kids Meal', 'rsvp' ) . ' <span class="count">(' . intval( $kidsMeals[0]->kidsMealCount ) . ')</span></a>',
			'veggie'            => '<a
		href="' . admin_url( 'admin.php?page=rsvp-top-level' ) . '&event_list=veggie_meal_count"
		class="' . ( ( 'veggie_meal_count' == $class ) ? 'current' : '' ) . '">' . esc_html__( 'Veggie Meal', 'rsvp' ) . ' <span class="count">(' . intval( $veggieMeals[0]->veggieMealCount ) . ')</span></a>',
		);
	}

}
