<?php
$rsvp_form_action     = '';
$rsvp_saved_form_vars = array();
// load some defaults.
$rsvp_saved_form_vars['mainRsvp']          = '';
$rsvp_saved_form_vars['rsvp_note']         = '';
$rsvp_saved_form_vars['attendeeFirstName'] = '';
$rsvp_saved_form_vars['attendeeLastName']  = '';

/**
 * Handles the output of the RSVP plugin.
 *
 * @param string $intial_text The text that is passed in to handle.
 * @param string $rsvp_text   The RSVP text we want to replace with the shortcode.
 *
 * @return string              The output with the shortcode replaced with the RSVP form.
 */
function rsvp_handle_output( $intial_text, $rsvp_text ) {
	$rsvp_text = '<a name="rsvpArea" id="rsvpArea"></a>' . $rsvp_text;
	remove_filter( 'the_content', 'wpautop' );

	return str_replace( RSVP_FRONTEND_TEXT_CHECK, $rsvp_text, $intial_text );
}

/**
 * Main handler for the front-end.
 *
 * @param string $text The text on the page that we need to replace the shortcode with the RSVP form.
 *
 * @return string       The page with the RSVP form in it now.
 */
function rsvp_frontend_handler( $text ) {
	global $wpdb;
	global $rsvp_form_action;
	$passcode_option_enabled = ( rsvp_require_passcode() ) ? true : false;
	// QUIT if the replacement string doesn't exist.
	if ( ! strstr( $text, RSVP_FRONTEND_TEXT_CHECK ) ) {
		return $text;
	}

	add_action( 'wp_footer', 'rsvp_front_scripts', 15 );

	// Add custom style if plugin is loaded on page.
	add_action( 'wp_footer', 'rsvp_add_css', 30 );

	$rsvp_form_action = rsvp_getCurrentPageURL();

	// See if we should allow people to RSVP, etc...
	$openDate  = get_option( OPTION_OPENDATE );
	$closeDate = get_option( OPTION_DEADLINE );
	if ( ( strtotime( $openDate ) !== false ) && ( strtotime( $openDate ) > time() ) ) {
		return rsvp_handle_output( $text, RSVP_START_PARA . sprintf( __( 'The ability to RSVP for this event will open on <strong>%s</strong>', 'rsvp' ), date_i18n( get_option( 'date_format' ), strtotime( $openDate ) ) ) . RSVP_END_PARA );
	}

	if ( ( strtotime( $closeDate ) !== false ) && ( strtotime( $closeDate ) < time() ) ) {
		return rsvp_handle_output( $text, RSVP_START_PARA . __( 'The deadline to RSVP for this event has passed.', 'rsvp' ) . RSVP_END_PARA );
	}

	if ( isset( $_POST['rsvpStep'] ) ) {
		$output = '';
		switch ( strtolower( sanitize_text_field( wp_unslash($_POST['rsvpStep'] ) ) ) ) {
			case ( 'newattendee' ):
				return rsvp_handlenewattendee( $output, $text );
				break;
			case ( 'addattendee' ):
				return rsvp_handleNewRsvp( $output, $text );
				break;
			case ( 'handlersvp' ):
				$output = rsvp_handlersvp( $output, $text );
				if ( ! empty( $output ) ) {
					return $output;
				}
				break;
			case ( 'editattendee' ):
				$output = rsvp_editAttendee( $output, $text );
				if ( ! empty( $output ) ) {
					return $output;
				}
				break;
			case ( 'foundattendee' ):
				$output = rsvp_foundAttendee( $output, $text );
				if ( ! empty( $output ) ) {
					return $output;
				}
				break;
			case ( 'find' ):
				$output = rsvp_find( $output, $text );
				if ( ! empty( $output ) ) {
					return $output;
				}
				break;
			case ( 'newsearch' ):
			default:
				return rsvp_handle_output( $text, rsvp_frontend_greeting() );
		}
	} else {
		if ( ( isset( $_REQUEST['firstName'] ) && isset( $_REQUEST['lastName'] ) ) ||
			 ( rsvp_require_only_passcode_to_register() && isset( $_REQUEST['passcode'] ) ) ) {
			$output = '';

			return rsvp_find( $output, $text );
		} else {
			return rsvp_handle_output( $text, rsvp_frontend_greeting() );
		}
	}
}

/**
 * Handles the output for a new attendee form.
 *
 * @param string $output The output we want to include in the original text.
 * @param string $text   The original text that we need to transform.
 *
 * @return string         Output that is ready for people to see.
 */
function rsvp_handlenewattendee( $output, $text ) {
	$output  = RSVP_START_CONTAINER;
	$output .= rsvp_frontend_main_form( 0, 'addAttendee' );
	$output .= RSVP_END_CONTAINER;

	return rsvp_handle_output( $text, $output );
}

/**
 * Handles the saving of custom questions for an attendee.
 *
 * @param int    $attendee_id The attendee ID we are saving the custom questions for.
 * @param string $form_name   The base form name we should be looking for custom questions on.
 */
function rsvp_handleAdditionalQuestions( $attendee_id, $form_name ) {
	global $wpdb;

	$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . ATTENDEE_ANSWERS . ' WHERE attendeeID = %d', $attendee_id ) );

	$q_rs = $wpdb->get_results(
		'SELECT q.id, questionType FROM ' . QUESTIONS_TABLE . ' q
		INNER JOIN ' . QUESTION_TYPE_TABLE . ' qt ON qt.id = q.questionTypeID
		ORDER BY q.sortOrder'
	);
	if ( count( $q_rs ) > 0 ) {
		foreach ( $q_rs as $q ) {
			if ( isset( $_POST[ $form_name . $q->id ] ) && ! empty( $_POST[ $form_name . $q->id ] ) ) {
				if ( $q->questionType == QT_MULTI ) {
					$selected_answers = '';
					$a_rs             = $wpdb->get_results( $wpdb->prepare( 'SELECT id, answer FROM ' . QUESTION_ANSWERS_TABLE . ' WHERE questionID = %d', $q->id ) );
					if ( count( $a_rs ) > 0 ) {
						foreach ( $a_rs as $a ) {
							if ( in_array( $a->id, $_POST[ $form_name . $q->id ] ) ) {
								$selected_answers .= ( ( strlen( $selected_answers ) == '0' ) ? '' : '||' ) . stripslashes( $a->answer );
							}
						}
					}

					if ( ! empty( $selected_answers ) ) {
						$wpdb->insert(
							ATTENDEE_ANSWERS,
							array(
								'attendeeID' => absint( $attendee_id ),
								'answer'     => stripslashes_deep( $selected_answers ),
								'questionID' => absint( $q->id ),
							),
							array(
								'%d',
								'%s',
								'%d',
							)
						);
					}
				} elseif ( ( $q->questionType == QT_DROP ) || ( $q->questionType == QT_RADIO ) ) {
					$a_rs = $wpdb->get_results( $wpdb->prepare( 'SELECT id, answer FROM ' . QUESTION_ANSWERS_TABLE . ' WHERE questionID = %d', $q->id ) );
					if ( count( $a_rs ) > 0 ) {
						foreach ( $a_rs as $a ) {
							if ( $a->id == $_POST[ $form_name . $q->id ] ) {
								$wpdb->insert(
									ATTENDEE_ANSWERS,
									array(
										'attendeeID' => absint( $attendee_id ),
										'answer'     => stripslashes_deep( $a->answer ),
										'questionID' => absint( $q->id ),
									),
									array(
										'%d',
										'%s',
										'%d',
									)
								);
								break;
							}
						}
					}
				} else {
					$wpdb->insert(
						ATTENDEE_ANSWERS,
						array(
							'attendeeID' => $attendee_id,
							'answer'     => sanitize_text_field( wp_unslash( $_POST[ $form_name . $q->id ] ) ),
							'questionID' => absint( $q->id ),
						),
						array(
							'%d',
							'%s',
							'%d',
						)
					);
				}
			}
		}
	}
}

/**
 * Displays a prompt to edit the attendee if they have already RSVp'd before.
 *
 * @param object $attendee The attendee row record from WPDB.
 *
 * @return string           The HTML to confirm they want to edit their RSVP.
 */
function rsvp_frontend_prompt_to_edit( $attendee ) {
	global $rsvp_form_action;
	$prompt        = RSVP_START_CONTAINER;
	$edit_greeting = __( 'Hi %s it looks like you have already RSVP\'d. Would you like to edit your reservation?', 'rsvp' );
	$prompt       .= sprintf(
		RSVP_START_PARA . $edit_greeting . RSVP_END_PARA,
		htmlspecialchars( stripslashes( $attendee->firstName . ' ' . $attendee->lastName ) )
	);
	$prompt       .= "<form method=\"post\" action=\"$rsvp_form_action\">\r\n
		<input type=\"hidden\" name=\"attendeeID\" value=\"" . $attendee->id . '" />
		<input type="hidden" name="rsvpStep" id="rsvpStep" value="editattendee" />
		<input type="submit" value="' . __( 'Yes', 'rsvp' ) . "\" onclick=\"document.getElementById('rsvpStep').value='editattendee';\" />
		<input type=\"submit\" value=\"" . __( 'No', 'rsvp' ) . "\" onclick=\"document.getElementById('rsvpStep').value='newsearch';\"  />
	</form>\r\n";
	$prompt       .= RSVP_END_CONTAINER;

	return $prompt;
}

/**
 * Shows the main RSVP form for attendee's to RSVP with.
 *
 * @param int    $attendee_id The attendee ID that we are RSVP'ing for.
 * @param string $rsvp_step   The step we are RSVP'ing for.
 *
 * @return string              The RSVP form ready for people to RSVP.
 */
