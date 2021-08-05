<?php
/**
 * @package rsvp
 * @author WPChill
 * @version 2.7.2
 * Plugin Name: RSVP
 * Text Domain: rsvp-plugin
 * Plugin URI: http://wordpress.org/extend/plugins/rsvp/
 * Description: This plugin allows guests to RSVP to an event.  It was made initially for weddings but could be used for other things.
 * Author: WPChill
 * Version: 2.7.2
 * Author URI: https://wpchill.com
 * License: GPLv3
 * Copyright 2010-2020 		Mike de Libero 		mikede@mde-dev.com
 * Copyright 2020 			MachoThemes 		office@machothemes.com
 * Copyright 2020 			WPChill 			heyyy@wpchill.com
 *
 * Original Plugin URI: 		http://www.swimordiesoftware.com
 * Original Author URI: 		http://www.swimordiesoftware.com
 * Original Author: 			https://profiles.wordpress.org/mdedev/
 *
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 3, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Common\Type;

$my_plugin_file = __FILE__;

if ( isset( $plugin ) ) {
	$my_plugin_file = $plugin;
} elseif ( isset( $mu_plugin ) ) {
	$my_plugin_file = $mu_plugin;
} elseif ( isset( $network_plugin ) ) {
	$my_plugin_file = $network_plugin;
}

define( 'RSVP_PLUGIN_FILE', $my_plugin_file );
define( 'RSVP_PLUGIN_PATH', WP_PLUGIN_DIR . '/' . basename( dirname( $my_plugin_file ) ) );

require_once 'includes/rsvp-constants.php';


if ( isset( $_GET['page'] ) && ( 'rsvp-upgrade-to-pro' === strtolower( $_GET['page'] ) ) ) {
	add_action( 'init', 'rsvp_upgrade_to_pro' );
}

require_once 'external-libs/wp-simple-nonce/wp-simple-nonce.php';
require_once __DIR__ . '/includes/rsvp_frontend.inc.php';

if ( is_admin() ) {
	require_once __DIR__ . '/includes/class-rsvp-review.php';
	require_once 'includes/class-rsvp-admin.php';
	require_once 'includes/class-rsvp-list-table.php';
	require_once 'includes/class-rsvp-events-list-table.php';
	require_once 'includes/class-rsvp-attendees-list-table.php';
	require_once 'includes/class-rsvp-questions-list-table.php';
	require_once 'includes/class-rsvp-helper.php';
	require_once 'includes/class-rsvp-upsells.php';

	if ( apply_filters( 'rsvp_show_upsells', true ) ) {
		RSVP_Upsells::get_instance();
	}
}

/**
 * Database setup for the rsvp plug-in.
 */
function rsvp_database_setup() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	require_once __DIR__ . '/includes/rsvp_db_setup.inc.php';
}

/**
 * Checks to see if the passcode field is installed in the attendees table, if not
 * it will add the field.
 */
function rsvp_install_passcode_field() {
	global $wpdb;
	$table = ATTENDEES_TABLE;
	$sql   = "SHOW COLUMNS FROM `$table` LIKE 'passcode'";
	if ( ! $wpdb->get_results( $sql ) ) {
		$sql = "ALTER TABLE `$table` ADD `passcode` VARCHAR(50) NOT NULL DEFAULT '';";
		$wpdb->query( $sql );
	}
}

/**
 * Checks to see if passcode is required for attendees.
 *
 * @return bool True if a passcode is required, false otherwise.
 */
function rsvp_require_passcode() {
	return ( ( get_option( OPTION_RSVP_PASSCODE ) == 'Y' ) || ( get_option( OPTION_RSVP_OPEN_REGISTRATION ) == 'Y' ) || ( get_option( OPTION_RSVP_ONLY_PASSCODE ) == 'Y' ) );
}

/**
 * Checks to see if only a passcode is required to RSVP.
 *
 * @return bool True if only a passcode is needed to RSVP, false otherwise.
 */
function rsvp_require_only_passcode_to_register() {
	return ( get_option( OPTION_RSVP_ONLY_PASSCODE ) === 'Y' );
}

