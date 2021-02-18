<?php
/**
 * RSVP Attendees list table
 *
 * @version 2.7.2
 */

class RSVP_Attendees_List_Table extends RSVP_List_Table {

	/**
	 * Message to be displayed when there are no items
	 *
	 * @since  2.7.2
	 * @access public
	 */
	public function no_items(){
		_e( 'No attendees found.', 'rsvp-plugin' );
	}

	public function prepare_list( $data = array() ){

		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		// Sort the list
		if ( isset( $_GET['orderby'] ) ){
			usort( $data, array( &$this, 'usort_reorder' ) );
		}
		$data = apply_filters( 'rsvp_list_views', $data );

		$this->items = $data;
	}


	//@todo: For the moment search is not included
	/*public function search_data( $search, $data = array() ){
		$items = array();
		foreach ( $data as $item ){
			if ( strtolower( $search ) == strtolower( $item['attendee'] ) ){
				$items[] = $item;
			}
		}
		return $items;
	}*/


	public function get_columns(){
		$columns = array(
				'cb'                   => __( 'ID', 'rsvp-plugin' ),
				'attendee'             => __( 'Attendee', 'rsvp-plugin' ),
				'rsvpStatus'           => __( 'RSVP Status', 'rsvp-plugin' ),
				'rsvpDate'             => __( 'RSVP Date', 'rsvp-plugin' ),
				'kidsMeal'             => __( 'Kids Meal', 'rsvp-plugin' ),
				'additionalAttendee'   => __( 'Additional Attendee', 'rsvp-plugin' ),
				'veggieMeal'           => __( 'Vegetarian', 'rsvp-plugin' ),
				'personalGreeting'     => __( 'Note', 'rsvp-plugin' ),
				'associated_attendees' => __( 'Associated Attendees', 'rsvp-plugin' ),
		);

		return $columns;
	}

	public function get_hidden_columns(){
		return array();
	}