function rsvp_frontend_main_form( $attendee_id, $rsvp_step = 'handleRsvp' ) {
	global $wpdb, $rsvp_form_action, $rsvp_saved_form_vars;
	$sql      = 'SELECT id, firstName, lastName, email, rsvpStatus, note, 
					kidsMeal, additionalAttendee, veggieMeal, personalGreeting
					FROM ' . ATTENDEES_TABLE . ' WHERE id = %d';
	$attendee = $wpdb->get_row( $wpdb->prepare( $sql, $attendee_id ) );

	$sql             = 'SELECT id FROM ' . ATTENDEES_TABLE .
					   ' WHERE (id IN (SELECT attendeeID FROM ' . ASSOCIATED_ATTENDEES_TABLE . ' WHERE associatedAttendeeID = %d)
			OR id in (SELECT associatedAttendeeID FROM ' . ASSOCIATED_ATTENDEES_TABLE . ' WHERE attendeeID = %d))
			AND additionalAttendee = \'Y\'';
	$new_rsvps       = $wpdb->get_results( $wpdb->prepare( $sql, $attendee_id, $attendee_id ) );
	$yes_text        = __( 'Yes', 'rsvp' );
	$no_text         = __( 'No', 'rsvp' );
	$yes_verbiage    = ( ( trim( get_option( OPTION_YES_VERBIAGE ) ) !== '' ) ? get_option( OPTION_YES_VERBIAGE ) :
		__( 'Yes, I will attend.', 'rsvp' ) );
	$no_verbiage     = ( ( trim( get_option( OPTION_NO_VERBIAGE ) ) !== '' ) ? get_option( OPTION_NO_VERBIAGE ) :
		__( 'No, I will not be able to attend.', 'rsvp' ) );
	$kids_verbiage   = ( ( trim( get_option( OPTION_KIDS_MEAL_VERBIAGE ) ) !== '' ) ? get_option( OPTION_KIDS_MEAL_VERBIAGE ) :
		__( 'We have the option of getting cheese pizza for the kids (and only kids).  Do you want pizza instead of "adult food?"', 'rsvp' ) );
	$veggie_verbiage = ( ( trim( get_option( OPTION_VEGGIE_MEAL_VERBIAGE ) ) !== '' ) ? get_option( OPTION_VEGGIE_MEAL_VERBIAGE ) :
		__( 'We also have the option of getting individual vegetarian meals instead of the fish or meat.  Would you like a vegetarian dinner?', 'rsvp' ) );
	$note_verbiage   = ( ( trim( get_option( OPTION_NOTE_VERBIAGE ) ) !== '' ) ? get_option( OPTION_NOTE_VERBIAGE ) :
		__( 'If you have any <strong style="color:red;">food allergies</strong>, please indicate what they are in the &quot;notes&quot; section below.  Or, if you just want to send us a note, please feel free.  If you have any questions, please send us an email.', 'rsvp' ) );

	$form         = '<form id="rsvpForm" name="rsvpForm" method="post" action="' . $rsvp_form_action . '" autocomplete="off">';
	$form        .= '	<input type="hidden" name="attendeeID" value="' . $attendee_id . '" />';
	$form        .= '	<input type="hidden" name="rsvpStep" value="' . $rsvp_step . '" />';
	$simple_nonce = WPSimpleNonce::createNonce( 'rsvp_fe_form' );
	$form        .= '	<input type="hidden" name="rsvp_nonce_name" value="' . $simple_nonce['name'] . '" />';
	$form        .= '	<input type="hidden" name="rsvp_nonce_value" value="' . $simple_nonce['value'] . '" />';

	if ( ! empty( $attendee->personalGreeting ) ) {
		$form .= rsvp_BeginningFormField( 'rsvpCustomGreeting', '' ) . nl2br( stripslashes_deep( $attendee->personalGreeting ) ) . RSVP_END_FORM_FIELD;
	}

	// New Attendee fields when open registration is allowed.
	if ( $attendee_id <= 0 ) {
		$form .= RSVP_START_PARA;
		$form .= rsvp_BeginningFormField( '', '' ) .
				 '<label for="attendeeFirstName">' . __( 'First Name', 'rsvp' ) . '</label>' .
				 '<input type="text" name="attendeeFirstName" id="attendeeFirstName" value="' . esc_html( $rsvp_saved_form_vars['attendeeFirstName'] ) . '" />' .
				 RSVP_END_FORM_FIELD;
		$form .= RSVP_END_PARA;

		$form .= RSVP_START_PARA;
		$form .= rsvp_BeginningFormField( '', '' ) .
				 '<label for="attendeeLastName">' . __( 'Last Name', 'rsvp' ) . '</label>' .
				 '<input type="text" name="attendeeLastName" id="attendeeLastName" value="' . htmlspecialchars( $rsvp_saved_form_vars['attendeeLastName'] ) . '" />' . RSVP_END_FORM_FIELD;
		$form .= RSVP_END_PARA;
	}

	$form .= RSVP_START_PARA;
	if ( trim( get_option( OPTION_RSVP_QUESTION ) ) !== '' ) {
		$form .= trim( get_option( OPTION_RSVP_QUESTION ) );
	} else {
		$form .= __( 'So, how about it?', 'rsvp' );
	}

	$form .= RSVP_END_PARA . rsvp_BeginningFormField( '', '' ) .
			 '<input type="radio" name="mainRsvp" value="Y" id="mainRsvpY" ' . ( ( ( ( $attendee !== null ) && ( $attendee->rsvpStatus === 'No' ) ) || ( $rsvp_saved_form_vars['mainRsvp'] == 'N' ) ) ? '' : 'checked="checked"' ) . ' /> <label for="mainRsvpY">' . $yes_verbiage . '</label>' .
			 RSVP_END_FORM_FIELD .
			 rsvp_BeginningFormField( '', '' ) .
			 '<input type="radio" name="mainRsvp" value="N" id="mainRsvpN" ' . ( ( ( ( $attendee !== null ) && ( $attendee->rsvpStatus == 'No' ) ) || ( $rsvp_saved_form_vars['mainRsvp'] == 'N' ) ) ? 'checked="checked"' : '' ) . ' /> ' .
			 '<label for="mainRsvpN">' . $no_verbiage . '</label>' .
			 RSVP_END_FORM_FIELD;

	if ( get_option( OPTION_HIDE_KIDS_MEAL ) !== 'Y' ) {
		$form .= rsvp_BeginningFormField( '', 'rsvpBorderTop' ) .
				 RSVP_START_PARA . $kids_verbiage . RSVP_END_PARA .
				 '<input type="radio" name="mainKidsMeal" value="Y" id="mainKidsMealY" ' .
				 ( ( ( ( $attendee !== null ) && ( $attendee->kidsMeal === 'Y' ) ) || ( isset( $rsvp_saved_form_vars['mainKidsMeal'] ) && ( $rsvp_saved_form_vars['mainKidsMeal'] == 'Y' ) ) ) ? 'checked="checked"' : '' ) . ' /> <label for="mainKidsMealY">' . $yes_text . '</label> ' .
				 '<input type="radio" name="mainKidsMeal" value="N" id="mainKidsMealN" ' . ( ( ( ( $attendee !== null ) && ( $attendee->kidsMeal == 'Y' ) ) || ( isset( $rsvp_saved_form_vars['mainKidsMeal'] ) && ( $rsvp_saved_form_vars['mainKidsMeal'] == 'Y' ) ) ) ? '' : 'checked="checked"' ) . ' /> <label for="mainKidsMealN">' . $no_text . '</label>' .
				 RSVP_END_FORM_FIELD;
	}

	if ( get_option( OPTION_HIDE_VEGGIE ) != 'Y' ) {
		$form .= rsvp_BeginningFormField( '', 'rsvpBorderTop' ) .
				 RSVP_START_PARA . $veggie_verbiage . RSVP_END_PARA .
				 '<input type="radio" name="mainVeggieMeal" value="Y" id="mainVeggieMealY" ' .
				 ( ( ( ( $attendee !== null ) && ( $attendee->veggieMeal === 'Y' ) ) || ( isset( $rsvp_saved_form_vars['mainVeggieMeal'] ) && ( $rsvp_saved_form_vars['mainVeggieMeal'] == 'Y' ) ) ) ? 'checked="checked"' : '' ) . '/> <label for="mainVeggieMealY">' . $yes_text . '</label> ' .
				 '<input type="radio" name="mainVeggieMeal" value="N" id="mainVeggieMealN" ' .
				 ( ( ( ( $attendee !== null ) && ( $attendee->veggieMeal == 'Y' ) ) || ( isset( $rsvp_saved_form_vars['mainVeggieMeal'] ) && ( $rsvp_saved_form_vars['mainVeggieMeal'] == 'Y' ) ) ) ? '' : 'checked="checked"' ) . ' /> <label for="mainVeggieMealN">' . $no_text . '</label>' .
				 RSVP_END_FORM_FIELD;
	}

	if ( 'Y' !== get_option( OPTION_RSVP_HIDE_EMAIL_FIELD ) ) {
		$email_value = '';

		if ( $attendee !== null ) {
			$email_value = stripslashes_deep( $attendee->email );
		}

		$form .= rsvp_BeginningFormField( '', 'rsvpBorderTop' ) .
				 '<label for="mainEmail">' . __( 'Email Address', 'rsvp' ) . '</label>' .
				 '<input type="text" name="mainEmail" id="mainEmail" value="' . esc_html( $email_value ) . '" />' .
				 RSVP_END_FORM_FIELD;
	}

	$form .= rsvp_buildAdditionalQuestions( $attendee_id, 'main' );

	if ( get_option( RSVP_OPTION_HIDE_NOTE ) != 'Y' ) {
		$form .= RSVP_START_PARA . $note_verbiage . RSVP_END_PARA .
				 rsvp_BeginningFormField( '', '' ) .
				 '<textarea name="rsvp_note" id="rsvp_note" rows="7" cols="50">' . ( ( ! empty( $attendee->note ) ) ? $attendee->note : $rsvp_saved_form_vars['rsvp_note'] ) . '</textarea>' . RSVP_END_FORM_FIELD;
	}

	$sql = 'SELECT id, firstName, lastName, email, personalGreeting, rsvpStatus FROM ' . ATTENDEES_TABLE . '
		WHERE (id IN (SELECT attendeeID FROM ' . ASSOCIATED_ATTENDEES_TABLE . ' WHERE associatedAttendeeID = %d)
			OR id in (SELECT associatedAttendeeID FROM ' . ASSOCIATED_ATTENDEES_TABLE . ' WHERE attendeeID = %d) OR
		id IN (SELECT waa1.attendeeID FROM ' . ASSOCIATED_ATTENDEES_TABLE . ' waa1
			INNER JOIN ' . ASSOCIATED_ATTENDEES_TABLE . ' waa2 ON waa2.attendeeID = waa1.attendeeID  OR
				waa1.associatedAttendeeID = waa2.attendeeID
			WHERE waa2.associatedAttendeeID = %d AND waa1.attendeeID <> %d))';

	$associations = $wpdb->get_results( $wpdb->prepare( $sql, $attendee_id, $attendee_id, $attendee_id, $attendee_id ) );
	if ( count( $associations ) > 0 ) {
		$form .= '<h3>' . __( 'The following people are associated with you.  At this time you can RSVP for them as well.', 'rsvp' ) . '</h3>';
		foreach ( $associations as $a ) {
			if ( $a->id != $attendee_id ) {
				$form .= "<div class=\"rsvpAdditionalAttendee\">\r\n";
				$form .= "<div class=\"rsvpAdditionalAttendeeQuestions\">\r\n";
				$form .= rsvp_BeginningFormField( '', '' ) . RSVP_START_PARA . sprintf( __( 'Will %s be attending?', 'rsvp' ), esc_html( stripslashes( $a->firstName . ' ' . $a->lastName ) ) ) . RSVP_END_PARA .
						 '<input type="radio" name="attending' . $a->id . '" value="Y" id="attending' . $a->id . 'Y" ' . ( ( $a->rsvpStatus === 'Yes' ) ? 'checked="checked"' : '' ) . ' /> ' .
						 '<label for="attending' . $a->id . 'Y">' . $yes_text . '</label>' . RSVP_END_FORM_FIELD .
						 rsvp_BeginningFormField( '', '' ) .
						 '<input type="radio" name="attending' . $a->id . '" value="N" id="attending' . $a->id . 'N" ' . ( ( $a->rsvpStatus === 'No' ) ? 'checked="checked"' : '' ) . ' /> ' .
						 '<label for="attending' . $a->id . 'N">' . $no_text . '</label>' .
						 RSVP_END_FORM_FIELD;

				if ( ! empty( $a->personalGreeting ) ) {
					$form .= RSVP_START_PARA . nl2br( $a->personalGreeting ) . RSVP_END_PARA;
				}

				if ( get_option( OPTION_HIDE_KIDS_MEAL ) != 'Y' ) {
					$form .= rsvp_BeginningFormField( '', '' ) .
							 RSVP_START_PARA . sprintf( __( 'Does %s need a kids meal?', 'rsvp' ), htmlspecialchars( $a->firstName ) ) .
							 RSVP_END_PARA . '&nbsp; ' .
							 '<input type="radio" name="attending' . $a->id . 'KidsMeal" value="Y" id="attending' . $a->id . 'KidsMealY" /> ' .
							 '<label for="attending' . $a->id . 'KidsMealY">' . $yes_text . '</label>
  					<input type="radio" name="attending' . $a->id . 'KidsMeal" value="N" id="attending' . $a->id . 'KidsMealN" checked="checked" /> ' .
							 '<label for="attending' . $a->id . 'KidsMealN">' . $no_text . '</label>' . RSVP_END_FORM_FIELD;
				}

				if ( get_option( OPTION_HIDE_VEGGIE ) != 'Y' ) {
					$form .= rsvp_BeginningFormField( '', '' ) .
							 RSVP_START_PARA .
							 sprintf( __( 'Does %s need a vegetarian meal?', 'rsvp' ), htmlspecialchars( $a->firstName ) ) .
							 RSVP_END_PARA . '&nbsp; ' .
							 '<input type="radio" name="attending' . $a->id . 'VeggieMeal" value="Y" id="attending' . $a->id . 'VeggieMealY" /> ' .
							 '<label for="attending' . $a->id . 'VeggieMealY">' . $yes_text . '</label>
						<input type="radio" name="attending' . $a->id . 'VeggieMeal" value="N" id="attending' . $a->id . 'VeggieMealN" checked="checked" /> ' .
							 '<label for="attending' . $a->id . 'VeggieMealN">' . $no_text . '</label>' . RSVP_END_FORM_FIELD;
				}

				if ( get_option( OPTION_RSVP_HIDE_EMAIL_FIELD ) != 'Y' ) {
					$form .= rsvp_BeginningFormField( '', 'rsvpBorderTop' ) .
							 RSVP_START_PARA .
							 '<label for="attending' . $a->id . 'Email">' . __( 'Email Address', 'rsvp' ) . '</label>' . RSVP_END_PARA .
							 '<input type="text" name="attending' . $a->id . 'Email" id="attending' . $a->id . 'Email" value="' . htmlspecialchars( $a->email ) . '" />' .
							 RSVP_END_FORM_FIELD;
				}

				$form .= rsvp_buildAdditionalQuestions( $a->id, $a->id );
				$form .= "</div>\r\n"; // -- rsvpAdditionalAttendeeQuestions.
				$form .= "</div>\r\n";
			} // if($a->id != ...).
		} // foreach($associations...).
	}

	if ( get_option( OPTION_HIDE_ADD_ADDITIONAL ) != 'Y' ) {
		$text = __( 'Did we slip up and forget to invite someone? If so, please add him or her here:', 'rsvp' );

		if ( trim( get_option( OPTION_RSVP_ADD_ADDITIONAL_VERBIAGE ) ) != '' ) {
			$text = get_option( OPTION_RSVP_ADD_ADDITIONAL_VERBIAGE );
		}

		$form .= "<h3>$text</h3>\r\n";
		$form .= '<div id="additionalRsvpContainer">' . "\r\n" .
				 '<input type="hidden" name="additionalRsvp" id="additionalRsvp" value="' . count( $new_rsvps ) . '" />
			<div style="text-align:right" id="addRsvpButtonContainer">
				<button id="addRsvp">' . __( 'Add Additional Guest', 'rsvp' ) . '</button></div>' .
				 '</div>';
	}

	$form .= RSVP_START_PARA . '<input type="submit" value="' . __( 'RSVP', 'rsvp' ) . '" />' . RSVP_END_PARA;
	rsvp_inject_add_guests_js( $attendee_id );

	$form .= "</form>\r\n";

	return $form;
}

