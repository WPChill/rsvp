<?php
$installed_ver = get_option( 'rsvp_db_version' );
$table         = $wpdb->prefix . 'attendees';
if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) != $table ) {
	$sql = 'CREATE TABLE ' . $table . " (
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
	$wpdb->query( $sql );
}
$table     = $wpdb->prefix . 'associatedAttendees';
$alt_table = $wpdb->prefix . 'associatedattendees';
if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) != $table && $wpdb->get_var( "SHOW TABLES LIKE '$alt_table'" ) != $alt_table ) {
	$sql = 'CREATE TABLE ' . $table . ' (
	`attendeeID` INT NOT NULL ,
	`associatedAttendeeID` INT NOT NULL
	);';
	$wpdb->query( $sql );
	$sql = 'ALTER TABLE `' . $table . '` ADD INDEX ( `attendeeID` ) ';
	$wpdb->query( $sql );
	$sql = 'ALTER TABLE `' . $table . '` ADD INDEX ( `associatedAttendeeID` )';
	$wpdb->query( $sql );
}
add_option( 'rsvp_db_version', '4' );

if ( (int) $installed_ver < 2 ) {
	$table = $wpdb->prefix . 'attendees';
	$sql   = 'ALTER TABLE ' . $table . ' ADD `personalGreeting` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ;';
	$wpdb->query( $sql );
	update_option( 'rsvp_db_version', RSVP_DB_VERSION );
}

if ( (int) $installed_ver < 4 ) {
	$table = $wpdb->prefix . 'rsvpCustomQuestions';
	$sql   = 'ALTER TABLE ' . $table . " ADD `sortOrder` INT NOT NULL DEFAULT '99';";
	$wpdb->query( $sql );
	update_option( 'rsvp_db_version', RSVP_DB_VERSION );
}

$table     = $wpdb->prefix . 'rsvpCustomQuestions';
$alt_table = $wpdb->prefix . 'rsvpcustomquestions';
if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) != $table && $wpdb->get_var( "SHOW TABLES LIKE '$alt_table'" ) != $alt_table ) {
	$sql = " CREATE TABLE $table (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`question` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
	`questionTypeID` INT NOT NULL, 
	`sortOrder` INT NOT NULL DEFAULT '99', 
	`permissionLevel` ENUM( 'public', 'private' ) NOT NULL DEFAULT 'public'
	);";
	$wpdb->query( $sql );
}

$table     = $wpdb->prefix . 'rsvpQuestionTypes';
$alt_table = $wpdb->prefix . 'rsvpquestiontypes';
if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) != $table && $wpdb->get_var( "SHOW TABLES LIKE '$alt_table'" ) != $alt_table ) {
	$sql = " CREATE TABLE $table (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`questionType` VARCHAR( 100 ) NOT NULL , 
	`friendlyName` VARCHAR(100) NOT NULL 
	);";
	$wpdb->query( $sql );

	$wpdb->insert(
		$table,
		array(
			'questionType' => 'shortAnswer',
			'friendlyName' => 'Short Answer',
		),
		array( '%s', '%s' )
	);
	$wpdb->insert(
		$table,
		array(
			'questionType' => 'multipleChoice',
			'friendlyName' => 'Multiple Choice',
		),
		array( '%s', '%s' )
	);
	$wpdb->insert(
		$table,
		array(
			'questionType' => 'longAnswer',
			'friendlyName' => 'Long Answer',
		),
		array( '%s', '%s' )
	);
	$wpdb->insert(
		$table,
		array(
			'questionType' => 'dropdown',
			'friendlyName' => 'Drop Down',
		),
		array( '%s', '%s' )
	);
	$wpdb->insert(
		$table,
		array(
			'questionType' => 'radio',
			'friendlyName' => 'Radio',
		),
		array( '%s', '%s' )
	);
} elseif ( (int) $installed_ver < 6 ) {
	$wpdb->insert(
		$table,
		array(
			'questionType' => 'radio',
			'friendlyName' => 'Radio',
		),
		array( '%s', '%s' )
	);
}

$table     = $wpdb->prefix . 'rsvpCustomQuestionAnswers';
$alt_table = $wpdb->prefix . 'rsvpcustomquestionanswers';
if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) != $table && $wpdb->get_var( "SHOW TABLES LIKE '$alt_table'" ) != $alt_table ) {
	$sql = "CREATE TABLE $table (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`questionID` INT NOT NULL, 
	`answer` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL
	);";
	$wpdb->query( $sql );
}

$table     = $wpdb->prefix . 'attendeeAnswers';
$alt_table = $wpdb->prefix . 'attendeeanswers';
if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) != $table && $wpdb->get_var( "SHOW TABLES LIKE '$alt_table'" ) != $alt_table ) {
	$sql = "CREATE TABLE $table (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`questionID` INT NOT NULL, 
	`answer` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL, 
	`attendeeID` INT NOT NULL 
	);";
	$wpdb->query( $sql );
}

$table     = $wpdb->prefix . 'rsvpCustomQuestionAttendees';
$alt_table = $wpdb->prefix . 'rsvpcustomquestionattendees';
if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) != $table && $wpdb->get_var( "SHOW TABLES LIKE '$alt_table'" ) != $alt_table ) {
	$sql = "CREATE TABLE $table (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`questionID` INT NOT NULL ,
	`attendeeID` INT NOT NULL
	);";
	$wpdb->query( $sql );
}

if ( (int) $installed_ver < 5 ) {
	$table = $wpdb->prefix . 'rsvpCustomQuestions';
	$sql   = "ALTER TABLE `$table` ADD `permissionLevel` ENUM( 'public', 'private' ) NOT NULL DEFAULT 'public';";
	$wpdb->query( $sql );
}

if ( (int) $installed_ver < 9 ) {
	rsvp_install_passcode_field();
}

$table = $wpdb->prefix . 'attendees';
if ( (int) $installed_ver < 11 || ( $wpdb->get_var( "SHOW COLUMNS FROM `$table` LIKE 'email'" ) != 'email' ) ) {
	$sql = 'ALTER TABLE ' . $table . ' ADD `email` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;';
	$wpdb->query( $sql );
}

$table    = $wpdb->prefix . 'attendees';
$col_info = $wpdb->get_row( 'SHOW FULL COLUMNS FROM ' . $table . ' WHERE Field="note" AND Collation<>"utf8mb4_unicode_520_ci"' );
if ( (int) $installed_ver < 12 || ( $col_info !== null ) ) {
	$sql = 'ALTER TABLE ' . $table . ' MODIFY note text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci';
	$wpdb->query( $sql );
}
update_option( 'rsvp_db_version', RSVP_DB_VERSION );
