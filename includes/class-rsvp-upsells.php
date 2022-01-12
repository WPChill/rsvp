<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) :
	exit;
endif;

if ( ! class_exists( 'RSVP_Upsells' ) ) {

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
		function __construct() {
			add_action( 'rsvp_events_before_table', array( $this, 'events_table_upsells' ) );
			add_action( 'rsvp_settings_page', array( $this, 'settings_upsells' ) );
			add_action( 'rsvp_after_question_table', array( $this, 'questions_upsells' ) );
			add_action( 'rsvp_after_add_guest', array( $this, 'add_guest_upsells' ) );
		}

		/**
		 * Returns the singleton instance of the class.
		 *
		 * @return object The RSVP_Upsells object.
		 * @since 2.7.2
		 */
		public static function get_instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof RSVP_Upsells ) ) {
				self::$instance = new RSVP_Upsells();
			}

			return self::$instance;

		}

		/**
		 * Events table upsells
		 *
		 * @since 2.7.2
		 */
		public function events_table_upsells() {

			?>
			<div class="rsvp-upsell rsvp-center-text">
				<h3><?php echo esc_html__( 'Looking for more events and customizations?', 'rsvp' ); ?></h3>
				<p class="rsvp-upsell-description"><?php echo esc_html__( 'RSVP Pro comes with a robust list of features, such as:', 'rsvp' ); ?></p>
				<ul class="rsvp-upsell-description">
					<li>
						<?php esc_html_e( 'Multiple Events', 'rsvp' ); ?>
					</li>
					<li>
						<?php esc_html_e( 'Multiple Sub-events', 'rsvp' ); ?>
					</li>
					<li>
						<?php esc_html_e( 'Recurring events', 'rsvp' ); ?>
					</li>
					<li>
						<?php esc_html_e( 'Multiple custom questions / event', 'rsvp' ); ?>
					</li>
					<li>
						<?php esc_html_e( 'Attendees / event', 'rsvp' ); ?>
					</li>
				</ul>
				<p class="rsvp-upsell-description">
					<?php esc_html_e( ' Upgrade to the premium version to edit your events with an opening and closing date, limit the number of participants and much much more...', 'rsvp' ); ?>
				<p>
					<a target="_blank"
					   href="<?php echo esc_url( admin_url( 'admin.php?page=rsvp-upgrade-to-pro' ) ); ?>"
					   class="button"><?php echo esc_html__( 'See PRO Features', 'rsvp' ); ?></a><a
							target="_blank"
							href="https://rsvpproplugin.com/"
							class="button-primary button"><?php echo esc_html__( 'Get RSVP PRO!', 'rsvp' ); ?></a>
				</p>
			</div>
			<?php

		}

		/**
		 * Events table upsells
		 *
		 * @since 2.7.2
		 */
		public function settings_upsells() {

			?>
			<div class="rsvp-upsell">
				<h3><?php echo esc_html__( 'Customize all', 'rsvp' ); ?></h3>
				<p class="rsvp-upsell-description"><?php echo esc_html__( 'Everything here is customizable - from events, number of attendees, lists to texts, buttons, titles, and much more. With RSVP Pro, you can customize each slice of your form.', 'rsvp' ); ?></p>
				<p>
					<a target="_blank"
					   href="<?php echo esc_url( admin_url( 'admin.php?page=rsvp-upgrade-to-pro' ) ); ?>"
					   class="button"><?php echo esc_html__( 'See PRO Features', 'rsvp' ); ?></a><a
							target="_blank"
							href="https://rsvpproplugin.com/"
							class="button-primary button"><?php echo esc_html__( 'Get RSVP PRO!', 'rsvp' ); ?></a>
				</p>
			</div>
			<?php

		}

		/**
		 * Events table upsells
		 *
		 * @since 2.7.2
		 */
		public function questions_upsells() {

			?>
			<div class="rsvp-upsell">
				<h3><?php echo esc_html__( 'Want to add questions for each event?', 'rsvp' ); ?></h3>
				<p class="rsvp-upsell-description"><?php echo esc_html__( 'Do you want to add custom questions for a specific event? Upgrade to RSVP Pro and find out more necessary info about your guests. ', 'rsvp' ); ?></p>
				<p>
					<a target="_blank"
					   href="<?php echo esc_url( admin_url( 'admin.php?page=rsvp-upgrade-to-pro' ) ); ?>"
					   class="button"><?php echo esc_html__( 'See PRO Features', 'rsvp' ); ?></a><a
							target="_blank"
							href="https://rsvpproplugin.com/"
							class="button-primary button"><?php echo esc_html__( 'Get RSVP PRO!', 'rsvp' ); ?></a>
				</p>
			</div>
			<?php

		}

		/**
		 * Events table upsells
		 *
		 * @since 2.7.2
		 */
		public function add_guest_upsells() {

			?>
			<div class="rsvp-upsell">
				<h3><?php echo esc_html__( 'Separate attendees for each event', 'rsvp' ); ?></h3>
				<p class="rsvp-upsell-description">
				<?php
				echo esc_html__(
					'If you want to keep in touch with your guests, upgrade to RSVP Pro to send messages, reset attendees’ responses, and add as many participants as you please. You can also increase your workflow by creating reminders and automated emails.
',
					'rsvp'
				);
				?>
													</p>
				<p class="rsvp-upsell-description"><?php echo esc_html__( 'We know how important it is to keep everything stored in a safe place, so we made everything easy for you to import and export attendees and events data.', 'rsvp' ); ?></p>
				<p>
					<a target="_blank"
					   href="<?php echo esc_url( admin_url( 'admin.php?page=rsvp-upgrade-to-pro' ) ); ?>"
					   class="button"><?php echo esc_html__( 'See PRO Features', 'rsvp' ); ?></a><a
							target="_blank"
							href="https://rsvpproplugin.com/"
							class="button-primary button"><?php echo esc_html__( 'Get RSVP PRO!', 'rsvp' ); ?></a>
				</p>
			</div>
			<?php

		}

	}

}