/**
 * Retrieves the answer the attendee already recorded.
 *
 * @param int $attendee_id The attendee ID we want the answer for.
 * @param int $question_id The question ID we want the answer for.
 *
 * @return string           The answer that has already been answered or an empty string.
 */
function rsvp_revtrievePreviousAnswer( $attendee_id, $question_id ) {
	global $wpdb;
	$answers = '';
	if ( ( $attendee_id > 0 ) && ( $question_id > 0 ) ) {
		$rs = $wpdb->get_results( $wpdb->prepare( 'SELECT answer FROM ' . ATTENDEE_ANSWERS . ' WHERE questionID = %d AND attendeeID = %d', $question_id, $attendee_id ) );
		if ( count( $rs ) > 0 ) {
			$answers = stripslashes( $rs[0]->answer );
		}
	}

	return $answers;
}

/**
 * Builds the additional custom questions that are displayed on the RSVP form.
 *
 * @param int    $attendee_id The attendee ID that we want to get the custom questions for.
 * @param string $prefix      The form element prefix we should use for these custom questions.
 *
 * @return string              The custom question form elements for the attendee.
 */
function rsvp_buildAdditionalQuestions( $attendee_id, $prefix ) {
	global $wpdb, $rsvp_saved_form_vars;
	$output = '<div class="rsvpCustomQuestions">';

	$sql       = 'SELECT q.id, q.question, questionType FROM ' . QUESTIONS_TABLE . ' q
		INNER JOIN ' . QUESTION_TYPE_TABLE . ' qt ON qt.id = q.questionTypeID
		WHERE q.permissionLevel = \'public\'
		OR (q.permissionLevel = \'private\' AND q.id IN (SELECT questionID FROM ' . QUESTION_ATTENDEES_TABLE . ' WHERE attendeeID = ' . $attendee_id . '))
		ORDER BY q.sortOrder ';
	$questions = $wpdb->get_results( $sql );
	if ( count( $questions ) > 0 ) {
		foreach ( $questions as $q ) {
			$oldAnswer = rsvp_revtrievePreviousAnswer( $attendee_id, $q->id );

			$output .= rsvp_BeginningFormField( '', '' ) . '<label>' . esc_html( stripslashes_deep( $q->question ) ) . '</label>';

			if ( $q->questionType == QT_MULTI ) {
				$oldAnswers = explode( '||', $oldAnswer );

				$sql     = 'SELECT id, answer FROM ' . QUESTION_ANSWERS_TABLE . ' WHERE questionID = %d';
				$answers = $wpdb->get_results( $wpdb->prepare( $sql, $q->id ) );
				if ( count( $answers ) > 0 ) {
					$i = 0;
					foreach ( $answers as $a ) {
						$output .= rsvp_BeginningFormField( '', 'rsvpCheckboxCustomQ' ) .
								   '<input type="checkbox" name="' . esc_attr( $prefix ) . 'question' . absint( $q->id ) . '[]" id="' . esc_attr( $prefix ) . 'question' . absint( $q->id ) . absint( $a->id ) . '" value="' . absint( $a->id ) . '" ' .
								   ( ( in_array( stripslashes_deep( $a->answer ), $oldAnswers ) ) ? ' checked="checked"' : '' ) . ' />' .
								   '<label for="' . esc_attr( $prefix ) . 'question' . absint( $q->id )  . absint( $a->id ) . '">' . esc_html( $a->answer ) . '</label>' . "\r\n" . RSVP_END_FORM_FIELD;
						$i ++;
					}
					$output .= '<div class="rsvpClear">&nbsp;</div>' . "\r\n";
				}
			} elseif ( $q->questionType == QT_DROP ) {
				$output .= '<select name="' . esc_attr( $prefix ) . 'question' . esc_attr( $q->id ) . '" size="1">' . "\r\n" .
							'<option value="">--</option>' . "\r\n";
				$sql     = 'SELECT id, answer FROM ' . QUESTION_ANSWERS_TABLE . ' WHERE questionID = %d';
				$answers = $wpdb->get_results( $wpdb->prepare( $sql, $q->id ) );
				if ( count( $answers ) > 0 ) {
					foreach ( $answers as $a ) {
						$output .= '<option value="' . absint( $a->id ) . '" ' . ( ( stripslashes_deep( $a->answer ) == $oldAnswer ) ? ' selected="selected"' : '' ) . '>' . esc_html( $a->answer ) . "</option>\r\n";
					}
				}
				$output .= "</select>\r\n";
			} elseif ( $q->questionType == QT_LONG ) {
				$output .= '<textarea name="' . esc_attr( $prefix ) . 'question' . absint( $q->id ) . '" rows="5" cols="35">' . esc_html( $oldAnswer ) . '</textarea>';
			} elseif ( $q->questionType == QT_RADIO ) {
				$sql     = 'SELECT id, answer FROM ' . QUESTION_ANSWERS_TABLE . ' WHERE questionID = %d';
				$answers = $wpdb->get_results( $wpdb->prepare( $sql, $q->id ) );
				if ( count( $answers ) > 0 ) {
					$i       = 0;
					$output .= RSVP_START_PARA;
					foreach ( $answers as $a ) {
						$output .= '<input type="radio" name="' . esc_attr( $prefix ) . 'question' . absint( $q->id ) . '" id="' . esc_attr( $prefix ) . 'question' . absint( $q->id ) . absint( $a->id ) . '" value="' . absint( $a->id ) . '" '
								   . ( ( stripslashes( $a->answer ) == $oldAnswer ) ? ' checked="checked"' : '' ) . ' /> ' .
								   '<label for="' . esc_attr( $prefix ) . 'question' . absint( $q->id ) . absint( $a->id ) . '">' . esc_html( stripslashes_deep( $a->answer ) ) . "</label>\r\n";
						$i ++;
					}
					$output .= RSVP_END_PARA;
				}
			} else {
				$output .= '<input type="text" name="' . esc_attr( $prefix ) . 'question' . absint( $q->id ) . '" value="' . esc_attr( $oldAnswer ) . '" size="25" />';
			}

			$output .= RSVP_END_FORM_FIELD;
		}
	}

	return $output . '</div>';
}

/**
 * Tries to find the attendee and displays possible matches.
 *
 * @param string &$output The output we want to display to the user.
 * @param string &$text   The original text that is displayed on the page.
 *
 * @return string          The find results ready to be viewed by the person.
 */
function rsvp_find( &$output, &$text ) {
	global $wpdb, $rsvp_form_action;
	$passcode_option_enabled = ( rsvp_require_passcode() ) ? true : false;
	$passcode_only_option    = ( rsvp_require_only_passcode_to_register() ) ? true : false;

	$passcode = '';
	if ( isset( $_REQUEST['passcode'] ) ) {
		$passcode = sanitize_text_field( wp_unslash( $_REQUEST['passcode'] ) );
	}

	$first_name = '';
	$last_name  = '';
	if ( isset( $_REQUEST['firstName'] ) ) {
		$first_name = sanitize_text_field( stripslashes_deep( $_REQUEST['firstName'] ) );
	}

	if ( isset( $_REQUEST['lastName'] ) ) {
		$last_name = sanitize_text_field( stripslashes_deep( $_REQUEST['lastName'] ) );
	}

	if ( ! $passcode_only_option && ( ( strlen( $first_name ) <= 1 ) || ( strlen( $last_name ) <= 1 ) ) ) {
		$output  = '<p class="rsvpParagraph" style="color:red">' . esc_html__( 'A first and last name must be specified', 'rsvp' ) . "</p>\r\n";
		$output .= rsvp_frontend_greeting();

		return rsvp_handle_output( $text, $output );
	}

	// Try to find the user.
	if ( $passcode_option_enabled ) {
		if ( $passcode_only_option ) {
			$sql      = 'SELECT id, firstName, lastName, rsvpStatus
						FROM ' . ATTENDEES_TABLE . ' WHERE passcode = %s';
			$attendee = $wpdb->get_row( $wpdb->prepare( $sql, $passcode ) );
		} else {
			$sql      = 'SELECT id, firstName, lastName, rsvpStatus
						FROM ' . ATTENDEES_TABLE . ' WHERE firstName = %s AND lastName = %s AND passcode = %s';
			$attendee = $wpdb->get_row( $wpdb->prepare( $sql, $first_name, $last_name, $passcode ) );
		}
	} else {
		$sql      = 'SELECT id, firstName, lastName, rsvpStatus
					FROM ' . ATTENDEES_TABLE . ' WHERE firstName = %s AND lastName = %s';
		$attendee = $wpdb->get_row( $wpdb->prepare( $sql, $first_name, $last_name ) );
	}

	if ( $attendee != null ) {
		// hey we found something, we should move on and print out any associated users and let them rsvp.
		$output = RSVP_START_CONTAINER;
		if ( strtolower( $attendee->rsvpStatus ) == 'noresponse' ) {
			$output .= RSVP_START_PARA . __( 'Hi', 'rsvp' ) . ' ' .
					   htmlspecialchars( stripslashes_deep( $attendee->firstName . ' ' . $attendee->lastName ) ) . '!' . RSVP_END_PARA;

			if ( trim( get_option( OPTION_WELCOME_TEXT ) ) !== '' ) {
				$output .= RSVP_START_PARA . trim( get_option( OPTION_WELCOME_TEXT ) ) . RSVP_END_PARA;
			} else {
				$output .= RSVP_START_PARA .
						   __( 'There are a few more questions we need to ask you if you could please fill them out below to finish up the RSVP process.', 'rsvp' ) . RSVP_END_PARA;
			}

			$output .= rsvp_frontend_main_form( $attendee->id );
		} else {
			$output .= rsvp_frontend_prompt_to_edit( $attendee );
		}

		return rsvp_handle_output( $text, $output . "</div>\r\n" );
	}

	// We did not find anyone let's try and do a rough search.
	$attendees = null;
	if ( ! $passcode_option_enabled && ( get_option( OPTION_RSVP_DISABLE_USER_SEARCH ) !== 'Y' ) ) {
		for ( $i = 3; $i >= 1; $i -- ) {
			$trunc_first_name = rsvp_chomp_name( $first_name, $i );
			$true_last_name   = substr( $last_name, strrpos( $last_name, ' ' ) + 1 );
			$middle_names     = substr( $last_name, 0, strrpos( $last_name, ' ' ) );
			$sql              = 'SELECT id, firstName, lastName, rsvpStatus FROM ' . ATTENDEES_TABLE .
								' WHERE (lastName = %s AND firstName LIKE \'' . esc_sql( $trunc_first_name ) . '%\') OR
				(lastName = %s AND firstName = %s)';
			$attendees        = $wpdb->get_results( $wpdb->prepare( $sql, $last_name, $true_last_name, $first_name . ' ' . $middle_names ) );
			if ( count( $attendees ) > 0 ) {
				$output = RSVP_START_PARA . '<strong>' . __( 'We could not find an exact match but could any of the below entries be you?', 'rsvp' ) . '</strong>' . RSVP_END_PARA;
				foreach ( $attendees as $a ) {
					$output .= "<form method=\"post\" action=\"$rsvp_form_action\">\r\n
						<input type=\"hidden\" name=\"rsvpStep\" value=\"foundattendee\" />\r\n
						<input type=\"hidden\" name=\"attendeeID\" value=\"" . $a->id . "\" />\r\n
						<p class=\"rsvpParagraph\" style=\"text-align:left;\">\r\n
							" . htmlspecialchars( $a->firstName . ' ' . $a->lastName ) . '
						<input type="submit" value="' . __( 'RSVP', 'rsvp' ) . "\" />\r\n
						</p>\r\n</form>\r\n";
				}

				return rsvp_handle_output( $text, $output );
			} else {
				$i = strlen( $trunc_first_name );
			}
		}
	}

	if ( rsvp_require_only_passcode_to_register() ) {
		$not_found_text = sprintf( RSVP_START_PARA . __( '<strong>We were unable to find anyone with the passcode you specified.</strong>', 'rsvp' ) . RSVP_END_PARA );
	} elseif ( rsvp_require_passcode() ) {
		$not_found_text = RSVP_START_PARA . sprintf( __( '<strong>We were unable to find anyone with a name of %1$s %2$s or the provided passcode was incorrect.</strong>', 'rsvp' ), htmlspecialchars( wp_unslash( $first_name ) ), htmlspecialchars( wp_unslash( $last_name ) ) ) . RSVP_END_PARA;
	} else {
		$not_found_text = RSVP_START_PARA . sprintf( __( '<strong>We were unable to find anyone with a name of %1$s %2$s</strong>', 'rsvp' ), htmlspecialchars( wp_unslash( $first_name ) ), htmlspecialchars( wp_unslash( $last_name ) ) ) . RSVP_END_PARA;
	}

	$not_found_text .= rsvp_frontend_greeting();

	return rsvp_handle_output( $text, $not_found_text );
}

/**
 * Handles the adding of new attendee registration.
 *
 * @param string &$output The output we want to send to the user.
 * @param string &$text   The text that is initially on the post/page.
 *
 * @return string          The return html that should be displayed to the user.
 */
function rsvp_handleNewRsvp( &$output, &$text ) {
	global $wpdb, $rsvp_saved_form_vars;
	$thank_you_primary    = '';
	$thank_you_associated = array();
	foreach ( $_POST as $key => $val ) {
		$rsvp_saved_form_vars[ $key ] = $val;
	}

	if ( ! isset( $_POST['rsvp_nonce_name'] ) || ! isset( $_POST['rsvp_nonce_value'] ) ||
		 ! WPSimpleNonce::checkNonce( $_POST['rsvp_nonce_name'], $_POST['rsvp_nonce_value'] )
	) {
		return rsvp_handle_output( $text, rsvp_frontend_greeting() );
		exit;
	}

	if ( empty( $_POST['attendeeFirstName'] ) || empty( $_POST['attendeeLastName'] ) ) {
		return rsvp_handlenewattendee( $output, $text );
	}

	$rsvp_password = '';
	$rsvp_status   = 'No';
	if ( strToUpper( sanitize_text_field( wp_unslash( $_POST['mainRsvp'] ) ) ) == 'Y' ) {
		$rsvp_status = 'Yes';
	}
	$kids_meal         = ( ( isset( $_POST['mainKidsMeal'] ) && ( strToUpper( sanitize_text_field( wp_unslash( $_POST['mainKidsMeal'] ) ) ) === 'Y' ) ) ? 'Y' : 'N' );
	$veggie_meal       = ( ( isset( $_POST['mainVeggieMeal'] ) && ( strToUpper( sanitize_text_field( wp_unslash( $_POST['mainVeggieMeal'] ) ) ) === 'Y' ) ) ? 'Y' : 'N' );
	$thank_you_primary = sanitize_text_field( wp_unslash( $_POST['attendeeFirstName'] ) );
	$email             = sanitize_email( wp_unslash( $_POST['mainEmail'] ) );
	if ( get_option( OPTION_RSVP_HIDE_EMAIL_FIELD ) === 'Y' ) {
		$email = '';
	}

	$wpdb->insert(
		ATTENDEES_TABLE,
		array(
			'rsvpDate'   => date( 'Y-m-d' ),
			'firstName'  => sanitize_text_field( wp_unslash( $_POST['attendeeFirstName'] ) ),
			'lastName'   => sanitize_text_field( wp_unslash( $_POST['attendeeLastName'] ) ),
			'email'      => sanitize_email( wp_unslash( $email ) ),
			'rsvpStatus' => sanitize_text_field( wp_unslash( $rsvp_status ) ),
			'note'       => sanitize_text_field( wp_unslash( $_POST['rsvp_note'] ) ),
			'kidsMeal'   => sanitize_text_field( wp_unslash( $kids_meal ) ),
			'veggieMeal' => sanitize_text_field( wp_unslash( $veggie_meal ) ),
		),
		array(
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
		)
	);
	$attendee_id = $wpdb->insert_id;

	if ( rsvp_require_passcode() ) {
		$rsvp_password = trim( rsvp_generate_passcode() );
		$wpdb->update(
			ATTENDEES_TABLE,
			array( 'passcode' => sanitize_text_field( $rsvp_password ) ),
			array( 'id' => absint( $attendee_id ) ),
			array( '%s' ),
			array( '%d' )
		);
	}

	rsvp_handleAdditionalQuestions( $attendee_id, 'mainquestion' );

	$sql          = 'SELECT id, firstName FROM ' . ATTENDEES_TABLE .
					' WHERE (id IN (SELECT attendeeID FROM ' . ASSOCIATED_ATTENDEES_TABLE . ' WHERE associatedAttendeeID = %d)
			OR id in (SELECT associatedAttendeeID FROM ' . ASSOCIATED_ATTENDEES_TABLE . ' WHERE attendeeID = %d)) ';
	$associations = $wpdb->get_results( $wpdb->prepare( $sql, $attendee_id, $attendee_id ) );
	foreach ( $associations as $a ) {
		if ( isset( $_POST[ 'attending' . $a->id ] ) && ( ( $_POST[ 'attending' . $a->id ] == 'Y' ) || ( $_POST[ 'attending' . $a->id ] == 'N' ) ) ) {
			if ( $_POST[ 'attending' . $a->id ] == 'Y' ) {
				$rsvp_status = 'Yes';
			} else {
				$rsvp_status = 'No';
			}
			$thank_you_associated[] = stripslashes_deep( $a->firstName );
			if ( get_option( OPTION_RSVP_HIDE_EMAIL_FIELD ) != 'Y' ) {
				$wpdb->update(
					ATTENDEES_TABLE,
					array(
						'rsvpDate'   => date( 'Y-m-d' ),
						'rsvpStatus' => sanitize_text_field( $rsvp_status ),
						'email'      => sanitize_email( wp_unslash ( $_POST[ 'attending' . $a->id . 'Email' ] ) ),
						'kidsMeal'   => ( ( strToUpper( ( isset( $_POST[ 'attending' . $a->id . 'KidsMeal' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'attending' . $a->id . 'KidsMeal' ] ) ) : 'N' ) ) == 'Y' ) ? 'Y' : 'N' ),
						'veggieMeal' => ( ( strToUpper( ( isset( $_POST[ 'attending' . $a->id . 'VeggieMeal' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'attending' . $a->id . 'VeggieMeal' ] ) ) : 'N' ) ) == 'Y' ) ? 'Y' : 'N' ),
					),
					array( 'id' => absint( $a->id ) ),
					array(
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
					),
					array( '%d' )
				);
			} else {
				$wpdb->update(
					ATTENDEES_TABLE,
					array(
						'rsvpDate'   => date( 'Y-m-d' ),
						'rsvpStatus' => $rsvp_status,
						'kidsMeal'   => ( ( strToUpper( ( isset( $_POST[ 'attending' . $a->id . 'KidsMeal' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'attending' . $a->id . 'KidsMeal' ] ) ) : 'N' ) ) == 'Y' ) ? 'Y' : 'N' ),
						'veggieMeal' => ( ( strToUpper( ( isset( $_POST[ 'attending' . $a->id . 'VeggieMeal' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'attending' . $a->id . 'VeggieMeal' ] ) ) : 'N' ) ) == 'Y' ) ? 'Y' : 'N' ),
					),
					array( 'id' => absint( $a->id ) ),
					array(
						'%s',
						'%s',
						'%s',
						'%s',
					),
					array( '%d' )
				);
			}
			rsvp_handleAdditionalQuestions( $a->id, $a->id . 'question' );
		}
	}

	if ( get_option( OPTION_HIDE_ADD_ADDITIONAL ) != 'Y' ) {
		if ( is_numeric( $_POST['additionalRsvp'] ) && ( absint( $_POST['additionalRsvp'] ) > 0 ) ) {
			for ( $i = 1; $i <= absint( $_POST['additionalRsvp'] ); $i ++ ) {
				$num_guests = 3;
				if ( get_option( OPTION_RSVP_NUM_ADDITIONAL_GUESTS ) != '' ) {
					$num_guests = get_optioN( OPTION_RSVP_NUM_ADDITIONAL_GUESTS );
					if ( ! is_numeric( $num_guests ) || ( $num_guests < 0 ) ) {
						$num_guests = 3;
					}
				}

				if ( ( $i <= $num_guests ) &&
					 ! empty( $_POST[ 'newAttending' . $i . 'FirstName' ] ) &&
					 ! empty( $_POST[ 'newAttending' . $i . 'LastName' ] ) ) {
					$email = sanitize_email( wp_unslash( $_POST[ 'newAttending' . $i . 'Email' ] ) );
					if ( get_option( OPTION_RSVP_HIDE_EMAIL_FIELD ) != 'Y' ) {
						$email = '';
					}

					$thank_you_associated[] = sanitize_text_field( wp_unslash( $_POST[ 'newAttending' . $i . 'FirstName' ] ) );
					$wpdb->insert(
						ATTENDEES_TABLE,
						array(
							'firstName'          => sanitize_text_field( wp_unslash( $_POST[ 'newAttending' . $i . 'FirstName' ] ) ),
							'lastName'           => sanitize_text_field( wp_unslash( $_POST[ 'newAttending' . $i . 'LastName' ] ) ),
							'email'              => sanitize_email( $email ),
							'rsvpDate'           => date( 'Y-m-d' ),
							'rsvpStatus'         => ( ( sanitize_text_field( wp_unslash( $_POST[ 'newAttending' . $i ] ) ) == 'Y' ) ? 'Yes' : 'No' ),
							'kidsMeal'           => ( isset( $_POST[ 'newAttending' . $i . 'KidsMeal' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'newAttending' . $i . 'KidsMeal' ] ) ) : 'N' ),
							'veggieMeal'         => ( isset( $_POST[ 'newAttending' . $i . 'VeggieMeal' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'newAttending' . $i . 'VeggieMeal' ] ) ) : 'N' ),
							'additionalAttendee' => 'Y',
						),
						array(
							'%s',
							'%s',
							'%s',
							'%s',
							'%s',
							'%s',
							'%s',
						)
					);
					$new_aid = $wpdb->insert_id;
					rsvp_handleAdditionalQuestions( $new_aid, $i . 'question' );

					// Add associations for this new user.
					$wpdb->insert(
						ASSOCIATED_ATTENDEES_TABLE,
						array(
							'attendeeID'           => absint( $new_aid ),
							'associatedAttendeeID' => absint( $attendee_id ),
						),
						array(
							'%d',
							'%d',
						)
					);
					$wpdb->query(
						'INSERT INTO ' . ASSOCIATED_ATTENDEES_TABLE . '(attendeeID, associatedAttendeeID)
						SELECT ' . $new_aid . ', associatedAttendeeID
						FROM ' . ASSOCIATED_ATTENDEES_TABLE . ' WHERE attendeeID = ' . absint( $attendee_id )
					);
				}
			}
		}
	}

	rsvp_handle_notifications( $attendee_id );

	return rsvp_handle_output( $text, rsvp_frontend_new_atendee_thankyou( $thank_you_primary, $thank_you_associated, $rsvp_password ) );
}

