<?php

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) :
	exit;
endif;

class RSVP_Events_List_Table extends RSVP_List_Table {

	public function __construct( $args = array() ){
		parent::__construct( array(
				'plural'   => 'events',
				'singular' => 'event',
				'ajax'     => false,
				'screen'   => null,
		) );
	}

	/**
	 * Message to be displayed when there are no items
	 *
	 * @since  2.7.2
	 * @access public
	 */
	public function no_items(){
		_e( 'No events found.', 'rsvp-plugin' );
	}

	public function prepare_items( $data = array() ){

		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->items = array(
				'event_name' => esc_html( 'General event', 'rsvp-plugin' ),
		);
	}

	public function get_columns(){

		$columns = array(
				'event_name' => __( 'Event Name', 'rsvp-plugin' ),
				'attendees'  => __( 'Attendees', 'rsvp-plugin' ),
		);

		return $columns;
	}

	public function get_hidden_columns(){
		return array();
	}

	public function get_sortable_columns(){
		return array();
	}


	/**
	 * @param $item
	 * @param $column_name
	 *
	 * @return mixed|void
	 * @since 4.4.8
	 */
	public function column_default( $item, $column_name ){
		switch ( $column_name ){
			case 'event_name':
				$text = esc_html( $item[ $column_name ] );
				break;
			default:
				$text = esc_html( $item[ $column_name ] );
		}

		return apply_filters( "rsvp_attendee_list_column_$column_name", $text, $item );
	}

	/**
	 * @param int $level
	 *
	 * @param     $item
	 *
	 * @since 4.4.8
	 */
	public function single_row( $item, $level = 0 ){
		?>
		<tr id="event-<?php echo $item['id']; ?>">
			<?php $this->single_row_columns( $item ); ?>
		</tr>
		<?php
	}


	/**
	 * Display the table
	 *
	 * @since  4.4.8
	 * @access public
	 */
	public function display(){

		$this->prepare_items();
		$singular = $this->_args['singular'];
		?>
		<form id="posts-filter" method="get">
			<p class="search-box">
				<label class="screen-reader-text"
					   for="post-search-input"><?php esc_html_e( 'Search', 'rsvp-plugin' ); ?></label>
				<input type="hidden" name="page" value="rsvp-pro-top-level">
				<input type="search" id="post-search-input" name="s"
					   value="<?php echo( isset( $_GET['s'] ) && !empty( $_GET['s'] ) ? $_GET['s'] : '' ) ?>">
				<input type="submit" id="search-submit" class="button"
					   value="<?php esc_html_e( 'Search event', 'rsvp-plugin' ); ?>">

			</p>
			<?php
			$this->display_tablenav( 'top' );
			?>
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
	 * The Attendees column output
	 *
	 * @param $item
	 *
	 * @since 4.4.8
	 */
	public function column_attendees( $item ){
		$url = add_query_arg( array(
				'page'   => 'rsvp-top-level',
		), admin_url( 'admin.php' ) );
		?>
		<a href="<?php echo esc_url( $url ); ?>"
		   title="Manage Attendees"><?php esc_html_e('Manage Attendees','rsvp-plugin') ?></a>
		<?php
		$actions = array();
		$actions = apply_filters( 'rsvp_pro_attendees_actions', $actions, $item );
		echo $this->row_actions( $actions );
	}

	/**
	 * The Attendees column output
	 *
	 * @param $item
	 *
	 * @since 4.4.8
	 */
	public function column_event_name( $item ){

		$links = array(
				'custom_questions' => array(
						'placeholder' => esc_html__( 'Custom Questions', 'rsvp-plugin' ),
						'url_vals'    => array(
								'page' => 'rsvp-admin-questions',
						),
				),
		);

		$actions = array();

		foreach ( $links as $key => $link ){
			$url             = add_query_arg( $link['url_vals'], admin_url( 'admin.php' ) );
			$actions[ $key ] = "<a href='" . esc_url( $url ) . "' >" . esc_html( $link['placeholder'] ) . '</a>';
		}

		$actions = apply_filters( 'rsvp_event_name_actions', $actions, $item );
		echo $item;
		echo $this->row_actions( $actions );
	}

}