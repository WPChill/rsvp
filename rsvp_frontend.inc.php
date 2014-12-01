<?php 
$rsvp_form_action = htmlspecialchars(rsvp_getCurrentPageURL());

if(get_option(OPTION_RSVP_DONT_USE_HASH) != "Y") {
  $rsvp_form_action .= "#rsvpArea";
}
$rsvp_saved_form_vars = array();
// load some defaults
$rsvp_saved_form_vars['mainRsvp'] = "";
$rsvp_saved_form_vars['rsvp_note'] = "";

function rsvp_handle_output ($intialText, $rsvpText) {	
  $rsvpText = "<a name=\"rsvpArea\" id=\"rsvpArea\"></a>".$rsvpText;
  remove_filter("the_content", "wpautop");
	return str_replace(RSVP_FRONTEND_TEXT_CHECK, $rsvpText, $intialText);
}

function rsvp_frontend_handler($text) {
	global $wpdb; 
	$passcodeOptionEnabled = (rsvp_require_passcode()) ? true : false;
	//QUIT if the replacement string doesn't exist
	if (!strstr($text,RSVP_FRONTEND_TEXT_CHECK)) return $text;
	
	// See if we should allow people to RSVP, etc...
	$openDate = get_option(OPTION_OPENDATE);
	$closeDate = get_option(OPTION_DEADLINE);
	if((strtotime($openDate) !== false) && (strtotime($openDate) > time())) {
		return rsvp_handle_output($text, sprintf(__(RSVP_START_PARA."I am sorry but the ability to RSVP for our wedding won't open till <strong>%s</strong>".RSVP_END_PARA, 'rsvp-plugin'), date("m/d/Y", strtotime($openDate))));
	} 
	
	if((strtotime($closeDate) !== false) && (strtotime($closeDate) < time())) {
		return rsvp_handle_output($text, __(RSVP_START_PARA."The deadline to RSVP for this wedding has passed, please contact the bride and groom to see if there is still a seat for you.".RSVP_END_PARA, 'rsvp-plugin'));
	}
	
	if(isset($_POST['rsvpStep'])) {
		$output = "";
		switch(strtolower($_POST['rsvpStep'])) {
      case("newattendee"):
        return rsvp_handlenewattendee($output, $text);
        break;
      case("addattendee"):
        return rsvp_handleNewRsvp($output, $text);
        break;
			case("handlersvp") :
				$output = rsvp_handlersvp($output, $text);
				if(!empty($output)) 
					return $output;
				break;
			case("editattendee") :
				$output = rsvp_editAttendee($output, $text);
				if(!empty($output)) 
					return $output;
				break;
			case("foundattendee") :
				$output = rsvp_foundAttendee($output, $text);
				if(!empty($output)) 
					return $output;
				break;
			case("find") :
				$output = rsvp_find($output, $text);
				if(!empty($output))
					return $output;
				break;
			case("newsearch"):
			default:
				return rsvp_handle_output($text, rsvp_frontend_greeting());
				break;
		}
	} else {
    if(isset($_REQUEST['firstName']) && isset($_REQUEST['lastName'])) {
      $output = "";
      return rsvp_find($output, $text);
    } else {
      return rsvp_handle_output($text, rsvp_frontend_greeting());
    }
	}
}

function rsvp_handlenewattendee($output, $text) {
  $output = RSVP_START_CONTAINER;
  $output .= rsvp_frontend_main_form(0, "addAttendee");
  $output .= RSVP_END_CONTAINER;
  
  return rsvp_handle_output($text, $output);
}

