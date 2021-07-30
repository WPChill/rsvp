<?php

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) :
	exit;
endif;


if ( !class_exists( 'RSVP' ) ){

	class RSVP_Admin {

		/**
		 * Holds the class object.
		 *
		 * @since 2.7.2
		 *
		 * @var object
		 */
		public static $instance;

		/**
		 * RSVP_Admin constructor.
		 *
		 * @since 2.7.2
		 */
		function __construct(){
			add_action( 'admin_menu', array( $this, 'submenu_pages' ) );
			add_action( 'admin_init', array( $this, 'rsvp_register_settings' ) );

		}

		/**
		 * Returns the singleton instance of the class.
		 *
		 * @return object The RSVP_Admin object.
		 * @since 2.7.2
		 */
		public static function get_instance(){

			if ( !isset( self::$instance ) && !( self::$instance instanceof RSVP_Admin ) ){
				self::$instance = new RSVP_Admin();
			}

			return self::$instance;

		}

		/**
		 * Add submenu page
		 *
		 * @since 2.7.2
		 */
		public function submenu_pages(){

			$rsvp_helper = RSVP_Helper::get_instance();

			$page = add_menu_page(
					'RSVP',
					'RSVP',
					'publish_posts',
					'rsvp-events',
					array( $this, 'rsvp_admin_events' ),
					plugins_url( 'images/rsvp_lite_icon.png', RSVP_PLUGIN_FILE )
			);
			add_action( 'admin_print_scripts-' . $page, 'rsvp_admin_scripts' );

			$page = add_submenu_page(
					'rsvp-events',
					'Events',
					'Events',
					'publish_posts',
					'rsvp-events',
					array( $this, 'rsvp_admin_events' )
			);
			add_action( 'admin_print_scripts-' . $page, 'rsvp_admin_scripts' );

			$page = add_submenu_page(
					'rsvp-events',
					'Attendees',
					'Attendees',
					'publish_posts',
					'rsvp-top-level',
					array( $this, 'rsvp_admin_guestlist' )
			);
			add_action( 'admin_print_scripts-' . $page, 'rsvp_admin_scripts' );

			$page = add_submenu_page(
					'rsvp-events',
					'Add Guest',
					'Add Guest',
					'publish_posts',
					'rsvp-admin-guest',
					array( $this, 'rsvp_admin_guest' )
			);
			add_action( 'admin_print_scripts-' . $page, 'rsvp_admin_scripts' );

			add_submenu_page(
					'rsvp-events',
					'RSVP Export',
					'RSVP Export',
					'publish_posts',
					'rsvp-admin-export',
					array( $rsvp_helper, 'rsvp_admin_export' )
			);
			add_submenu_page(
					'rsvp-events',
					'RSVP Import',
					'RSVP Import',
					'publish_posts',
					'rsvp-admin-import',
					array( $rsvp_helper, 'rsvp_admin_import' )
			);
			$page = add_submenu_page(
					'rsvp-events',
					'Custom Questions',
					'Custom Questions',
					'publish_posts',
					'rsvp-admin-questions',
					array( $this, 'rsvp_admin_questions' )
			);
			add_action( 'admin_print_scripts-' . $page, 'rsvp_admin_scripts' );

			$page = add_submenu_page(
					'rsvp-events',
					'RSVP Settings',       // page title
					'RSVP Settings',       // subpage title
					'manage_options',      // access
					'rsvp-options',        // current file
					array( $this, 'rsvp_admin_guestlist_options' )    // options function above)
			);
			add_action( 'admin_print_scripts-' . $page, 'rsvp_admin_scripts' );

			$page = add_submenu_page(
					'rsvp-events',
					'Upgrade to Pro',
					'<span id="rsvp_upgrade_to_pro_link">Upgrade to Pro</span>',
					'publish_posts',
					'rsvp-upgrade-to-pro',
					'rsvp_upgrade_to_pro'
			);
			add_action( 'admin_print_scripts-' . $page, 'rsvp_admin_scripts' );


		}

		/**
		 * Events page
		 *
		 * @since 2.7.2
		 */
		public function rsvp_admin_events(){

			if ( get_option( 'rsvp_db_version' ) != RSVP_DB_VERSION ){
				rsvp_database_setup();
			}
			rsvp_install_passcode_field();

			?>

			<div class="wrap">
				<div id="icon-edit" class="icon32"><br/></div>
				<h1 class="wp-heading-inline"><?php echo __( 'RSVP Events', 'rsvp-plugin' ); ?></h1>
				<hr class="wp-header-end">

				<?php
				do_action('rsvp_events_before_table');
				$views_table = new RSVP_Events_List_Table();
				$views_table->display();
				do_action('rsvp_events_after_table');
				?>
			</div>
			<?php
		}

		/**
		 * Attendees page
		 *
		 * @since 2.7.2
		 */
		public function rsvp_admin_guestlist(){
			global $wpdb;
			if ( get_option( 'rsvp_db_version' ) != RSVP_DB_VERSION ){
				rsvp_database_setup();
			}
			rsvp_install_passcode_field();

			?>

			<div class="wrap">
				<div id="icon-edit" class="icon32"><br/></div>
				<h1 class="wp-heading-inline"><?php echo __( 'List of current attendees', 'rsvp-plugin' ); ?></h1>
				<a class="page-title-action"
				   href="<?php echo add_query_arg( array( 'page' => 'rsvp-admin-guest' ), admin_url( 'admin.php' ) ); ?>"><?php _e( 'Add Guest', 'rsvp-plugin' ); ?></a>
				<hr class="wp-header-end">
				<?php

				$views_table = new RSVP_Attendees_List_Table();
				$views_table->views();
				$views_table->display();
				?>
			</div>
			<?php
		}

		/**
		 * Guest add page
		 *
		 * @since 2.7.2
		 */
		public function rsvp_admin_guest(){
			global $wpdb;
			$rsvp_helper = RSVP_Helper::get_instance();
			echo '<div class="wrap"><h1 class="wp-heading-inline">' . esc_html__( 'Add guest', 'rsvp-plugin' ) . '</h1><hr class="wp-header-end">';
			if ( ( count( $_POST ) > 0 ) && !empty( $_POST['firstName'] ) && !empty( $_POST['lastName'] ) ){
				check_admin_referer( 'rsvp_add_guest' );
				$passcode = ( isset( $_POST['passcode'] ) ) ? $_POST['passcode'] : '';

				if ( isset( $_POST['attendeeId'] ) && is_numeric( $_POST['attendeeId'] ) && ( $_POST['attendeeId'] > 0 ) ){
					$wpdb->update(
							ATTENDEES_TABLE,
							array(
									'firstName'        => rsvp_smart_quote_replace( trim( wp_unslash( $_POST['firstName'] ) ) ),
									'lastName'         => rsvp_smart_quote_replace( trim( wp_unslash( $_POST['lastName'] ) ) ),
									'email'            => trim( wp_unslash( $_POST['email'] ) ),
									'personalGreeting' => trim( wp_unslash( $_POST['personalGreeting'] ) ),
									'rsvpStatus'       => trim( wp_unslash( $_POST['rsvpStatus'] ) ),
							),
							array( 'id' => $_POST['attendeeId'] ),
							array( '%s', '%s', '%s', '%s', '%s' ),
							array( '%d' )
					);
					$attendeeId = $_POST['attendeeId'];
					$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . ASSOCIATED_ATTENDEES_TABLE . ' WHERE attendeeId = %d', $attendeeId ) );
					$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . ASSOCIATED_ATTENDEES_TABLE . ' WHERE associatedAttendeeID = %d', $attendeeId ) );
				} else {
					$wpdb->insert(
							ATTENDEES_TABLE,
							array(
									'firstName'        => rsvp_smart_quote_replace( trim( $_POST['firstName'] ) ),
									'lastName'         => rsvp_smart_quote_replace( trim( $_POST['lastName'] ) ),
									'email'            => trim( $_POST['email'] ),
									'personalGreeting' => trim( $_POST['personalGreeting'] ),
									'rsvpStatus'       => trim( $_POST['rsvpStatus'] ),
							),
							array( '%s', '%s', '%s', '%s', '%s' )
					);

					$attendeeId = $wpdb->insert_id;
				}
				if ( isset( $_POST['associatedAttendees'] ) && is_array( $_POST['associatedAttendees'] ) ){
					foreach ( $_POST['associatedAttendees'] as $aid ){
						if ( is_numeric( $aid ) && ( $aid > 0 ) ){
							$wpdb->insert(
									ASSOCIATED_ATTENDEES_TABLE,
									array(
											'attendeeID'           => $attendeeId,
											'associatedAttendeeID' => $aid,
									),
									array( '%d', '%d' )
							);
							$wpdb->insert(
									ASSOCIATED_ATTENDEES_TABLE,
									array(
											'attendeeID'           => $aid,
											'associatedAttendeeID' => $attendeeId,
									),
									array( '%d', '%d' )
							);
						}
					}
				}

				if ( rsvp_require_passcode() ){
					if ( empty( $passcode ) ){
						$passcode = rsvp_generate_passcode();
					}
					if ( rsvp_require_unique_passcode() && !rsvp_is_passcode_unique( $passcode, $attendeeId ) ){
						$passcode = rsvp_generate_passcode();
					}
					$wpdb->update(
							ATTENDEES_TABLE,
							array( 'passcode' => trim( $passcode ) ),
							array( 'id' => $attendeeId ),
							array( '%s' ),
							array( '%d' )
					);
				}
				?>
				<p><?php echo sprintf( __( 'Attendee %1$s %2$s has been successfully saved.', 'rsvp-plugin' ),
							htmlspecialchars( stripslashes( $_POST['firstName'] ) ),
							htmlspecialchars( stripslashes( $_POST['lastName'] ) )
					); ?></p>
				<p>
					<a href="<?php echo add_query_arg( array( 'page' => 'rsvp-top-level' ), admin_url( 'admin.php' ) ); ?>"
					   class="button button-secondary"><?php echo __( 'Continue to Attendee List', 'rsvp-plugin' ); ?></a>
					<a href="<?php echo add_query_arg( array( 'page' => 'rsvp-admin-guest' ), admin_url( 'admin.php' ) ) ?>"
					   class="button button-primary"><?php echo __( 'Add a Guest', 'rsvp-plugin' ); ?></a>
				</p>
				<?php
			} else {
				$attendee            = null;
				$associatedAttendees = array();
				$firstName           = '';
				$lastName            = '';
				$email               = '';
				$personalGreeting    = '';
				$rsvpStatus          = 'NoResponse';
				$passcode            = '';
				$attendeeId          = 0;

				if ( isset( $_GET['id'] ) && is_numeric( $_GET['id'] ) ){
					$attendee = $wpdb->get_row( 'SELECT id, firstName, lastName, email, personalGreeting, rsvpStatus, passcode FROM ' . ATTENDEES_TABLE . ' WHERE id = ' . $_GET['id'] );
					if ( $attendee != null ){
						$attendeeId       = $attendee->id;
						$firstName        = stripslashes( $attendee->firstName );
						$lastName         = stripslashes( $attendee->lastName );
						$email            = stripslashes( $attendee->email );
						$personalGreeting = stripslashes( $attendee->personalGreeting );
						$rsvpStatus       = $attendee->rsvpStatus;
						$passcode         = stripslashes( $attendee->passcode );

						// Get the associated attendees and add them to an array
						$associations = $wpdb->get_results(
								'SELECT associatedAttendeeID FROM ' . ASSOCIATED_ATTENDEES_TABLE . ' WHERE attendeeId = ' . $attendee->id .
								' UNION ' .
								'SELECT attendeeID FROM ' . ASSOCIATED_ATTENDEES_TABLE . ' WHERE associatedAttendeeID = ' . $attendee->id
						);
						foreach ( $associations as $aId ){
							$associatedAttendees[] = $aId->associatedAttendeeID;
						}
					}
				}
				?>
				<div class="rsvp-left-panel">
					<form name="contact" action="admin.php?page=rsvp-admin-guest" method="post">
						<?php wp_nonce_field( 'rsvp_add_guest' ); ?>
						<input type="hidden" name="attendeeId" value="<?php echo $attendeeId; ?>"/>
						<p class="submit">
							<input type="submit" class="button-primary" value="<?php _e( 'Save', 'rsvp-plugin' ); ?>"/>
						</p>
						<table class="form-table">
							<tr valign="top">
								<th scope="row"><label for="firstName"><?php echo __( 'First Name', 'rsvp-plugin' ); ?>
										:</label>
								</th>
								<td align="left"><input type="text" name="firstName" id="firstName" size="30"
														value="<?php echo htmlspecialchars( $firstName ); ?>"/></td>
							</tr>
							<tr valign="top">
								<th scope="row"><label for="lastName"><?php echo __( 'Last Name', 'rsvp-plugin' ); ?>
										:</label></th>
								<td align="left"><input type="text" name="lastName" id="lastName" size="30"
														value="<?php echo htmlspecialchars( $lastName ); ?>"/></td>
							</tr>
							<tr valign="top">
								<th scope="row"><label for="email"><?php echo __( 'Email', 'rsvp-plugin' ); ?>:</label>
								</th>
								<td align="left"><input type="text" name="email" id="email" size="30"
														value="<?php echo htmlspecialchars( $email ); ?>"/></td>
							</tr>
							<?php
							if ( rsvp_require_passcode() ){
								?>
								<tr valign="top">
									<th scope="row"><label for="passcode"><?php echo __( 'Passcode', 'rsvp-plugin' ); ?>
											:</label>
									</th>
									<td align="left"><input type="text" name="passcode" id="passcode" size="30"
															value="<?php echo htmlspecialchars( $passcode ); ?>"/></td>
								</tr>
								<?php
							}
							?>
							<tr>
								<th scope="row"><label
											for="rsvpStatus"><?php echo __( 'RSVP Status', 'rsvp-plugin' ); ?></label>
								</th>
								<td align="left">
									<select name="rsvpStatus" id="rsvpStatus" size="1">
										<option value="NoResponse"
												<?php
												echo( ( $rsvpStatus == 'NoResponse' ) ? ' selected="selected"' : '' );
												?>
										><?php echo __( 'No Response', 'rsvp-plugin' ); ?></option>
										<option value="Yes"
												<?php
												echo( ( $rsvpStatus == 'Yes' ) ? ' selected="selected"' : '' );
												?>
										><?php echo __( 'Yes', 'rsvp-plugin' ); ?></option>
										<option value="No"
												<?php
												echo( ( $rsvpStatus == 'No' ) ? ' selected="selected"' : '' );
												?>
										><?php echo __( 'No', 'rsvp-plugin' ); ?></option>
									</select>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row" valign="top"><label
											for="personalGreeting"><?php echo __( 'Custom Message', 'rsvp-plugin' ); ?>
										:</label>
								</th>
								<td align="left"><textarea name="personalGreeting" id="personalGreeting" rows="5"
														   cols="40"><?php echo htmlspecialchars( $personalGreeting ); ?></textarea>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php echo __( 'Associated Attendees', 'rsvp-plugin' ); ?>:</th>
								<td align="left">
									<p>
										<span style="margin-left: -5px;"><?php _e( 'Non-Associated Attendees', 'rsvp-plugin' ); ?></span>
										<span style="margin-left:26px;"><?php _e( 'Associated Attendees', 'rsvp-plugin' ); ?></span>
									</p>
									<select name="associatedAttendees[]" id="associatedAttendeesSelect"
											multiple="multiple"
											size="5"
											style="height: 200px;">
										<?php
										$attendees = $rsvp_helper->get_attendees();

										foreach ( $attendees as $a ){
											if ( $a->id != $attendeeId ){
												?>
												<option value="<?php echo $a->id; ?>"
														<?php echo( ( in_array( $a->id, $associatedAttendees ) ) ? 'selected="selected"' : '' ); ?>><?php echo htmlspecialchars( stripslashes( $a->firstName ) . ' ' . stripslashes( $a->lastName ) ); ?></option>
												<?php
											}
										}
										?>
									</select>
								</td>
							</tr>
							<?php
							if ( ( $attendee != null ) && ( $attendee->id > 0 ) ){
								$sql = 'SELECT question, answer FROM ' . ATTENDEE_ANSWERS . ' ans
					INNER JOIN ' . QUESTIONS_TABLE . ' q ON q.id = ans.questionID
					WHERE attendeeID = %d
					ORDER BY q.sortOrder';
								$aRs = $wpdb->get_results( $wpdb->prepare( $sql, $attendee->id ) );
								if ( count( $aRs ) > 0 ){
									?>
									<tr>
										<td colspan="2">
											<h4><?php echo __( 'Custom Questions Answered', 'rsvp-plugin' ); ?></h4>
											<table cellpadding="2" cellspacing="0" border="0" class="rsvp-answered-questions">
												<tr>
													<th><?php echo __( 'Question', 'rsvp-plugin' ); ?></th>
													<th><?php echo __( 'Answer', 'rsvp-plugin' ); ?></th>
												</tr>
												<?php
												foreach ( $aRs as $a ){
													?>
													<tr>
														<td><?php echo stripslashes( $a->question ); ?></td>
														<td><?php echo str_replace( '||', ', ', stripslashes( $a->answer ) ); ?></td>
													</tr>
													<?php
												}
												?>
											</table>
										</td>
									</tr>
									<?php
								}
							}
							?>
						</table>
						<p class="submit">
							<input type="submit" class="button-primary" value="<?php _e( 'Save', 'rsvp-plugin' ); ?>"/>
						</p>
					</form>
				</div>
				<div class="rsvp-right-panel">
					<?php do_action( 'rsvp_after_add_guest' ); ?>
				</div>
				<?php
			}
			echo '</div>'; // .wrap class div end
		}

		/**
		 * Questions page
		 *
		 * @since 2.7.2
		 */
		public function rsvp_admin_questions(){
			global $wpdb;
			$rsvp_helper = RSVP_Helper::get_instance();

			if ( isset( $_GET['action'] ) && ( 'add' === strtolower( $_GET['action'] ) ) ){
				rsvp_admin_custom_question();
				return;
			}


			?>
			<div class="wrap">
				<div id="icon-edit" class="icon32"><br/></div>
				<h1 class="wp-heading-inline"><?php echo __( 'List of current custom questions', 'rsvp-plugin' ); ?></h1>
				<a href="<?php echo add_query_arg( 'action', 'add' ); ?>"
				   class="page-title-action"><?php _e( 'Add New', 'rsvp' ); ?></a>
				<hr class="wp-header-end">
				<?php
				$questions_table = new RSVP_Questions_List_Table();
				$questions_table->display();

				do_action('rsvp_after_question_table');
				?>
			</div>
			<?php
		}

		/**
		 * Settings page
		 *
		 * @since 2.7.2
		 */
		public function rsvp_admin_guestlist_options(){
			global $wpdb;

			if ( rsvp_require_unique_passcode() ){
				$sql       = 'SELECT id, passcode FROM ' . ATTENDEES_TABLE . " a
				WHERE passcode <> '' AND (SELECT COUNT(*) FROM " . ATTENDEES_TABLE . ' WHERE passcode = a.passcode) > 1';
				$attendees = $wpdb->get_results( $sql );
				foreach ( $attendees as $a ){
					$wpdb->update(
							ATTENDEES_TABLE,
							array( 'passcode' => rsvp_generate_passcode() ),
							array( 'id' => $a->id ),
							array( '%s' ),
							array( '%d' )
					);
				}
			}

			if ( rsvp_require_passcode() ){
				rsvp_install_passcode_field();

				$sql       = 'SELECT id, passcode FROM ' . ATTENDEES_TABLE . " WHERE passcode = ''";
				$attendees = $wpdb->get_results( $sql );
				foreach ( $attendees as $a ){
					$wpdb->update(
							ATTENDEES_TABLE,
							array( 'passcode' => rsvp_generate_passcode() ),
							array( 'id' => $a->id ),
							array( '%s' ),
							array( '%d' )
					);
				}
			} ?>
			<script type="text/javascript" language="javascript">
				jQuery( document ).ready( function () {
					jQuery( "#rsvp_opendate" ).datepicker();
					jQuery( "#rsvp_deadline" ).datepicker();
				} );
			</script>
			<div class="wrap">
				<h2><?php echo __( 'RSVP Plugin Settings', 'rsvp-plugin' ); ?></h2>
				<div class="rsvp-left-panel">
					<form method="post" action="options.php">
					<?php settings_fields( 'rsvp-option-group' ); ?>
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><label
										for="rsvp_opendate"><?php echo __( 'RSVP Open Date:', 'rsvp-plugin' ); ?></label>
							</th>
							<td align="left"><input type="text" name="rsvp_opendate" id="rsvp_opendate"
													value="<?php echo htmlspecialchars( get_option( OPTION_OPENDATE ) ); ?>"/>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label
										for="rsvp_deadline"><?php echo __( 'RSVP Deadline:', 'rsvp-plugin' ); ?></label>
							</th>
							<td align="left"><input type="text" name="rsvp_deadline" id="rsvp_deadline"
													value="<?php echo htmlspecialchars( get_option( OPTION_DEADLINE ) ); ?>"/>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label
										for="rsvp_num_additional_guests"><?php echo __( 'Number of Additional Guests Allowed:', 'rsvp-plugin' ); ?></label>
							</th>
							<td align="left"><input type="text" name="rsvp_num_additional_guests"
													id="rsvp_num_additional_guests"
													value="<?php echo htmlspecialchars( get_option( OPTION_RSVP_NUM_ADDITIONAL_GUESTS ) ); ?>"/>
								<br/>
								<span class="description"><?php _e( 'Default is three', 'rsvp-plugin' ); ?></span>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label
										for="rsvp_custom_greeting"><?php echo __( 'Custom Greeting:', 'rsvp-plugin' ); ?></label>
							</th>
							<td align="left"><textarea name="rsvp_custom_greeting" id="rsvp_custom_greeting" rows="5"
													   cols="60"><?php echo htmlspecialchars( get_option( OPTION_GREETING ) ); ?></textarea>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label
										for="rsvp_custom_welcome"><?php echo __( 'Custom Welcome:', 'rsvp-plugin' ); ?></label>
							</th>
							<td align="left">
						<textarea name="rsvp_custom_welcome" id="rsvp_custom_welcome" rows="5"
								  cols="60"><?php echo htmlspecialchars( get_option( OPTION_WELCOME_TEXT ) ); ?></textarea>
								<br/>
								<span class="description"><?php _e( 'Default is: &quot;There are a few more questions we need to ask you if you could please fill them out below to finish up the RSVP process.&quot;', 'rsvp-plugin' ); ?></span>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label
										for="<?php echo OPTION_RSVP_EMAIL_TEXT; ?>"><?php echo __( 'Email Text: <br />Sent to guests in confirmation, at top of email', 'rsvp-plugin' ); ?></label>
							</th>
							<td align="left"><textarea name="<?php echo OPTION_RSVP_EMAIL_TEXT; ?>"
													   id="<?php echo OPTION_RSVP_EMAIL_TEXT; ?>" rows="5"
													   cols="60"><?php echo htmlspecialchars( get_option( OPTION_RSVP_EMAIL_TEXT ) ); ?></textarea>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label
										for="rsvp_custom_question_text"><?php echo __( 'RSVP Question Verbiage:', 'rsvp-plugin' ); ?></label>
							</th>
							<td align="left">
								<input type="text" name="rsvp_custom_question_text" id="rsvp_custom_question_text"
									   value="<?php echo htmlspecialchars( get_option( OPTION_RSVP_QUESTION ) ); ?>"
									   size="65"/>
								<br/>
								<span class="description"><?php echo __( 'Default is: &quot;So, how about it?&quot;', 'rsvp-plugin' ); ?></span>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label
										for="rsvp_yes_verbiage"><?php echo __( 'RSVP Yes Verbiage:', 'rsvp-plugin' ); ?></label>
							</th>
							<td align="left"><input type="text" name="rsvp_yes_verbiage" id="rsvp_yes_verbiage"
													value="<?php echo htmlspecialchars( get_option( OPTION_YES_VERBIAGE ) ); ?>"
													size="65"/>
								<br/>
								<span class="description"><?php _e( 'Default is: &quot;Yes, I will attend.&quot;', 'rsvp-plugin' ); ?></span>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label
										for="rsvp_no_verbiage"><?php echo __( 'RSVP No Verbiage:', 'rsvp-plugin' ); ?></label>
							</th>
							<td align="left"><input type="text" name="rsvp_no_verbiage" id="rsvp_no_verbiage"
													value="<?php echo htmlspecialchars( get_option( OPTION_NO_VERBIAGE ) ); ?>"
													size="65"/>
								<br/>
								<span class="description"><?php _e( 'Default is: &quot;No, I will not be able to attend.&quot;', 'rsvp-plugin' ); ?></span>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label
										for="rsvp_kids_meal_verbiage"><?php echo __( 'RSVP Kids Meal Verbiage:', 'rsvp-plugin' ); ?></label>
							</th>
							<td align="left"><input type="text" name="rsvp_kids_meal_verbiage"
													id="rsvp_kids_meal_verbiage"
													value="<?php echo htmlspecialchars( get_option( OPTION_KIDS_MEAL_VERBIAGE ) ); ?>"
													size="65"/>
								<br/>
								<span class="description"><?php _e( 'Default is: &quot;We have the option of getting cheese pizza for the kids (and only kids). Do you want pizza instead of \'adult food?\'&quot;', 'rsvp-plugin' ); ?></span>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label
										for="rsvp_hide_kids_meal"><?php echo __( 'Hide Kids Meal Question:', 'rsvp-plugin' ); ?></label>
							</th>
							<td align="left"><input type="checkbox" name="rsvp_hide_kids_meal" id="rsvp_hide_kids_meal"
													value="Y" <?php echo( ( get_option( OPTION_HIDE_KIDS_MEAL ) == 'Y' ) ? ' checked="checked"' : '' ); ?> />
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label
										for="rsvp_veggie_meal_verbiage"><?php echo __( 'RSVP Vegetarian Meal Verbiage:', 'rsvp-plugin' ); ?></label>
							</th>
							<td align="left"><input type="text" name="rsvp_veggie_meal_verbiage"
													id="rsvp_veggie_meal_verbiage"
													value="<?php echo htmlspecialchars( get_option( OPTION_VEGGIE_MEAL_VERBIAGE ) ); ?>"
													size="65"/>
								<br/>
								<span class="description"><?php _e( 'Default is: &quot;We also have the option of getting individual vegetarian meals instead of the fish or meat. Would you like a vegetarian dinner?&quot;', 'rsvp-plugin' ); ?></span>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label
										for="rsvp_hide_veggie"><?php echo __( 'Hide Vegetarian Meal Question:', 'rsvp-plugin' ); ?></label>
							</th>
							<td align="left"><input type="checkbox" name="rsvp_hide_veggie" id="rsvp_hide_veggie"
													value="Y" <?php echo( ( get_option( OPTION_HIDE_VEGGIE ) == 'Y' ) ? ' checked="checked"' : '' ); ?> />
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label
										for="rsvp_note_verbiage"><?php echo __( 'Note Verbiage:', 'rsvp-plugin' ); ?></label>
							</th>
							<td align="left"><textarea name="rsvp_note_verbiage" id="rsvp_note_verbiage" rows="3"
													   cols="60"><?php
									echo htmlspecialchars( get_option( OPTION_NOTE_VERBIAGE ) );
									?></textarea>
								<br/>
								<span class="description"><?php _e( 'Default is: &quot;If you have any food allergies, please indicate what they are in the &quot;notes&quot; section below. Or, if you just want to send us a note, please feel free. If you have any questions, please send us an email.&quot;', 'rsvp-plugin' ); ?></span>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label
										for="rsvp_hide_note_field"><?php echo __( 'Hide Note Field:', 'rsvp-plugin' ); ?></label>
							</th>
							<td align="left"><input type="checkbox" name="rsvp_hide_note_field"
													id="rsvp_hide_note_field"
													value="Y"
										<?php echo( ( get_option( RSVP_OPTION_HIDE_NOTE ) == 'Y' ) ? ' checked="checked"' : '' ); ?> />
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label
										for="<?php echo OPTION_RSVP_HIDE_EMAIL_FIELD; ?>"><?php echo __( 'Hide email field on rsvp form:', 'rsvp-plugin' ); ?></label>
							</th>
							<td align="left"><input type="checkbox" name="<?php echo OPTION_RSVP_HIDE_EMAIL_FIELD; ?>"
													id="<?php echo OPTION_RSVP_HIDE_EMAIL_FIELD; ?>"
													value="Y" <?php echo( ( get_option( OPTION_RSVP_HIDE_EMAIL_FIELD ) == 'Y' ) ? ' checked="checked"' : '' ); ?> />
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label
										for="rsvp_custom_thankyou"><?php echo __( 'Custom Thank You:', 'rsvp-plugin' ); ?></label>
							</th>
							<td align="left"><textarea name="rsvp_custom_thankyou" id="rsvp_custom_thankyou" rows="5"
													   cols="60"><?php echo htmlspecialchars( get_option( OPTION_THANKYOU ) ); ?></textarea>
							</td>
						</tr>
						<tr>
							<th scope="row"><label
										for="rsvp_hide_add_additional"><?php echo __( 'Do not allow additional guests', 'rsvp-plugin' ); ?></label>
							</th>
							<td align="left"><input type="checkbox" name="rsvp_hide_add_additional"
													id="rsvp_hide_add_additional" value="Y"
										<?php echo( ( get_option( OPTION_HIDE_ADD_ADDITIONAL ) == 'Y' ) ? ' checked="checked"' : '' ); ?> />
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label
										for="<?php echo OPTION_RSVP_ADD_ADDITIONAL_VERBIAGE; ?>"><?php echo __( 'Add Additional Verbiage:', 'rsvp-plugin' ); ?></label>
							</th>
							<td align="left"><input type="text"
													name="<?php echo OPTION_RSVP_ADD_ADDITIONAL_VERBIAGE; ?>"
													id="<?php echo OPTION_RSVP_ADD_ADDITIONAL_VERBIAGE; ?>"
													value="<?php echo htmlspecialchars( get_option( OPTION_RSVP_ADD_ADDITIONAL_VERBIAGE ) ); ?>"
													size="65"/>
								<br/>
								<span class="description"><?php _e( 'Default is: &quot;Did we slip up and forget to invite someone? If so, please add him or her here:&quot;', 'rsvp-plugin' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><label
										for="rsvp_notify_when_rsvp"><?php echo __( 'Notify When Guest RSVPs', 'rsvp-plugin' ); ?></label>
							</th>
							<td align="left"><input type="checkbox" name="rsvp_notify_when_rsvp"
													id="rsvp_notify_when_rsvp"
													value="Y"
										<?php echo( ( get_option( OPTION_NOTIFY_ON_RSVP ) == 'Y' ) ? ' checked="checked"' : '' ); ?> />
							</td>
						</tr>
						<tr>
							<th scope="row"><label
										for="rsvp_notify_email_address"><?php echo __( 'Email address to notify', 'rsvp-plugin' ); ?></label>
							</th>
							<td align="left"><input type="text" name="rsvp_notify_email_address"
													id="rsvp_notify_email_address"
													value="<?php echo htmlspecialchars( get_option( OPTION_NOTIFY_EMAIL ) ); ?>"/>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label
										for="rsvp_guest_email_confirmation"><?php echo __( 'Send email to main guest when they RSVP', 'rsvp-plugin' ); ?></label>
							</th>
							<td align="left"><input type="checkbox" name="rsvp_guest_email_confirmation"
													id="rsvp_guest_email_confirmation" value="Y"
										<?php echo( ( get_option( OPTION_RSVP_GUEST_EMAIL_CONFIRMATION ) == 'Y' ) ? ' checked="checked"' : '' ); ?> />
							</td>
						</tr>
						<tr>
							<th scope="ropw"><label
										for="<?php echo OPTION_RSVP_PASSCODE; ?>"><?php echo __( 'Require a Passcode to RSVP:', 'rsvp-plugin' ); ?></label>
							</th>
							<td align="left"><input type="checkbox" name="<?php echo OPTION_RSVP_PASSCODE; ?>"
													id="<?php echo OPTION_RSVP_PASSCODE; ?>" value="Y"
										<?php echo( ( get_option( OPTION_RSVP_PASSCODE ) == 'Y' ) ? ' checked="checked"' : '' ); ?> />
							</td>
						</tr>
						<tr>
							<th scope="ropw"><label
										for="<?php echo OPTION_RSVP_ONLY_PASSCODE; ?>"><?php echo __( 'Require only a Passcode to RSVP<br />(requires that passcodes are unique):', 'rsvp-plugin' ); ?></label>
							</th>
							<td align="left"><input type="checkbox" name="<?php echo OPTION_RSVP_ONLY_PASSCODE; ?>"
													id="<?php echo OPTION_RSVP_ONLY_PASSCODE; ?>" value="Y"
										<?php echo( ( get_option( OPTION_RSVP_ONLY_PASSCODE ) == 'Y' ) ? ' checked="checked"' : '' ); ?> />
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label
										for="<?php echo OPTION_RSVP_OPEN_REGISTRATION; ?>"><?php echo __( 'Allow Open Registration (note - this will force passcodes for attendees):', 'rsvp-plugin' ); ?></label>
							</th>
							<td align="left"><input type="checkbox" name="<?php echo OPTION_RSVP_OPEN_REGISTRATION; ?>"
													id="<?php echo OPTION_RSVP_OPEN_REGISTRATION; ?>" value="Y"
										<?php echo( ( get_option( OPTION_RSVP_OPEN_REGISTRATION ) == 'Y' ) ? ' checked="checked"' : '' ); ?> />
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label
										for="<?php echo OPTION_RSVP_DONT_USE_HASH; ?>"><?php echo __( 'Do not scroll page to the top of the RSVP form:', 'rsvp-plugin' ); ?></label>
							</th>
							<td align="left"><input type="checkbox" name="<?php echo OPTION_RSVP_DONT_USE_HASH; ?>"
													id="<?php echo OPTION_RSVP_DONT_USE_HASH; ?>" value="Y"
										<?php echo( ( get_option( OPTION_RSVP_DONT_USE_HASH ) == 'Y' ) ? ' checked="checked"' : '' ); ?> />
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label
										for="<?php echo OPTION_RSVP_DISABLE_CUSTOM_EMAIL_FROM; ?>"><?php echo __( 'Do not use the specified notification email as the from email<br /> (if you are not receiving email notifications try this):', 'rsvp-plugin' ); ?></label>
							</th>
							<td align="left"><input type="checkbox"
													name="<?php echo OPTION_RSVP_DISABLE_CUSTOM_EMAIL_FROM; ?>"
													id="<?php echo OPTION_RSVP_DISABLE_CUSTOM_EMAIL_FROM; ?>"
													value="Y" <?php echo( ( get_option( OPTION_RSVP_DISABLE_CUSTOM_EMAIL_FROM ) == 'Y' ) ? ' checked="checked"' : '' ); ?> />
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label
										for="<?php echo OPTION_RSVP_DISABLE_USER_SEARCH; ?>"><?php echo __( 'Disable searching for a user when no user is found:', 'rsvp-plugin' ); ?></label>
							</th>
							<td align="left"><input type="checkbox"
													name="<?php echo OPTION_RSVP_DISABLE_USER_SEARCH; ?>"
													id="<?php echo OPTION_RSVP_DISABLE_USER_SEARCH; ?>"
													value="Y" <?php echo( ( get_option( OPTION_RSVP_DISABLE_USER_SEARCH ) == 'Y' ) ? ' checked="checked"' : '' ); ?> />
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label
										for="<?php echo RSVP_OPTION_DELETE_DATA_ON_UNINSTALL; ?>"><?php echo __( 'Delete all data on uninstall:', 'rsvp-plugin' ); ?></label>
							</th>
							<td align="left"><input type="checkbox"
													name="<?php echo RSVP_OPTION_DELETE_DATA_ON_UNINSTALL; ?>"
													id="<?php echo RSVP_OPTION_DELETE_DATA_ON_UNINSTALL; ?>"
													value="Y" <?php echo( ( get_option( RSVP_OPTION_DELETE_DATA_ON_UNINSTALL ) == 'Y' ) ? ' checked="checked"' : '' ); ?> />
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label
										for="<?php echo RSVP_OPTION_CSS_STYLING; ?>"><?php echo __( 'Custom Styling:', 'rsvp-plugin' ); ?></label>
							</th>
							<td align="left"><textarea name="<?php echo RSVP_OPTION_CSS_STYLING; ?>"
													   id="<?php echo RSVP_OPTION_CSS_STYLING; ?>" rows="5"
													   cols="60"><?php echo htmlspecialchars( get_option( RSVP_OPTION_CSS_STYLING ) ); ?></textarea>
								<br/>
								<span class="description"><?php _e( 'Add custom CSS for the RSVP plugin. More details <a href="https://www.rsvpproplugin.com/knowledge-base/customizing-the-rsvp-pro-front-end/">here</a>', 'rsvp-plugin' ); ?></span>
							</td>
						</tr>
					</table>
					<input type="hidden" name="action" value="update"/>
					<p class="submit">
						<input type="submit" class="button-primary"
							   value="<?php echo __( 'Save Changes', 'rsvp-plugin' ); ?>"/>
					</p>
				</form>
				</div>
				<div class="rsvp-right-panel">
					<?php do_action('rsvp_settings_page'); ?>
				</div>
			</div>
			<?php
		}

		/**
		 * Register our settings
		 *
		 * @since 2.7.2
		 */
		public function rsvp_register_settings(){
			register_setting( 'rsvp-option-group', OPTION_OPENDATE );
			register_setting( 'rsvp-option-group', OPTION_GREETING );
			register_setting( 'rsvp-option-group', OPTION_THANKYOU );
			register_setting( 'rsvp-option-group', OPTION_HIDE_VEGGIE );
			register_setting( 'rsvp-option-group', OPTION_HIDE_KIDS_MEAL );
			register_setting( 'rsvp-option-group', OPTION_NOTE_VERBIAGE );
			register_setting( 'rsvp-option-group', OPTION_VEGGIE_MEAL_VERBIAGE );
			register_setting( 'rsvp-option-group', OPTION_KIDS_MEAL_VERBIAGE );
			register_setting( 'rsvp-option-group', OPTION_YES_VERBIAGE );
			register_setting( 'rsvp-option-group', OPTION_NO_VERBIAGE );
			register_setting( 'rsvp-option-group', OPTION_DEADLINE );
			register_setting( 'rsvp-option-group', OPTION_THANKYOU );
			register_setting( 'rsvp-option-group', OPTION_HIDE_ADD_ADDITIONAL );
			register_setting( 'rsvp-option-group', OPTION_NOTIFY_EMAIL );
			register_setting( 'rsvp-option-group', OPTION_NOTIFY_ON_RSVP );
			register_setting( 'rsvp-option-group', OPTION_DEBUG_RSVP_QUERIES );
			register_setting( 'rsvp-option-group', OPTION_WELCOME_TEXT );
			register_setting( 'rsvp-option-group', OPTION_RSVP_QUESTION );
			register_setting( 'rsvp-option-group', OPTION_RSVP_CUSTOM_YES_NO );
			register_setting( 'rsvp-option-group', OPTION_RSVP_PASSCODE );
			register_setting( 'rsvp-option-group', RSVP_OPTION_HIDE_NOTE );
			register_setting( 'rsvp-option-group', OPTION_RSVP_OPEN_REGISTRATION );
			register_setting( 'rsvp-option-group', OPTION_RSVP_DONT_USE_HASH );
			register_setting( 'rsvp-option-group', OPTION_RSVP_ADD_ADDITIONAL_VERBIAGE );
			register_setting( 'rsvp-option-group', OPTION_RSVP_GUEST_EMAIL_CONFIRMATION );
			register_setting( 'rsvp-option-group', OPTION_RSVP_NUM_ADDITIONAL_GUESTS );
			register_setting( 'rsvp-option-group', OPTION_RSVP_HIDE_EMAIL_FIELD );
			register_setting( 'rsvp-option-group', OPTION_RSVP_DISABLE_CUSTOM_EMAIL_FROM );
			register_setting( 'rsvp-option-group', OPTION_RSVP_ONLY_PASSCODE );
			register_setting( 'rsvp-option-group', OPTION_RSVP_EMAIL_TEXT );
			register_setting( 'rsvp-option-group', OPTION_RSVP_DISABLE_USER_SEARCH );
			register_setting( 'rsvp-option-group', RSVP_OPTION_DELETE_DATA_ON_UNINSTALL );
			register_setting( 'rsvp-option-group', RSVP_OPTION_CSS_STYLING );
		}

	}

	RSVP_Admin::get_instance();
}
