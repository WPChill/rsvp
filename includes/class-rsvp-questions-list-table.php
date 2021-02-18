<?php
/**
 * Admin List Table
 *
 * @version 0.2.1
 */

class RSVP_Questions_List_Table extends RSVP_List_Table {

	public $stickies;

	/**
	 * Message to be displayed when there are no items
	 *
	 * @since  0.2.1
	 * @access public
	 */
	public function no_items(){
		_e( 'No questions found.', 'rsvp-plugin' );
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

		$this->items = $data;
	}

	/*@todo: For the moment the search function is disabled*/
	/*public function search_data( $search, $data = array() ){
		$items = array();
		foreach ( $data as $item ){
			if ( strtolower( $search ) == strtolower( $item['name'] ) ){
				$items[] = $item;
			}
		}
		return $items;
	}*/


	public function get_columns(){
		$columns = array(
				'cb'                 => __( 'ID', 'rsvp-plugin' ),
				'question'           => __( 'Question', 'rsvp-plugin' ),
				'private_import_key' => __( 'Private Import Key', 'rsvp-plugin' ),
		);

		return $columns;
	}

	public function get_hidden_columns(){
		return array();
	}

	public function get_sortable_columns(){
		return array(
				'question' => array( 'id', false ),
		);
	}

	public function usort_reorder( $a, $b ){

		// If no order, default to asc
		$order = ( !empty( $_GET['order'] ) ) ? $_GET['order'] : 'asc';

		$result = strcasecmp( $a['question'], $b['question'] );

		// Send final sort direction to usort
		return ( $order === 'asc' ) ? $result : -$result;
	}


	public function column_question( $item ){
		// Edit link
		$edit_link = add_query_arg( array(
				'page' => 'rsvp-admin-questions',
				'id'   => $item['id']
		), admin_url( 'admin.php' ) );

		// Delete link
		$delete_link = add_query_arg( array(
				'action' => 'delete-rsvp-question',
				'id'     => absint( $item['id'] )
		), admin_url( 'admin.php' ) );

		echo '<a class="row-title" href="' . $edit_link . '">' . esc_html( $item['question'] ) . '</a>';

		// Assemble links

		$actions           = array();
		$actions['edit']   = '<a href="' . $edit_link . '">' . __( 'Edit', 'rsvp-plugin' ) . '</a>';
		$actions['delete'] = "<a class='submitdelete' href='" . wp_nonce_url( $delete_link, 'delete-rsvp-question_' . absint( $item['id'] ) ) . "' onclick=\"if ( confirm( '" . esc_js( sprintf( __( 'Delete "%s"?', 'rsvp-plugin' ), esc_html( $item['question'] ) ) ) . "' ) ) { return true;} return false;\">" . __( 'Delete', 'rsvp-plugin' ) . '</a>';

		$actions = apply_filters( 'rsvp_questions_actions', $actions, $item );

		echo $this->row_actions( $actions );
	}

	public function column_default( $item, $column_name ){

		switch ( $column_name ){
			case 'question':
				$text = $item[ $column_name ];
				break;
			case 'private_import_key':
				$text = $item[ $column_name ];
				break;
			default:
				$text = $item[ $column_name ];
		}

		return apply_filters( "rsvp_question_list_column_$column_name", $text, $item );
	}

	/**
	 * Display the table
	 *
	 * @since  3.1.0
	 * @access public
	 */
	public function display(){
		$singular = $this->_args['singular'];
		// Disabling the table nav options to regain some real estate.
		//$this->display_tablenav( 'top' );
		?>
		<form id="posts-filter" method="get">
			<!--<p class="search-box">
				<label class="screen-reader-text"
					   for="post-search-input"><?php /*esc_html_e( 'Search', 'rsvp-plugin' ); */ ?></label>
				<input type="search" id="post-search-input" name="s"
					   value="<?php /*echo( isset( $_GET['s'] ) && !empty( $_GET['s'] ) ? $_GET['s'] : '' ) */ ?>">
				<input type="submit" id="search-submit" class="button"
					   value="<?php /*esc_html_e( 'Search', 'rsvp-plugin' ); */ ?>">
				<input type="hidden" name="post_type" class="post_type_page" value="wpm-testimonial">
				<input type="hidden" name="page" value="testimonial-views">
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
	 * @param $questions
	 *
	 * @return mixed
	 */
	function prepare_questions( $questions ){

		foreach ( $questions as $view ){

			$return[ $view->id ] = array(
					'id'       => $view->id,
					'question' => $view->question
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
		<input id="cb-select-<?php echo absint( $item['id'] ); ?>" type="checkbox" name="q[]"
			   value="<?php echo absint( $item['id'] ); ?>"/>
		<div class="locked-indicator">
			<span class="locked-indicator-icon" aria-hidden="true"></span>
		</div>
		<?php

	}
}
