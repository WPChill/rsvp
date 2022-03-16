<?php
/**
 * Admin List Table
 *
 * @version 0.2.1
 */

class RSVP_Questions_List_Table extends RSVP_List_Table {

	public function __construct( $args = array() ) {
		parent::__construct(
			array(
				'plural'   => 'questions',
				'singular' => 'question',
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
		esc_html_e( 'No questions found.', 'rsvp' );
	}

	/**
	 * Prepare our data
	 *
	 * @param array $data
	 *
	 * @since 2.7.2
	 */
	public function prepare_items( $data = array() ) {

		global $wpdb;

		if ( isset( $_GET['s'] ) ) {
			$sql  = 'SELECT id, question, sortOrder, permissionLevel FROM ' . QUESTIONS_TABLE . " WHERE question LIKE '%%%s%%' ORDER BY sortOrder ASC";
			$data = $wpdb->get_results( $wpdb->prepare( $sql, sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) );
		} else {
			$sql  = 'SELECT id, question, sortOrder, permissionLevel FROM ' . QUESTIONS_TABLE . ' ORDER BY sortOrder ASC';
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

		$this->items = $this->prepare_questions( $data );
	}


	/**
	 * Question columns
	 *
	 * @return array
	 * @Since 2.7.2
	 */
	public function get_columns() {
		$columns = array(
			'cb'                 => __( 'ID', 'rsvp' ),
			'question'           => __( 'Question', 'rsvp' ),
			'private_import_key' => __( 'Private Import Key', 'rsvp' ),
		);

		return $columns;
	}

	public function get_hidden_columns() {
		return array();
	}

	/**
	 * Sortable columns
	 *
	 * @return array[]
	 * @since 2.7.2
	 */
	public function get_sortable_columns() {
		return array(
			'question' => array( 'id', false ),
		);
	}

	/**
	 * Sor our questions
	 *
	 * @param $a
	 * @param $b
	 *
	 * @return int|lt
	 * @since 2.7.2
	 */
	public function usort_reorder( $a, $b ) {

		// If no order, default to asc
		$order = ( ! empty( $_GET['order'] ) ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'asc';

		$result = strcasecmp( $a['question'], $b['question'] );

		// Send final sort direction to usort
		return ( $order === 'asc' ) ? $result : - $result;
	}


	/**
	 * Question column
	 *
	 * @param $item
	 *
	 * @since 2.7.2
	 */
	public function column_question( $item ) {
		// Edit link
		$edit_link = add_query_arg(
			array(
				'page'   => 'rsvp-admin-questions',
				'action' => 'add',
				'id'     => $item['id'],
			),
			admin_url( 'admin.php' )
		);

		// Delete link
		$delete_link = add_query_arg(
			array(
				'action' => 'delete-rsvp-question',
				'id'     => absint( $item['id'] ),
			),
			admin_url( 'admin.php' )
		);

		echo '<a class="row-title" href="' . esc_url( $edit_link ) . '">' . esc_html( $item['question'] ) . '</a>';

		// Assemble links

		$actions           = array();
		$actions['edit']   = '<a href="' . esc_url( $edit_link ) . '">' . __( 'Edit', 'rsvp' ) . '</a>';
		$actions['delete'] = "<a class='submitdelete' href='" . wp_nonce_url( $delete_link, 'delete-rsvp-question_' . absint( $item['id'] ) ) . "' onclick=\"if ( confirm( '" . esc_js( sprintf( __( 'Delete "%s"?', 'rsvp' ), esc_html( $item['question'] ) ) ) . "' ) ) { return true;} return false;\">" . __( 'Delete', 'rsvp' ) . '</a>';

		$actions = apply_filters( 'rsvp_questions_actions', $actions, $item );

		echo wp_kses_post( $this->row_actions( $actions ) );
	}

	/**
	 * Default column
	 *
	 * @param $item
	 * @param $column_name
	 *
	 * @return mixed|void
	 * @since 2.7.2
	 */
	public function column_default( $item, $column_name ) {

		switch ( $column_name ) {
			case 'question':
				$text = $item[ $column_name ];
				break;
			case 'private_import_key':
				$text = isset( $item[ $column_name ] ) ? $item[ $column_name ] : '';
				break;
			default:
				$text = $item[ $column_name ];
		}

		return apply_filters( "rsvp_question_list_column_$column_name", $text, $item );
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
		<form id="posts-filter" method="get">
			<p class="search-box">
				<label class="screen-reader-text"
					   for="post-search-input"><?php esc_html_e( 'Search', 'rsvp' ); ?></label>
				<input type="search" id="post-search-input" name="s"
					   value="<?php echo( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) : '' ); ?>">
				<input type="hidden" name="page" value="rsvp-admin-questions">
				<input type="hidden" id="post-pagesize" name="pagesize"
					   value="<?php echo( isset( $_GET['pagesize'] ) && ! empty( $_GET['pagesize'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['pagesize'] ) ) ) : esc_attr( wp_unslash( $pagesize ) ) ); ?>">
				<input type="submit" id="search-submit" class="button"
					   value="<?php esc_html_e( 'Search question', 'rsvp' ); ?>">
			</p>
			<?php
			$this->display_tablenav( 'top' );
			?>
			<table class="wp-list-table rsvp-plugin_page_rsvp-admin-questions <?php echo esc_attr( implode( ' ', $this->get_table_classes() ) ); ?>">
				<thead>
				<tr>
					<?php $this->print_column_headers(); ?>
				</tr>
				</thead>

				<tbody id="the-list"
				<?php
				if ( $singular ) {
					echo " data-wp-lists='list:" . esc_attr( $singular ) . "'";
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
	 * Prepare our questions for display
	 *
	 * @param $questions
	 *
	 * @return mixed
	 * @since 2.7.2
	 */
	function prepare_questions( $questions ) {

		$return = array();
		foreach ( $questions as $view ) {

			$return[ $view->id ] = array(
				'id'       => $view->id,
				'question' => $view->question,
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
		<input id="cb-select-<?php echo absint( $item['id'] ); ?>" type="checkbox" name="q[]"
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
}