/**
 * [rsvp_require_unique_passcode description]
 *
 * @return [type] [description]
 */
function rsvp_require_unique_passcode() {
	return rsvp_require_only_passcode_to_register();
}

/**
 * [rsvp_is_passcode_unique description]
 *
 * @param  [type] $passcode    [description]
 * @param  [type] $attendee_id [description]
 *
 * @return [type]              [description]
 */
function rsvp_is_passcode_unique( $passcode, $attendee_id ) {
	global $wpdb;

	$is_unique = false;

	$sql = $wpdb->prepare( 'SELECT * FROM ' . ATTENDEES_TABLE . ' WHERE id <> %d AND passcode = %s', $attendee_id, $passcode );
	if ( ! $wpdb->get_results( $sql ) ) {
		$is_unique = true;
	}

	return $is_unique;
}

/**
 * This generates a random 6 character passcode to be used for guests when the option is enabled.
 */
function rsvp_generate_passcode() {
	$length     = 6;
	$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
	$passcode   = '';

	for ( $p = 0; $p < $length; $p ++ ) {
		$passcode .= $characters[ mt_rand( 0, strlen( $characters ) ) ];
	}

	return $passcode;
}


/**
 * Gets the file type based on the users uploaded extension.
 *
 * @param string $file_path The file name that the user uploaded for importing.
 *
 * @return object            Null or the Spout type used for parsing the files.
 */
function rsvp_free_import_get_file_type( $file_path ) {
	$ext = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );

	if ( 'csv' === $ext ) {
		return Type::CSV;
	} elseif ( 'xlsx' === $ext ) {
		return Type::XLSX;
	} elseif ( 'ods' === $ext ) {
		return Type::ODS;
	}

	return null;
}


function rsvp_get_question_with_answer_type_ids() {
	global $wpdb;

	$ids     = array();
	$sql     = 'SELECT id FROM ' . QUESTION_TYPE_TABLE . "
			WHERE questionType IN ('" . QT_MULTI . "', '" . QT_DROP . "', '" . QT_RADIO . "')";
	$results = $wpdb->get_results( $sql );
	foreach ( $results as $r ) {
		$ids[] = (int) $r->id;
	}

	return $ids;
}

/**
 * Populates the custom question types
 *
 * @since 2.2.8
 */
function rsvp_populate_custom_question_types() {
	global $wpdb;

	$question_types = array(
			array(
					'questionType' => 'shortAnswer',
					'friendlyName' => 'Short Answer',
			),
			array(
					'questionType' => 'multipleChoice',
					'friendlyName' => 'Multiple Choice',
			),
			array(
					'questionType' => 'longAnswer',
					'friendlyName' => 'Long Answer',
			),
			array(
					'questionType' => 'dropdown',
					'friendlyName' => 'Drop Down',
			),
			array(
					'questionType' => 'radio',
					'friendlyName' => 'Radio',
			),
	);

	foreach ( $question_types as $qt ) {
		$qType = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . QUESTION_TYPE_TABLE . ' WHERE questionType = %s ', $qt['questionType'] ) );
		if ( $qType == null ) {
			$wpdb->insert(
					QUESTION_TYPE_TABLE,
					array(
							'questionType' => $qt['questionType'],
							'friendlyName' => $qt['friendlyName'],
					),
					array( '%s', '%s' )
			);
		}
	}
}