function rsvp_handleAdditionalQuestions($attendeeID, $formName) {
	global $wpdb;
	
	$wpdb->query($wpdb->prepare("DELETE FROM ".ATTENDEE_ANSWERS." WHERE attendeeID = %d ", $attendeeID));
	
	$qRs = $wpdb->get_results("SELECT q.id, questionType FROM ".QUESTIONS_TABLE." q 
					INNER JOIN ".QUESTION_TYPE_TABLE." qt ON qt.id = q.questionTypeID 
					ORDER BY q.sortOrder");
	if(count($qRs) > 0) {
		foreach($qRs as $q) {
			if(isset($_POST[$formName.$q->id]) && !empty($_POST[$formName.$q->id])) {
				if($q->questionType == QT_MULTI) {
					$selectedAnswers = "";
					$aRs = $wpdb->get_results($wpdb->prepare("SELECT id, answer FROM ".QUESTION_ANSWERS_TABLE." WHERE questionID = %d", $q->id));
					if(count($aRs) > 0) {
						foreach($aRs as $a) {
							if(in_array($a->id, $_POST[$formName.$q->id])) {
								$selectedAnswers .= ((strlen($selectedAnswers) == "0") ? "" : "||").stripslashes($a->answer);
							}
						}
					}
					
					if(!empty($selectedAnswers)) {
						$wpdb->insert(ATTENDEE_ANSWERS, array("attendeeID" => $attendeeID, 
																									 "answer" => stripslashes($selectedAnswers), 
																									 "questionID" => $q->id), 
																						 array('%d', '%s', '%d'));
						rsvp_printQueryDebugInfo();
					}
				} else if (($q->questionType == QT_DROP) || ($q->questionType == QT_RADIO)) {
					$aRs = $wpdb->get_results($wpdb->prepare("SELECT id, answer FROM ".QUESTION_ANSWERS_TABLE." WHERE questionID = %d", $q->id));
					if(count($aRs) > 0) {
						foreach($aRs as $a) {
							if($a->id == $_POST[$formName.$q->id]) {
								$wpdb->insert(ATTENDEE_ANSWERS, array("attendeeID" => $attendeeID, 
																											 "answer" => stripslashes($a->answer), 
																											 "questionID" => $q->id), 
																								 array('%d', '%s', '%d'));
								rsvp_printQueryDebugInfo();
								break;
							}
						}
					}
				} else {
					$wpdb->insert(ATTENDEE_ANSWERS, array("attendeeID" => $attendeeID, 
																								 "answer" => $_POST[$formName.$q->id], 
																								 "questionID" => $q->id), 
																					 array('%d', '%s', '%d'));
					rsvp_printQueryDebugInfo();
				}
			}
		}
	}
}

function rsvp_frontend_prompt_to_edit($attendee) {
  global $rsvp_form_action;
  $prompt = RSVP_START_CONTAINER; 
  $editGreeting = __("Hi %s it looks like you have already RSVP'd. Would you like to edit your reservation?", 'rsvp-plugin');
	$prompt .= sprintf(RSVP_START_PARA.$editGreeting.RSVP_END_PARA, 
                     htmlspecialchars(stripslashes($attendee->firstName." ".$attendee->lastName)));
	$prompt .= "<form method=\"post\" action=\"$rsvp_form_action\">\r\n
								<input type=\"hidden\" name=\"attendeeID\" value=\"".$attendee->id."\" />
								<input type=\"hidden\" name=\"rsvpStep\" id=\"rsvpStep\" value=\"editattendee\" />
								<input type=\"submit\" value=\"".__("Yes", 'rsvp-plugin')."\" onclick=\"document.getElementById('rsvpStep').value='editattendee';\" />
								<input type=\"submit\" value=\"".__("No", 'rsvp-plugin')."\" onclick=\"document.getElementById('rsvpStep').value='newsearch';\"  />
							</form>\r\n";
  $prompt .= RSVP_END_CONTAINER;
	return $prompt;
}

function rsvp_frontend_main_form($attendeeID, $rsvpStep = "handleRsvp") {
	global $wpdb, $rsvp_form_action, $rsvp_saved_form_vars;
	$attendee = $wpdb->get_row($wpdb->prepare("SELECT id, firstName, lastName, email, rsvpStatus, note, kidsMeal, additionalAttendee, veggieMeal, personalGreeting   
																						 FROM ".ATTENDEES_TABLE." 
																						 WHERE id = %d", $attendeeID));
	$sql = "SELECT id FROM ".ATTENDEES_TABLE." 
	 	WHERE (id IN (SELECT attendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE associatedAttendeeID = %d) 
			OR id in (SELECT associatedAttendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE attendeeID = %d)) 
			 AND additionalAttendee = 'Y'";
	$newRsvps = $wpdb->get_results($wpdb->prepare($sql, $attendeeID, $attendeeID));
  $yesText = __("Yes", 'rsvp-plugin');
  $noText  = __("No", 'rsvp-plugin'); 
	$yesVerbiage = ((trim(get_option(OPTION_YES_VERBIAGE)) != "") ? get_option(OPTION_YES_VERBIAGE) : 
		__("Yes, of course I will be there! Who doesn't like family, friends, weddings, and a good time?", 'rsvp-plugin'));
	$noVerbiage = ((trim(get_option(OPTION_NO_VERBIAGE)) != "") ? get_option(OPTION_NO_VERBIAGE) : 
			__("Um, unfortunately, there is a Star Trek marathon on that day that I just cannot miss.", 'rsvp-plugin'));
	$kidsVerbiage = ((trim(get_option(OPTION_KIDS_MEAL_VERBIAGE)) != "") ? get_option(OPTION_KIDS_MEAL_VERBIAGE) : 
					__("We have the option of getting cheese pizza for the kids (and only kids).  Do you want pizza instead of \"adult food?\"", 'rsvp-plugin'));
	$veggieVerbiage = ((trim(get_option(OPTION_VEGGIE_MEAL_VERBIAGE)) != "") ? get_option(OPTION_VEGGIE_MEAL_VERBIAGE) : 
					__("We also have the option of getting individual vegetarian meals instead of the fish or meat.  Would you like a vegetarian dinner?", 'rsvp-plugin'));
	$noteVerbiage = ((trim(get_option(OPTION_NOTE_VERBIAGE)) != "") ? get_option(OPTION_NOTE_VERBIAGE) : 
		__("If you have any <strong style=\"color:red;\">food allergies</strong>, please indicate what they are in the &quot;notes&quot; section below.  Or, if you just want to send us a note, please feel free.  If you have any questions, please send us an email.", 'rsvp-plugin'));
    
	$form = "<form id=\"rsvpForm\" name=\"rsvpForm\" method=\"post\" action=\"$rsvp_form_action\" autocomplete=\"off\">";
	$form .= "	<input type=\"hidden\" name=\"attendeeID\" value=\"".$attendeeID."\" />";
	$form .= "	<input type=\"hidden\" name=\"rsvpStep\" value=\"$rsvpStep\" />";
	
  // New Attendee fields when open registration is allowed 
  if($attendeeID <= 0) {
    $form .= RSVP_START_PARA;
    $form .= rsvp_BeginningFormField("", "").
      "<label for=\"attendeeFirstName\">".__("First Name: ", 'rsvp-plugin')."</label>".
      "<input type=\"text\" name=\"attendeeFirstName\" id=\"attendeeFirstName\" value=\"".htmlspecialchars($rsvp_saved_form_vars['attendeeFirstName'])."\" />".
      RSVP_END_FORM_FIELD;
    $form .= RSVP_END_PARA;
    
    $form .= RSVP_START_PARA;
    $form .= rsvp_BeginningFormField("", "").
      "<label for=\"attendeeLastName\">".__("Last Name: ", 'rsvp-plugin')."</label>".
      "<input type=\"text\" name=\"attendeeLastName\" id=\"attendeeLastName\" value=\"".htmlspecialchars($rsvp_saved_form_vars['attendeeLastName'])."\" />".
      RSVP_END_FORM_FIELD;
    $form .= RSVP_END_PARA;
  }
  
	$form .= RSVP_START_PARA;
								if(trim(get_option(OPTION_RSVP_QUESTION)) != "") {
									$form .= trim(get_option(OPTION_RSVP_QUESTION));
								} else {
									$form .= __("So, how about it?", 'rsvp-plugin');
								}
  
	$form .= RSVP_END_PARA.
    rsvp_BeginningFormField("", "").
    "<input type=\"radio\" name=\"mainRsvp\" value=\"Y\" id=\"mainRsvpY\" ".((($attendee->rsvpStatus == "No") || ($rsvp_saved_form_vars['mainRsvp'] == "N")) ? "" : "checked=\"checked\"")." /> <label for=\"mainRsvpY\">".$yesVerbiage."</label>".
    RSVP_END_FORM_FIELD.
    rsvp_BeginningFormField("", "").
      "<input type=\"radio\" name=\"mainRsvp\" value=\"N\" id=\"mainRsvpN\" ".((($attendee->rsvpStatus == "No") || ($rsvp_saved_form_vars['mainRsvp'] == "N")) ? "checked=\"checked\"" : "")." /> ".
      "<label for=\"mainRsvpN\">".$noVerbiage."</label>".
    RSVP_END_FORM_FIELD;
	if(!empty($attendee->personalGreeting)) {
		$form .= rsvp_BeginningFormField("rsvpCustomGreeting", "").nl2br(stripslashes($attendee->personalGreeting)).RSVP_END_FORM_FIELD;
	}
	if(get_option(OPTION_HIDE_KIDS_MEAL) != "Y") {		
		$form .= rsvp_BeginningFormField("", "rsvpBorderTop").
      RSVP_START_PARA.$kidsVerbiage.RSVP_END_PARA.
      "<input type=\"radio\" name=\"mainKidsMeal\" value=\"Y\" id=\"mainKidsMealY\" ".
      ((($attendee->kidsMeal == "Y") || ($rsvp_saved_form_vars['mainKidsMeal'] == "Y")) ? "checked=\"checked\"" : "")." /> <label for=\"mainKidsMealY\">$yesText</label> ".
				"<input type=\"radio\" name=\"mainKidsMeal\" value=\"N\" id=\"mainKidsMealN\" ".((($attendee->kidsMeal == "Y") || ($rsvp_saved_form_vars['mainKidsMeal'] == "Y")) ? "" : "checked=\"checked\"")." /> <label for=\"mainKidsMealN\">$noText</label>".
      RSVP_END_FORM_FIELD;
	}
	
	if(get_option(OPTION_HIDE_VEGGIE) != "Y") {		
		$form .= rsvp_BeginningFormField("", "rsvpBorderTop").
      RSVP_START_PARA.$veggieVerbiage.RSVP_END_PARA.
      "<input type=\"radio\" name=\"mainVeggieMeal\" value=\"Y\" id=\"mainVeggieMealY\" ".
        ((($attendee->veggieMeal == "Y") || ($rsvp_saved_form_vars['mainVeggieMeal'] == "Y")) ? "checked=\"checked\"" : "")."/> <label for=\"mainVeggieMealY\">$yesText</label> ".
      "<input type=\"radio\" name=\"mainVeggieMeal\" value=\"N\" id=\"mainVeggieMealN\" ".
        ((($attendee->veggieMeal == "Y") || ($rsvp_saved_form_vars['mainVeggieMeal'] == "Y")) ? "" : "checked=\"checked\"")." /> <label for=\"mainVeggieMealN\">$noText</label>".
      RSVP_END_FORM_FIELD;
	}
  
  if(get_option(OPTION_RSVP_HIDE_EMAIL_FIELD) != "Y") {
    $form .= rsvp_BeginningFormField("", "rsvpBorderTop").
      RSVP_START_PARA."<label for=\"mainEmail\">".__("Email Address", 'rsvp-plugin')."</label>".RSVP_END_PARA.
        "<input type=\"text\" name=\"mainEmail\" id=\"mainEmail\" value=\"".htmlspecialchars($attendee->email)."\" />".
      RSVP_END_FORM_FIELD;
  }
	
	$form .= rsvp_buildAdditionalQuestions($attendeeID, "main");
	
	if(get_option(RSVP_OPTION_HIDE_NOTE) != "Y") {
  	$form .= RSVP_START_PARA.$noteVerbiage.RSVP_END_PARA.
      rsvp_BeginningFormField("", "").
        "<textarea name=\"rsvp_note\" id=\"rsvp_note\" rows=\"7\" cols=\"50\">".((!empty($attendee->note)) ? $attendee->note : $rsvp_saved_form_vars['rsvp_note'])."</textarea>".RSVP_END_FORM_FIELD;
	
  }
	
  /*$sql = "SELECT id, firstName, lastName FROM ".ATTENDEES_TABLE." 
	 	WHERE (id IN (SELECT attendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE associatedAttendeeID = %d) 
			OR id in (SELECT associatedAttendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE attendeeID = %d) OR 
      id IN (SELECT waa1.attendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." waa1 
           INNER JOIN ".ASSOCIATED_ATTENDEES_TABLE." waa2 ON waa2.attendeeID = waa1.attendeeID  OR 
                                                     waa1.associatedAttendeeID = waa2.attendeeID 
           WHERE waa2.associatedAttendeeID = %d AND waa1.attendeeID <> %d)) 
			 AND rsvpStatus <> 'NoResponse'";
	$rsvpd = $wpdb->get_results($wpdb->prepare($sql, $attendeeID, $attendeeID, $attendeeID, $attendeeID));
	if(count($rsvpd) > 0) {
    $form .= "<div class=\"rsvpAdditionalAttendee\">\r\n";
		$form .= RSVP_START_PARA.__("The following people associated with you have already registered:", 'rsvp-plugin')." ";
		foreach($rsvpd as $r) {
			$form .= "<br />".htmlspecialchars($r->firstName." ".$r->lastName);
		}
		$form .= RSVP_END_PARA;
    $form .= "</div>";
	}*/
	
	$sql = "SELECT id, firstName, lastName, email, personalGreeting FROM ".ATTENDEES_TABLE." 
	 	WHERE (id IN (SELECT attendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE associatedAttendeeID = %d) 
			OR id in (SELECT associatedAttendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE attendeeID = %d) OR 
      id IN (SELECT waa1.attendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." waa1 
           INNER JOIN ".ASSOCIATED_ATTENDEES_TABLE." waa2 ON waa2.attendeeID = waa1.attendeeID  OR 
                                                     waa1.associatedAttendeeID = waa2.attendeeID 
           WHERE waa2.associatedAttendeeID = %d AND waa1.attendeeID <> %d))";
	
	$associations = $wpdb->get_results($wpdb->prepare($sql, $attendeeID, $attendeeID, $attendeeID, $attendeeID));
	if(count($associations) > 0) {
		$form .= "<h3>".__("The following people are associated with you.  At this time you can RSVP for them as well.", 'rsvp-plugin')."</h3>";
		foreach($associations as $a) {
      if($a->id != $attendeeID) {
  			$form .= "<div class=\"rsvpAdditionalAttendee\">\r\n";
        $form .= "<div class=\"rsvpAdditionalAttendeeQuestions\">\r\n";
  			$form .= rsvp_BeginningFormField("", "").RSVP_START_PARA.sprintf(__(" Will %s be attending?", 'rsvp-plugin'), htmlspecialchars($a->firstName." ".$a->lastName)).RSVP_END_PARA.
                "<input type=\"radio\" name=\"attending".$a->id."\" value=\"Y\" id=\"attending".$a->id."Y\" /> ".
                "<label for=\"attending".$a->id."Y\">$yesText</label> 
  							<input type=\"radio\" name=\"attending".$a->id."\" value=\"N\" id=\"attending".$a->id."N\" /> ".
                "<label for=\"attending".$a->id."N\">$noText</label>".
                RSVP_END_FORM_FIELD;
			
  			if(!empty($a->personalGreeting)) {
  				$form .= RSVP_START_PARA.nl2br($a->personalGreeting).RSVP_END_PARA;
  			}
			
  			if(get_option(OPTION_HIDE_KIDS_MEAL) != "Y") {		
  				$form .= rsvp_BeginningFormField("", "").
            RSVP_START_PARA.sprintf(__("Does %s need a kids meal?", 'rsvp-plugin'), htmlspecialchars($a->firstName)).
              RSVP_END_PARA."&nbsp; ".
            "<input type=\"radio\" name=\"attending".$a->id."KidsMeal\" value=\"Y\" id=\"attending".$a->id."KidsMealY\" /> ".
  					"<label for=\"attending".$a->id."KidsMealY\">$yesText</label> 
  					<input type=\"radio\" name=\"attending".$a->id."KidsMeal\" value=\"N\" id=\"attending".$a->id."KidsMealN\" checked=\"checked\" /> ".
  					"<label for=\"attending".$a->id."KidsMealN\">$noText</label>".RSVP_END_FORM_FIELD;
  			}
			
  			if(get_option(OPTION_HIDE_VEGGIE) != "Y") {		
  				$form .= rsvp_BeginningFormField("", "").
                  RSVP_START_PARA.sprintf(__("Does %s need a vegetarian meal?", 'rsvp-plugin'), htmlspecialchars($a->firstName)).
                    RSVP_END_PARA."&nbsp; ".
      						"<input type=\"radio\" name=\"attending".$a->id."VeggieMeal\" value=\"Y\" id=\"attending".$a->id."VeggieMealY\" /> ".
      						"<label for=\"attending".$a->id."VeggieMealY\">$yesText</label> 
      						<input type=\"radio\" name=\"attending".$a->id."VeggieMeal\" value=\"N\" id=\"attending".$a->id."VeggieMealN\" checked=\"checked\" /> ".
      						"<label for=\"attending".$a->id."VeggieMealN\">$noText</label>".RSVP_END_FORM_FIELD;
  			}
			
        if(get_option(OPTION_RSVP_HIDE_EMAIL_FIELD) != "Y") {
          $form .= rsvp_BeginningFormField("", "rsvpBorderTop").
            RSVP_START_PARA."<label for=\"attending".$a->id."Email\">".__("Email Address", 'rsvp-plugin')."</label>".RSVP_END_PARA.
              "<input type=\"text\" name=\"attending".$a->id."Email\" id=\"attending".$a->id."Email\" value=\"".htmlspecialchars($a->email)."\" />".
            RSVP_END_FORM_FIELD;
        }
      
  			$form .= rsvp_buildAdditionalQuestions($a->id, $a->id);
        $form .= "</div>\r\n"; //-- rsvpAdditionalAttendeeQuestions
  			$form .= "</div>\r\n";
		  } // if($a->id != ...)
    } // foreach($associations...)
	}
	
	if(get_option(OPTION_HIDE_ADD_ADDITIONAL) != "Y") {
    $text = __("Did we slip up and forget to invite someone? If so, please add him or her here:", 'rsvp-plugin');
    
    if(trim(get_option(OPTION_RSVP_ADD_ADDITIONAL_VERBIAGE)) != "") {
      $text = get_option(OPTION_RSVP_ADD_ADDITIONAL_VERBIAGE);
    }
    
		$form .= "<h3>$text</h3>\r\n";
		$form .= "<div id=\"additionalRsvpContainer\">\r\n
								<input type=\"hidden\" name=\"additionalRsvp\" id=\"additionalRsvp\" value=\"".count($newRsvps)."\" />
								<div style=\"text-align:right\"><img ".
									"src=\"".get_option("siteurl")."/wp-content/plugins/rsvp/plus.png\" width=\"24\" height=\"24\" border=\"0\" id=\"addRsvp\" /></div>".
							"</div>";
	}
						
	$form .= RSVP_START_PARA."<input type=\"submit\" value=\"RSVP\" />".RSVP_END_PARA;
	if(get_option(OPTION_HIDE_ADD_ADDITIONAL) != "Y") {
    // TODO: Need to move this into the main JS file but not sure how to do that with the options and the custom questions.
    //       - Moving the options would be fairly easy. Just set two JS variables in here and then go off of that. 
    //       - No clue on custom questions....
    $numGuests = 3;
    if(get_option(OPTION_RSVP_NUM_ADDITIONAL_GUESTS) != "") {
      $numGuests = get_option(OPTION_RSVP_NUM_ADDITIONAL_GUESTS);
      if(!is_numeric($numGuests) || ($numGuests < 0)) {
        $numGuests = 3;
      }
    }
		$form .= "<script type=\"text/javascript\" language=\"javascript\">\r\n							
								function handleAddRsvpClick() {
									var numAdditional = jQuery(\"#additionalRsvp\").val();
									numAdditional++;
									if(numAdditional > ".$numGuests.") {
										alert('".__("You have already added ".$numGuests." additional rsvp\'s you can add no more.", 'rsvp-plugin')."');
									} else {
										jQuery(\"#additionalRsvpContainer\").append(\"<div class=\\\"rsvpAdditionalAttendee\\\">\" + \r\n
                        \"<div class=\\\"rsvpAdditionalAttendeeQuestions\\\">\" + \r\n
												\"<div class=\\\"rsvpFormField\\\">\" + \r\n
                        \"	<label for=\\\"newAttending\" + numAdditional + \"FirstName\\\">".__("Person's first name", 'rsvp-plugin')."&nbsp;</label>\" + \r\n 
													\"  <input type=\\\"text\\\" name=\\\"newAttending\" + numAdditional + \"FirstName\\\" id=\\\"newAttending\" + numAdditional + \"FirstName\\\" />\" + \r\n
										  	\"</div>\" + \r\n
												\"<div class=\\\"rsvpFormField\\\">\" + \r\n
                        \"	<label for=\\\"newAttending\" + numAdditional + \"LastName\\\">".__("Person's last name", 'rsvp-plugin')."</label>\" + \r\n 
													\"  <input type=\\\"text\\\" name=\\\"newAttending\" + numAdditional + \"LastName\\\" id=\\\"newAttending\" + numAdditional + \"LastName\\\" />\" + \r\n
                        \"</div>\" + \r\n";
                        if(get_option(OPTION_RSVP_HIDE_EMAIL_FIELD) != "Y") {
  												$form .= "\"<div class=\\\"rsvpFormField\\\">\" + \r\n
                          \"	<label for=\\\"newAttending\" + numAdditional + \"Email\\\">".__("Person's email address", 'rsvp-plugin')."</label>\" + \r\n 
  													\"  <input type=\\\"text\\\" name=\\\"newAttending\" + numAdditional + \"Email\\\" id=\\\"newAttending\" + numAdditional + \"Email\\\" />\" + \r\n
                          \"</div>\" + \r\n";
                        }
                        
										  	$form .= "\"<div class=\\\"rsvpFormField\\\">\" + \r\n
														\"<p>Will this person be attending?</p>\" + \r\n
														\"<input type=\\\"radio\\\" name=\\\"newAttending\" + numAdditional + \"\\\" value=\\\"Y\\\" id=\\\"newAttending\" + numAdditional + \"Y\\\" checked=\\\"checked\\\" /> \" + 
														\"<label for=\\\"newAttending\" + numAdditional + \"Y\\\">$yesText</label> \" + 
														\"<input type=\\\"radio\\\" name=\\\"newAttending\" + numAdditional + \"\\\" value=\\\"N\\\" id=\\\"newAttending\" + numAdditional + \"N\\\"> <label for=\\\"newAttending\" + numAdditional + \"N\\\">$noText</label>\" + 
													\"</div>\" + \r\n";
												if(get_option(OPTION_HIDE_KIDS_MEAL) != "Y") {		
													$form .= "\"<div class=\\\"rsvpFormField\\\">\" + 
                          \"<p>".__("Does this person need a kids meal?", 'rsvp-plugin')."</p>\" + 
														\"<input type=\\\"radio\\\" name=\\\"newAttending\" + numAdditional + \"KidsMeal\\\" value=\\\"Y\\\" id=\\\"newAttending\" + numAdditional + \"KidsMealY\\\" /> \" + 
														\"<label for=\\\"newAttending\" + numAdditional + \"KidsMealY\\\">$yesText</label> \" + 
														\"<input type=\\\"radio\\\" name=\\\"newAttending\" + numAdditional + \"KidsMeal\\\" value=\\\"N\\\" id=\\\"newAttending\" + numAdditional + \"KidsMealN\\\" checked=\\\"checked\\\" /> \" + 
														\"<label for=\\\"newAttending\" + numAdditional + \"KidsMealN\\\">$noText</label>\" + 
                          \"</div>\" + 
													\"</div>\" + \r\n";
												}
												if(get_option(OPTION_HIDE_VEGGIE) != "Y") {		
													$form .= "\"<div class=\\\"rsvpFormField\\\">\" + \r\n
                          \"<p>".__("Does this person need a vegetarian meal?", 'rsvp-plugin')."</p>\" + 
														\"<input type=\\\"radio\\\" name=\\\"newAttending\" + numAdditional + \"VeggieMeal\\\" value=\\\"Y\\\" id=\\\"newAttending\" + numAdditional + \"VeggieMealY\\\" /> \" + 
														\"<label for=\\\"newAttending\" + numAdditional + \"VeggieMealY\\\">$yesText</label> \" + 
														\"<input type=\\\"radio\\\" name=\\\"newAttending\" + numAdditional + \"VeggieMeal\\\" value=\\\"N\\\" id=\\\"newAttending\" + numAdditional + \"VeggieMealN\\\" checked=\\\"checked\\\" /> \" + 
														\"<label for=\\\"newAttending\" + numAdditional + \"VeggieMealN\\\">$noText</label>\" + 
													\"</div>\" + ";
												}
												$tmpVar = str_replace("\r\n", "", str_replace("|", "\"", addSlashes(rsvp_buildAdditionalQuestions($attendeeID, "| + numAdditional + |"))));
												
												$form .= "\"".$tmpVar."\" + 
                          \"<p><button onclick=\\\"removeAdditionalRSVP(this);\\\">Remove Guest</button></p>\" + 
											\"</div>\");
										jQuery(\"#additionalRsvp\").val(numAdditional);
									}
								}
                
                function removeAdditionalRSVP(rsvp) {
									var numAdditional = jQuery(\"#additionalRsvp\").val();
									numAdditional--;
                  jQuery(rsvp).parent().parent().remove();
                  jQuery(\"#additionalRsvp\").val(numAdditional);
                }
							</script>\r\n";
	}
	$form .= "</form>\r\n";
	
	return $form;
}

function rsvp_revtrievePreviousAnswer($attendeeID, $questionID) {
	global $wpdb;
	$answers = "";
	if(($attendeeID > 0) && ($questionID > 0)) {
		$rs = $wpdb->get_results($wpdb->prepare("SELECT answer FROM ".ATTENDEE_ANSWERS." WHERE questionID = %d AND attendeeID = %d", $questionID, $attendeeID));
		if(count($rs) > 0) {
			$answers = stripslashes($rs[0]->answer);
		}
	}
	
	return $answers;
}

function rsvp_buildAdditionalQuestions($attendeeID, $prefix) {
	global $wpdb, $rsvp_saved_form_vars;
	$output = "<div class=\"rsvpCustomQuestions\">";
	
	$sql = "SELECT q.id, q.question, questionType FROM ".QUESTIONS_TABLE." q 
					INNER JOIN ".QUESTION_TYPE_TABLE." qt ON qt.id = q.questionTypeID 
					WHERE q.permissionLevel = 'public' 
					  OR (q.permissionLevel = 'private' AND q.id IN (SELECT questionID FROM ".QUESTION_ATTENDEES_TABLE." WHERE attendeeID = $attendeeID))
					ORDER BY q.sortOrder ";
  $questions = $wpdb->get_results($sql);
	if(count($questions) > 0) {
		foreach($questions as $q) {
			$oldAnswer = rsvp_revtrievePreviousAnswer($attendeeID, $q->id);
			
			$output .= rsvp_BeginningFormField("", "").RSVP_START_PARA.stripslashes($q->question).RSVP_END_PARA;
				
				if($q->questionType == QT_MULTI) {
					$oldAnswers = explode("||", $oldAnswer);
					
					$answers = $wpdb->get_results($wpdb->prepare("SELECT id, answer FROM ".QUESTION_ANSWERS_TABLE." WHERE questionID = %d", $q->id));
					if(count($answers) > 0) {
						$i = 0;
						foreach($answers as $a) {
							$output .= rsvp_BeginningFormField("", "rsvpCheckboxCustomQ")."<input type=\"checkbox\" name=\"".$prefix."question".$q->id."[]\" id=\"".$prefix."question".$q->id.$a->id."\" value=\"".$a->id."\" "
							  .((in_array(stripslashes($a->answer), $oldAnswers)) ? " checked=\"checked\"" : "")." />".
                "<label for=\"".$prefix."question".$q->id.$a->id."\">".stripslashes($a->answer)."</label>\r\n".RSVP_END_FORM_FIELD;
							$i++;
						}
            $output .= "<div class=\"rsvpClear\">&nbsp;</div>\r\n";
					}
				} else if ($q->questionType == QT_DROP) {
					//$oldAnswers = explode("||", $oldAnswer);
					
					$output .= "<select name=\"".$prefix."question".$q->id."\" size=\"1\">\r\n".
						"<option value=\"\">--</option>\r\n";
					$answers = $wpdb->get_results($wpdb->prepare("SELECT id, answer FROM ".QUESTION_ANSWERS_TABLE." WHERE questionID = %d", $q->id));
					if(count($answers) > 0) {
						foreach($answers as $a) {
							$output .= "<option value=\"".$a->id."\" ".((stripslashes($a->answer) == $oldAnswer) ? " selected=\"selected\"" : "").">".stripslashes($a->answer)."</option>\r\n";
						}
					}
					$output .= "</select>\r\n";
				} else if ($q->questionType == QT_LONG) {
					$output .= "<textarea name=\"".$prefix."question".$q->id."\" rows=\"5\" cols=\"35\">".htmlspecialchars($oldAnswer)."</textarea>";
				} else if ($q->questionType == QT_RADIO) {
					//$oldAnswers = explode("||", $oldAnswer);
					$answers = $wpdb->get_results($wpdb->prepare("SELECT id, answer FROM ".QUESTION_ANSWERS_TABLE." WHERE questionID = %d", $q->id));
					if(count($answers) > 0) {
						$i = 0;
						$output .= RSVP_START_PARA;
						foreach($answers as $a) {
							$output .= "<input type=\"radio\" name=\"".$prefix."question".$q->id."\" id=\"".$prefix."question".$q->id.$a->id."\" value=\"".$a->id."\" "
							  .((stripslashes($a->answer) == $oldAnswer) ? " checked=\"checked\"" : "")." /> ".
              "<label for=\"".$prefix."question".$q->id.$a->id."\">".stripslashes($a->answer)."</label>\r\n";
							$i++;
						}
						$output .= RSVP_END_PARA;
					}
				} else {
					// normal text input
					$output .= "<input type=\"text\" name=\"".$prefix."question".$q->id."\" value=\"".htmlspecialchars($oldAnswer)."\" size=\"25\" />";
				}
				
			$output .= RSVP_END_FORM_FIELD;
		}
	}
	
	return $output."</div>";
}

function rsvp_find(&$output, &$text) {
	global $wpdb, $rsvp_form_action;
  $passcodeOptionEnabled = (rsvp_require_passcode()) ? true : false;
  $passcodeOnlyOption = (rsvp_require_only_passcode_to_register()) ? true : false;
	
	//$_SESSION['rsvpFirstName'] = $_POST['firstName'];
	//$_SESSION['rsvpLastName'] = $_POST['lastName'];
	$passcode = "";
	if(isset($_REQUEST['passcode'])) {
		$passcode = $_REQUEST['passcode'];
		//$_SESSION['rsvpPasscode'] = $_POST['passcode'];
	}
  
	$firstName = $_REQUEST['firstName'];
	$lastName = $_REQUEST['lastName'];
				
	if(!$passcodeOnlyOption && ((strlen($_REQUEST['firstName']) <= 1) || (strlen($_REQUEST['lastName']) <= 1))) {
		$output = "<p class=\"rsvpParagraph\" style=\"color:red\">".__("A first and last name must be specified", 'rsvp-plugin')."</p>\r\n";
		$output .= rsvp_frontend_greeting();
					
		return rsvp_handle_output($text, $output);
	}
				
	// Try to find the user.
	if($passcodeOptionEnabled) {
    if($passcodeOnlyOption) {
  		$attendee = $wpdb->get_row($wpdb->prepare("SELECT id, firstName, lastName, rsvpStatus 
  																							 FROM ".ATTENDEES_TABLE." 
  																							 WHERE passcode = %s", $passcode));
    } else {
  		$attendee = $wpdb->get_row($wpdb->prepare("SELECT id, firstName, lastName, rsvpStatus 
  																							 FROM ".ATTENDEES_TABLE." 
  																							 WHERE firstName = %s AND lastName = %s AND passcode = %s", $firstName, $lastName, $passcode));
    }
    
		
	} else {
		$attendee = $wpdb->get_row($wpdb->prepare("SELECT id, firstName, lastName, rsvpStatus 
																							 FROM ".ATTENDEES_TABLE." 
																							 WHERE firstName = %s AND lastName = %s", $firstName, $lastName));
	}
  
	if($attendee != null) {
		// hey we found something, we should move on and print out any associated users and let them rsvp
		$output = "<div>\r\n";
		if(strtolower($attendee->rsvpStatus) == "noresponse") {
			$output .= RSVP_START_PARA."Hi ".htmlspecialchars(stripslashes($attendee->firstName." ".$attendee->lastName))."!".RSVP_END_PARA;
						
			if(trim(get_option(OPTION_WELCOME_TEXT)) != "") {
				$output .= RSVP_START_PARA.trim(get_option(OPTION_WELCOME_TEXT)).RSVP_END_PARA;
			} else {
				$output .= RSVP_START_PARA.__("There are a few more questions we need to ask you if you could please fill them out below to finish up the RSVP process.", 'rsvp-plugin').RSVP_END_PARA;
			}
						
			$output .= rsvp_frontend_main_form($attendee->id);
		} else {
			$output .= rsvp_frontend_prompt_to_edit($attendee);
		}
		return rsvp_handle_output($text, $output."</div>\r\n");
	}
				
	// We did not find anyone let's try and do a rough search
	$attendees = null;
	if(!$passcodeOptionEnabled) {
		for($i = 3; $i >= 1; $i--) {
			$truncFirstName = rsvp_chomp_name($firstName, $i);
			$attendees = $wpdb->get_results("SELECT id, firstName, lastName, rsvpStatus FROM ".ATTENDEES_TABLE." 
																			 WHERE lastName = '".mysql_real_escape_string($lastName)."' AND firstName LIKE '".mysql_real_escape_string($truncFirstName)."%'");
			if(count($attendees) > 0) {
				$output = RSVP_START_PARA."<strong>".__("We could not find an exact match but could any of the below entries be you?", 'rsvp-plugin')."</strong>".RSVP_END_PARA;
				foreach($attendees as $a) {
					$output .= "<form method=\"post\" action=\"$rsvp_form_action\">\r\n
									<input type=\"hidden\" name=\"rsvpStep\" value=\"foundattendee\" />\r\n
									<input type=\"hidden\" name=\"attendeeID\" value=\"".$a->id."\" />\r\n
									<p class=\"rsvpParagraph\" style=\"text-align:left;\">\r\n
							".htmlspecialchars($a->firstName." ".$a->lastName)." 
							<input type=\"submit\" value=\"RSVP\" />\r\n
							</p>\r\n</form>\r\n";
				}
				return rsvp_handle_output($text, $output);
			} else {
				$i = strlen($truncFirstName);
			}
		}
	}
  
  if(rsvp_require_only_passcode_to_register()) {
    $notFoundText = sprintf(__(RSVP_START_PARA.'<strong>We were unable to find anyone with the password you specified.</strong>'.RSVP_END_PARA, 'rsvp-plugin'));
  } else {
    $notFoundText = sprintf(__(RSVP_START_PARA.'<strong>We were unable to find anyone with a name of %1$s %2$s</strong>'.RSVP_END_PARA, 'rsvp-plugin'), htmlspecialchars($firstName), htmlspecialchars($lastName));
  }
  
	
	$notFoundText .= rsvp_frontend_greeting();
	return rsvp_handle_output($text, $notFoundText);
}

function rsvp_handleNewRsvp(&$output, &$text) {
  global $wpdb, $rsvp_saved_form_vars;
  $thankYouPrimary = "";
  $thankYouAssociated = array();
  foreach($_POST as $key=>$val) {
    $rsvp_saved_form_vars[$key] = $val;
  }
  
  if(empty($_POST['attendeeFirstName']) || empty($_POST['attendeeLastName'])) {
    return rsvp_handlenewattendee($output, $text);
  }
  
  $rsvpPassword = "";
  $rsvpStatus = "No";
	if(strToUpper($_POST['mainRsvp']) == "Y") {
		$rsvpStatus = "Yes";
	}
  $kidsMeal = ((isset($_POST['mainKidsMeal']) && (strToUpper($_POST['mainKidsMeal']) == "Y")) ? "Y" : "N");
  $veggieMeal = ((isset($_POST['mainVeggieMeal']) && (strToUpper($_POST['mainVeggieMeal']) == "Y")) ? "Y" : "N");
  $thankYouPrimary = $_POST['attendeeFirstName'];
	$wpdb->insert(ATTENDEES_TABLE, array("rsvpDate" => date("Y-m-d"), 
                                       "firstName" => $_POST['attendeeFirstName'], 
                                       "lastName"  => $_POST['attendeeLastName'], 
                                       "email"     => $_POST['mainEmail'], 
                                       "rsvpStatus" => $rsvpStatus, 
                                       "note" => $_POST['rsvp_note'], 
                                       "kidsMeal" => $kidsMeal, 
                                       "veggieMeal" => $veggieMeal), 
																 array("%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s"));
	rsvp_printQueryDebugInfo();									
  $attendeeID = $wpdb->insert_id;
  
	if(rsvp_require_passcode()) {
    $rsvpPassword = trim(rsvp_generate_passcode());
		$wpdb->update(ATTENDEES_TABLE, 
									array("passcode" => $rsvpPassword), 
									array("id"=>$attendeeID), 
									array("%s"), 
									array("%d"));
	}
  
	rsvp_handleAdditionalQuestions($attendeeID, "mainquestion");
																			
	$sql = "SELECT id, firstName FROM ".ATTENDEES_TABLE." 
	 	WHERE (id IN (SELECT attendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE associatedAttendeeID = %d) 
			OR id in (SELECT associatedAttendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE attendeeID = %d)) ";
	$associations = $wpdb->get_results($wpdb->prepare($sql, $attendeeID, $attendeeID));
	foreach($associations as $a) {
		if(isset($_POST['attending'.$a->id]) && (($_POST['attending'.$a->id] == "Y") || ($_POST['attending'.$a->id] == "N"))) {
			if($_POST['attending'.$a->id] == "Y") {
				$rsvpStatus = "Yes";
			} else {
				$rsvpStatus = "No";
			}
      $thankYouAssociated[] = stripslashes($a->firstName);
      if(get_option(OPTION_RSVP_HIDE_EMAIL_FIELD) != "Y") { 
  			$wpdb->update(ATTENDEES_TABLE, array("rsvpDate" => date("Y-m-d"), 
  							"rsvpStatus" => $rsvpStatus, 
                "email" => $_POST['attending'.$a->id."Email"], 
  							"kidsMeal" => ((strToUpper((isset($_POST['attending'.$a->id.'KidsMeal']) ? $_POST['attending'.$a->id.'KidsMeal'] : "N")) == "Y") ? "Y" : "N"), 
  							"veggieMeal" => ((strToUpper((isset($_POST['attending'.$a->id.'VeggieMeal']) ? $_POST['attending'.$a->id.'VeggieMeal'] : "N")) == "Y") ? "Y" : "N")),
  							array("id" => $a->id), 
  							array("%s", "%s", "%s", "%s", "%s"), 
  							array("%d"));
          
      } else {
  			$wpdb->update(ATTENDEES_TABLE, array("rsvpDate" => date("Y-m-d"), 
  							"rsvpStatus" => $rsvpStatus, 
  							"kidsMeal" => ((strToUpper((isset($_POST['attending'.$a->id.'KidsMeal']) ? $_POST['attending'.$a->id.'KidsMeal'] : "N")) == "Y") ? "Y" : "N"), 
  							"veggieMeal" => ((strToUpper((isset($_POST['attending'.$a->id.'VeggieMeal']) ? $_POST['attending'.$a->id.'VeggieMeal'] : "N")) == "Y") ? "Y" : "N")),
  							array("id" => $a->id), 
  							array("%s", "%s", "%s", "%s"), 
  							array("%d"));
        
      }
			rsvp_printQueryDebugInfo();
			rsvp_handleAdditionalQuestions($a->id, $a->id."question");
		}
	}
				
	if(get_option(OPTION_HIDE_ADD_ADDITIONAL) != "Y") {
		if(is_numeric($_POST['additionalRsvp']) && ($_POST['additionalRsvp'] > 0)) {
			for($i = 1; $i <= $_POST['additionalRsvp']; $i++) {
        $numGuests = 3;
        if(get_option(OPTION_RSVP_NUM_ADDITIONAL_GUESTS) != "") {
          $numGuests = get_optioN(OPTION_RSVP_NUM_ADDITIONAL_GUESTS);
          if(!is_numeric($numGuests) || ($numGuests < 0)) {
            $numGuests = 3;
          }
        }
				if(($i <= $numGuests) && 
				   !empty($_POST['newAttending'.$i.'FirstName']) && 
				   !empty($_POST['newAttending'.$i.'LastName'])) {		
          $thankYouAssociated[] = $_POST['newAttending'.$i.'FirstName'];
					$wpdb->insert(ATTENDEES_TABLE, array("firstName" => trim($_POST['newAttending'.$i.'FirstName']), 
									"lastName" => trim($_POST['newAttending'.$i.'LastName']), 
                  "email" => trim($_POST['newAttending'.$i."Email"]), 
									"rsvpDate" => date("Y-m-d"), 
									"rsvpStatus" => (($_POST['newAttending'.$i] == "Y") ? "Yes" : "No"), 
									"kidsMeal" => (isset($_POST['newAttending'.$i.'KidsMeal']) ? $_POST['newAttending'.$i.'KidsMeal'] : "N"), 
									"veggieMeal" => (isset($_POST['newAttending'.$i.'VeggieMeal']) ? $_POST['newAttending'.$i.'VeggieMeal'] : "N"), 
									"additionalAttendee" => "Y"), 
									array('%s', '%s', '%s', '%s', '%s', '%s', '%s'));
					rsvp_printQueryDebugInfo();
					$newAid = $wpdb->insert_id;
					rsvp_handleAdditionalQuestions($newAid, $i.'question');
					// Add associations for this new user
					$wpdb->insert(ASSOCIATED_ATTENDEES_TABLE, array("attendeeID" => $newAid, 
										"associatedAttendeeID" => $attendeeID), 
										array("%d", "%d"));
					rsvp_printQueryDebugInfo();
					$wpdb->query("INSERT INTO ".ASSOCIATED_ATTENDEES_TABLE."(attendeeID, associatedAttendeeID)
																			 SELECT ".$newAid.", associatedAttendeeID 
																			 FROM ".ASSOCIATED_ATTENDEES_TABLE." 
																			 WHERE attendeeID = ".$attendeeID);
					rsvp_printQueryDebugInfo();
				}
			}
		}
	}
				
	if((get_option(OPTION_NOTIFY_ON_RSVP) == "Y") && (get_option(OPTION_NOTIFY_EMAIL) != "")) {
		$sql = "SELECT firstName, lastName, rsvpStatus, note, kidsMeal, veggieMeal FROM ".ATTENDEES_TABLE." WHERE id= ".$attendeeID;
		$attendee = $wpdb->get_results($sql);
		if(count($attendee) > 0) {
			$body = "Hello, \r\n\r\n";
						
			$body .= stripslashes($attendee[0]->firstName)." ".stripslashes($attendee[0]->lastName).
							 " has submitted their RSVP and has RSVP'd with '".$attendee[0]->rsvpStatus."'.\r\n";
      
      if(get_option(OPTION_HIDE_KIDS_MEAL) != "Y") {
        $body .= "Kids Meal: ".$attendee[0]->kidsMeal."\r\n";
      }
      
      if(get_option(OPTION_HIDE_VEGGIE) != "Y") {
        $body .= "Vegetarian Meal: ".$attendee[0]->veggieMeal."\r\n";
      }
      
      if(get_option(RSVP_OPTION_HIDE_NOTE) != "Y") {
        $body .= "Note: ".stripslashes($attendee[0]->note)."\r\n";
      }
      
			$sql = "SELECT question, answer FROM ".QUESTIONS_TABLE." q 
				LEFT JOIN ".ATTENDEE_ANSWERS." ans ON q.id = ans.questionID AND ans.attendeeID = %d 
				ORDER BY q.sortOrder, q.id";
			$aRs = $wpdb->get_results($wpdb->prepare($sql, $attendeeID));
			if(count($aRs) > 0) {
        $body .= "\r\n\r\n--== Custom Questions ==--\r\n";
				foreach($aRs as $a) {
          $body .= stripslashes($a->question).": ".stripslashes($a->answer)."\r\n";
				}
			}
      
			$sql = "SELECT firstName, lastName, rsvpStatus FROM ".ATTENDEES_TABLE." 
			 	WHERE id IN (SELECT attendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE associatedAttendeeID = %d) 
					OR id in (SELECT associatedAttendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE attendeeID = %d)";
		
			$associations = $wpdb->get_results($wpdb->prepare($sql, $attendeeID, $attendeeID));
      if(count($associations) > 0) {
  			foreach($associations as $a) {
          $body .= "\r\n\r\n--== Associated Attendees ==--\r\n";
          $body .= stripslashes($a->firstName." ".$a->lastName)." rsvp status: ".$a->rsvpStatus."\r\n";
  			}
      }
			
      $emailAddy = get_option(OPTION_NOTIFY_EMAIL);		
      $headers = "";
      if(get_option(OPTION_RSVP_DISABLE_CUSTOM_EMAIL_FROM) != "Y")	
        $headers = 'From: '. $emailAddy . "\r\n";
      
			wp_mail($emailAddy, "New RSVP Submission", $body, $headers);
		}
	}
  
  if((get_option(OPTION_RSVP_GUEST_EMAIL_CONFIRMATION) == "Y") && !empty($_POST['mainEmail'])) {
		$sql = "SELECT firstName, lastName, email, rsvpStatus FROM ".ATTENDEES_TABLE." WHERE id= ".$attendeeID;
		$attendee = $wpdb->get_results($sql);
		if(count($attendee) > 0) {
			$body = "Hello ".stripslashes($attendee[0]->firstName)." ".stripslashes($attendee[0]->lastName).", \r\n\r\n";
						
      if(get_option(OPTION_RSVP_EMAIL_TEXT) != "") {
        $body .= "\r\n";
        $body .= get_option(OPTION_RSVP_EMAIL_TEXT);
        $body .= "\r\n";
      }
            
			$body .= "You have successfully RSVP'd with '".$attendee[0]->rsvpStatus."'.";
      
			$sql = "SELECT firstName, lastName, rsvpStatus FROM ".ATTENDEES_TABLE." 
			 	WHERE id IN (SELECT attendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE associatedAttendeeID = %d) 
					OR id in (SELECT associatedAttendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE attendeeID = %d)";
		
			$associations = $wpdb->get_results($wpdb->prepare($sql, $attendeeID, $attendeeID));
      if(count($associations) > 0) {
  			foreach($associations as $a) {
          $body .= "\r\n\r\n--== Associated Attendees ==--\r\n";
          $body .= stripslashes($a->firstName." ".$a->lastName)." rsvp status: ".$a->rsvpStatus."\r\n";
  			}
      }
      $emailAddy = get_option(OPTION_NOTIFY_EMAIL);	
      $headers = "";
      if(!empty($emailAddy) && (get_option(OPTION_RSVP_DISABLE_CUSTOM_EMAIL_FROM) != "Y")) {
        $headers = 'From: '. $emailAddy . "\r\n";
      }
      wp_mail($attendee[0]->email, "RSVP Confirmation", $body, $headers);
    }
  }
				
	return rsvp_handle_output($text, rsvp_frontend_new_atendee_thankyou($thankYouPrimary, $thankYouAssociated, $rsvpPassword));
}

function rsvp_handlersvp(&$output, &$text) {
	global $wpdb;
  $thankYouPrimary = "";
  $thankYouAssociated = array();
	if(is_numeric($_POST['attendeeID']) && ($_POST['attendeeID'] > 0)) {
		// update their information and what not....
		if(strToUpper($_POST['mainRsvp']) == "Y") {
			$rsvpStatus = "Yes";
		} else {
			$rsvpStatus = "No";
		}
		$attendeeID = $_POST['attendeeID'];
    // Get Attendee first name
    $thankYouPrimary = $wpdb->get_var($wpdb->prepare("SELECT firstName FROM ".ATTENDEES_TABLE." WHERE id = %d", $attendeeID));
    if(get_option(OPTION_RSVP_HIDE_EMAIL_FIELD) != "Y") { 
  		$wpdb->update(ATTENDEES_TABLE, array("rsvpDate" => date("Y-m-d"), 
  						"rsvpStatus" => $rsvpStatus, 
  						"note" => $_POST['rsvp_note'],
              "email" => $_POST['mainEmail'],  
  						"kidsMeal" => ((isset($_POST['mainKidsMeal']) && (strToUpper($_POST['mainKidsMeal']) == "Y")) ? "Y" : "N"), 
  						"veggieMeal" => ((isset($_POST['mainVeggieMeal']) && (strToUpper($_POST['mainVeggieMeal']) == "Y")) ? "Y" : "N")), 
  																	array("id" => $attendeeID), 
  																	array("%s", "%s", "%s", "%s", "%s", "%s"), 
  																	array("%d"));
    } else {
  		$wpdb->update(ATTENDEES_TABLE, array("rsvpDate" => date("Y-m-d"), 
  						"rsvpStatus" => $rsvpStatus, 
  						"note" => $_POST['rsvp_note'],
  						"kidsMeal" => ((isset($_POST['mainKidsMeal']) && (strToUpper($_POST['mainKidsMeal']) == "Y")) ? "Y" : "N"), 
  						"veggieMeal" => ((isset($_POST['mainVeggieMeal']) && (strToUpper($_POST['mainVeggieMeal']) == "Y")) ? "Y" : "N")), 
  																	array("id" => $attendeeID), 
  																	array("%s", "%s", "%s", "%s", "%s"), 
  																	array("%d"));
    }
							
		rsvp_handleAdditionalQuestions($attendeeID, "mainquestion");
																				
		$sql = "SELECT id, firstName FROM ".ATTENDEES_TABLE." 
		 	WHERE (id IN (SELECT attendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE associatedAttendeeID = %d) 
				OR id in (SELECT associatedAttendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE attendeeID = %d)) ";
		$associations = $wpdb->get_results($wpdb->prepare($sql, $attendeeID, $attendeeID));
		foreach($associations as $a) {
      if(isset($_POST['attending'.$a->id]) && (($_POST['attending'.$a->id] == "Y") || ($_POST['attending'.$a->id] == "N"))) {
        $thankYouAssociated[] = $a->firstName;
				if($_POST['attending'.$a->id] == "Y") {
					$rsvpStatus = "Yes";
				} else {
					$rsvpStatus = "No";
				}
        if(get_option(OPTION_RSVP_HIDE_EMAIL_FIELD) != "Y") { 
  				$wpdb->update(ATTENDEES_TABLE, array("rsvpDate" => date("Y-m-d"), 
  								"rsvpStatus" => $rsvpStatus,
                  "email" => $_POST['attending'.$a->id."Email"], 
  								"kidsMeal" => ((strToUpper((isset($_POST['attending'.$a->id.'KidsMeal']) ? $_POST['attending'.$a->id.'KidsMeal'] : "N")) == "Y") ? "Y" : "N"), 
  								"veggieMeal" => ((strToUpper((isset($_POST['attending'.$a->id.'VeggieMeal']) ? $_POST['attending'.$a->id.'VeggieMeal'] : "N")) == "Y") ? "Y" : "N")),
  								array("id" => $a->id), 
  								array("%s", "%s", "%s", "%s", "%s"), 
  								array("%d"));
        } else {
  				$wpdb->update(ATTENDEES_TABLE, array("rsvpDate" => date("Y-m-d"), 
  								"rsvpStatus" => $rsvpStatus,
  								"kidsMeal" => ((strToUpper((isset($_POST['attending'.$a->id.'KidsMeal']) ? $_POST['attending'.$a->id.'KidsMeal'] : "N")) == "Y") ? "Y" : "N"), 
  								"veggieMeal" => ((strToUpper((isset($_POST['attending'.$a->id.'VeggieMeal']) ? $_POST['attending'.$a->id.'VeggieMeal'] : "N")) == "Y") ? "Y" : "N")),
  								array("id" => $a->id), 
  								array("%s", "%s", "%s", "%s"), 
  								array("%d"));
        }
				
				rsvp_printQueryDebugInfo();
				rsvp_handleAdditionalQuestions($a->id, $a->id."question");
			}
		}
					
		if(get_option(OPTION_HIDE_ADD_ADDITIONAL) != "Y") {
			if(is_numeric($_POST['additionalRsvp']) && ($_POST['additionalRsvp'] > 0)) {
				for($i = 1; $i <= $_POST['additionalRsvp']; $i++) {
          $numGuests = 3;
          if(get_option(OPTION_RSVP_NUM_ADDITIONAL_GUESTS) != "") {
            $numGuests = get_optioN(OPTION_RSVP_NUM_ADDITIONAL_GUESTS);
            if(!is_numeric($numGuests) || ($numGuests < 0)) {
              $numGuests = 3;
            }
          }
					if(($i <= $numGuests) && 
					   !empty($_POST['newAttending'.$i.'FirstName']) && 
					   !empty($_POST['newAttending'.$i.'LastName'])) {		
            $thankYouAssociated[] = $_POST['newAttending'.$i.'FirstName'];
						$wpdb->insert(ATTENDEES_TABLE, array("firstName" => trim($_POST['newAttending'.$i.'FirstName']), 
										"lastName" => trim($_POST['newAttending'.$i.'LastName']), 
                    "email" => trim($_POST['newAttending'.$i.'Email']), 
										"rsvpDate" => date("Y-m-d"), 
										"rsvpStatus" => (($_POST['newAttending'.$i] == "Y") ? "Yes" : "No"), 
										"kidsMeal" => (isset($_POST['newAttending'.$i.'KidsMeal']) ? $_POST['newAttending'.$i.'KidsMeal'] : "N"), 
										"veggieMeal" => (isset($_POST['newAttending'.$i.'VeggieMeal']) ? $_POST['newAttending'.$i.'VeggieMeal'] : "N"), 
										"additionalAttendee" => "Y"), 
										array('%s', '%s', '%s', '%s', '%s', '%s', '%s'));
						rsvp_printQueryDebugInfo();
						$newAid = $wpdb->insert_id;
						rsvp_handleAdditionalQuestions($newAid, $i.'question');
						// Add associations for this new user
						$wpdb->insert(ASSOCIATED_ATTENDEES_TABLE, array("attendeeID" => $newAid, 
											"associatedAttendeeID" => $attendeeID), 
											array("%d", "%d"));
						rsvp_printQueryDebugInfo();
						$wpdb->query($wpdb->prepare("INSERT INTO ".ASSOCIATED_ATTENDEES_TABLE."(attendeeID, associatedAttendeeID)
																				 SELECT ".$newAid.", associatedAttendeeID 
																				 FROM ".ASSOCIATED_ATTENDEES_TABLE." 
																				 WHERE attendeeID = %d", $attendeeID));
						rsvp_printQueryDebugInfo();
					}
				}
			}
		}
		
    $email = get_option(OPTION_NOTIFY_EMAIL);
    
		if((get_option(OPTION_NOTIFY_ON_RSVP) == "Y") && ($email != "")) {
			$sql = "SELECT firstName, lastName, rsvpStatus, kidsMeal, veggieMeal, note FROM ".ATTENDEES_TABLE." WHERE id= ".$attendeeID;
			$attendee = $wpdb->get_results($sql);
			if(count($attendee) > 0) {
				$body = "Hello, \r\n\r\n";
							
				$body .= stripslashes($attendee[0]->firstName)." ".stripslashes($attendee[0]->lastName).
								 " has submitted their RSVP and has RSVP'd with '".$attendee[0]->rsvpStatus."'.";
				
        if(get_option(OPTION_HIDE_KIDS_MEAL) != "Y") {
          $body .= "Kids Meal: ".$attendee[0]->kidsMeal."\r\n";
        }
      
        if(get_option(OPTION_HIDE_VEGGIE) != "Y") {
          $body .= "Vegetarian Meal: ".$attendee[0]->veggieMeal."\r\n";
        }
      
        if(get_option(RSVP_OPTION_HIDE_NOTE) != "Y") {
          $body .= "Note: ".stripslashes($attendee[0]->note)."\r\n";
        }
      
  			$sql = "SELECT question, answer FROM ".QUESTIONS_TABLE." q 
  				LEFT JOIN ".ATTENDEE_ANSWERS." ans ON q.id = ans.questionID AND ans.attendeeID = %d 
  				ORDER BY q.sortOrder, q.id";
  			$aRs = $wpdb->get_results($wpdb->prepare($sql, $attendeeID));
  			if(count($aRs) > 0) {
          $body .= "\r\n\r\n--== Custom Questions ==--\r\n";
  				foreach($aRs as $a) {
            $body .= stripslashes($a->question).": ".stripslashes($a->answer)."\r\n";
  				}
  			}
        
  			$sql = "SELECT firstName, lastName, rsvpStatus FROM ".ATTENDEES_TABLE." 
  			 	WHERE id IN (SELECT attendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE associatedAttendeeID = %d) 
  					OR id in (SELECT associatedAttendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE attendeeID = %d)";
		
  			$associations = $wpdb->get_results($wpdb->prepare($sql, $attendeeID, $attendeeID));
        if(count($associations) > 0) {
          $body .= "\r\n\r\n--== Associated Attendees ==--\r\n";
    			foreach($associations as $a) {
            $body .= stripslashes($a->firstName." ".$a->lastName)." RSVP status: ".$a->rsvpStatus."\r\n";
    			}
        }
        $headers = "";
				if(get_option(OPTION_RSVP_DISABLE_CUSTOM_EMAIL_FROM) != "Y")
          $headers = 'From: '. $email . "\r\n";		
        
				wp_mail($email, "New RSVP Submission", $body, $headers);
			}
		}
    
    if((get_option(OPTION_RSVP_GUEST_EMAIL_CONFIRMATION) == "Y") && !empty($_POST['mainEmail'])) {
  		$sql = "SELECT firstName, lastName, email, rsvpStatus FROM ".ATTENDEES_TABLE." WHERE id= ".$attendeeID;
  		$attendee = $wpdb->get_results($sql);
  		if(count($attendee) > 0) {
  			$body = "Hello ".stripslashes($attendee[0]->firstName)." ".stripslashes($attendee[0]->lastName).", \r\n\r\n";
						
        if(get_option(OPTION_RSVP_EMAIL_TEXT) != "") {
          $body .= "\r\n";
          $body .= get_option(OPTION_RSVP_EMAIL_TEXT);
          $body .= "\r\n";
        }
        
  			$body .= "You have successfully RSVP'd with '".$attendee[0]->rsvpStatus."'.";
      
  			$sql = "SELECT firstName, lastName, rsvpStatus FROM ".ATTENDEES_TABLE." 
  			 	WHERE id IN (SELECT attendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE associatedAttendeeID = %d) 
  					OR id in (SELECT associatedAttendeeID FROM ".ASSOCIATED_ATTENDEES_TABLE." WHERE attendeeID = %d)";
		
  			$associations = $wpdb->get_results($wpdb->prepare($sql, $attendeeID, $attendeeID));
        if(count($associations) > 0) {
    			foreach($associations as $a) {
            $body .= "\r\n\r\n--== Associated Attendees ==--\r\n";
            $body .= stripslashes($a->firstName." ".$a->lastName)." rsvp status: ".$a->rsvpStatus."\r\n";
    			}
        }
        $headers = "";
        if(!empty($email) && (get_option(OPTION_RSVP_DISABLE_CUSTOM_EMAIL_FROM) != "Y")) {
          $headers = 'From: '. $email . "\r\n";		
        }
        wp_mail($attendee[0]->email, "RSVP Confirmation", $body, $headers);
      }
    }
					
		return rsvp_handle_output($text, frontend_rsvp_thankyou($thankYouPrimary, $thankYouAssociated));
	} else {
		return rsvp_handle_output($text, rsvp_frontend_greeting());
	}
}

function rsvp_editAttendee(&$output, &$text) {
	global $wpdb;
	
	if(is_numeric($_POST['attendeeID']) && ($_POST['attendeeID'] > 0)) {
		// Try to find the user.
		$attendee = $wpdb->get_row($wpdb->prepare("SELECT id, firstName, lastName, rsvpStatus 
													FROM ".ATTENDEES_TABLE." 
													WHERE id = %d", $_POST['attendeeID']));
		if($attendee != null) {
			$output .= RSVP_START_CONTAINER;
			$output .= RSVP_START_PARA.__("Welcome back", 'rsvp-plugin')." ".htmlspecialchars($attendee->firstName." ".$attendee->lastName)."!".RSVP_END_PARA;
			$output .= rsvp_frontend_main_form($attendee->id);
			return rsvp_handle_output($text, $output.RSVP_END_CONTAINER);
		}
	}
}

function rsvp_foundAttendee(&$output, &$text) {
	global $wpdb;
	
	if(is_numeric($_POST['attendeeID']) && ($_POST['attendeeID'] > 0)) {
		$attendee = $wpdb->get_row($wpdb->prepare("SELECT id, firstName, lastName, rsvpStatus 
																							 FROM ".ATTENDEES_TABLE." 
																							 WHERE id = %d", $_POST['attendeeID']));
		if($attendee != null) {
			$output = RSVP_START_CONTAINER;
			if(strtolower($attendee->rsvpStatus) == "noresponse") {
				$output .= RSVP_START_PARA.__("Hi", 'rsvp-plugin')." ".htmlspecialchars(stripslashes($attendee->firstName." ".$attendee->lastName))."!".RSVP_END_PARA;
							
				if(trim(get_option(OPTION_WELCOME_TEXT)) != "") {
					$output .= RSVP_START_PARA.trim(get_option(OPTION_WELCOME_TEXT)).RSVP_END_PARA;
				} else {
					$output .= RSVP_START_PARA.__("There are a few more questions we need to ask you if you could please fill them out below to finish up the RSVP process.", 'rsvp-plugin').RSVP_END_PARA;
				}
												
				$output .= rsvp_frontend_main_form($attendee->id);
			} else {
				$output .= rsvp_frontend_prompt_to_edit($attendee);
			}
			return rsvp_handle_output($text, $output.RSVP_END_CONTAINER);
		} 
					
		return rsvp_handle_output($text, rsvp_frontend_greeting());
	} else {
		return rsvp_handle_output($text, rsvp_frontend_greeting());
	}
}
	
	

function frontend_rsvp_thankyou($thankYouPrimary, $thankYouAssociated) {
	$customTy = get_option(OPTION_THANKYOU);
	if(!empty($customTy)) {
		return nl2br($customTy);
	} else {    
    $tyText = __("Thank you", 'rsvp-plugin');
    if(!empty($thankYouPrimary)) {
      $tyText .= " ".htmlspecialchars($thankYouPrimary);
    }
    $tyText .= __(" for RSVPing.", 'rsvp-plugin');
    
    if(count($thankYouAssociated) > 0) {
      $tyText .= __(" You have also RSVPed for - ", 'rsvp-plugin');
      foreach($thankYouAssociated as $name) {
        $tyText .= htmlspecialchars(" ".$name).", ";
      }
      $tyText = rtrim(trim($tyText), ",").".";
    }
		return RSVP_START_CONTAINER.RSVP_START_PARA.$tyText.RSVP_END_PARA.RSVP_END_CONTAINER;
	}
}

function rsvp_frontend_new_atendee_thankyou($thankYouPrimary, $thankYouAssociated, $password = "") {
	/*$customTy = get_option(OPTION_THANKYOU);
	if(!empty($customTy)) {
		return nl2br($customTy);
	} else {*/
    $thankYouText = __("Thank you ", 'rsvp-plugin');
    if(!empty($thankYouPrimary)) {
      $thankYouText .= htmlspecialchars($thankYouPrimary);
    }
    $thankYouText .= __(" for RSVPing. To modify your RSVP just come back ".
                    "to this page and enter in your first and last name.", 'rsvp-plugin');
    if(!empty($password)) {
      $thankYouText .= __(" You will also need to know your password which is", 'rsvp-plugin').
                      " - <strong>$password</strong>";
    }
    
    if(count($thankYouAssociated) > 0) {
      $thankYouText .= __("<br /><br />You have also RSVPed for - ", 'rsvp-plugin');
      foreach($thankYouAssociated as $name) {
        $thankYouText .= htmlspecialchars(" ".$name).", ";
      }
      $thankYouText = rtrim(trim($thankYouText), ",").".";
    }

		return RSVP_START_CONTAINER.RSVP_START_PARA.$thankYouText.RSVP_END_PARA.RSVP_END_CONTAINER;
	//}
}

function rsvp_chomp_name($name, $maxLength) {
	for($i = $maxLength; $maxLength >= 1; $i--) {
		if(strlen($name) >= $i) {
			return substr($name, 0, $i);
		}
	}
}

function rsvp_BeginningFormField($id, $additionalClasses) {
  return "<div ".(!empty($id) ? "id=\"$id\"" : "")." class=\"rsvpFormField ".(!empty($additionalClasses) ? $additionalClasses : "")."\">";
}

function rsvp_frontend_greeting() {
  global $rsvp_form_action;
	$customGreeting = get_option(OPTION_GREETING);
  if(rsvp_require_only_passcode_to_register()) {
    $output = RSVP_START_PARA.__("Please enter your passcode to RSVP.", 'rsvp-plugin').RSVP_END_PARA;
  } else if(rsvp_require_passcode()) {
    $output = RSVP_START_PARA.__("Please enter your first name, last name and passcode to RSVP.", 'rsvp-plugin').RSVP_END_PARA;
  } else {
    $output = RSVP_START_PARA.__("Please enter your first and last name to RSVP.", 'rsvp-plugin').RSVP_END_PARA;
  }
	
	$firstName = "";
	$lastName = "";
	$passcode = "";
	if(isset($_SESSION['rsvpFirstName'])) {
		$firstName = $_SESSION['rsvpFirstName'];
	}
	if(isset($_SESSION['rsvpLastName'])) {
		$lastName = $_SESSION['rsvpLastName'];
	}
	if(isset($_SESSION['rsvpPasscode'])) {
		$passcode = $_SESSION['rsvpPasscode'];
	}
	if(!empty($customGreeting)) {
		$output = RSVP_START_PARA.nl2br($customGreeting).RSVP_END_PARA;
	} 
  
  $output .= RSVP_START_CONTAINER;
  
  if(get_option(OPTION_RSVP_OPEN_REGISTRATION) == "Y") {
    $output .= "<form name=\"rsvpNew\" method=\"post\" id=\"rsvpNew\" action=\"$rsvp_form_action\">\r\n";
    $output .= "	<input type=\"hidden\" name=\"rsvpStep\" value=\"newattendee\" />";
      $output .= "<input type=\"submit\" value=\"".__("New Attendee Registration", "rsvp-plugin")."\" />\r\n";
    $output .= "</form>\r\n";
    
    $output .= "<hr />";
    $output .= RSVP_START_PARA.__("Need to modify your registration? Start with the below form.", "rsvp-plugin").RSVP_END_PARA;
  }
  
	$output .= "<form name=\"rsvp\" method=\"post\" id=\"rsvp\" action=\"$rsvp_form_action\" autocomplete=\"off\">\r\n";
	$output .= "	<input type=\"hidden\" name=\"rsvpStep\" value=\"find\" />";
  if(!rsvp_require_only_passcode_to_register()) {
	$output .= RSVP_START_PARA."<label for=\"firstName\">".__("First Name", 'rsvp-plugin').":</label> 
								 <input type=\"text\" name=\"firstName\" id=\"firstName\" size=\"30\" value=\"".htmlspecialchars($firstName)."\" class=\"required\" />".RSVP_END_PARA;
	$output .= RSVP_START_PARA."<label for=\"lastName\">".__("Last Name", 'rsvp-plugin').":</label> 
								 <input type=\"text\" name=\"lastName\" id=\"lastName\" size=\"30\" value=\"".htmlspecialchars($lastName)."\" class=\"required\" />".RSVP_END_PARA;
  }
	if(rsvp_require_passcode()) {
		$output .= RSVP_START_PARA."<label for=\"passcode\">".__("Passcode", 'rsvp-plugin').":</label> 
									 <input type=\"password\" name=\"passcode\" id=\"passcode\" size=\"30\" value=\"".htmlspecialchars($passcode)."\" class=\"required\" autocomplete=\"off\" />".RSVP_END_PARA;
	}
	$output .= RSVP_START_PARA."<input type=\"submit\" value=\"".__("Complete your RSVP!", 'rsvp-plugin')."\" />".RSVP_END_PARA;
	$output .= "</form>\r\n";
	$output .= RSVP_END_CONTAINER;
	return $output;
}
?>
