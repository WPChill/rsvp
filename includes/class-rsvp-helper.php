<?php


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
	 * Modula_Compatibility constructor.
	 *
	 * @since 2.4.2
	 */
	function __construct(){

		add_action( 'admin_action_delete-rsvp-attendee', array( $this, 'delete_attendee' ) );

	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The RSVP_Helper object.
	 * @since 2.7.2
	 */
	public static function get_instance(){

		if ( !isset( self::$instance ) && !( self::$instance instanceof RSVP_Helper ) ){
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
	public function get_associated_attendees( $id ){

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
	public function delete_attendee( $attendee_id = false ){

		if ( !$attendee_id ){


			check_admin_referer( 'delete-rsvp-attendee_' . $_REQUEST['id'] );

			if ( isset( $_REQUEST['action'] ) && 'delete-rsvp-attendee' == $_REQUEST['action'] && isset( $_REQUEST['id'] ) ){

				global $wpdb;
				$attendee_id = absint( $_REQUEST['id'] );

				$wpdb->query(
					$wpdb->prepare(
						'DELETE FROM ' . ASSOCIATED_ATTENDEES_TABLE . ' WHERE attendeeID = %d OR associatedAttendeeID = %d',
						$attendee_id,
						$attendee_id
					)
				);

				$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . ATTENDEE_ANSWERS . ' WHERE attendeeID = %d', $_REQUEST['id'] ) );

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

			if ( is_numeric( $attendee_id ) && ( $attendee_id > 0 ) ){
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
}

RSVP_Helper::get_instance();