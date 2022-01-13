<?php

use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Common\Type;

class RSVP_Helper {

	/**
	 * Holds the class object.
	 *
	 * @since 2.7.2
	 *
	 * @var object
	 */
	public static $instance;


	/**
	 * RSVP_Helper constructor.
	 *
	 * @since 2.7.2
	 */
	function __construct() {

		add_action( 'admin_action_delete-rsvp-attendee', array( $this, 'delete_attendee' ) );
		add_action( 'admin_action_delete-rsvp-question', array( $this, 'delete_question' ) );
		add_action( 'wp_ajax_update-questions-menu-order', array( $this, 'update_questions_order' ) );
		add_action( 'admin_init', array( $this, 'bulk_delete_attendees' ) );
		add_action( 'admin_init', array( $this, 'bulk_delete_questions' ) );

		add_action( 'init', array( $this, 'rsvp_admin_export' ) );

	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The RSVP_Helper object.
	 * @since 2.7.2
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof RSVP_Helper ) ) {
			self::$instance = new RSVP_Helper();
		}

		return self::$instance;

	}

	/**
	 * Get attendee associated attendees
	 *
	 * @param $id
	 *
	 * @return array|object|null
	 */
	public function get_associated_attendees( $id ) {

		global $wpdb;
		$sql = 'SELECT firstName, lastName FROM ' . ATTENDEES_TABLE . '
							 	WHERE id IN (SELECT attendeeID FROM ' . ASSOCIATED_ATTENDEES_TABLE . ' WHERE associatedAttendeeID = %d)
									OR id in (SELECT associatedAttendeeID FROM ' . ASSOCIATED_ATTENDEES_TABLE . ' WHERE attendeeID = %d)';

		$associations = $wpdb->get_results( $wpdb->prepare( $sql, $id, $id ) );

		return $associations;
	}

	/**
	 * Delete Attendee
	 *
	 *
	 * @since 2.7.2
	 */
	public function delete_attendee( $attendee_id = false ) {
		
		if ( ! $attendee_id ) {

			if ( isset( $_REQUEST['action'] ) && 'delete-rsvp-attendee' == $_REQUEST['action'] && isset( $_REQUEST['id'] ) ) {

				check_admin_referer( 'delete-rsvp-attendee_' . absint( $_REQUEST['id'] ) );

				global $wpdb;
				$attendee_id = absint( $_REQUEST['id'] );

				$wpdb->query(
					$wpdb->prepare(
						'DELETE FROM ' . ASSOCIATED_ATTENDEES_TABLE . ' WHERE attendeeID = %d OR associatedAttendeeID = %d',
						 $attendee_id,
						 $attendee_id
					)
				);

				$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . ATTENDEE_ANSWERS . ' WHERE attendeeID = %d', absint( $_REQUEST['id'] ) ) );

				$wpdb->query(
					$wpdb->prepare(
						'DELETE FROM ' . ATTENDEES_TABLE . ' WHERE id = %d',
						$attendee_id
					)
				);

				wp_redirect( wp_get_referer() );
				exit;
			}
		} else {

			global $wpdb;

			if ( is_numeric( $attendee_id ) && ( $attendee_id > 0 ) ) {
				$wpdb->query(
					$wpdb->prepare(
						'DELETE FROM ' . ASSOCIATED_ATTENDEES_TABLE . ' WHERE attendeeID = %d OR associatedAttendeeID = %d',
						 $attendee_id,
						 $attendee_id
					)
				);

				$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . ATTENDEE_ANSWERS . ' WHERE attendeeID = %d', $attendee_id ) );

				$wpdb->query(
					$wpdb->prepare(
						'DELETE FROM ' . ATTENDEES_TABLE . ' WHERE id = %d',
						$attendee_id
					)
				);
			}
		}
	}

	/**
	 * Delete Question
	 *
	 *
	 * @since 2.7.2
	 */
	public function delete_question( $question_id = false ) {

		if ( ! $question_id ) {

			check_admin_referer( 'delete-rsvp-question_' . absint( $_REQUEST['id'] ) );

			if ( isset( $_REQUEST['action'] ) && 'delete-rsvp-question' == $_REQUEST['action'] && isset( $_REQUEST['id'] ) ) {

				global $wpdb;
				$question_id = absint( $_REQUEST['id'] );
				$wpdb->query(
					$wpdb->prepare(
						'DELETE FROM ' . QUESTIONS_TABLE . ' WHERE id = %d',
						$question_id
					)
				);

				wp_redirect( wp_get_referer() );
				exit;
			}
		} else {

			global $wpdb;

			if ( is_numeric( $question_id ) && ( $question_id > 0 ) ) {
				$wpdb->query(
					$wpdb->prepare(
						'DELETE FROM ' . QUESTIONS_TABLE . ' WHERE id = %d',
						$question_id
					)
				);
			}
		}
	}


	/**
	 * Get all attendees
	 *
	 * @param string $orderby
	 * @param string $order
	 *
	 * @return array|object|null
	 * @since 2.7.2
	 */
	public function get_attendees( $orderby = 'lastName, firstName', $order = 'ASC' ) {

		global $wpdb;

		$sql = 'SELECT id, firstName, lastName, rsvpStatus, note, kidsMeal, additionalAttendee, veggieMeal, personalGreeting, passcode, email, rsvpDate FROM ' . ATTENDEES_TABLE . ' ORDER BY ' . $orderby . ' ' . $order;

		return $wpdb->get_results( $sql );
	}

	/**
	 * Get all custom questions
	 *
	 * @return array|object|null
	 * @since 2.7.2
	 */
	public function get_custom_questions() {
		global $wpdb;
		$sql = 'SELECT id, question, sortOrder, permissionLevel FROM ' . QUESTIONS_TABLE . ' ORDER BY sortOrder ASC';

		return $wpdb->get_results( $sql );
	}

	/**
	 * Handles exporting of attendees
	 *
	 * @Since 2.7.2
	 */
	public function rsvp_admin_export() {

		if ( ( isset( $_GET['page'] ) && ( strToLower( sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) == 'rsvp-admin-export' ) ) ||
			 ( isset( $_POST['rsvp-bulk-action'] ) && ( 'export' === strToLower( sanitize_text_field( wp_unslash( $_POST['rsvp-bulk-action'] ) ) ) ) ) ) {

			global $wpdb;

			$customLinkBase = '';

			// Get page associated with the page to build out prefill link.
			$query = new WP_Query( 's=rsvp-pluginhere' );
			if ( $query->have_posts() ) {
				$query->the_post();
				$customLinkBase = get_permalink();
				if ( strpos( $customLinkBase, '?' ) !== false ) {
					$customLinkBase .= '&';
				} else {
					$customLinkBase .= '?';
				}

				if ( rsvp_require_only_passcode_to_register() ) {
					$customLinkBase .= 'passcode=%s';
				} else {
					$customLinkBase .= 'firstName=%s&lastName=%s';
					if ( rsvp_require_passcode() ) {
						$customLinkBase .= '&passcode=%s';
					}
				}
			}

			wp_reset_postdata();

			$orderby = 'firstName, lastName';
			$order   = 'ASC';

			if ( isset( $_POST['orderby'] ) && 'attendee' != $_POST['orderby'] && '' != $_POST['orderby'] ) {
				$orderby = sanitize_sql_orderby( wp_unslash( $_POST['orderby'] ) );
			}

			if ( isset( $_POST['order'] ) && '' != $_POST['order'] ) {
				$order = sanitize_text_field( wp_unslash( $_POST['order'] ) );
			}

			$attendees = $this->get_attendees( $orderby, $order );

			$csv = '"' . esc_html__( 'First Name', 'rsvp' ) . '","' . esc_html__( 'Last Name', 'rsvp' ) . '","' . esc_html__( 'Email', 'rsvp' ) . '","' . esc_html__( 'RSVP Status', 'rsvp' ) . '",';

			if ( get_option( OPTION_HIDE_KIDS_MEAL ) != 'Y' ) {
				$csv .= '"' . esc_html__( 'Kids Meal', 'rsvp' ) . '",';
			}

			$csv .= '"' . esc_html__( 'Associated Attendees', 'rsvp' ) . '",';

			if ( get_option( OPTION_HIDE_VEGGIE ) != 'Y' ) {
				$csv .= '"' . esc_html__( 'Vegetarian', 'rsvp' ) . '",';
			}
			if ( rsvp_require_passcode() ) {
				$csv .= '"' . esc_html__( 'Passcode', 'rsvp' ) . '",';
			}
			$csv .= '"' . esc_html__( 'Note', 'rsvp' ) . '"';

			$qRs = $wpdb->get_results( 'SELECT id, question, permissionLevel FROM ' . QUESTIONS_TABLE . ' ORDER BY sortOrder, id' );
			if ( count( $qRs ) > 0 ) {
				foreach ( $qRs as $q ) {
					$csv .= ',"' . stripslashes( $q->question ) . '"';
					if ( $q->permissionLevel == 'private' ) {
						$csv .= ',"pq_' . $q->id . '"';
					}
				}
			}

			$csv .= ',"' . esc_html__( 'Additional Attendee', 'rsvp' ) . '"';
			$csv .= ',"' . esc_html__( 'pre-fill URL', 'rsvp' ) . '"';

			$csv .= "\r\n";

			foreach ( $attendees as $a ) {
				$fName = stripslashes( $a->firstName );
				$fName = rsvp_handle_text_encoding( $fName );
				$lName = stripslashes( $a->lastName );
				$lName = rsvp_handle_text_encoding( $lName );
				$csv  .= '"' . $fName . '","' . $lName . '","' . stripslashes( $a->email ) . '","' . ( $a->rsvpStatus ) . '",';

				if ( get_option( OPTION_HIDE_KIDS_MEAL ) != 'Y' ) {
					$csv .= '"' . ( ( $a->kidsMeal == 'Y' ) ? 'Y' : 'N' ) . '",';
				}

				$csv .= '"';

				$associations = $this->get_associated_attendees( $a->id );
				foreach ( $associations as $assc ) {
					$csv .= rsvp_handle_text_encoding( trim( stripslashes( $assc->firstName . ' ' . $assc->lastName ) ) ) . ', ';
				}
				$csv .= '",';

				if ( get_option( OPTION_HIDE_VEGGIE ) != 'Y' ) {
					$csv .= '"' . ( ( $a->veggieMeal == 'Y' ) ? 'Y' : 'N' ) . '",';
				}

				if ( rsvp_require_passcode() ) {
					$csv .= '"' . ( ( $a->passcode ) ) . '",';
				}

				$csv .= '"' . ( str_replace( '"', '""', stripslashes( $a->note ) ) ) . '"';

				$qRs = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT q.id, question, permissionLevel, qat.questionID AS hasAccess,
							(SELECT GROUP_CONCAT(answer) FROM ' . ATTENDEE_ANSWERS . ' WHERE questionID = q.id AND attendeeID = %d) AS answer
							FROM ' . QUESTIONS_TABLE . ' q
							LEFT JOIN ' . QUESTION_ATTENDEES_TABLE . ' qat ON qat.questionID = q.id AND qat.attendeeID = %d
							ORDER BY sortOrder, q.id',
						$a->id,
						$a->id
					)
				);
				if ( count( $qRs ) > 0 ) {
					foreach ( $qRs as $q ) {
						if ( $q->answer != '' ) {
							$csv .= ',"' . stripslashes( $q->answer ) . '"';
						} else {
							$csv .= ',""';
						}

						if ( $q->permissionLevel == 'private' ) {
							$csv .= ',"' . ( ( $q->hasAccess != '' ) ? 'Y' : 'N' ) . '"';
						}
					}
				}

				$csv .= ',"' . ( ( $a->additionalAttendee == 'Y' ) ? 'Y' : 'N' ) . '"';
				if ( empty( $customLinkBase ) ) {
					$csv .= ',""';
				} else {
					if ( rsvp_require_only_passcode_to_register() ) {
						$csv .= ',"' . sprintf( $customLinkBase, urlencode( stripslashes( $a->passcode ) ) ) . '"';
					} elseif ( rsvp_require_passcode() ) {
						$csv .= ',"' . sprintf( $customLinkBase, urlencode( stripslashes( $a->firstName ) ), urlencode( stripslashes( $a->lastName ) ), urlencode( stripslashes( $a->passcode ) ) ) . '"';
					} else {
						$csv .= ',"' . sprintf( $customLinkBase, urlencode( stripslashes( $a->firstName ) ), urlencode( stripslashes( $a->lastName ) ) ) . '"';
					}
				}
				$csv .= "\r\n";
			}
			if ( isset( $_SERVER['HTTP_USER_AGENT'] ) && preg_match( '/MSIE/', sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) ) {
				// IE Bug in download name workaround
				ini_set( 'zlib.output_compression', 'Off' );
			}
			header( 'Content-Description: RSVP Export' );
			header( 'Content-Type: application/vnd.ms-excel', true );
			header( 'Content-Disposition: attachment; filename="rsvpEntries.csv"' );
			echo $csv;
			exit();
		}
	}

	/**
	 * Handles importing of attendees
	 *
	 * @since 2.7.2
	 */
	public function rsvp_admin_import() {
		global $wpdb;
		if ( count( $_FILES ) > 0 ) {
			check_admin_referer( 'rsvp-import' );
			require RSVP_PLUGIN_PATH . '/external-libs/spout/src/Spout/Autoloader/autoload.php';

			$file_type = ( isset( $_FILES['importFile']['name'] ) ) ? rsvp_free_import_get_file_type( $_FILES['importFile']['name'] ) : null;

			if ( null === $file_type ) {
				?>
				<p><?php esc_html_e( 'Unsupported file type, only XLSX, CSV, and ODS are supported.', 'rsvp' ); ?></p>
				<?php
				return;
			}

			$reader    = ReaderFactory::create( $file_type );
			$i         = 0;
			$count     = 0;
			$headerRow = array();
			$reader->open( $_FILES['importFile']['tmp_name'] );
			foreach ( $reader->getSheetIterator() as $sheet ) {
				foreach ( $sheet->getRowIterator() as $row ) {
					if ( count( $row ) <= 2 ) {
						break;
					}

					if ( $i > 0 ) { // We want to skip the first row.
						$numCols = count( $row );
						$fName   = trim( $row[0] );
						$fName   = rsvp_smart_quote_replace( rsvp_handle_text_encoding( $fName ) );

						$lName      = trim( $row[1] );
						$lName      = rsvp_smart_quote_replace( rsvp_handle_text_encoding( $lName ) );
						$email      = trim( $row[2] );
						$rsvpStatus = 'noresponse';
						if ( isset( $row[3] ) ) {
							$tmpStatus = strtolower( $row[3] );
							if ( ( $tmpStatus == 'yes' ) || ( $tmpStatus == 'no' ) ) {
								$rsvpStatus = $tmpStatus;
							}
						}
						$kidsMeal   = 'N';
						$vegetarian = 'N';
						if ( isset( $row[4] ) && ( strtolower( $row[4] ) == 'y' ) ) {
							$kidsMeal = 'Y';
						}

						if ( isset( $row[6] ) && ( strtolower( $row[6] ) == 'y' ) ) {
							$vegetarian = 'Y';
						}

						$personalGreeting = ( isset( $row[8] ) ) ? $personalGreeting = $row[8] : '';
						$passcode         = ( isset( $row[7] ) ) ? $row[7] : '';
						if ( rsvp_require_unique_passcode() && ! rsvp_is_passcode_unique( $passcode, 0 ) ) {
							$passcode = rsvp_generate_passcode();
						}

						if ( ! empty( $fName ) && ! empty( $lName ) ) {
							$sql = 'SELECT id, email, passcode FROM ' . ATTENDEES_TABLE . '
					 		WHERE firstName = %s AND lastName = %s ';
							$res = $wpdb->get_results( $wpdb->prepare( $sql, $fName, $lName ) );
							if ( count( $res ) == 0 ) {
								$wpdb->insert(
									ATTENDEES_TABLE,
									array(
										'firstName'        => $fName,
										'lastName'         => $lName,
										'email'            => $email,
										'personalGreeting' => $personalGreeting,
										'kidsMeal'         => $kidsMeal,
										'veggieMeal'       => $vegetarian,
										'rsvpStatus'       => $rsvpStatus,
										'passcode'         => $passcode,
									),
									array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
								);
								$count ++;
							} elseif ( empty( $res->email ) && empty( $res->passcode ) ) {
								// More than likely the attendee was inserted via an
								// associated attendee and we will want to update this record...
								$wpdb->update(
									ATTENDEES_TABLE,
									array(
										'email'            => $email,
										'personalGreeting' => $personalGreeting,
										'passcode'         => $passcode,
										'rsvpStatus'       => $rsvpStatus,
									),
									array( 'id' => $res[0]->id ),
									array( '%s', '%s', '%s', '%s' ),
									array( '%d' )
								);
							}

							if ( $numCols >= 4 ) {
								// Get the user's id
								$sql = 'SELECT id FROM ' . ATTENDEES_TABLE . '
							 	WHERE firstName = %s AND lastName = %s ';
								$res = $wpdb->get_results( $wpdb->prepare( $sql, $fName, $lName ) );
								if ( ( count( $res ) > 0 ) && isset( $row[5] ) ) {
									$userId = $res[0]->id;

									// Deal with the assocaited users...
									$associatedUsers = explode( ',', trim( $row[5] ) );
									if ( is_array( $associatedUsers ) ) {
										foreach ( $associatedUsers as $au ) {
											$user = explode( ' ', trim( $au ), 2 );
											// Three cases, they didn't enter in all of the information, user exists or doesn't.
											// If user exists associate the two users
											// If user does not exist add the user and then associate the two
											if ( is_array( $user ) && ( count( $user ) == 2 ) ) {
												$sql     = 'SELECT id FROM ' . ATTENDEES_TABLE . '
											 	WHERE firstName = %s AND lastName = %s ';
												$userRes = $wpdb->get_results(
													$wpdb->prepare(
														$sql,
														rsvp_handle_text_encoding( trim( $user[0] ) ),
														rsvp_handle_text_encoding( trim( $user[1] ) )
													)
												);
												if ( count( $userRes ) > 0 ) {
													$newUserId = $userRes[0]->id;
												} else {
													// Insert them and then we can associate them...
													$wpdb->insert(
														ATTENDEES_TABLE,
														array(
															'firstName' => rsvp_handle_text_encoding( trim( $user[0] ) ),
															'lastName'  => rsvp_handle_text_encoding( trim( $user[1] ) ),
														),
														array( '%s', '%s' )
													);
													$newUserId = $wpdb->insert_id;
													$count ++;
												}

												$wpdb->insert(
													ASSOCIATED_ATTENDEES_TABLE,
													array(
														'attendeeID'           => $newUserId,
														'associatedAttendeeID' => $userId,
													),
													array( '%d', '%d' )
												);

												$wpdb->insert(
													ASSOCIATED_ATTENDEES_TABLE,
													array(
														'attendeeID'           => $userId,
														'associatedAttendeeID' => $newUserId,
													),
													array( '%d', '%d' )
												);
											}
										} // foreach($associatedUsers...
									} // if(is_array($associated...
								} // if((count($res) > 0...
							} // if check for associated attendees

							if ( $numCols >= 9 ) {
								$private_questions = array();
								for ( $qid = 9; $qid <= $numCols; $qid ++ ) {
									if ( isset( $headerRow[ $qid ] ) ) {
										$pqid = str_replace( 'pq_', '', $headerRow[ $qid ] );
										if ( is_numeric( $pqid ) ) {
											$private_questions[ $qid ] = $pqid;
										}
									}
								} // for($qid = 6...

								if ( count( $private_questions ) > 0 ) {
									// Get the user's id
									$sql = 'SELECT id FROM ' . ATTENDEES_TABLE . ' WHERE firstName = %s AND lastName = %s ';
									$res = $wpdb->get_results( $wpdb->prepare( $sql, $fName, $lName ) );
									if ( count( $res ) > 0 ) {
										$userId = $res[0]->id;
										foreach ( $private_questions as $key => $val ) {
											if ( strToUpper( $row[ $key ] ) == 'Y' ) {
												$wpdb->insert(
													QUESTION_ATTENDEES_TABLE,
													array(
														'attendeeID' => $userId,
														'questionID' => $val,
													),
													array( '%d', '%d' )
												);
											}
										}
									}
								} // if(count($priv...))
							} // if($numCols > = 9
						} // if(!empty($fName) && !empty($lName))
					} else {
						$headerRow = $row;
					}
					$i ++;
				}
				break;
			}
			?>
			<p><strong><?php echo esc_html( $count ); ?></strong> <?php echo esc_html__( 'total records were imported', 'rsvp' ); ?>.
			</p>
			<p><?php echo esc_html__( 'Continue to the RSVP', 'rsvp' ); ?> <a
						href="admin.php?page=rsvp-top-level"><?php echo esc_html__( 'list', 'rsvp' ); ?></a></p>
			<?php
		} else {
			?>
			<form name="rsvp_import" method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'rsvp-import' ); ?>
				<p><?php echo esc_html__( 'Select a file in the following file format: XLSX, CSV and ODS. It has to have the following layout:', 'rsvp' ); ?>
					<br/>
					<strong><?php echo esc_html__( 'First Name', 'rsvp' ); ?></strong> |
					<strong><?php echo esc_html__( 'Last Name', 'rsvp' ); ?></strong> |
					<strong><?php echo esc_html__( 'Email', 'rsvp' ); ?></strong> |
					<strong><?php echo esc_html__( 'RSVP Status', 'rsvp' ); ?></strong> |
					<strong><?php echo esc_html__( 'Kids Meal', 'rsvp' ); ?></strong> |
					<strong><?php echo esc_html__( 'Associated Attendees', 'rsvp' ); ?>*</strong> |
					<strong><?php echo esc_html__( 'Vegetarian', 'rsvp' ); ?></strong> |
					<strong><?php echo esc_html__( 'Passcode', 'rsvp' ); ?></strong> |
					<strong><?php echo esc_html__( 'Note', 'rsvp' ); ?></strong> |
					<strong><?php echo esc_html__( 'Private Question Association', 'rsvp' ); ?>**</strong>
				</p>
				<p>
					* <?php echo esc_html__( 'associated attendees should be separated by a comma it is assumed that the first space encountered will separate the first and last name.', 'rsvp' ); ?>
				</p>
				<p>
					**
					<?php
					echo esc_html__(
						'This can be multiple columns each column is associated with one of the following private questions. If you wish
      to have the guest associated with the question put a &quot;Y&quot; in the column otherwise put whatever else you want. The header name will be the &quot;private import key&quot; which is also listed below. It has the format of pq_* where * is a number.',
						'rsvp'
					);
					?>
				<ul>
					<?php
					$questions = $wpdb->get_results( 'SELECT id, question FROM ' . QUESTIONS_TABLE . " WHERE permissionLevel = 'private'" );
					foreach ( $questions as $q ) {
						?>
						<li><?php echo esc_html( stripslashes( $q->question ) ); ?> -
							pq_<?php echo esc_html( $q->id ); ?></li>
						<?php
					}
					?>
				</ul>
				</p>
				<p><?php echo esc_html__( 'A header row is always expected.', 'rsvp' ); ?></p>
				<p><input type="file" name="importFile" id="importFile"/></p>
				<p><input type="submit" value="Import File" name="goRsvp"/></p>
			</form>
			<?php
		}
	}

	/**
	 * Update questions order
	 *
	 * @return false
	 * @Since 2.7.2
	 */
	public function update_questions_order() {

		global $wpdb;

		if ( isset( $_POST['order'] ) ){
			parse_str( sanitize_text_field( wp_unslash( $_POST['order'] ) ), $data );
		}

		if ( ! is_array( $data ) ) {
			return false;
		}

		$id_arr = array();
		foreach ( $data as $key => $values ) {
			foreach ( $values as $position => $id ) {
				$id_arr[] = $id;
			}
		}

		$menu_order_arr = array();
		foreach ( $id_arr as $key => $id ) {
			$results = $wpdb->get_results( 'SELECT sortOrder FROM ' . QUESTIONS_TABLE . ' WHERE id = ' . (int) $id );

			foreach ( $results as $result ) {
				$menu_order_arr[] = $result->sortOrder;
			}
		}

		sort( $menu_order_arr );

		foreach ( $data as $key => $values ) {

			foreach ( $values as $position => $id ) {

				$wpdb->update( QUESTIONS_TABLE, array( 'sortOrder' => $position ), array( 'id' => (int) $id ) );
			}
		}

		die();
	}

	/**
	 * Handle the bulk delete of attendees
	 *
	 * @since 2.7.2
	 */
	public function bulk_delete_attendees() {

		if ( count( $_GET ) > 0 && isset( $_GET['rsvp-bulk-action'] ) && isset( $_GET['attendee'] ) && $_GET['rsvp-bulk-action'] == 'delete' && ( is_array( $_GET['attendee'] ) && ( count( $_GET['attendee'] ) > 0 ) ) ) {

			if ( isset( $_GET['_wpnonce'] ) && ! empty( $_GET['_wpnonce'] ) ) {

				$action = 'rsvp-bulk-attendees';
				if ( ! wp_verify_nonce( $_GET['_wpnonce'], $action ) ) {
					wp_die( 'Nope! Security check failed!' );
				}
			}
	
			foreach ( array_map( 'sanitize_text_field', array_map( 'wp_unslash', $_GET['attendee'] ) ) as $attendee ) {
				
				if ( is_numeric( $attendee ) && ( $attendee > 0 ) ) {
					$this->delete_attendee( $attendee );
				}
			}
		}
	}

	/**
	 * Bulk delete our questions
	 *
	 * @since 2.7.2
	 */
	public function bulk_delete_questions() {

		if ( count( $_GET ) > 0 && isset( $_GET['rsvp-bulk-action'] ) && isset( $_GET['q'] ) && $_GET['rsvp-bulk-action'] == 'delete' && ( is_array( $_GET['q'] ) && ( count( $_GET['q'] ) > 0 ) ) ) {

			if ( isset( $_GET['_wpnonce'] ) && ! empty( $_GET['_wpnonce'] ) ) {

				$action = 'rsvp-bulk-questions';
				if ( ! wp_verify_nonce( $_GET['_wpnonce'], $action ) ) {
					wp_die( 'Nope! Security check failed!' );
				}
			}

			global $wpdb;

			foreach ( array_map( 'absint', $_GET['q'] ) as $q ) {

				if ( is_numeric( $q ) && ( $q > 0 ) ) {
					$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . QUESTIONS_TABLE . ' WHERE id = %d', absint( $q ) ) );
					$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . ATTENDEE_ANSWERS . ' WHERE questionID = %d', absint( $q ) ) );
				}
			}
		}

	}
}

RSVP_Helper::get_instance();
