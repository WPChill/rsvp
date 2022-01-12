<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) :
	exit;
endif;

class RSVP_Events_List_Table extends RSVP_List_Table {

	public function __construct( $args = array() ) {
		parent::__construct(
			array(
				'plural'   => 'events',
				'singular' => 'event',
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
		esc_html_e( 'No events found.', 'rsvp' );
	}

	public function prepare_items( $data = array() ) {

		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->items = array(
			'event_name' => esc_html__( 'General event', 'rsvp' ),
		);
	}

	public function get_columns() {

		$columns = array(
			'event_name' => esc_html__( 'Event Name', 'rsvp' ),
			'attendees'  => esc_html__( 'Attendees', 'rsvp' ),
		);

		return $columns;
	}

	public function get_hidden_columns() {
		return array();
	}

	public function get_sortable_columns() {
		return array();
	}


	/**
	 * @param $item
	 * @param $column_name
	 *
	 * @return mixed|void
	 * @since 4.4.8
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
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
	public function single_row( $item, $level = 0 ) {
		?>
		<tr>
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
	public function display() {

		$this->prepare_items();
		$singular = $this->_args['singular'];
		?>
		<form id="posts-filter" method="get">
			<p class="search-box">
				<label class="screen-reader-text"
					   for="post-search-input"><?php esc_html_e( 'Search', 'rsvp' ); ?></label>
				<input type="hidden" name="page" value="rsvp-pro-top-level">
				<input type="search" id="post-search-input" name="s"
					   value="<?php echo( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) : '' ); ?>">
				<input type="submit" id="search-submit" class="button"
					   value="<?php esc_attr_e( 'Search event', 'rsvp' ); ?>">

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
					echo " data-wp-lists='list:". esc_attr( $singular ) . "'";
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
	 * The Attendees column output
	 *
	 * @param $item
	 *
	 * @since 4.4.8
	 */
	public function column_attendees( $item ) {
		$url = add_query_arg(
			array(
				'page' => 'rsvp-top-level',
			),
			admin_url( 'admin.php' )
		);
		?>
		<a href="<?php echo esc_url( $url ); ?>"
		   title="Manage Attendees"><?php esc_html_e( 'Manage Attendees', 'rsvp' ); ?></a>
		<?php
		$actions = array();
		$actions = apply_filters( 'rsvp_pro_attendees_actions', $actions, $item );
		echo wp_kses_post( $this->row_actions( $actions ) );
	}

	/**
	 * The Attendees column output
	 *
	 * @param $item
	 *
	 * @since 4.4.8
	 */
	public function column_event_name( $item ) {

		$links = array(
			'custom_questions' => array(
				'placeholder' => esc_html__( 'Custom Questions', 'rsvp' ),
				'url_vals'    => array(
					'page' => 'rsvp-admin-questions',
				),
			),
		);

		$actions = array();

		foreach ( $links as $key => $link ) {
			$url             = add_query_arg( $link['url_vals'], admin_url( 'admin.php' ) );
			$actions[ $key ] = "<a href='" . esc_url( $url ) . "' >" . esc_html( $link['placeholder'] ) . '</a>';
		}

		$actions = apply_filters( 'rsvp_event_name_actions', $actions, $item );
		echo wp_kses_post( $item );
		echo wp_kses_post( $this->row_actions( $actions ) );
	}

}
