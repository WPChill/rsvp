<?php
	$installed_ver = get_option("rsvp_db_version");
	$table = $wpdb->prefix."attendees";
	if($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
		$sql = "CREATE TABLE ".$table." (
		`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`firstName` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
		`lastName` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
		`rsvpDate` DATE NULL ,
		`rsvpStatus` ENUM( 'Yes', 'No', 'NoResponse' ) NOT NULL DEFAULT 'NoResponse',
		`note` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL,
		`kidsMeal` ENUM( 'Y', 'N' ) NOT NULL DEFAULT 'N',
		`additionalAttendee` ENUM( 'Y', 'N' ) NOT NULL DEFAULT 'N',
		`veggieMeal` ENUM( 'Y', 'N' ) NOT NULL DEFAULT 'N', 
		`personalGreeting` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL 
		);";
		$wpdb->query($sql);
	}
	$table = $wpdb->prefix."associatedAttendees";
	if($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
		$sql = "CREATE TABLE ".$table." (
		`attendeeID` INT NOT NULL ,
		`associatedAttendeeID` INT NOT NULL
		);";
		$wpdb->query($sql);
		$sql = "ALTER TABLE `".$table."` ADD INDEX ( `attendeeID` ) ";
		$wpdb->query($sql);
		$sql = "ALTER TABLE `".$table."` ADD INDEX ( `associatedAttendeeID` )";
		$wpdb->query($sql);
	}				
	add_option("rsvp_db_version", "4");
	
	if((int)$installed_ver < 2) {
		$table = $wpdb->prefix."attendees";
		$sql = "ALTER TABLE ".$table." ADD `personalGreeting` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ;";
		$wpdb->query($sql);
		update_option( "rsvp_db_version", RSVP_DB_VERSION);
	}
	
	if((int)$installed_ver < 4) {
		$table = $wpdb->prefix."rsvpCustomQuestions";
		$sql = "ALTER TABLE ".$table." ADD `sortOrder` INT NOT NULL DEFAULT '99';";
		$wpdb->query($sql);
		update_option( "rsvp_db_version", RSVP_DB_VERSION);
	}
	
	$table = $wpdb->prefix."rsvpCustomQuestions";
	if($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
		$sql = " CREATE TABLE $table (
		`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`question` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
		`questionTypeID` INT NOT NULL, 
		`sortOrder` INT NOT NULL DEFAULT '99', 
		`permissionLevel` ENUM( 'public', 'private' ) NOT NULL DEFAULT 'public'
		);";
		$wpdb->query($sql);
	}
	
	$table =  $wpdb->prefix."rsvpQuestionTypes";
	if($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
		$sql = " CREATE TABLE $table (
		`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`questionType` VARCHAR( 100 ) NOT NULL , 
		`friendlyName` VARCHAR(100) NOT NULL 
		);";
		$wpdb->query($sql);
		
		$wpdb->insert($table, array("questionType" => "shortAnswer", "friendlyName" => "Short Answer"), array('%s', '%s'));
		$wpdb->insert($table, array("questionType" => "multipleChoice", "friendlyName" => "Multiple Choice"), array('%s', '%s'));
		$wpdb->insert($table, array("questionType" => "longAnswer", "friendlyName" => "Long Answer"), array('%s', '%s'));
		$wpdb->insert($table, array("questionType" => "dropdown", "friendlyName" => "Drop Down"), array('%s', '%s'));
		$wpdb->insert($table, array("questionType" => "radio", "friendlyName" => "Radio"), array('%s', '%s'));
	} else if((int)$installed_ver < 6) {
		$wpdb->insert($table, array("questionType" => "radio", "friendlyName" => "Radio"), array('%s', '%s'));
	}
	
	$table = $wpdb->prefix."rsvpCustomQuestionAnswers";
	if($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
		$sql = "CREATE TABLE $table (
		`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`questionID` INT NOT NULL, 
		`answer` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL
		);";
		$wpdb->query($sql);
	}
	
	$table = $wpdb->prefix."attendeeAnswers";
	if($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
		$sql = "CREATE TABLE $table (
		`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`questionID` INT NOT NULL, 
		`answer` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL, 
		`attendeeID` INT NOT NULL 
		);";
		$wpdb->query($sql);
	}
	
	$table = $wpdb->prefix."rsvpCustomQuestionAttendees";
	if($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
		$sql = "CREATE TABLE $table (
		`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`questionID` INT NOT NULL ,
		`attendeeID` INT NOT NULL
		);";
		$wpdb->query($sql);
	}
	
	if((int)$installed_ver < 5) {
		$table = $wpdb->prefix."rsvpCustomQuestions";
		$sql = "ALTER TABLE `$table` ADD `permissionLevel` ENUM( 'public', 'private' ) NOT NULL DEFAULT 'public';";
		$wpdb->query($sql);
	}
	
	if((int)$installed_ver < 9) {
		rsvp_install_passcode_field();
	}
  
  $table = $wpdb->prefix."attendees";
  if((int)$installed_ver < 11 || ($wpdb->get_var("SHOW COLUMNS FROM `$table` LIKE 'email'") != "email")) {
		$sql = "ALTER TABLE ".$table." ADD `email` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;";
		$wpdb->query($sql);
		update_option( "rsvp_db_version", RSVP_DB_VERSION);
  }
	update_option( "rsvp_db_version", RSVP_DB_VERSION);
?>