function rsvp_admin_custom_question() {
	global $wpdb;
	$rsvp_helper = RSVP_Helper::get_instance();

	$answerQuestionTypes = rsvp_get_question_with_answer_type_ids();

	rsvp_populate_custom_question_types();

	if ( ( count( $_POST ) > 0 ) && ! empty( $_POST['question'] ) && is_numeric( $_POST['questionTypeID'] ) ) {
		check_admin_referer( 'rsvp_add_custom_question' );
		if ( isset( $_POST['questionId'] ) && is_numeric( $_POST['questionId'] ) && ( $_POST['questionId'] > 0 ) ) {
			$wpdb->update(
					QUESTIONS_TABLE,
					array(
							'question'        => trim( $_POST['question'] ),
							'questionTypeID'  => trim( $_POST['questionTypeID'] ),
							'permissionLevel' => ( ( trim( $_POST['permissionLevel'] ) == 'private' ) ? 'private' : 'public' ),
					),
					array( 'id' => $_POST['questionId'] ),
					array( '%s', '%d', '%s' ),
					array( '%d' )
			);
			$questionId = $_POST['questionId'];

			$answers = $wpdb->get_results( $wpdb->prepare( 'SELECT id FROM ' . QUESTION_ANSWERS_TABLE . ' WHERE questionID = %d', $questionId ) );
			if ( count( $answers ) > 0 ) {
				foreach ( $answers as $a ) {
					if ( isset( $_POST[ 'deleteAnswer' . $a->id ] ) && ( strToUpper( $_POST[ 'deleteAnswer' . $a->id ] ) == 'Y' ) ) {
						$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . QUESTION_ANSWERS_TABLE . ' WHERE id = %d', $a->id ) );
					} elseif ( isset( $_POST[ 'answer' . $a->id ] ) && ! empty( $_POST[ 'answer' . $a->id ] ) ) {
						$wpdb->update(
								QUESTION_ANSWERS_TABLE,
								array( 'answer' => trim( $_POST[ 'answer' . $a->id ] ) ),
								array( 'id' => $a->id ),
								array( '%s' ),
								array( '%d' )
						);
					}
				}
			}
		} else {
			$wpdb->insert(
					QUESTIONS_TABLE,
					array(
							'question'        => trim( $_POST['question'] ),
							'questionTypeID'  => trim( $_POST['questionTypeID'] ),
							'permissionLevel' => ( ( trim( $_POST['permissionLevel'] ) == 'private' ) ? 'private' : 'public' ),
					),
					array( '%s', '%d', '%s' )
			);
			$questionId = $wpdb->insert_id;
		}

		if ( isset( $_POST['numNewAnswers'] ) && is_numeric( $_POST['numNewAnswers'] ) &&
			 in_array( $_POST['questionTypeID'], $answerQuestionTypes ) ) {
			for ( $i = 0; $i < $_POST['numNewAnswers']; $i ++ ) {
				if ( isset( $_POST[ 'newAnswer' . $i ] ) && ! empty( $_POST[ 'newAnswer' . $i ] ) ) {
					$wpdb->insert(
							QUESTION_ANSWERS_TABLE,
							array(
									'questionID' => $questionId,
									'answer'     => $_POST[ 'newAnswer' . $i ],
							)
					);
				}
			}
		}

		if ( strToLower( trim( $_POST['permissionLevel'] ) ) == 'private' ) {
			$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . QUESTION_ATTENDEES_TABLE . ' WHERE questionID = %d', $questionId ) );
			if ( isset( $_POST['attendees'] ) && is_array( $_POST['attendees'] ) ) {
				foreach ( $_POST['attendees'] as $aid ) {
					if ( is_numeric( $aid ) && ( $aid > 0 ) ) {
						$wpdb->insert(
								QUESTION_ATTENDEES_TABLE,
								array(
										'attendeeID' => $aid,
										'questionID' => $questionId,
								),
								array( '%d', '%d' )
						);
					}
				}
			}
		}
		?>
		<p><?php echo __( 'Custom Question saved', 'rsvp-plugin' ); ?></p>
		<p>
			<a href="<?php echo add_query_arg( array( 'page' => 'rsvp-admin-questions' ), admin_url( 'admin.php' ) ); ?>"
			   class="button button-secondary"><?php echo __( 'Continue to Question List', 'rsvp-plugin' ); ?></a>
			<a href="<?php echo add_query_arg( array(
					'page'   => 'rsvp-admin-questions',
					'action' => 'add'
			), admin_url( 'admin.php' ) ); ?>"
			   class="button button-primary"><?php echo __( 'Add another Question', 'rsvp-plugin' ); ?></a>
		</p>
		<?php
	} else {
		$questionTypeId  = 0;
		$question        = '';
		$isNew           = true;
		$questionId      = 0;
		$permissionLevel = 'public';
		$savedAttendees  = array();
		if ( isset( $_GET['id'] ) && is_numeric( $_GET['id'] ) ) {
			$qRs = $wpdb->get_results( $wpdb->prepare( 'SELECT id, question, questionTypeID, permissionLevel FROM ' . QUESTIONS_TABLE . ' WHERE id = %d', $_GET['id'] ) );
			if ( count( $qRs ) > 0 ) {
				$isNew           = false;
				$questionId      = $qRs[0]->id;
				$question        = stripslashes( $qRs[0]->question );
				$permissionLevel = stripslashes( $qRs[0]->permissionLevel );
				$questionTypeId  = $qRs[0]->questionTypeID;

				if ( $permissionLevel == 'private' ) {
					$aRs = $wpdb->get_results( $wpdb->prepare( 'SELECT attendeeID FROM ' . QUESTION_ATTENDEES_TABLE . ' WHERE questionID = %d', $questionId ) );
					if ( count( $aRs ) > 0 ) {
						foreach ( $aRs as $a ) {
							$savedAttendees[] = $a->attendeeID;
						}
					}
				}
			}
		}

		$sql           = 'SELECT id, questionType, friendlyName FROM ' . QUESTION_TYPE_TABLE;
		$questionTypes = $wpdb->get_results( $sql );
		?>
		<script type="text/javascript">
			var questionTypeId = [
				<?php
				foreach ( $answerQuestionTypes as $aqt ) {
					echo '"' . $aqt . '",';
				}
				?>
			];

			function addAnswer( counterElement ) {
				var currAnswer = jQuery( "#numNewAnswers" ).val();
				if ( isNaN( currAnswer ) ) {
					currAnswer = 0;
				}

				var s = "<tr>\r\n" +
						"<td align=\"right\" width=\"75\"><label for=\"newAnswer" + currAnswer + "\"><?php echo __( 'Answer', 'rsvp-plugin' ); ?>:</label></td>\r\n" +
						"<td><input type=\"text\" name=\"newAnswer" + currAnswer + "\" id=\"newAnswer" + currAnswer + "\" size=\"40\" /></td>\r\n" +
						"</tr>\r\n";
				jQuery( "#answerContainer" ).append( s );
				currAnswer++;
				jQuery( "#numNewAnswers" ).val( currAnswer );
				return false;
			}

			jQuery( document ).ready( function () {

				<?php
				if ( $isNew || ! in_array( $questionTypeId, $answerQuestionTypes ) ) {
					echo 'jQuery("#answerContainer").hide();';
				}

				if ( $isNew || ( $permissionLevel == 'public' ) ) {
				?>
				jQuery( "#attendeesArea" ).hide();
				<?php
				}
				?>
				jQuery( "#questionType" ).change( function () {
					var selectedValue = jQuery( "#questionType" ).val();
					if ( questionTypeId.indexOf( selectedValue ) != -1 ) {
						jQuery( "#answerContainer" ).show();
					} else {
						jQuery( "#answerContainer" ).hide();
					}
				} )

				jQuery( "#permissionLevel" ).change( function () {
					if ( jQuery( "#permissionLevel" ).val() != "public" ) {
						jQuery( "#attendeesArea" ).show();
					} else {
						jQuery( "#attendeesArea" ).hide();
					}
				} )
			} );
		</script>
		<form name="contact" action="<?php echo add_query_arg( 'action', 'add' ); ?>" method="post">
			<input type="hidden" name="numNewAnswers" id="numNewAnswers" value="0"/>
			<input type="hidden" name="questionId" value="<?php echo $questionId; ?>"/>
			<?php wp_nonce_field( 'rsvp_add_custom_question' ); ?>
			<p class="submit">
				<a href="<?php echo admin_url( 'admin.php?page=rsvp-admin-questions' ); ?>"
				   class="button button-secondary"><?php _e( 'Back to custom question list', 'rsvp-plugin' ); ?></a>
				<input type="submit" class="button-primary" value="<?php _e( 'Save', 'rsvp-plugin' ); ?>"/>
			</p>
			<table id="customQuestions" class="form-table">
				<tr valign="top">
					<th scope="row"><label for="questionType"><?php echo __( 'Question Type', 'rsvp-plugin' ); ?>
							:</label></th>
					<td align="left"><select name="questionTypeID" id="questionType" size="1">
							<?php
							foreach ( $questionTypes as $qt ) {
								echo '<option value="' . $qt->id . '" ' . ( ( $questionTypeId == $qt->id ) ? ' selected="selected"' : '' ) . '>' . $qt->friendlyName . "</option>\r\n";
							}
							?>
						</select>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="question"><?php echo __( 'Question', 'rsvp-plugin' ); ?>:</label></th>
					<td align="left"><input type="text" name="question" id="question" size="40"
											value="<?php echo htmlspecialchars( $question ); ?>"/></td>
				</tr>
				<tr>
					<th scope="row"><label
								for="permissionLevel"><?php echo __( 'Question Permission Level', 'rsvp-plugin' ); ?>
							:</label></th>
					<td align="left"><select name="permissionLevel" id="permissionLevel" size="1">
							<option value="public" <?php echo ( $permissionLevel == 'public' ) ? ' selected="selected"' : ''; ?>><?php echo __( 'Everyone', 'rsvp-plugin' ); ?></option>
							<option value="private" <?php echo ( $permissionLevel == 'private' ) ? ' selected="selected"' : ''; ?>><?php echo __( 'Select People', 'rsvp-plugin' ); ?></option>
						</select></td>
				</tr>
				<?php if ( ! $isNew && ( $permissionLevel == 'private' ) ) : ?>
					<tr>
						<th scope="row"><?php echo __( 'Private Import Key', 'rsvp-plugin' ); ?>:</th>
						<td align="left">pq_<?php echo $questionId; ?></td>
					</tr>
				<?php endif; ?>
				<tr>
					<td colspan="2">
						<table cellpadding="0" cellspacing="0" border="0" id="answerContainer">
							<tr>
								<th><?php echo __( 'Answers', 'rsvp-plugin' ); ?></th>
								<th align="right"><a href="#"
													 onclick="return addAnswer();"><?php echo __( 'Add new Answer', 'rsvp-plugin' ); ?></a>
								</th>
							</tr>
							<?php
							if ( ! $isNew ) {
								$aRs = $wpdb->get_results( $wpdb->prepare( 'SELECT id, answer FROM ' . QUESTION_ANSWERS_TABLE . ' WHERE questionID = %d', $questionId ) );
								if ( count( $aRs ) > 0 ) {
									foreach ( $aRs as $answer ) {
										?>
										<tr>
											<td width="75" align="right"><label
														for="answer<?php echo $answer->id; ?>"><?php echo __( 'Answer', 'rsvp-plugin' ); ?>
													:</label></td>
											<td><input type="text" name="answer<?php echo $answer->id; ?>"
													   id="answer<?php echo $answer->id; ?>" size="40"
													   value="<?php echo htmlspecialchars( stripslashes( $answer->answer ) ); ?>"/>
												&nbsp; <input type="checkbox"
															  name="deleteAnswer<?php echo $answer->id; ?>"
															  id="deleteAnswer<?php echo $answer->id; ?>"
															  value="Y"/><label
														for="deleteAnswer<?php echo $answer->id; ?>"><?php echo __( 'Delete', 'rsvp-plugin' ); ?></label>
											</td>
										</tr>
										<?php
									}
								}
							}
							?>
						</table>
					</td>
				</tr>
				<tr id="attendeesArea">
					<th scope="row"><label
								for="attendees"><?php echo __( 'Attendees allowed to answer this question', 'rsvp-plugin' ); ?>
							:</label></th>
					<td>
						<p>
							<span style="margin-left: 30px;"><?php _e( 'Available people', 'rsvp-plugin' ); ?></span>
							<span style="margin-left: 65px;"><?php _e( 'People that have access', 'rsvp-plugin' ); ?></span>
						</p>
						<select name="attendees[]" id="attendeesQuestionSelect" style="height:75px;"
								multiple="multiple">
							<?php
							$attendees = $rsvp_helper->get_attendees();
							foreach ( $attendees as $a ) {
								?>
								<option value="<?php echo $a->id; ?>"
										<?php echo( ( in_array( $a->id, $savedAttendees ) ) ? ' selected="selected"' : '' ); ?>><?php echo htmlspecialchars( stripslashes( $a->firstName ) . ' ' . stripslashes( $a->lastName ) ); ?></option>
								<?php
							}
							?>
						</select>
					</td>
				</tr>
			</table>
		</form>
		<?php
	}
}

