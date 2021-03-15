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
			add_action( 'rsvp_settings_page', array( $this, 'settings_upsells' ) );
			add_action( 'rsvp_settings_page', array( $this, 'text_customization_upsells' ) );
			add_action( 'rsvp_after_question_table', array( $this, 'questions_upsells' ) );
			add_action( 'rsvp_after_add_guest', array( $this, 'add_guest_upsells' ) );
			add_action( 'rsvp_after_add_guest', array( $this, 'mass_mail' ) );
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
		 *
		 * @since 2.7.2
		 */
		public function events_table_upsells(){

			?>
			<div class="rsvp-upsell">
				<h3><?php echo esc_html__( 'Looking for more events and customizations?', 'rsvp-plugin' ); ?></h3>
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

		/**
		 * Events table upsells
		 *
		 * @since 2.7.2
		 */
		public function settings_upsells(){

			?>
			<div class="rsvp-upsell">
				<h3><?php echo esc_html__( 'Send notifications and reminders to your attendees', 'rsvp-plugin' ); ?></h3>
				<p class="rsvp-upsell-description"><?php echo esc_html__( 'Upgrade to RSVP Pro today and fulfill your needs.', 'rsvp-plugin' ); ?></p>
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

		/**
		 * Events table upsells
		 *
		 * @since 2.7.2
		 */
		public function text_customization_upsells(){

			?>
			<div class="rsvp-upsell">
				<h3><?php echo esc_html__( 'Get more text customization', 'rsvp-plugin' ); ?></h3>
				<p class="rsvp-upsell-description"><?php echo esc_html__( 'Upgrade to RSVP Pro today to get more text customization options, alongside with .', 'rsvp-plugin' ); ?></p>
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

		/**
		 * Events table upsells
		 *
		 * @since 2.7.2
		 */
		public function questions_upsells(){

			?>
			<div class="rsvp-upsell">
				<h3><?php echo esc_html__( 'Want to add questions for each event?', 'rsvp-plugin' ); ?></h3>
				<p class="rsvp-upsell-description"><?php echo esc_html__( 'Upgrade to RSVP Pro today to get access to multiple questions / event and much much more... ', 'rsvp-plugin' ); ?></p>
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

		/**
		 * Events table upsells
		 *
		 * @since 2.7.2
		 */
		public function add_guest_upsells(){

			?>
			<div class="rsvp-upsell">
				<h3><?php echo esc_html__( 'Separate attendees for each event', 'rsvp-plugin' ); ?></h3>
				<p class="rsvp-upsell-description"><?php echo esc_html__( 'Upgrade to RSVP Pro today to get access to multiple events, recurring events, multiple attendess per event.  ', 'rsvp-plugin' ); ?></p>
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

		/**
		 * Events table upsells
		 *
		 * @since 2.7.2
		 */
		public function mass_mail(){

			?>
			<div class="rsvp-upsell">
				<h3><?php echo esc_html__( 'Send messages to all attendees', 'rsvp-plugin' ); ?></h3>
				<p class="rsvp-upsell-description"><?php echo esc_html__( 'Send messages to all or a selected range of attendees from an event, reset attendees response and much more using our Premium version.', 'rsvp-plugin' ); ?></p>
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