/**
 * Handles notifying people via email after an RSVP occurs.
 *
 * @param int $attendee_id The main attendee ID that just RSVP'd.
 */
function rsvp_handle_notifications( $attendee_id ) {
	global $wpdb;

	if ( ( get_option( OPTION_NOTIFY_ON_RSVP ) == 'Y' ) && ( get_option( OPTION_NOTIFY_EMAIL ) != '' ) ) {
		$sql      = 'SELECT firstName, lastName, rsvpStatus, note, kidsMeal, veggieMeal, email, passcode FROM ' . ATTENDEES_TABLE . ' WHERE id= ' . $attendee_id;
		$attendee = $wpdb->get_row( $sql );
		if ( $attendee !== null ) {
			$body = __( 'Hello', 'rsvp' ) . ", \r\n\r\n";

			$body .= sprintf(
				__( '%1$s %2$s has submitted their RSVP and has RSVP\'d with %3$s.', 'rsvp' ),
				esc_html( wp_unslash( $attendee->firstName ) ),
				esc_html( wp_unslash( $attendee->lastName ) ),
				$attendee->rsvpStatus
			) . "\r\n";

			if ( get_option( OPTION_HIDE_KIDS_MEAL ) != 'Y' ) {
				$body .= __( 'Kids Meal: ', 'rsvp' ) . esc_html( $attendee->kidsMeal ) . "\r\n";
			}

			if ( get_option( OPTION_HIDE_VEGGIE ) != 'Y' ) {
				$body .= __( 'Vegetarian Meal: ', 'rsvp' ) . esc_html( $attendee->veggieMeal ) . "\r\n";
			}

			if ( get_option( OPTION_RSVP_HIDE_EMAIL_FIELD ) != 'Y' ) {
				$body .= __( 'Email: ', 'rsvp' ) . sanitize_email( stripslashes_deep( $attendee->email ) ) . "\r\n";
			}

			if ( get_option( RSVP_OPTION_HIDE_NOTE ) != 'Y' ) {
				$body .= __( 'Note: ', 'rsvp' ) . esc_html( stripslashes_deep( $attendee->note ) ) . "\r\n";
			}

			$sql = 'SELECT q.id, question, answer, q.sortOrder FROM ' . QUESTIONS_TABLE . ' q
				LEFT JOIN ' . ATTENDEE_ANSWERS . ' ans ON q.id = ans.questionID AND ans.attendeeID = %d
				WHERE (q.permissionLevel = \'public\' OR
			  		(q.permissionLevel = \'private\' AND q.id IN (SELECT questionID FROM ' . QUESTION_ATTENDEES_TABLE . ' WHERE attendeeID = %d)))
				ORDER BY q.sortOrder, q.id';

			$a_rs = $wpdb->get_results( $wpdb->prepare( $sql, absint( $attendee_id ), absint( $attendee_id ) ) );
			if ( count( $a_rs ) > 0 ) {
				foreach ( $a_rs as $a ) {
					$body .= esc_html( stripslashes_deep( $a->question ) ) . ': ' . esc_html( stripslashes_deep( $a->answer ) ) . "\r\n";
				}
			}

			$sql = 'SELECT firstName, lastName, rsvpStatus, id, email FROM ' . ATTENDEES_TABLE .
				   ' WHERE id IN (SELECT attendeeID FROM ' . ASSOCIATED_ATTENDEES_TABLE . ' WHERE associatedAttendeeID = %d)
					OR id in (SELECT associatedAttendeeID FROM ' . ASSOCIATED_ATTENDEES_TABLE . ' WHERE attendeeID = %d)';

			$associations = $wpdb->get_results( $wpdb->prepare( $sql, absint( $attendee_id ) , absint( $attendee_id ) ) );
			if ( count( $associations ) > 0 ) {
				$body .= "\r\n\r\n--== " . __( 'Associated Attendees', 'rsvp' ) . " ==--\r\n";
				foreach ( $associations as $a ) {
					$body .= sprintf(
						__( '%1$s %2$s rsvp status: %3$s', 'rsvp' ),
						esc_html( wp_unslash( $a->firstName ) ),
						esc_html( wp_unslash( $a->lastName ) ),
						$a->rsvpStatus
					) . "\r\n";

					$sql  = 'SELECT question, answer FROM ' . QUESTIONS_TABLE . ' q
						LEFT JOIN ' . ATTENDEE_ANSWERS . ' ans ON q.id = ans.questionID AND ans.attendeeID = %d
						WHERE (q.permissionLevel = \'public\' OR
							(q.permissionLevel = \'private\' AND q.id IN (SELECT questionID FROM ' . QUESTION_ATTENDEES_TABLE . ' WHERE attendeeID = %d)))
						ORDER BY q.sortOrder, q.id';
					$a_rs = $wpdb->get_results( $wpdb->prepare( $sql, $a->id, $a->id ) );
					if ( count( $a_rs ) > 0 ) {
						foreach ( $a_rs as $ans ) {
							$body .= esc_html( stripslashes_deep( $ans->question ) ) . ': ' . esc_html( stripslashes_deep( $ans->answer ) ) . "\r\n";
						}
						$body .= "\r\n";
					}
				}
			}

			$email_addy = get_option( OPTION_NOTIFY_EMAIL );
			$headers    = '';
			if ( get_option( OPTION_RSVP_DISABLE_CUSTOM_EMAIL_FROM ) != 'Y' ) {
				$headers = 'From: ' . sanitize_email( wp_unslash( $email_addy ) ) . "\r\n";
			}

			wp_mail( $email_addy, __( 'New RSVP Submission', 'rsvp' ), $body, $headers );
		}
	}

	if ( ( 'Y' === get_option( OPTION_RSVP_GUEST_EMAIL_CONFIRMATION ) ) && ! empty( $_POST['mainEmail'] ) ) {
		$sql      = 'SELECT firstName, lastName, email, rsvpStatus, passcode, kidsMeal, veggieMeal, note FROM ' . ATTENDEES_TABLE . ' WHERE id = %d';
		$attendee = $wpdb->get_row( $wpdb->prepare( $sql, absint( $attendee_id ) ) );
		if ( null !== $attendee ) {
			$body = sprintf(
				__( 'Hello %1$s %2$s,', 'rsvp' ),
				esc_html( stripslashes_deep( $attendee->firstName ) ),
				esc_html( stripslashes_deep( $attendee->lastName ) )
			) . '<br /><br />';

			if ( get_option( OPTION_RSVP_EMAIL_TEXT ) != '' ) {
				$body .= '<br />';
				$body .= wp_kses_post( get_option( OPTION_RSVP_EMAIL_TEXT ) );
				$body .= '<br />';
			}

			if ( rsvp_require_passcode() ) {
				$body .= __( 'Passcode', 'rsvp' ) . ': ' . sanitize_text_field( stripslashes_deep( $attendee->passcode ) ) . '<br />';
			}

			if ( get_option( OPTION_HIDE_KIDS_MEAL ) != 'Y' ) {
				$body .= __( 'Kids Meal: ', 'rsvp' ) . esc_html( $attendee->kidsMeal ) . '<br />';
			}

			if ( get_option( OPTION_HIDE_VEGGIE ) != 'Y' ) {
				$body .= __( 'Vegetarian Meal: ', 'rsvp' ) . esc_html( $attendee->veggieMeal ) . '<br />';
			}

			if ( get_option( OPTION_RSVP_HIDE_EMAIL_FIELD ) != 'Y' ) {
				$body .= __( 'Email: ', 'rsvp' ) . sanitize_email( stripslashes_deep( $attendee->email ) ) . '<br />';
			}

			if ( get_option( RSVP_OPTION_HIDE_NOTE ) != 'Y' ) {
				$body .= __( 'Note: ', 'rsvp' ) . esc_html( stripslashes_deep( $attendee->note ) ) . '<br />';
			}

			$body .= __( 'You have successfully RSVP\'d with', 'rsvp' ) . ' \'' . esc_html( $attendee->rsvpStatus ) . '\'.<br /><br />';

			$sql  = 'SELECT question, answer FROM ' . QUESTIONS_TABLE . ' q
				LEFT JOIN ' . ATTENDEE_ANSWERS . ' ans ON q.id = ans.questionID AND ans.attendeeID = %d
				WHERE (q.permissionLevel = \'public\' OR
				(q.permissionLevel = \'private\' AND q.id IN (SELECT questionID FROM ' . QUESTION_ATTENDEES_TABLE . ' WHERE attendeeID = %d)))
            	ORDER BY q.sortOrder, q.id';
			$a_rs = $wpdb->get_results( $wpdb->prepare( $sql, $attendee_id, $attendee_id ) );
			if ( count( $a_rs ) > 0 ) {
				foreach ( $a_rs as $ans ) {
					$body .= stripslashes( $ans->question ) . ': ' . stripslashes( $ans->answer ) . '<br />';
				}
				$body .= '<br />';
			}

			$sql          = 'SELECT firstName, lastName, rsvpStatus, id, email FROM ' . ATTENDEES_TABLE .
							' WHERE id IN (SELECT attendeeID FROM ' . ASSOCIATED_ATTENDEES_TABLE . ' WHERE associatedAttendeeID = %d)
					OR id in (SELECT associatedAttendeeID FROM ' . ASSOCIATED_ATTENDEES_TABLE . ' WHERE attendeeID = %d)';
			$associations = $wpdb->get_results( $wpdb->prepare( $sql, $attendee_id, $attendee_id ) );
			if ( count( $associations ) > 0 ) {
				$body .= '<br /><br />--== ' . __( 'Associated Attendees', 'rsvp' ) . ' ==--<br />';
				foreach ( $associations as $a ) {
					$body .= stripslashes_deep( $a->firstName . ' ' . $a->lastName ) . ' rsvp status: ' . $a->rsvpStatus . "\r\n";
					$sql   = 'SELECT question, answer FROM ' . QUESTIONS_TABLE . ' q
						LEFT JOIN ' . ATTENDEE_ANSWERS . ' ans ON q.id = ans.questionID AND ans.attendeeID = %d
    				WHERE (q.permissionLevel = \'public\' OR
						(q.permissionLevel = \'private\' AND q.id IN (SELECT questionID FROM ' . QUESTION_ATTENDEES_TABLE . ' WHERE attendeeID = %d)))
    				ORDER BY q.sortOrder, q.id';
					$a_rs  = $wpdb->get_results( $wpdb->prepare( $sql, $a->id, $a->id ) );
					if ( count( $a_rs ) > 0 ) {
						foreach ( $a_rs as $ans ) {
							$body .= stripslashes_deep( $ans->question ) . ': ' . stripslashes_deep( $ans->answer ) . "\r\n";
						}
						$body .= '<br />';
					}
				}
			}
			$email_addy = get_option( OPTION_NOTIFY_EMAIL );
			$headers    = array( 'Content-Type: text/html; charset=UTF-8' );
			if ( ! empty( $email_addy ) && ( get_option( OPTION_RSVP_DISABLE_CUSTOM_EMAIL_FROM ) != 'Y' ) ) {
				$headers[] = 'From: ' . $email_addy . "\r\n";
			}

			wp_mail( $attendee->email, __( 'RSVP Confirmation', 'rsvp' ), $body, $headers );
		}
	}
}

/**
 * Handles saving of an existing attendee.
 *
 * @param string &$output The current output that needs to be sent to the user.
 * @param string &$text   The original page/post text for this page.
 *
 * @return string          The formatted text that needs to get displayed to the user.
 */
function rsvp_handlersvp( &$output, &$text ) {
	global $wpdb;
	$thank_you_primary    = '';
	$thank_you_associated = array();

	if ( ! isset( $_POST['rsvp_nonce_name'] ) || ! isset( $_POST['rsvp_nonce_value'] ) ||
		 ! WPSimpleNonce::checkNonce( $_POST['rsvp_nonce_name'], $_POST['rsvp_nonce_value'] )
	) {
		return rsvp_handle_output( $text, rsvp_frontend_greeting() );
	}

	if ( is_numeric( $_POST['attendeeID'] ) && ( absint( $_POST['attendeeID'] ) > 0 ) ) {
		// update their information and what not....
		if ( strToUpper( sanitize_text_field( wp_unslash( $_POST['mainRsvp'] ) ) ) == 'Y' ) {
			$rsvp_status = 'Yes';
		} else {
			$rsvp_status = 'No';
		}
		$attendee_id = absint( $_POST['attendeeID'] );
		// Get Attendee first name.
		$thank_you_primary = $wpdb->get_var( $wpdb->prepare( 'SELECT firstName FROM ' . ATTENDEES_TABLE . ' WHERE id = %d', absint( $attendee_id ) ) );
		if ( get_option( OPTION_RSVP_HIDE_EMAIL_FIELD ) != 'Y' ) {
			$wpdb->update(
				ATTENDEES_TABLE,
				array(
					'rsvpDate'   => date( 'Y-m-d' ),
					'rsvpStatus' => $rsvp_status,
					'note'       => isset( $_POST['rsvp_note'] ) ? sanitize_text_field( wp_unslash( $_POST['rsvp_note'] ) ) : '',
					'email'      => isset( $_POST['mainEmail'] ) ? sanitize_email( wp_unslash( $_POST['mainEmail'] ) ) : '',
					'kidsMeal'   => ( ( isset( $_POST['mainKidsMeal'] ) && ( strToUpper( sanitize_text_field( wp_unslash( $_POST['mainKidsMeal'] ) ) ) == 'Y' ) ) ? 'Y' : 'N' ),
					'veggieMeal' => ( ( isset( $_POST['mainVeggieMeal'] ) && ( strToUpper( sanitize_text_field( wp_unslash( $_POST['mainVeggieMeal'] ) ) ) == 'Y' ) ) ? 'Y' : 'N' ),
				),
				array( 'id' => absint( $attendee_id ) ),
				array(
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
				),
				array( '%d' )
			);
		} else {
			$wpdb->update(
				ATTENDEES_TABLE,
				array(
					'rsvpDate'   => date( 'Y-m-d' ),
					'rsvpStatus' => sanitize_text_field( $rsvp_status ),
					'note'       => isset( $_POST['rsvp_note'] ) ? sanitize_text_field( wp_unslash( $_POST['rsvp_note'] ) ) : '',
					'kidsMeal'   => ( ( isset( $_POST['mainKidsMeal'] ) && ( strToUpper( sanitize_text_field( wp_unslash( $_POST['mainKidsMeal'] ) ) ) == 'Y' ) ) ? 'Y' : 'N' ),
					'veggieMeal' => ( ( isset( $_POST['mainVeggieMeal'] ) && ( strToUpper( sanitize_text_field( wp_unslash( $_POST['mainVeggieMeal'] ) ) ) == 'Y' ) ) ? 'Y' : 'N' ),
				),
				array( 'id' => absint( $attendee_id ) ),
				array(
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
				),
				array( '%d' )
			);
		}

		rsvp_handleAdditionalQuestions( $attendee_id, 'mainquestion' );

		$sql          = 'SELECT id, firstName FROM ' . ATTENDEES_TABLE .
						' WHERE (id IN (SELECT attendeeID FROM ' . ASSOCIATED_ATTENDEES_TABLE . ' WHERE associatedAttendeeID = %d)
			OR id in (SELECT associatedAttendeeID FROM ' . ASSOCIATED_ATTENDEES_TABLE . ' WHERE attendeeID = %d) OR
    		id IN (SELECT waa1.attendeeID FROM ' . ASSOCIATED_ATTENDEES_TABLE . ' waa1
        			INNER JOIN ' . ASSOCIATED_ATTENDEES_TABLE . ' waa2 ON waa2.attendeeID = waa1.attendeeID  OR
                    waa1.associatedAttendeeID = waa2.attendeeID
      				WHERE waa2.associatedAttendeeID = %d AND waa1.attendeeID <> %d))';
		$associations = $wpdb->get_results( $wpdb->prepare( $sql, absint( $attendee_id ), absint( $attendee_id ), absint( $attendee_id ), absint( $attendee_id ) ) );
		foreach ( $associations as $a ) {
			if ( isset( $_POST[ 'attending' . $a->id ] ) && ( ( sanitize_text_field( wp_unslash( $_POST[ 'attending' . $a->id ] ) ) == 'Y' ) || ( sanitize_text_field( wp_unslash( $_POST[ 'attending' . $a->id ] ) ) == 'N' ) ) ) {
				$thank_you_associated[] = sanitize_text_field( stripslashes_deep( $a->firstName ) );
				if ( $_POST[ 'attending' . $a->id ] == 'Y' ) {
					$rsvp_status = 'Yes';
				} else {
					$rsvp_status = 'No';
				}

				if ( get_option( OPTION_RSVP_HIDE_EMAIL_FIELD ) != 'Y' ) {
					$wpdb->update(
						ATTENDEES_TABLE,
						array(
							'rsvpDate'   => date( 'Y-m-d' ),
							'rsvpStatus' => sanitize_text_field( wp_unslash( $rsvp_status ) ),
							'email'      => sanitize_email( wp_unslash( $_POST[ 'attending' . $a->id . 'Email' ] ) ),
							'kidsMeal'   => ( ( strToUpper( ( isset( $_POST[ 'attending' . $a->id . 'KidsMeal' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'attending' . $a->id . 'KidsMeal' ] ) ) : 'N' ) ) == 'Y' ) ? 'Y' : 'N' ),
							'veggieMeal' => ( ( strToUpper( ( isset( $_POST[ 'attending' . $a->id . 'VeggieMeal' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'attending' . $a->id . 'VeggieMeal' ] ) ) : 'N' ) ) == 'Y' ) ? 'Y' : 'N' ),
						),
						array( 'id' => absint( $a->id ) ),
						array(
							'%s',
							'%s',
							'%s',
							'%s',
							'%s',
						),
						array( '%d' )
					);
				} else {
					$wpdb->update(
						ATTENDEES_TABLE,
						array(
							'rsvpDate'   => date( 'Y-m-d' ),
							'rsvpStatus' => sanitize_text_field( wp_unslash( $rsvp_status ) ),
							'kidsMeal'   => ( ( strToUpper( ( isset( $_POST[ 'attending' . $a->id . 'KidsMeal' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'attending' . $a->id . 'KidsMeal' ] ) ) : 'N' ) == 'Y' ) ? 'Y' : 'N' ) ),
							'veggieMeal' => ( ( strToUpper( ( isset( $_POST[ 'attending' . $a->id . 'VeggieMeal' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'attending' . $a->id . 'VeggieMeal' ] ) ) : 'N' ) ) == 'Y' ) ? 'Y' : 'N' ),
						),
						array( 'id' => absint( $a->id ) ),
						array(
							'%s',
							'%s',
							'%s',
							'%s',
						),
						array( '%d' )
					);
				}

				rsvp_handleAdditionalQuestions( $a->id, $a->id . 'question' );
			}
		}

		if ( get_option( OPTION_HIDE_ADD_ADDITIONAL ) != 'Y' ) {
			if ( is_numeric( $_POST['additionalRsvp'] ) && ( absint( $_POST['additionalRsvp'] ) > 0 ) ) {
				for ( $i = 1; $i <= absint( $_POST['additionalRsvp'] ); $i ++ ) {
					$num_guests = 3;
					if ( get_option( OPTION_RSVP_NUM_ADDITIONAL_GUESTS ) != '' ) {
						$num_guests = get_optioN( OPTION_RSVP_NUM_ADDITIONAL_GUESTS );
						if ( ! is_numeric( $num_guests ) || ( $num_guests < 0 ) ) {
							$num_guests = 3;
						}
					}

					if ( ( $i <= $num_guests ) &&
						 ! empty( $_POST[ 'newAttending' . $i . 'FirstName' ] ) &&
						 ! empty( $_POST[ 'newAttending' . $i . 'LastName' ] ) ) {
						$thank_you_associated[] = sanitize_text_field( wp_unslash( $_POST[ 'newAttending' . $i . 'FirstName' ] ) );
						$wpdb->insert(
							ATTENDEES_TABLE,
							array(
								'firstName'          => sanitize_text_field( wp_unslash( $_POST[ 'newAttending' . $i . 'FirstName' ] ) ),
								'lastName'           => sanitize_text_field( wp_unslash( $_POST[ 'newAttending' . $i . 'LastName' ] ) ),
								'email'              => sanitize_email( wp_unslash( $_POST[ 'newAttending' . $i . 'Email' ] ) ),
								'rsvpDate'           => date( 'Y-m-d' ),
								'rsvpStatus'         => ( ( $_POST[ 'newAttending' . $i ] == 'Y' ) ? 'Yes' : 'No' ),
								'kidsMeal'           => ( isset( $_POST[ 'newAttending' . $i . 'KidsMeal' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'newAttending' . $i . 'KidsMeal' ] ) ) : 'N' ),
								'veggieMeal'         => ( isset( $_POST[ 'newAttending' . $i . 'VeggieMeal' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'newAttending' . $i . 'VeggieMeal' ] ) ) : 'N' ),
								'additionalAttendee' => 'Y',
							),
							array(
								'%s',
								'%s',
								'%s',
								'%s',
								'%s',
								'%s',
								'%s',
							)
						);
						$new_aid = $wpdb->insert_id;
						rsvp_handleAdditionalQuestions( $new_aid, $i . 'question' );
						// Add associations for this new user.
						$wpdb->insert(
							ASSOCIATED_ATTENDEES_TABLE,
							array(
								'attendeeID'           => absint( $new_aid ),
								'associatedAttendeeID' => absint( $attendee_id ),
							),
							array(
								'%d',
								'%d',
							)
						);

						$sql = 'INSERT INTO ' . ASSOCIATED_ATTENDEES_TABLE . '(attendeeID, associatedAttendeeID)
							SELECT ' . $new_aid . ', associatedAttendeeID
							FROM ' . ASSOCIATED_ATTENDEES_TABLE .
							   ' WHERE attendeeID = %d';
						$wpdb->query( $wpdb->prepare( $sql, absint( $attendee_id ) ) );
					}
				}
			}
		}

		rsvp_handle_notifications( $attendee_id );

		return rsvp_handle_output( $text, frontend_rsvp_thankyou( $thank_you_primary, $thank_you_associated ) );
	} else {
		return rsvp_handle_output( $text, rsvp_frontend_greeting() );
	}
}

/**
 * Checks to see if an attendee is coming back and if so we give them a prompt to edit.
 *
 * @param string &$output The output that needs to be displayed.
 * @param string &$text   The original text.
 *
 * @return string          The output that is ready for the user to see.
 */
function rsvp_editAttendee( &$output, &$text ) {
	global $wpdb;

	if ( is_numeric( $_POST['attendeeID'] ) && ( absint( $_POST['attendeeID'] ) > 0 ) ) {
		// Try to find the user.
		$attendee = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT id, firstName, lastName, rsvpStatus
		FROM ' . ATTENDEES_TABLE .
				' WHERE id = %d',
				absint( $_POST['attendeeID'] )
			)
		);
		if ( $attendee != null ) {
			$output .= RSVP_START_CONTAINER;
			$output .= RSVP_START_PARA . sprintf( __( 'Welcome back %s!', 'rsvp' ), esc_html( stripslashes_deep( $attendee->firstName . ' ' . $attendee->lastName ) ) ) . RSVP_END_PARA;
			$output .= rsvp_frontend_main_form( $attendee->id );

			return rsvp_handle_output( $text, $output . RSVP_END_CONTAINER );
		}
	}
}