function rsvp_upgrade_to_pro() {
	wp_redirect( 'https://www.rsvpproplugin.com' );
}

function rsvp_admin_scripts() {
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'jquery-ui-datepicker' );
	wp_enqueue_script( 'jquery-ui-sortable' );
	wp_enqueue_style( 'jquery-ui' );
	wp_register_script( 'jquery_multi_select', plugins_url( 'multi-select/js/jquery.multi-select.js', RSVP_PLUGIN_FILE ) );
	wp_enqueue_script( 'jquery_multi_select' );
	wp_register_style( 'jquery_multi_select_css', plugins_url( 'multi-select/css/multi-select.css', RSVP_PLUGIN_FILE ) );
	wp_enqueue_style( 'jquery_multi_select_css' );

	wp_register_style( 'rsvp_jquery-ui', plugins_url( '/assets/admin/css/jquery-ui.css', RSVP_PLUGIN_FILE ) );
	wp_enqueue_style( 'rsvp_jquery-ui' );

	wp_register_script( 'rsvp_admin', plugins_url( 'assets/admin/js/rsvp_plugin_admin.js', RSVP_PLUGIN_FILE ), array( 'jquery-ui-sortable' ), '', true );
	wp_enqueue_script( 'rsvp_admin' );
}

/**
 * Function for loading the needed assets for the plugin.
 */
