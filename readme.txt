=== RSVP and Event Management ===
Contributors: wpchill, silkalns
Tags: rsvp, event management, planning, event registration, calendar
Requires at least: 5.6
Tested up to: 6.6
Requires PHP: 5.6
Stable tag: 2.7.13
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

The RSVP Plugin is ideal for managing event registrations, offering powerful features to streamline the RSVP process for any type of event.

== Description ==

The RSVP Plugin offers a comprehensive solution for event registration and management, providing an easy way for users to RSVP to your events. The plugin comes in two versions: Free and Pro. The free version includes essential features for managing registration for one event, while the Pro version goes one step further and offers advanced functionalities for more complex scenarios.

**In the Free version of RSVP you can::**
* Manage a single event
* [Define the questions that will be used during the RSVP process](https://rsvpproplugin.com/knowledge-base/using-custom-questions/?utm_source=wordpress.org&utm_medium=link&utm_campaign=description&utm_term=Define+questions)
* [Setup email notifications to the event owner and the responding user](https://rsvpproplugin.com/knowledge-base/rsvp-email-notifications/?utm_source=wordpress.org&utm_medium=link&utm_campaign=description&utm_term=Email+notifications)
* Export or Import the RSVP list to CSV

**With the Pro Version you can:**
* Manage multiple events
* [Setup sub-events](https://rsvpproplugin.com/knowledge-base/sub-events/?utm_source=wordpress.org&utm_medium=link&utm_campaign=description&utm_term=Sub+events), for example if you have an event spanning multiple days and you wish to create a separate event on each day
* Provide a [calendar invite for events](https://rsvpproplugin.com/knowledge-base/calendar-invite-download/?utm_source=wordpress.org&utm_medium=link&utm_campaign=description&utm_term=Calendar+invite)
* [Create reminder emails](https://rsvpproplugin.com/knowledge-base/reminder-notifications/?utm_source=wordpress.org&utm_medium=link&utm_campaign=description&utm_term=Reminder+emails) for your attendees
* [Generate QR Codes](https://rsvpproplugin.com/knowledge-base/qr-code-tickets/?utm_source=wordpress.org&utm_medium=link&utm_campaign=description&utm_term=QR+codes)
* Record event attendance with a [check-in functionality](https://rsvpproplugin.com/knowledge-base/setup-checking-attendees-rsvp-pro-plugin/?utm_source=wordpress.org&utm_medium=link&utm_campaign=description&utm_term=Check+in)
* Display [attendees list in the front-end](https://rsvpproplugin.com/knowledge-base/public-attendee-lists/?utm_source=wordpress.org&utm_medium=link&utm_campaign=description&utm_term=Display+attendees)
* [Setup a Waiting list](https://rsvpproplugin.com/knowledge-base/waitlist/?utm_source=wordpress.org&utm_medium=link&utm_campaign=description&utm_term=Waiting+list) for your events
* Receive priority support

== Installation ==

1. Go to Plugins > Add New.
1. Search for "rsvp".
1. Click "Install Now".

OR

1. Download the zip file.
1. Upload the zip file via Plugins > Add New > Upload.

Activate the plugin. Look for "RSVP" in the admin menu.

== Frequently Asked Questions ==

= What is the difference between the free and pro versions? =
The free version includes basic RSVP features for a single event, while the Pro version offers advanced functionalities such as multiple event management, attendance recording, and a better interaction with attendees through notifications and reminders.

= How can I upgrade to the Pro version? =
You can upgrade to the Pro version by [purchasing a license](https://rsvpproplugin.com/pricing/?utm_source=wordpress.org&utm_medium=link&utm_campaign=description&utm_term=purchase+license) from our website, then folowing the [instructions provided in this article](https://rsvpproplugin.com/knowledge-base/migrating-from-free-to-pro/?utm_source=wordpress.org&utm_medium=link&utm_campaign=description&utm_term=upgrade+to+pro).

= How do I allow users to RSVP? =
This can be easily done by adding the [rsvp] shortcode to one of your pages.

= Can users register to the event themselves? =
Yes, with the help of the [Open Registrations option](https://rsvpproplugin.com/knowledge-base/using-open-registrations/?utm_source=wordpress.org&utm_medium=link&utm_campaign=description&utm_term=open+registration).

= Can I Prefill Attendee Information? =
Yes, go to the page associated with the RSVP form and add to the query string the following parameters.

* firstName – For the person's first name
* lastName – For the person's last name
* passcode – If passcode is enabled and/or required this will need to be added as well

For example if you have a page that is /rsvp for domain example.com your URL might look like – http://www.example.com/rsvp?firstName=test&lastName=test

== Screenshots ==

1. What a list of attendees looks like
1. The options page
1. The text you need to add for the rsvp front-end

== Changelog ==
= 2.7.13 – 18.09.2023 =
* Changed: Email address is now required to rsvp if there are several guest with the same name. ( [#69](https://github.com/WPChill/rsvp/issues/69) )
* Changed: Settings checkboxes transformed into toggles ( [#64](https://github.com/WPChill/rsvp/issues/64) )
* Fixed: Hidden radio/checkbox inputs with Go theme by GoDaddy ( [#57](https://github.com/WPChill/rsvp/issues/57) )

= 2.7.12 – 13.06.2023 =
* Fixed: Add max length to short answer ( [#61](https://github.com/WPChill/rsvp/issues/61) )

= 2.7.11 – 16.02.2023 =
* Added: Classes to h3 tags ( [#85](https://github.com/WPChill/rsvp/issues/85) )
* Fixed: Custom question saving ( [#84](https://github.com/WPChill/rsvp/issues/84) )

You can read the complete changelog [here](https://github.com/WPChill/rsvp/blob/master/changelog.txt)

== Upgrade Notice ==
= 2.7.13 =
– Changes to the RSVP process when multiple users have the same name, settings checkbox tranformed into toggles and fixed the hidden radio/checkbox issue with Go Theme by GoDaddy.