/**
 * Displays the form if an attendee has been found.
 *
 * @param string &$output The output that needs to be displayed to the user.
 * @param string &$text   The original text.
 *
 * @return string          The HTML ready to be displayed to the user.
 */
function rsvp_foundAttendee( &$output, &$text ) {
	global $wpdb;

	if ( is_numeric( $_POST['attendeeID'] ) && ( absint( $_POST['attendeeID'] ) > 0 ) ) {
		$sql      = 'SELECT id, firstName, lastName, rsvpStatus FROM ' . ATTENDEES_TABLE . ' WHERE id = %d';
		$attendee = $wpdb->get_row( $wpdb->prepare( $sql, absint( $_POST['attendeeID'] ) ) );
		if ( $attendee != null ) {
			$output = RSVP_START_CONTAINER;
			if ( strtolower( $attendee->rsvpStatus ) == 'noresponse' ) {
				$output .= RSVP_START_PARA . sprintf(
					__( 'Hi %1$s %2$s!', 'rsvp' ),
					htmlspecialchars( stripslashes_deep( $attendee->firstName ) ),
					htmlspecialchars( stripslashes_deep( $attendee->lastName ) )
				) . RSVP_END_PARA;

				if ( trim( get_option( OPTION_WELCOME_TEXT ) ) != '' ) {
					$output .= RSVP_START_PARA . trim( get_option( OPTION_WELCOME_TEXT ) ) . RSVP_END_PARA;
				} else {
					$output .= RSVP_START_PARA . __( 'There are a few more questions we need to ask you if you could please fill them out below to finish up the RSVP process.', 'rsvp' ) . RSVP_END_PARA;
				}

				$output .= rsvp_frontend_main_form( $attendee->id );
			} else {
				$output .= rsvp_frontend_prompt_to_edit( $attendee );
			}

			return rsvp_handle_output( $text, $output . RSVP_END_CONTAINER );
		}

		return rsvp_handle_output( $text, rsvp_frontend_greeting() );
	} else {
		return rsvp_handle_output( $text, rsvp_frontend_greeting() );
	}
}