function rsvp_init() {
	$result = load_plugin_textdomain( 'rsvp-plugin', false, basename( dirname( __FILE__ ) ) . '/languages/' );
	wp_register_script( 'jquery_validate', plugins_url( 'assets/js/jquery.validate.min.js', RSVP_PLUGIN_FILE ), array( 'jquery' ) );
	wp_register_script( 'rsvp_plugin', plugins_url( 'assets/js/rsvp_plugin.js', RSVP_PLUGIN_FILE ), array( 'jquery' ) );
	wp_localize_script(
			'rsvp_plugin',
			'rsvp_plugin_vars',
			array(
					'askEmail'               => __( 'Please enter an email address that we can use to contact you about the extra guest.  We have to keep a pretty close eye on the number of attendees.  Thanks!', 'rsvp-plugin' ),
					'customNote'             => __( 'If you are adding additional RSVPs please enter your email address in case we have questions', 'rsvp-plugin' ),
					'newAttending1LastName'  => __( 'Please enter a last name', 'rsvp-plugin' ),
					'newAttending1FirstName' => __( 'Please enter a first name', 'rsvp-plugin' ),
					'newAttending2LastName'  => __( 'Please enter a last name', 'rsvp-plugin' ),
					'newAttending2FirstName' => __( 'Please enter a first name', 'rsvp-plugin' ),
					'newAttending3LastName'  => __( 'Please enter a last name', 'rsvp-plugin' ),
					'newAttending3FirstName' => __( 'Please enter a first name', 'rsvp-plugin' ),
					'attendeeFirstName'      => __( 'Please enter a first name', 'rsvp-plugin' ),
					'attendeeLastName'       => __( 'Please enter a last name', 'rsvp-plugin' ),
					'firstName'              => __( 'Please enter your first name', 'rsvp-plugin' ),
					'lastName'               => __( 'Please enter your last name', 'rsvp-plugin' ),
					'passcode'               => __( 'Please enter your password', 'rsvp-plugin' ),
			)
	);

	wp_register_style( 'rsvp_css', plugins_url( 'assets/css/rsvp_plugin.css', RSVP_PLUGIN_FILE ) );
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'jquery_validate' );
	wp_enqueue_script( 'rsvp_plugin' );
	wp_enqueue_style( 'rsvp_css' );
}