	public function get_sortable_columns(){
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
	public function usort_reorder( $a, $b ){
		// If no sort, default to name
		$orderby = ( !empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'attendee';

		// If no order, default to asc
		$order = ( !empty( $_GET['order'] ) ) ? $_GET['order'] : 'asc';

		// Determine sort order
		if ( 'attendee' == $orderby ){
			$result = $this->attendee_orderby( $a, $b );
		} else {
			$result = strcasecmp( $a[ $orderby ], $b[ $orderby ] );
		}

		// Send final sort direction to usort
		return ( $order === 'asc' ) ? $result : -$result;
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
	public function attendee_orderby( $a, $b ){
		if ( $a["firstName"] == $b["firstName"] ){
			return strcmp( $a["lastName"], $b["lastName"] );
		} else {
			return strcmp( $a["firstName"], $b["firstName"] );
		}

	}

	/**
	 * The Attendee column output
	 *
	 * @param $item
	 *
	 * @since 2.7.2
	 */
	public function column_attendee( $item ){

		// Edit link
		$edit_link = add_query_arg( array(
				'page' => 'rsvp-admin-guest',
				'id'   => $item['id']
		), admin_url( 'admin.php' ) );

		// Delete link
		$delete_link = add_query_arg( array(
				'action' => 'delete-rsvp-attendee',
				'id'     => absint( $item['id'] )
		), admin_url( 'admin.php' ) );

		echo '<a class="row-title" href="' . $edit_link . '">' . esc_html( $item['firstName'] . ' ' . $item['lastName'] ) . '</a>';

		// Assemble links

		$actions           = array();
		$actions['edit']   = '<a href="' . $edit_link . '">' . __( 'Edit', 'rsvp-plugin' ) . '</a>';
		$actions['delete'] = "<a class='submitdelete' href='" . wp_nonce_url( $delete_link, 'delete-rsvp-attendee_' . absint( $item['id'] ) ) . "' onclick=\"if ( confirm( '" . esc_js( sprintf( __( 'Delete "%s"?', 'rsvp-plugin' ), esc_html( $item['firstName'] . ' ' . $item['lastName'] ) ) ) . "' ) ) { return true;} return false;\">" . __( 'Delete', 'rsvp-plugin' ) . '</a>';

		$actions = apply_filters( 'rsvp_views_actions', $actions, $item );

		echo $this->row_actions( $actions );
	}

	public function column_default( $item, $column_name ){

		switch ( $column_name ){

			case 'attendee':
				$text = esc_html( $item['firstName'] . ' ' . $item['lastName'] );
				break;
			case 'rsvpStatus':
				$text = ( isset( $item[ $column_name ] ) && $item[ $column_name ] && 'NoResponse' != $item[ $column_name ] ) ? $item[ $column_name ] : esc_html__( 'No response', 'rsvp-plugin' );
				break;
			case 'rsvpDate':
				$text = ( isset( $item[ $column_name ] ) && $item[ $column_name ] ) ? esc_html( $item[ $column_name ] ) : esc_html__( 'No date set', 'rsvp-plugin' );
				break;
			case 'additionalAttendee':
				$text = ( isset( $item[ $column_name ] ) && $item[ $column_name ] && 'Y' == $item[ $column_name ] ) ? esc_html__( 'Yes', 'rsvp-plugin' ) : esc_html__( 'No', 'rsvp-plugin' );
				break;
			case 'veggieMeal':
				$text = ( isset( $item[ $column_name ] ) && $item[ $column_name ] && 'Y' == $item[ $column_name ] ) ? esc_html__( 'Yes', 'rsvp-plugin' ) : esc_html__( 'No', 'rsvp-plugin' );
				break;
			case 'kidsMeal':
				$text = ( isset( $item[ $column_name ] ) && $item[ $column_name ] && 'Y' == $item[ $column_name ] ) ? esc_html__( 'Yes', 'rsvp-plugin' ) : esc_html__( 'No', 'rsvp-plugin' );
				break;
			case 'additionalAttendee':
				$text = ( isset( $item[ $column_name ] ) && 'N' == $item[ $column_name ] ) ? esc_html__( 'No', 'rsvp-plugin' ) : esc_html( $item[ $column_name ] );
				break;
			case 'associated_attendees':
				$rsvp_helper  = RSVP_Helper::get_instance();
				$associations = $rsvp_helper->get_associated_attendees( $item['id'] );
				$text         = '';
				foreach ( $associations as $a ){
					$text .= htmlspecialchars( stripslashes( $a->firstName . ' ' . $a->lastName ) ) . '<br />';
				}
				break;
			default:
				$text = esc_html( $item[ $column_name ] );
		}

		return apply_filters( "rsvp_view_list_column_$column_name", $text, $item );
	}


	/**
	 * Display the table
	 *
	 * @since  2.7.2
	 * @access public
	 */
	public function display(){
		$singular = $this->_args['singular'];
		// Disabling the table nav options to regain some real estate.
		//$this->display_tablenav( 'top' );
		?>
		<form id="posts-filter" method="get">
			<!--@todo: For the moment comment the search form-->
			<!--<p class="search-box">
				<label class="screen-reader-text"
					   for="post-search-input"><?php /*esc_html_e( 'Search', 'rsvp-plugin' ); */ ?></label>
				<input type="search" id="post-search-input" name="s"
					   value="<?php /*echo( isset( $_GET['s'] ) && !empty( $_GET['s'] ) ? $_GET['s'] : '' ) */ ?>">
				<input type="submit" id="search-submit" class="button"
					   value="<?php /*esc_html_e( 'Search', 'rsvp-plugin' ); */ ?>">
				<input type="hidden" name="post_type" class="post_type_page" value="wpm-testimonial">
				<input type="hidden" name="page" value="rsvp-attendee">
			</p>-->
			<table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">
				<thead>
				<tr>
					<?php $this->print_column_headers(); ?>
				</tr>
				</thead>

				<tbody id="the-list"<?php
				if ( $singular ){
					echo " data-wp-lists='list:$singular'";
				} ?>>
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
	function prepare_attendees( $attendees ){

		foreach ( $attendees as $view ){

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
	public function column_cb( $item ){
		?>
		<input id="cb-select-<?php echo absint( $item['id'] ); ?>" type="checkbox" name="attendee[]"
			   value="<?php echo absint( $item['id'] ); ?>"/>
		<div class="locked-indicator">
			<span class="locked-indicator-icon" aria-hidden="true"></span>
		</div>
		<?php

	}

}