/**
 * Displays the thank you confirmation prompt for existing attendees.
 *
 * @param string $thank_you_primary    The name of the primary attendee.
 * @param array  $thank_you_associated An array of associated attendee names.
 *
 * @return string                       The thank you confirmation page.
 */
function frontend_rsvp_thankyou( $thank_you_primary, $thank_you_associated ) {
	$custom_ty = get_option( OPTION_THANKYOU );
	if ( ! empty( $custom_ty ) ) {
		return nl2br( $custom_ty );
	} else {
		$ty_text = sprintf( __( 'Thank you %1$s for RSVPing.', 'rsvp' ), esc_html( $thank_you_primary ) );

		if ( count( $thank_you_associated ) > 0 ) {
			$ty_text .= __( ' You have also RSVPed for - ', 'rsvp' );
			foreach ( $thank_you_associated as $name ) {
				$ty_text .= htmlspecialchars( ' ' . $name ) . ', ';
			}
			$ty_text = rtrim( trim( $ty_text ), ',' ) . '.';
		}

		return RSVP_START_CONTAINER . RSVP_START_PARA . $ty_text . RSVP_END_PARA . RSVP_END_CONTAINER;
	}
}

/**
 * Displays the thank you confirmation message for new attendees.
 *
 * @param string $thank_you_primary    The name of the primary attendee.
 * @param array  $thank_you_associated An array of associated attendee names.
 * @param string $password             The optional passcode that the new attendee will need to edit their RSVP.
 *
 * @return string                       The HTML that should be displayed to the user.
 */