/**
 * Handles converting text encodings for characters like umlauts that might be stored in different encodings
 *
 * @param string $text The text we wish to handle the encoding against
 *
 * @return string       The converted text
 */
function rsvp_handle_text_encoding( $text ) {
	if ( function_exists( 'mb_convert_encoding' ) && function_exists( 'mb_detect_encoding' ) ) {
		return mb_convert_encoding( $text, 'UTF-8', mb_detect_encoding( $text, 'UTF-8, ISO-8859-1', true ) );
	}

	return $text;
}

function rsvp_free_is_addslashes_enabled() {
	return get_magic_quotes_gpc();
}

function rsvp_getCurrentPageURL() {
	global $wp;
	global $wp_rewrite;

	$pageURL = home_url( $wp->request );
	$pageURL = trailingslashit( $pageURL );

	if ( $wp_rewrite->using_index_permalinks() && ( strpos( $pageURL, 'index.php' ) === false ) ) {
		$parts = parse_url( $pageURL );

		$pageURL = $parts['scheme'] . '://' . $parts['host'];

		if ( isset( $parts['port'] ) ) {
			$pageURL .= ':' . $parts['port'];
		}

		$pageURL .= '/index.php' . $parts['path'];

		if ( isset( $parts['query'] ) && ( $parts['query'] != '' ) ) {
			$pageURL .= '?' . $parts['query'];
		}
	} elseif ( empty( $wp_rewrite->permalink_structure ) ) {
		$pageURL = get_permalink();
	}

	if ( get_option( OPTION_RSVP_DONT_USE_HASH ) != 'Y' ) {
		$pageURL .= '#rsvpArea';
	}

	return $pageURL;
}

