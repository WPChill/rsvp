<?php
/**
 * Base class for displaying a list of items in an ajaxified HTML table.
 *
 * -!- This is a copy of /wp-admin/includes/class-wp-list-table.php. -!-
 * See Code Reference for reasoning.
 *
 * @link   https://developer.wordpress.org/reference/classes/wp_list_table/
 *
 * @since  2.7.2
 * @access private
 */

if ( ! class_exists( 'RSVP_List_Table' ) ) :

	class RSVP_List_Table {

		/**
		 * The current list of items
		 *
		 * @since  2.7.2
		 * @var array
		 * @access public
		 */
		public $items;

		/**
		 * Various information about the current table
		 *
		 * @since  2.7.2
		 * @var array
		 * @access protected
		 */
		protected $_args;

		/**
		 * Various information needed for displaying the pagination
		 *
		 * @since 2.7.2
		 * @var array
		 */
		protected $_pagination_args = array();

		/**
		 * The current screen
		 *
		 * @since  2.7.2
		 * @var object
		 * @access protected
		 */
		protected $screen;

		/**
		 * Cached bulk actions
		 *
		 * @since  2.7.2
		 * @var array
		 * @access private
		 */
		private $_actions;

		/**
		 * Cached pagination output
		 *
		 * @since  2.7.2
		 * @var string
		 * @access private
		 */
		private $_pagination;

		/**
		 * The view switcher modes.
		 *
		 * @since  2.7.2
		 * @var array
		 * @access protected
		 */
		protected $modes = array();

		/**
		 * Stores the value returned by ->get_column_info()
		 *
		 * @var array
		 */
		protected $_column_headers;

		protected $compat_fields = array( '_args', '_pagination_args', 'screen', '_actions', '_pagination' );

		protected $compat_methods = array(
			'set_pagination_args',
			'get_views',
			'get_bulk_actions',
			'bulk_actions',
			'row_actions',
			'view_switcher',
			'get_items_per_page',
			'pagination',
			'get_sortable_columns',
			'get_column_info',
			'get_table_classes',
			'display_tablenav',
			'extra_tablenav',
			'single_row_columns',
		);

		/**
		 * Constructor.
		 *
		 * The child class should call this constructor from its own constructor to override
		 * the default $args.
		 *
		 * @param array|string $args     {
		 *                               Array or string of arguments.
		 *
		 * @type string        $plural   Plural value used for labels and the objects being listed.
		 *                            This affects things such as CSS class-names and nonces used
		 *                            in the list table, e.g. 'posts'. Default empty.
		 * @type string        $singular Singular label for an object being listed, e.g. 'post'.
		 *                            Default empty
		 * @type bool          $ajax     Whether the list table supports AJAX. This includes loading
		 *                            and sorting data, for example. If true, the class will call
		 *                            the {@see _js_vars()} method in the footer to provide variables
		 *                            to any scripts handling AJAX events. Default false.
		 * @type string        $screen   String containing the hook name used to determine the current
		 *                            screen. If left null, the current screen will be automatically set.
		 *                            Default null.
		 * }
		 * @since  2.7.2
		 * @access public
		 *
		 */
		public function __construct( $args = array() ) {
			$args = wp_parse_args(
				$args,
				array(
					'plural'   => '',
					'singular' => '',
					'ajax'     => false,
					'screen'   => null,
				)
			);

			$this->screen = convert_to_screen( $args['screen'] );

			add_filter( "manage_{$this->screen->id}_columns", array( $this, 'get_columns' ), 0 );

			if ( ! $args['plural'] ) {
				$args['plural'] = $this->screen->base;
			}

			$args['plural']   = sanitize_key( $args['plural'] );
			$args['singular'] = sanitize_key( $args['singular'] );

			$this->_args = $args;

			if ( $args['ajax'] ) {
				// wp_enqueue_script( 'list-table' );
				add_action( 'admin_footer', array( $this, '_js_vars' ) );
			}

			if ( empty( $this->modes ) ) {
				$this->modes = array(
					'list'    => __( 'List View', 'rsvp' ),
					'excerpt' => __( 'Excerpt View', 'rsvp' ),
				);
			}
		}

		/**
		 * Make private properties readable for backwards compatibility.
		 *
		 * @param string $name Property to get.
		 *
		 * @return mixed Property.
		 * @since  2.7.2
		 * @access public
		 *
		 */
		public function __get( $name ) {
			if ( in_array( $name, $this->compat_fields ) ) {
				return $this->$name;
			}
		}

		/**
		 * Make private properties settable for backwards compatibility.
		 *
		 * @param string $name  Property to check if set.
		 * @param mixed  $value Property value.
		 *
		 * @return mixed Newly-set property.
		 * @since  2.7.2
		 * @access public
		 *
		 */
		public function __set( $name, $value ) {
			if ( in_array( $name, $this->compat_fields ) ) {
				return $this->$name = $value;
			}
		}

		/**
		 * Make private properties checkable for backwards compatibility.
		 *
		 * @param string $name Property to check if set.
		 *
		 * @return bool Whether the property is set.
		 * @since  2.7.2
		 * @access public
		 *
		 */
		public function __isset( $name ) {
			if ( in_array( $name, $this->compat_fields ) ) {
				return isset( $this->$name );
			}
		}

		/**
		 * Make private properties un-settable for backwards compatibility.
		 *
		 * @param string $name Property to unset.
		 *
		 * @since  2.7.2
		 * @access public
		 *
		 */
		public function __unset( $name ) {
			if ( in_array( $name, $this->compat_fields ) ) {
				unset( $this->$name );
			}
		}

		/**
		 * Make private/protected methods readable for backwards compatibility.
		 *
		 * @param callable $name      Method to call.
		 * @param array    $arguments Arguments to pass when calling.
		 *
		 * @return mixed|bool Return value of the callback, false otherwise.
		 * @since  2.7.2
		 * @access public
		 *
		 */
		public function __call( $name, $arguments ) {
			if ( in_array( $name, $this->compat_methods ) ) {
				return call_user_func_array( array( $this, $name ), $arguments );
			}

			return false;
		}

		/**
		 * Checks the current user's permissions
		 *
		 * @since  2.7.2
		 * @access public
		 * @abstract
		 */
		public function ajax_user_can() {
			die( 'function RSVP_List_Table::ajax_user_can() must be over-ridden in a sub-class.' );
		}

		/**
		 * Prepares the list of items for displaying.
		 *
		 * @uses   RSVP_List_Table::set_pagination_args()
		 *
		 * @since  2.7.2
		 * @access public
		 * @abstract
		 */
		public function prepare_items() {
			die( 'function RSVP_List_Table::prepare_items() must be over-ridden in a sub-class.' );
		}

		/**
		 * An internal method that sets all the necessary pagination arguments
		 *
		 * @param array $args An associative array with information about the pagination
		 *
		 * @access protected
		 * @since  2.7.2
		 */
		protected function set_pagination_args( $args ) {
			$args = wp_parse_args(
				$args,
				array(
					'total_items' => 0,
					'total_pages' => 0,
					'per_page'    => 0,
				)
			);

			if ( ! $args['total_pages'] && $args['per_page'] > 0 ) {
				$args['total_pages'] = ceil( $args['total_items'] / $args['per_page'] );
			}

			// Redirect if page number is invalid and headers are not already sent.
			if ( ! headers_sent() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) && $args['total_pages'] > 0 && $this->get_pagenum() > $args['total_pages'] ) {
				wp_redirect( add_query_arg( 'paged', $args['total_pages'] ) );
				exit;
			}

			$this->_pagination_args = $args;
		}

		/**
		 * Access the pagination args.
		 *
		 * @param string $key Pagination argument to retrieve. Common values include 'total_items',
		 *                    'total_pages', 'per_page', or 'infinite_scroll'.
		 *
		 * @return int Number of items that correspond to the given pagination argument.
		 * @since  2.7.2
		 * @access public
		 *
		 */
		public function get_pagination_arg( $key ) {
			if ( 'page' == $key ) {
				return $this->get_pagenum();
			}

			if ( isset( $this->_pagination_args[ $key ] ) ) {
				return $this->_pagination_args[ $key ];
			}
		}

		/**
		 * Whether the table has items to display or not
		 *
		 * @return bool
		 * @since  2.7.2
		 * @access public
		 *
		 */
		public function has_items() {
			return ! empty( $this->items );
		}

		/**
		 * Message to be displayed when there are no items
		 *
		 * @since  2.7.2
		 * @access public
		 */
		public function no_items() {
			esc_html_e( 'No items found.', 'rsvp' );
		}

		/**
		 * Display the search box.
		 *
		 * @param string $text     The search button text
		 * @param string $input_id The search input id
		 *
		 * @since  2.7.2
		 * @access public
		 *
		 */
		public function search_box( $text, $input_id ) {
			if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) {
				return;
			}

			$input_id = $input_id . '-search-input';

			if ( ! empty( $_REQUEST['orderby'] ) ) {
				echo '<input type="hidden" name="orderby" value="' . esc_attr( sanitize_sql_orderby( wp_unslash( $_REQUEST['orderby'] ) ) ) . '">';
			}
			if ( ! empty( $_REQUEST['order'] ) ) {
				echo '<input type="hidden" name="order" value="' . esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) ) . '">';
			}
			if ( ! empty( $_REQUEST['post_mime_type'] ) ) {
				echo '<input type="hidden" name="post_mime_type" value="' . esc_attr( sanitize_mime_type( wp_unslash( $_REQUEST['post_mime_type'] ) ) ) . '">';
			}
			if ( ! empty( $_REQUEST['detached'] ) ) {
				echo '<input type="hidden" name="detached" value="' . esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['detached'] ) ) ) . '">';
			}
			?>
			<p class="search-box">
				<label class="screen-reader-text"
					   for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $text ); ?>:</label>
				<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s"
					   value="<?php esc_attr( _admin_search_query() ); ?>">
				<?php submit_button( $text, 'button', '', false, array( 'id' => 'search-submit' ) ); ?>
			</p>
			<?php
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
			return array();
		}

		/**
		 * Display the list of views available on this table.
		 *
		 * @since  2.7.2
		 * @access public
		 */
		public function views() {
			$views = $this->get_views();
			/**
			 * Filter the list of available list table views.
			 *
			 * The dynamic portion of the hook name, `$this->screen->id`, refers
			 * to the ID of the current screen, usually a string.
			 *
			 * @param array $views An array of available list table views.
			 *
			 * @since 2.7.2
			 *
			 */
			$views = apply_filters( "views_{$this->screen->id}", $views );

			if ( empty( $views ) ) {
				return;
			}

			echo "<ul class='subsubsub'>\n";
			$i = count( $views );
			foreach ( $views as $class => $view ) {
				echo "<li class='" . esc_attr( $class) . "'>" . wp_kses_post( $view ) . "</li>";
				if ( $i - 1 > 0 ) {
					echo ' | ';
				}
				$i --;
			}
			echo '</ul>';
		}

		/**
		 * Get an associative array ( option_name => option_title ) with the list
		 * of bulk actions available on this table.
		 *
		 * @return array
		 * @since  2.7.2
		 * @access protected
		 *
		 */
		protected function get_bulk_actions() {
			return array();
		}

		/**
		 * Display the bulk actions dropdown.
		 *
		 * @param string $which The location of the bulk actions: 'top' or 'bottom'.
		 *                      This is designated as optional for backwards-compatibility.
		 *
		 * @since  2.7.2
		 * @access protected
		 *
		 */
		protected function bulk_actions( $which = '' ) {
			if ( is_null( $this->_actions ) ) {
				$no_new_actions = $this->_actions = $this->get_bulk_actions();
				/**
				 * Filter the list table Bulk Actions drop-down.
				 *
				 * The dynamic portion of the hook name, `$this->screen->id`, refers
				 * to the ID of the current screen, usually a string.
				 *
				 * This filter can currently only be used to remove bulk actions.
				 *
				 * @param array $actions An array of the available bulk actions.
				 *
				 * @since 3.5.0
				 *
				 */
				$this->_actions = apply_filters( "bulk_actions-{$this->screen->id}", $this->_actions );
				$this->_actions = array_intersect_assoc( $this->_actions, $no_new_actions );
				$two            = '';
			} else {
				$two = '2';
			}

			if ( empty( $this->_actions ) ) {
				return;
			}

			// Temp fix for bottom bulk actions
			$two = '';

			echo "<label for='bulk-action-selector-" . esc_attr( $which ) . "' class='screen-reader-text'>" . esc_html__( 'Select bulk action', 'rsvp' ) . '</label>';
			echo "<select name='rsvp-bulk-action" . esc_attr( $two ) . "' id='rsvp-bulk-action-selector-" . esc_attr( $which ) . "'>\n";
			echo "<option value='-1' selected='selected'>" . esc_html__( 'Bulk Actions', 'rsvp' ) . "</option>\n";

			foreach ( $this->_actions as $name => $title ) {
				$class = 'edit' == $name ? 'hide-if-no-js' : '';

				echo "\t<option value='" . esc_attr( $name ) . "' class='" . esc_attr( $class ) . "'>" . esc_html( $title ) . "</option>\n";
			}

			echo "</select>\n";

			submit_button( __( 'Apply', 'rsvp' ), 'action', '', false, array( 'id' => "doaction$two" ) );
			echo "\n";
		}

		/**
		 * Get the current action selected from the bulk actions dropdown.
		 *
		 * @return string|bool The action name or False if no action was selected
		 * @since  2.7.2
		 * @access public
		 *
		 */
		public function current_action() {
			if ( isset( $_REQUEST['filter_action'] ) && ! empty( $_REQUEST['filter_action'] ) ) {
				return false;
			}

			if ( isset( $_REQUEST['action'] ) && - 1 != $_REQUEST['action'] ) {
				return sanitize_text_field( wp_unslash( $_REQUEST['action'] ) );
			}

			if ( isset( $_REQUEST['action2'] ) && - 1 != $_REQUEST['action2'] ) {
				return sanitize_text_field( wp_unslash( $_REQUEST['action2'] ) );
			}

			return false;
		}

		/**
		 * Generate row actions div
		 *
		 * @param array $actions        The list of actions
		 * @param bool  $always_visible Whether the actions should be always visible
		 *
		 * @return string
		 * @since  2.7.2
		 * @access protected
		 *
		 */
		protected function row_actions( $actions, $always_visible = false ) {
			$action_count = count( $actions );
			$i            = 0;

			if ( ! $action_count ) {
				return '';
			}

			$out = '<div class="' . ( $always_visible ? 'row-actions visible' : 'row-actions' ) . '">';
			foreach ( $actions as $action => $link ) {
				++ $i;
				( $i == $action_count ) ? $sep = '' : $sep = ' | ';
				$out                          .= "<span class='$action'>$link$sep</span>";
			}
			$out .= '</div>';

			return $out;
		}


		/**
		 * Display a view switcher
		 *
		 * @param string $current_mode
		 *
		 * @since  2.7.2
		 * @access protected
		 *
		 */
		protected function view_switcher( $current_mode ) {
			?>
			<input type="hidden" name="mode" value="<?php echo esc_attr( $current_mode ); ?>">
			<div class="view-switch">
				<?php
				foreach ( $this->modes as $mode => $title ) {
					$classes = array( 'view-' . $mode );
					if ( $current_mode == $mode ) {
						$classes[] = 'current';
					}
					printf(
						"<a href='%s' class='%s' id='view-switch-". esc_attr( $mode ) . "'><span class='screen-reader-text'>%s</span></a>\n",
						esc_url( add_query_arg( 'mode', $mode ) ),
						esc_attr( implode( ' ', $classes ) ),
						esc_html( $title )
					);
				}
				?>
			</div>
			<?php
		}


		/**
		 * Get the current page number
		 *
		 * @return int
		 * @since  2.7.2
		 * @access public
		 *
		 */
		public function get_pagenum() {
			$pagenum = isset( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : 0;

			if ( isset( $this->_pagination_args['total_pages'] ) && $pagenum > $this->_pagination_args['total_pages'] ) {
				$pagenum = $this->_pagination_args['total_pages'];
			}

			return max( 1, $pagenum );
		}

		/**
		 * Get number of items to display on a single page
		 *
		 * @param string $option
		 * @param int    $default
		 *
		 * @return int
		 * @since  2.7.2
		 * @access protected
		 *
		 */
		protected function get_items_per_page( $option, $default = 20 ) {
			$per_page = (int) get_user_option( $option );
			if ( empty( $per_page ) || $per_page < 1 ) {
				$per_page = $default;
			}

			/**
			 * Filter the number of items to be displayed on each page of the list table.
			 *
			 * The dynamic hook name, $option, refers to the `per_page` option depending
			 * on the type of list table in use. Possible values include: 'edit_comments_per_page',
			 * 'sites_network_per_page', 'site_themes_network_per_page', 'themes_network_per_page',
			 * 'users_network_per_page', 'edit_post_per_page', 'edit_page_per_page',
			 * 'edit_{$post_type}_per_page', etc.
			 *
			 * @param int $per_page Number of items to be displayed. Default 20.
			 *
			 * @since 2.7.2
			 *
			 */
			return (int) apply_filters( $option, $per_page );
		}

		/**
		 * Display the pagination.
		 *
		 * @param string $which
		 *
		 * @since  2.7.2
		 * @access protected
		 *
		 */
		protected function pagination( $which ) {
			if ( empty( $this->_pagination_args ) ) {
				return;
			}

			$total_items     = $this->_pagination_args['total_items'];
			$total_pages     = $this->_pagination_args['total_pages'];
			$infinite_scroll = false;
			if ( isset( $this->_pagination_args['infinite_scroll'] ) ) {
				$infinite_scroll = $this->_pagination_args['infinite_scroll'];
			}

			$output = '<span class="displaying-num">' . sprintf( _n( '1 item', '%s items', $total_items, 'rsvp' ), number_format_i18n( $total_items ) ) . '</span>';

			$current = $this->get_pagenum();

			$current_url = set_url_scheme( 'http://' . sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] )  ) . '/' . sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );

			$current_url = remove_query_arg(
				array(
					'hotkeys_highlight_last',
					'hotkeys_highlight_first',
				),
				$current_url
			);

			$page_links = array();

			$disable_first = $disable_last = '';
			if ( $current == 1 ) {
				$disable_first = ' disabled';
			}
			if ( $current == $total_pages ) {
				$disable_last = ' disabled';
			}
			$page_links[] = sprintf(
				"<a class='%s' title='%s' href='%s'>%s</a>",
				'first-page' . $disable_first,
				esc_attr__( 'Go to the first page', 'rsvp' ),
				esc_url( remove_query_arg( 'paged', $current_url ) ),
				'&laquo;'
			);

			$page_links[] = sprintf(
				"<a class='%s' title='%s' href='%s'>%s</a>",
				'prev-page' . $disable_first,
				esc_attr__( 'Go to the previous page', 'rsvp' ),
				esc_url( add_query_arg( 'paged', max( 1, $current - 1 ), $current_url ) ),
				'&lsaquo;'
			);

			if ( 'bottom' == $which ) {
				$html_current_page = $current;
			} else {
				$html_current_page = sprintf(
					"%s<input class='current-page' id='current-page-selector' title='%s' type='text' name='paged' value='%s' size='%d'>",
					'<label for="current-page-selector" class="screen-reader-text">' . __( 'Select Page', 'rsvp' ) . '</label>',
					esc_attr__( 'Current page', 'rsvp' ),
					$current,
					strlen( $total_pages )
				);
			}
			$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
			$page_links[]     = '<span class="paging-input">' . sprintf( _x( '%1$s of %2$s', 'paging', 'rsvp' ), $html_current_page, $html_total_pages ) . '</span>';

			$page_links[] = sprintf(
				"<a class='%s' title='%s' href='%s'>%s</a>",
				'next-page' . $disable_last,
				esc_attr__( 'Go to the next page', 'rsvp' ),
				esc_url( add_query_arg( 'paged', min( $total_pages, $current + 1 ), $current_url ) ),
				'&rsaquo;'
			);

			$page_links[] = sprintf(
				"<a class='%s' title='%s' href='%s'>%s</a>",
				'last-page' . $disable_last,
				esc_attr__( 'Go to the last page', 'rsvp' ),
				esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
				'&raquo;'
			);

			$pagination_links_class = 'pagination-links';
			if ( ! empty( $infinite_scroll ) ) {
				$pagination_links_class = ' hide-if-js';
			}
			$output .= "\n<span class='$pagination_links_class'>" . implode( "\n", $page_links ) . '</span>';

			if ( $total_pages ) {
				$page_class = $total_pages < 2 ? ' one-page' : '';
			} else {
				$page_class = ' no-pages';
			}
			$this->_pagination = "<div class='tablenav-pages" . esc_attr( $page_class ) . "'>" . wp_kses_post( $output ) . "</div>";

			echo wp_kses_post( $this->_pagination );
		}

		/**
		 * Get a list of columns. The format is:
		 * 'internal-name' => 'Title'
		 *
		 * @return array
		 * @since  2.7.2
		 * @access public
		 * @abstract
		 *
		 */
		public function get_columns() {
			die( 'function RSVP_List_Table::get_columns() must be over-ridden in a sub-class.' );
		}

		/**
		 * Get a list of sortable columns. The format is:
		 * 'internal-name' => 'orderby'
		 * or
		 * 'internal-name' => array( 'orderby', true )
		 *
		 * The second format will make the initial sorting order be descending
		 *
		 * @return array
		 * @since  2.7.2
		 * @access protected
		 *
		 */
		protected function get_sortable_columns() {
			return array();
		}

		/**
		 * Get a list of all, hidden and sortable columns, with filter applied
		 *
		 * @return array
		 * @since  2.7.2
		 * @access protected
		 *
		 */
		protected function get_column_info() {
			if ( isset( $this->_column_headers ) ) {
				return $this->_column_headers;
			}

			$columns = get_column_headers( $this->screen );
			$hidden  = get_hidden_columns( $this->screen );

			$sortable_columns = $this->get_sortable_columns();
			/**
			 * Filter the list table sortable columns for a specific screen.
			 *
			 * The dynamic portion of the hook name, `$this->screen->id`, refers
			 * to the ID of the current screen, usually a string.
			 *
			 * @param array $sortable_columns An array of sortable columns.
			 *
			 * @since 2.7.2
			 *
			 */
			$_sortable = apply_filters( "manage_{$this->screen->id}_sortable_columns", $sortable_columns );

			$sortable = array();
			foreach ( $_sortable as $id => $data ) {
				if ( empty( $data ) ) {
					continue;
				}

				$data = (array) $data;
				if ( ! isset( $data[1] ) ) {
					$data[1] = false;
				}

				$sortable[ $id ] = $data;
			}

			$this->_column_headers = array( $columns, $hidden, $sortable );

			return $this->_column_headers;
		}

		/**
		 * Return number of visible columns
		 *
		 * @return int
		 * @since  2.7.2
		 * @access public
		 *
		 */
		public function get_column_count() {
			list ( $columns, $hidden ) = $this->get_column_info();
			$hidden                    = array_intersect( array_keys( $columns ), array_filter( $hidden ) );

			return count( $columns ) - count( $hidden );
		}

		/**
		 * Print column headers, accounting for hidden and sortable columns.
		 *
		 * @param bool $with_id Whether to set the id attribute or not
		 *
		 * @since  2.7.2
		 * @access public
		 *
		 */
		public function print_column_headers( $with_id = true ) {
			list( $columns, $hidden, $sortable ) = $this->get_column_info();

			$current_url = set_url_scheme( 'http://' . sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) . '/' . sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
			$current_url = remove_query_arg( 'paged', $current_url );

			if ( isset( $_GET['orderby'] ) ) {
				$current_orderby = sanitize_sql_orderby( wp_unslash( $_GET['orderby'] ) );
			} else {
				$current_orderby = '';
			}

			if ( isset( $_GET['order'] ) && 'desc' === $_GET['order'] ) {
				$current_order = 'desc';
			} else {
				$current_order = 'asc';
			}

			if ( ! empty( $columns['cb'] ) ) {
				static $cb_counter = 1;
				$columns['cb']     = '<label class="screen-reader-text" for="cb-select-all-' . esc_attr( $cb_counter ) . '">' . esc_html__( 'Select All', 'rsvp' ) . '</label>'
								 . '<input id="cb-select-all-' . esc_attr( $cb_counter ) . '" type="checkbox" />';
				$cb_counter ++;
			}

			foreach ( $columns as $column_key => $column_display_name ) {
				$class = array( 'manage-column', "column-$column_key" );

				if ( in_array( $column_key, $hidden, true ) ) {
					$class[] = 'hidden';
				}

				if ( 'cb' === $column_key ) {
					$class[] = 'check-column';
				} elseif ( in_array( $column_key, array( 'posts', 'comments', 'links' ), true ) ) {
					$class[] = 'num';
				}

				if ( isset( $sortable[ $column_key ] ) ) {
					list( $orderby, $desc_first ) = $sortable[ $column_key ];

					if ( $current_orderby === $orderby ) {
						$order = 'asc' === $current_order ? 'desc' : 'asc';

						$class[] = 'sorted';
						$class[] = $current_order;
					} else {
						$order = strtolower( $desc_first );

						if ( ! in_array( $order, array( 'desc', 'asc' ), true ) ) {
							$order = $desc_first ? 'desc' : 'asc';
						}

						$class[] = 'sortable';
						$class[] = 'desc' === $order ? 'asc' : 'desc';
					}

					$column_display_name = sprintf(
						'<a href="%s"><span>%s</span><span class="sorting-indicator"></span></a>',
						esc_url( add_query_arg( compact( 'orderby', 'order' ), $current_url ) ),
						esc_html( $column_display_name )
					);
				}

				$tag   = ( 'cb' === $column_key ) ? 'td' : 'th';
				$scope = ( 'th' === $tag ) ? 'scope="col"' : '';
				$id    = $with_id ? "id='".esc_attr( $column_key ) ."'" : '';

				if ( ! empty( $class ) ) {
					$class = "class='" . esc_attr( implode( ' ', $class ) ) . "'";
				}

				echo wp_kses_post( "<$tag $scope $id $class>$column_display_name</$tag>" );
			}
		}

		/**
		 * Display the table
		 *
		 * @since  2.7.2
		 * @access public
		 */
		public function display() {
			$singular = $this->_args['singular'];

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
		}

		/**
		 * Get a list of CSS classes for the list table table tag.
		 *
		 * @return array List of CSS classes for the table tag.
		 * @since  2.7.2
		 * @access protected
		 *
		 */
		protected function get_table_classes() {
			return array( 'widefat', 'fixed', 'striped', $this->_args['plural'] );
		}

		/**
		 * Generate the table navigation above or below the table
		 *
		 * @param string $which
		 *
		 * @since  2.7.2
		 * @access protected
		 */
		protected function display_tablenav( $which ) {
			if ( 'top' == $which ) {
				wp_nonce_field( 'rsvp-bulk-' . $this->_args['plural'] );
			}
			?>
			<div class="tablenav <?php echo esc_attr( $which ); ?>">

				<div class="alignleft actions bulkactions">
					<?php $this->bulk_actions( $which ); ?>
				</div>
				<?php
				$this->extra_tablenav( $which );
				$this->pagination( $which );
				?>

				<br class="clear">
			</div>
			<?php
		}

		/**
		 * Extra controls to be displayed between bulk actions and pagination
		 *
		 * @param string $which
		 *
		 * @since  2.7.2
		 * @access protected
		 *
		 */
		protected function extra_tablenav( $which ) {
		}

		/**
		 * Generate the tbody element for the list table.
		 *
		 * @since  2.7.2
		 * @access public
		 */
		public function display_rows_or_placeholder() {
			if ( $this->has_items() ) {
				$this->display_rows();
			} else {
				echo '<tr class="no-items"><td class="colspanchange" colspan="' . esc_attr( $this->get_column_count() ) . '">';
				$this->no_items();
				echo '</td></tr>';
			}
		}

		/**
		 * Generate the table rows
		 *
		 * @since  2.7.2
		 * @access public
		 */
		public function display_rows() {
			foreach ( $this->items as $item ) {
				$this->single_row( $item );
			}
		}

		/**
		 * Generates content for a single row of the table
		 *
		 * @param object $item The current item
		 *
		 * @since  2.7.2
		 * @access public
		 *
		 */
		public function single_row( $item ) {
			echo '<tr id="question-' . absint( $item['id'] ) . '">';
			$this->single_row_columns( $item );
			echo '</tr>';
		}

		protected function column_default( $item, $column_name ) {
		}

		protected function column_cb( $item ) {
		}

		/**
		 * Generates the columns for a single row of the table
		 *
		 * @param object $item The current item
		 *
		 * @since  2.7.2
		 * @access protected
		 *
		 */
		protected function single_row_columns( $item ) {

			list( $columns, $hidden ) = $this->get_column_info();

			foreach ( $columns as $column_name => $column_display_name ) {
				$class = "class='" . esc_attr( $column_name) . " column-" . esc_attr( $column_name ) . "'";

				$style = '';
				if ( in_array( $column_name, $hidden ) ) {
					$style = ' style="display:none;"';
				}

				$attributes = "$class$style";

				if ( 'cb' == $column_name ) {
					echo '<th scope="row" class="check-column">';
					echo esc_html( $this->column_cb( $item ) );
					echo '</th>';
				} elseif ( method_exists( $this, 'column_' . $column_name ) ) {
					echo wp_kses_post( "<td $attributes>" );
					echo wp_kses_post( call_user_func( array( $this, 'column_' . $column_name ), $item ) );
					echo '</td>';
				} else {
					echo wp_kses_post( "<td $attributes>" );
					echo wp_kses_post( $this->column_default( $item, $column_name ) );
					echo '</td>';
				}
			}
		}

		/**
		 * Handle an incoming ajax request (called from admin-ajax.php)
		 *
		 * @since  2.7.2
		 * @access public
		 */
		public function ajax_response() {
			$this->prepare_items();

			ob_start();
			if ( ! empty( $_REQUEST['no_placeholder'] ) ) {
				$this->display_rows();
			} else {
				$this->display_rows_or_placeholder();
			}

			$rows = ob_get_clean();

			$response = array( 'rows' => $rows );

			if ( isset( $this->_pagination_args['total_items'] ) ) {
				$response['total_items_i18n'] = sprintf(
					_n( '1 item', '%s items', $this->_pagination_args['total_items'], 'rsvp' ),
					number_format_i18n( $this->_pagination_args['total_items'] )
				);
			}
			if ( isset( $this->_pagination_args['total_pages'] ) ) {
				$response['total_pages']      = $this->_pagination_args['total_pages'];
				$response['total_pages_i18n'] = number_format_i18n( $this->_pagination_args['total_pages'] );
			}

			die( wp_json_encode( $response ) );
		}

		/**
		 * Send required variables to JavaScript land
		 *
		 * @access public
		 */
		public function _js_vars() {
			$args = array(
				'class'  => get_class( $this ),
				'screen' => array(
					'id'   => $this->screen->id,
					'base' => $this->screen->base,
				),
			);

			printf( "<script type='text/javascript'>list_args = %s;</script>\n", wp_json_encode( $args ) );
		}
	}

endif;