function rsvp_frontend_new_atendee_thankyou( $thank_you_primary, $thank_you_associated, $password = '' ) {
	$thank_you_text = sprintf( __( 'Thank you %1$s for RSVPing. To modify your RSVP just come back to this page and enter in your first and last name.', 'rsvp' ), esc_html( $thank_you_primary ) );

	if ( ! empty( $password ) ) {
		$thank_you_text .= ' ' . __( 'You will also need to know your passcode which is', 'rsvp' ) .
						   " - <strong>$password</strong>";
	}

	if ( count( $thank_you_associated ) > 0 ) {
		$thank_you_text .= __( '<br /><br />You have also RSVPed for - ', 'rsvp' );
		foreach ( $thank_you_associated as $name ) {
			$thank_you_text .= htmlspecialchars( ' ' . $name ) . ', ';
		}
		$thank_you_text = rtrim( trim( $thank_you_text ), ',' ) . '.';
	}

	return RSVP_START_CONTAINER . RSVP_START_PARA . $thank_you_text . RSVP_END_PARA . RSVP_END_CONTAINER;
}

/**
 * Returns a substring of the string based on the max length passed in.
 *
 * @param string $name       The name we want the first X characters of.
 * @param int    $max_length The max length of the string we want.
 *
 * @return string             The substring of the name.
 */
