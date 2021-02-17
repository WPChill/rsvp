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
	 * @since 0.2.1
	 * @access public
	 */
	public function no_items() {
		_e( 'No views found.', 'rsvp-plugin' );
	}

	public function prepare_list( $data = array() ) {

		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		// Sort the list
		if ( isset( $_GET['orderby'] ) ) {
			usort( $data, array( &$this, 'usort_reorder' ) );
		}

		/*if (isset($_GET['s']) && !empty($_GET['s'])) {
			$data = $this->search_data( $_GET['s'], $data );
		}*/
		$this->items = $data;
	}

	public function prepare_filters( $data = array() ) {
		$links = array();
		foreach ($data as $item) {
			$value = unserialize($item['value']);
			$links[$value['mode']][] = $item;
		}
		return $links;
	}

	public function filter_data( $mode, $data = array() ) {
		$items = array();
		foreach ($data as $item) {
			if ($mode == $item['data']['mode']) {
				$items[] = $item;
			}
		}
		return $items;
	}

	public function search_data( $search, $data = array() ) {
		$items = array();
		foreach ($data as $item) {
			if (strtolower($search) == strtolower($item['name'])) {
				$items[] = $item;
			}
		}
		return $items;
	}

	/**
	 * Move sticky views to the top
	 *
	 * @param $data
	 * @since 0.2.0
	 * @return array
	 */
	public function move_sticky( $data ) {
		$sticky_views = $views = array();
		foreach ( $data as $view ) {
			if ( in_array( $view['id'], $this->stickies ) ) {
				$sticky_views[] = $view;
			} else {
				$views[] = $view;
			}
		}

		return array_merge( $sticky_views, $views );
	}

	public function get_columns() {
		$columns = array(
			'id'        => __( 'ID', 'rsvp-plugin' ),
			//'sticky'    => __( 'Sticky', 'rsvp-plugin' ),
			'sticky'    => '',
			'name'      => __( 'Name', 'rsvp-plugin' ),
			'mode'      => __( 'Mode', 'rsvp-plugin' ),
			'template'  => __( 'Template', 'rsvp-plugin' ),
			'shortcode' => __( 'Shortcode', 'rsvp-plugin' ),
		);

		return $columns;
	}

	public function get_hidden_columns() {
		return array();
	}

	public function get_sortable_columns() {
		return array(
			'id'       => array( 'id', false ),
			'name'     => array( 'name', false ),
		);
	}

	public function usort_reorder( $a, $b ) {
		// If no sort, default to name
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'name';

		// If no order, default to asc
		$order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';

		// Determine sort order
		if ( 'id' == $orderby ) {
			$result = $this->cmp( intval( $a[ $orderby ] ), intval( $b[ $orderby ] ) );
		} else {
			$result = strcasecmp( $a[$orderby], $b[$orderby] );
		}

		// Send final sort direction to usort
		return ( $order === 'asc' ) ? $result : -$result;
	}

	public function cmp( $a, $b ) {
		if ( $a == $b ) {
			return 0;
		}

		return ( $a < $b ) ? -1 : 1;
	}

	public function column_name( $item ) {
		$screen = get_current_screen();
		$url    = $screen->parent_file;

		// Edit link
		$edit_link = $url . '&page=testimonial-views&action=edit&id=' . $item['id'];
		echo '<a class="row-title" href="' . $edit_link . '">' . $item['name'] . '</a>';

		// Duplicate link
		// @since 2.1.0
		$duplicate_link = $url . '&page=testimonial-views&action=duplicate&id=' . $item['id'];

		// Delete link
		$delete_link = 'admin.php?action=delete-strong-view&id=' . $item['id'];

		// Assemble links

		$actions              = array();
		$actions['edit']      = '<a href="' . $edit_link . '">' . __( 'Edit', 'rsvp-plugin' ) . '</a>';
		$actions['duplicate'] = '<a href="' . $duplicate_link . '">' . __( 'Duplicate', 'rsvp-plugin' ) . '</a>';
		$actions['delete']    = "<a class='submitdelete' href='" . wp_nonce_url( $delete_link, 'delete-strong-view_' . $item['id'] ) . "' onclick=\"if ( confirm( '" . esc_js( sprintf( __( 'Delete "%s"?', 'rsvp-plugin' ), $item['name'] ) ) . "' ) ) { return true;} return false;\">" . __( 'Delete', 'rsvp-plugin' ) . '</a>';

		$actions = apply_filters('rsvp_views_actions',$actions,$item);

		echo $this->row_actions( $actions );
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
				$text = $item['id'];
				break;
			case 'sticky':
				$stuck = $this->is_stuck( $item['id'] ) ? 'stuck' : '';
				$text = '<a href="#" class="stickit ' . $stuck . '" title="' . __( 'stick to top of list', 'rsvp-plugin' ) . '"></>';
				break;
			case 'name':
				$text = $item['name'];
				break;
			case 'mode':
				$mode = $item['data']['mode'];
				$text = $mode;
				$view_options = apply_filters( 'wpmtst_view_options', get_option( 'wpmtst_view_options' ) );
				if ( isset( $view_options['mode'][ $mode ]['label'] ) ) {
					$text = $view_options['mode'][ $mode ]['label'];
				}
				break;
			case 'template':
				if ( 'single_template' == $item['data']['mode'] ) {
					$text = __( 'theme single post template', 'rsvp-plugin' );
				} else {
					$text = $this->find_template( array( 'template' => $item['data']['template'] ) );
				}
				break;
			case 'shortcode':
				if ( 'single_template' == $item['data']['mode'] ) {
					$text = '';
				} else {
					$text = '[testimonial_view id="' . $item['id'] . '"]';
				}
				break;
			default:
				$text = print_r( $item, true );
		}

		return apply_filters( "rsvp_view_list_column_$column_name", $text, $item );
	}

	/**
	 * Display the table
	 *
	 * @since 3.1.0
	 * @access public
	 */
	public function display() {
		$singular = $this->_args['singular'];
		// Disabling the table nav options to regain some real estate.
		//$this->display_tablenav( 'top' );
		?>
		<form id="posts-filter" method="get">
			<p class="search-box">
				<label class="screen-reader-text" for="post-search-input"><?php esc_html_e( 'Search', 'rsvp-plugin' ); ?></label>
				<input type="search" id="post-search-input" name="s" value="<?php echo (isset($_GET['s']) && !empty($_GET['s']) ? $_GET['s'] : '') ?>">
				<input type="submit" id="search-submit" class="button" value="<?php esc_html_e( 'Search', 'rsvp-plugin' ); ?>">
				<input type="hidden" name="post_type" class="post_type_page" value="wpm-testimonial">
				<input type="hidden" name="page" value="testimonial-views">
			</p>
			<table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">
				<thead>
				<tr>
					<?php $this->print_column_headers(); ?>
				</tr>
				</thead>

				<tbody id="the-list"<?php
				if ( $singular ) {
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
			//$this->display_tablenav( 'bottom' );
			?>
		</form>
		<?php
	}

}
