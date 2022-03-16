<?php
/**
 * Uninstall RSVP Plugin
 *
 * @package     rsvp
 * @subpackage  Uninstall
 * @copyright   Copyright (c) 2016, MDE Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.1.4
 */

// Exit if accessed directly
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load EDD file
require_once 'wp-rsvp.php';

global $wpdb;

if ( get_option( RSVP_OPTION_DELETE_DATA_ON_UNINSTALL ) === 'Y' ) {
	// Delete the tables
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'attendees' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'associatedAttendees' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'rsvpCustomQuestions' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'rsvpQuestionTypes' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'attendeeAnswers' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'rsvpCustomQuestionAnswers' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'rsvpCustomQuestionAttendees' );

	// Delete the options...
	delete_option( OPTION_OPENDATE );
	delete_option( OPTION_DEADLINE );
	delete_option( OPTION_RSVP_NUM_ADDITIONAL_GUESTS );
	delete_option( OPTION_GREETING );
	delete_option( OPTION_WELCOME_TEXT );
	delete_option( OPTION_RSVP_EMAIL_TEXT );
	delete_option( OPTION_RSVP_QUESTION );
	delete_option( OPTION_YES_VERBIAGE );
	delete_option( OPTION_NO_VERBIAGE );
	delete_option( OPTION_KIDS_MEAL_VERBIAGE );
	delete_option( OPTION_HIDE_KIDS_MEAL );
	delete_option( OPTION_VEGGIE_MEAL_VERBIAGE );
	delete_option( OPTION_HIDE_VEGGIE );
	delete_option( OPTION_NOTE_VERBIAGE );
	delete_option( RSVP_OPTION_HIDE_NOTE );
	delete_option( OPTION_THANKYOU );
	delete_option( OPTION_HIDE_ADD_ADDITIONAL );
	delete_option( OPTION_RSVP_ADD_ADDITIONAL_VERBIAGE );
	delete_option( OPTION_NOTIFY_ON_RSVP );
	delete_option( OPTION_NOTIFY_EMAIL );
	delete_option( OPTION_RSVP_GUEST_EMAIL_CONFIRMATION );
	delete_option( OPTION_RSVP_PASSCODE );
	delete_option( OPTION_RSVP_ONLY_PASSCODE );
	delete_option( OPTION_RSVP_OPEN_REGISTRATION );
	delete_option( OPTION_RSVP_DONT_USE_HASH );
	delete_option( OPTION_RSVP_HIDE_EMAIL_FIELD );
	delete_option( OPTION_RSVP_DISABLE_CUSTOM_EMAIL_FROM );
	delete_option( OPTION_RSVP_DISABLE_USER_SEARCH );
	delete_option( RSVP_OPTION_DELETE_DATA_ON_UNINSTALL );
	delete_option( 'rsvp_db_version' );
}
