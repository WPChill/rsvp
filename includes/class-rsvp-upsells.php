<?php

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) :
	exit;
endif;

if ( !class_exists( 'RSVP_Upsells' ) ){

	class RSVP_Upsells {

		/**
		 * Holds the class object.
		 *
		 * @since 2.7.2
		 *
		 * @var object
		 */
		public static $instance;

		/**
		 * RSVP_Upsells constructor.
		 *
		 * @since 2.7.2
		 */
		function __construct(){
			add_action( 'rsvp_events_before_table', array( $this, 'events_table_upsells' ) );
		}

		/**
		 * Returns the singleton instance of the class.
		 *
		 * @return object The RSVP_Upsells object.
		 * @since 2.7.2
		 */
		public static function get_instance(){

			if ( !isset( self::$instance ) && !( self::$instance instanceof RSVP_Upsells ) ){
				self::$instance = new RSVP_Upsells();
			}

			return self::$instance;

		}

		/**
		 * Events table upsells
		 */
		public function events_table_upsells(){

			?>
			<div class="rsvp-upsell">
				<h2><?php echo esc_html__( 'Looking for more events and customizations?', 'rsvp-plugin' ); ?></h2>
				<p class="rsvp-upsell-description"><?php echo esc_html__( 'Upgrade to RSVP Pro today to get access to multiple events, recurring events,custom questions / event and more... ', 'rsvp-plugin' ); ?></p>
				<p>
					<a target="_blank"
					   href="https://rsvpproplugin.com/features/"
					   class="button"><?php echo esc_html__( 'See PRO Features', 'rsvp-plugin' ); ?></a><a
							target="_blank"
							href="https://rsvpproplugin.com/"
							class="button-primary button"><?php echo esc_html__( 'Get RSVP PRO!', 'rsvp-plugin' ); ?></a>
				</p>
			</div>
			<?php

		}

	}

}