function rsvp_add_css() {
	$css = get_option( RSVP_OPTION_CSS_STYLING );

	if ( ! empty( $css ) ) {
		$output = '<!-- RSVP Free Styling -->';
		$output .= '<style type="text/css">' . $css . '</style>';

		echo $output;
	}
}

function rsvp_add_privacy_policy_content() {
	if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
		return;
	}

	$content = __(
			'All information entered either from an attendee or a WordPress admin for the RSVP 
	         plugin is never sent to external sites. The data stays in database tables 
	        on the WordPress instance.',
			'rsvp_plugin'
	);

	wp_add_privacy_policy_content(
			'RSVP Plugin',
			wp_kses_post( wpautop( $content, false ) )
	);
}

/**
 * Handles the data erasing for a given email address.
 *
 * @param string  $email_address The email address we want to delete from the attendees table.
 * @param integer $page          The page we are on.
 *
 * @return array                  An array containing how many attendees were deleted.
 */
function rsvp_data_eraser_handler( $email_address, $page = 1 ) {
	global $wpdb;
	$rsvp_helper = RSVP_Helper::get_instance();

	$num_deleted = 0;
	$sql         = 'SELECT id FROM ' . ATTENDEES_TABLE . ' WHERE email = %s';
	$attendees   = $wpdb->get_results( $wpdb->prepare( $sql, $email_address ) );
	foreach ( $attendees as $a ) {
		$rsvp_helper->delete_attendee( $a->id );
		$num_deleted ++;
	}

	return array(
			'items_removed'  => $num_deleted,
			'items_retained' => false, // We never retain items.
			'messages'       => array( __( 'RSVP Data Erased Successfully', 'rsvp-plugin' ) ),
			'done'           => true,
	);
}

/**
 * The data eraser registration that lets the core of WP know
 * we can handle erasing of the RSVP Plugin information
 * if it is ever requested.
 *
 * @param array $erasers The array of erasers already registered with this WP instance.
 *
 * @return array           The erasers array now with the RSVP eraser added.
 */
function rsvp_register_data_eraser( $erasers ) {
	$erasers['rsvp-plugin'] = array(
			'eraser_friendly_name' => __( 'RSVP Plugin', 'rsvp-plugin' ),
			'callback'             => 'rsvp_data_eraser_handler',
	);

	return $erasers;
}

/**
 * Retrieves and packages up the exporter information for the new WordPress compliance functionality
 *
 * @param string  $email_address The email address we need to export the information for.
 * @param integer $page          The current page.
 *
 * @return array                  Containing the information and if everything is done being exported.
 */