function rsvp_chomp_name( $name, $max_length ) {
	for ( $i = $max_length; $max_length >= 1; $i -- ) {
		if ( strlen( $name ) >= $i ) {
			return substr( $name, 0, $i );
		}
	}
}

/**
 * Gets the opening tag for a form field container.
 *
 * @param string $id                 An option HTML ID value.
 * @param string $additional_classes Optional CSS classes for this div container.
 *
 * @return string                     The opening div tag for a form field.
 */
function rsvp_BeginningFormField( $id, $additional_classes ) {
	return '<div ' . ( ! empty( $id ) ? "id=\"$id\"" : '' ) . ' class="rsvpFormField ' . ( ! empty( $additional_classes ) ? $additional_classes : '' ) . '">';
}

/**
 * Displays the front-end greeting for the user.
 *
 * @return string The HTML that will be used to greet the user.
 */
function rsvp_frontend_greeting() {
	global $rsvp_form_action;
	$custom_greeting = get_option( OPTION_GREETING );
	if ( rsvp_require_only_passcode_to_register() ) {
		$output = RSVP_START_PARA . __( 'Please enter your passcode to RSVP.', 'rsvp' ) . RSVP_END_PARA;
	} elseif ( rsvp_require_passcode() ) {
		$output = RSVP_START_PARA . __( 'Please enter your first name, last name and passcode to RSVP.', 'rsvp' ) . RSVP_END_PARA;
	} else {
		$output = RSVP_START_PARA . __( 'Please enter your first and last name to RSVP.', 'rsvp' ) . RSVP_END_PARA;
	}

	if ( ! empty( $custom_greeting ) ) {
		$output = RSVP_START_PARA . nl2br( $custom_greeting ) . RSVP_END_PARA;
	}

	$output .= RSVP_START_CONTAINER;

	if ( get_option( OPTION_RSVP_OPEN_REGISTRATION ) == 'Y' ) {
		$output .= "<form name=\"rsvpNew\" method=\"post\" id=\"rsvpNew\" action=\"$rsvp_form_action\">\r\n";
		$output .= '	<input type="hidden" name="rsvpStep" value="newattendee" />';
		$output .= '<input type="submit" value="' . __( 'New Attendee Registration', 'rsvp' ) . '" />' . "\r\n";
		$output .= "</form>\r\n";

		$output .= '<hr />';
		$output .= RSVP_START_PARA . __( 'Need to modify your registration? Start with the below form.', 'rsvp' ) . RSVP_END_PARA;
	}

	$output .= "<form name=\"rsvp\" method=\"post\" id=\"rsvp\" action=\"$rsvp_form_action\" autocomplete=\"off\">\r\n";
	$output .= '	<input type="hidden" name="rsvpStep" value="find" />';
	if ( ! rsvp_require_only_passcode_to_register() ) {
		$output .= RSVP_START_PARA . '<label for="firstName">' . __( 'First Name', 'rsvp' ) . '</label>
		<input type="text" name="firstName" id="firstName" size="30" value="" class="required" />' . RSVP_END_PARA;
		$output .= RSVP_START_PARA . '<label for="lastName">' . __( 'Last Name', 'rsvp' ) . '</label>
		<input type="text" name="lastName" id="lastName" size="30" value="" class="required" />' . RSVP_END_PARA;
	}
	if ( rsvp_require_passcode() ) {
		$output .= RSVP_START_PARA . '<label for="passcode">' . __( 'Passcode', 'rsvp' ) . '</label>
		<input type="password" name="passcode" id="passcode" size="30" value="" class="required" autocomplete="off" />' . RSVP_END_PARA;
	}
	$output .= RSVP_START_PARA . '<input type="submit" value="' . __( 'Complete your RSVP!', 'rsvp' ) . '" />' . RSVP_END_PARA;
	$output .= "</form>\r\n";
	$output .= RSVP_END_CONTAINER;

	return $output;
}

/**
 * Adds in the JavaScript for adding additional attendees.
 *
 * @param int $attendee_id The main attendee ID that we will be adding attendees to.
 *
 * @return string              The JavaScript to add additional attendees.
 */
function rsvp_inject_add_guests_js( $attendee_id ) {
	
	if ( get_option( OPTION_HIDE_ADD_ADDITIONAL ) != 'Y' ) {
		$yes_text   = __( 'Yes', 'rsvp' );
		$no_text    = __( 'No', 'rsvp' );
		$num_guests = 3;
		if ( get_option( OPTION_RSVP_NUM_ADDITIONAL_GUESTS ) != '' ) {
			$num_guests = get_option( OPTION_RSVP_NUM_ADDITIONAL_GUESTS );
			if ( ! is_numeric( $num_guests ) || ( $num_guests < 0 ) ) {
				$num_guests = 3;
			}
		}
		echo '<script type="text/javascript" language="javascript">';
		echo 'function handleAddRsvpClick(){';
		echo 'var numAdditional = jQuery("#additionalRsvp").val();numAdditional++;';
		echo 'if( numAdditional > ' . absint( $num_guests ) . ' ){ alert("' . sprintf( esc_html__( 'You have already added %1$d additional rsvp\\\'s you can add no more.', 'rsvp' ), absint( $num_guests ) ) . '") }';
		echo 'else {';
		echo 'jQuery("#additionalRsvpContainer").append("<div class=\"rsvpAdditionalAttendee\"><div class=\"rsvpAdditionalAttendeeQuestions\"><label for=\"newAttending" + numAdditional + "FirstName\">' . esc_html__( 'Person\'s first name', 'rsvp') . '&nbsp;</label>';
		echo '<input type=\"text\" name=\"newAttending" + numAdditional + "FirstName\" id=\"newAttending" + numAdditional + "FirstName\" /></div>';
		echo '<div class=\"rsvpFormField\"><label for=\"newAttending" + numAdditional + "LastName\">' . esc_html__( "Person's last name", 'rsvp' ) . '</label><input type=\"text\" name=\"newAttending" + numAdditional + "LastName\" id=\"newAttending" + numAdditional + "LastName\" /></div>';

		if ( 'Y' !== get_option( OPTION_RSVP_HIDE_EMAIL_FIELD ) ) {
			echo '<div class=\"rsvpFormField\"><label for=\"newAttending" + numAdditional + "Email\">' . esc_html__( "Person's email address", 'rsvp' ) . '</label><input type=\"text\" name=\"newAttending" + numAdditional + "Email\" id=\"newAttending" + numAdditional + "Email\" /></div>';
		}

		echo '<div class=\"rsvpFormField\"><p>' . esc_html__( 'Will this person be attending?', 'rsvp' ) . '</p><input type=\"radio\" name=\"newAttending" + numAdditional + "\" value=\"Y\" id=\"newAttending" + numAdditional + "Y\" checked=\"checked\" /><label for=\"newAttending" + numAdditional + "Y\">'.esc_html( $yes_text) .'</label><input type=\"radio\" name=\"newAttending" + numAdditional + "\" value=\"N\" id=\"newAttending" + numAdditional + "N\"> <label for=\"newAttending" + numAdditional + "N\">' . esc_html( $no_text ) . '</label></div>';

		if ( 'Y' !== get_option( OPTION_HIDE_KIDS_MEAL ) ) {
			echo  '<div class=\"rsvpFormField\"><p>' . esc_html__( 'Does this person need a kids meal?', 'rsvp' ) . '</p><input type=\"radio\" name=\"newAttending" + numAdditional + "KidsMeal\" value=\"Y\" id=\"newAttending" + numAdditional + "KidsMealY\" /><label for=\"newAttending" + numAdditional + "KidsMealY\">' . esc_html( $yes_text ) . '</label><input type=\"radio\" name=\"newAttending" + numAdditional + "KidsMeal\" value=\"N\" id=\"newAttending" + numAdditional + "KidsMealN\" checked=\"checked\" /><label for=\"newAttending" + numAdditional + "KidsMealN\">' . esc_html( $no_text ) . '</label></div>';
		}

		if ( 'Y' !== get_option( OPTION_HIDE_VEGGIE ) ) {
			echo '<div class=\"rsvpFormField\"><p>' . esc_html__( 'Does this person need a vegetarian meal?', 'rsvp' ) . '</p><input type=\"radio\" name=\"newAttending" + numAdditional + "VeggieMeal\" value=\"Y\" id=\"newAttending" + numAdditional + "VeggieMealY\" /><label for=\"newAttending" + numAdditional + "VeggieMealY\">' . esc_html( $yes_text ) . '</label><input type=\"radio\" name=\"newAttending" + numAdditional + "VeggieMeal\" value=\"N\" id=\"newAttending" + numAdditional + "VeggieMealN\" checked=\"checked\" /><label for=\"newAttending" + numAdditional + "VeggieMealN\">' . esc_html( $no_text ) . '</label></div>';
		}

		// output sanitized in rsvp_buildAdditionalQuestions()
		echo str_replace( "\r\n", '', str_replace( '||', '\"', addSlashes( rsvp_buildAdditionalQuestions( $attendee_id, '|| + numAdditional + ||' ) ) ) );
		echo '<p><button onclick=\"removeAdditionalRSVP(event, this);\">' . esc_html__( 'Remove Guest', 'rsvp' ) . '</button></p></div>");';
		echo 'jQuery("#additionalRsvp").val(numAdditional); } }';
		echo 'function removeAdditionalRSVP(e, rsvp) {e.preventDefault(); var numAdditional = jQuery("#additionalRsvp").val();numAdditional--;jQuery(rsvp).parent().parent().remove();jQuery("#additionalRsvp").val(numAdditional); }</script>';

	}
}