function rsvp_data_exporter_handler( $email_address, $page = 1 ) {
	global $wpdb;

	$export_items = array();
	$sql          = 'SELECT a.id, a.firstName, a.lastName, a.rsvpDate, 
      a.rsvpStatus, a.note, a.additionalAttendee, a.kidsMeal, 
      a.veggieMeal, a.personalGreeting
    FROM ' . ATTENDEES_TABLE . ' a 
    WHERE email = %s';
	$attendees    = $wpdb->get_results( $wpdb->prepare( $sql, $email_address ) );
	foreach ( $attendees as $a ) {
		$export_items['firstName']          = stripslashes( $a->firstName );
		$export_items['lastName']           = stripslashes( $a->lastName );
		$export_items['rsvpDate']           = $a->rsvpDate;
		$export_items['rsvpStatus']         = stripslashes( $a->rsvpStatus );
		$export_items['note']               = stripslashes( $a->note );
		$export_items['additionalAttendee'] = stripslashes( $a->additionalAttendee );
		$export_items['personalGreeting']   = stripslashes( $a->personalGreeting );
		$export_items['veggieMeal']         = stripslashes( $a->veggieMeal );
		$export_items['kidsMeal']           = stripslashes( $a->kidsMeal );

		// Print out the custom question information for the main event.
		$export_items = rsvp_data_exporter_custom_questions( $a->id, $export_items );
	}

	return array(
			'data' => $export_items,
			'done' => true,
	);
}

/**
 * Retrieves the custom question and answers for export
 *
 * @param integer $attendee_id  The attendee we want to get the answers for.
 * @param array   $export_items The current exported items that we need to add to.
 *
 * @return array                 The export items with the custom questions added for the event passed in.
 */
function rsvp_data_exporter_custom_questions( $attendee_id, $export_items ) {
	global $wpdb;

	$sql = 'SELECT answer, question FROM ' . ATTENDEE_ANSWERS . ' aa 
	JOIN ' . QUESTIONS_TABLE . ' q ON q.id = aa.questionID 
	WHERE aa.attendeeID = %d';

	$custom_questions = $wpdb->get_results( $wpdb->prepare( $sql, $attendee_id ) );
	foreach ( $custom_questions as $cq ) {
		$export_items[ stripslashes( $cq->question ) ] = stripslashes( $cq->answer );
	}

	return $export_items;
}

/**
 * Replace a smart quote with an actual single-quote so that people can
 * find themselves when they do a search on the front-end.
 *
 * @param string $in The string we want to replace smart-quotes with single-quotes.
 *
 * @return string     The string that has had the values replaced.
 */
function rsvp_smart_quote_replace( $in ) {
	return str_replace( '’', '\'', $in );
}

/**
 * Registers the RSVP data exporter to WP core
 *
 * @param array $exporters The current array of exporters registered with this WP instance.
 *
 * @return array             The exporters array now with the RSVP exporter added.
 */
function rsvp_register_data_exporter( $exporters ) {
	$exporters['rsvp-plugin'] = array(
			'exporter_friendly_name' => __( 'RSVP Plugin', 'rsvp_plugin' ),
			'callback'               => 'rsvp_data_exporter_handler',
	);

	return $exporters;
}

/**
 * RSVP shortcode handler
 *
 * @param array $atts The array of attributes for this shortcode.
 *
 * @return string       The output of the page.
 */
function rsvpshortcode_func( $atts ) {
	return rsvp_frontend_handler( 'rsvp-pluginhere ' );
}

add_shortcode( 'rsvp', 'rsvpshortcode_func' );
add_action( 'admin_init', 'rsvp_add_privacy_policy_content' );
add_filter( 'wp_privacy_personal_data_erasers', 'rsvp_register_data_eraser', 10 );
add_filter( 'wp_privacy_personal_data_exporters', 'rsvp_register_data_exporter', 10 );
add_action( 'init', 'rsvp_init' );
add_action( 'wp_head', 'rsvp_add_css' );
add_filter( 'the_content', 'rsvp_frontend_handler' );
register_activation_hook( __FILE__, 'rsvp_database_setup' );
