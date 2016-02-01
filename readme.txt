=== RSVP and Event Management Plugin ===
Contributors: mdedev
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=mikede%40mde%2ddev%2ecom&lc=US&item_name=Wordpress%20RSVP%20Plugin&no_note=0&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest
Tags: rsvp, reserve, wedding, guestlist, attendee management, event management, event planning, wedding planning, event registration, event, events, events management, events registration
Requires at least: 3.0
Tested up to: 4.4.1
Stable tag: 2.1.4

Simple and easy to use RSVP and event management plugin to handle registrations for a single event. 

== Description ==

The <a target="_new" href="https://www.rsvpproplugin.com?utm_source=wordpress.com&utm_medium=referral">RSVP plugin</a> was created for events to make RSVP'ing easy as possible for guests. 

= Features of the Free Version = 
* Easy to customize text
* No need to know a passcode to RSVP 
 * Ability to enable a passcode if desired
* Easy attendee management
* Relate attendees to make it easier for groups and families to RSVP together 
* Email notifications 
* Custom questions 
* Import/export attendees
* Open or set attendee list registration
* Ability for +1s
* Much, much more 

> <strong>Support</strong><br />
> Currently I do not monitor the forums for issues. However, if you need help or have found a bug feel free to get in touch with me via email  at mikede@mde-dev.com and I will be happy to help out where I can. 

= Additional Features in the <a target="_new" href="https://www.rsvpproplugin.com/">Premium Version</a> = 
* Multiple events
* Even more text customizations
* Public attendee lists 
* Ability to send mass emails 
* More info at <a target="_new" href="https://www.rsvpproplugin.com"/>https://rsvpproplugin.com</a>
* <a target="_new" href="http://rsvpprodemo.swimordiesoftware.com">Demo</a>

== Installation ==

1. Upload the `rsvp` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Add and/or import your attendees to the plugin and set the options you want
1. Create a blank page and add [rsvp] on this page

== Frequently Asked Questions ==

= Why can't this plugin do X? =

Good question, maybe I didn't think about having this feature or didn't feel anyone would use it.  Contact me at mike AT mde DASH dev.com and 
I will see if I can get it added for you.  

= Can I style the plugin? = 

Yes. Below are the styles for the plugin:

* rsvpPlugin - ID of the main RSVP Container. Each RSVP step will be wrapped in this container 
* rsvpParagraph - Class name that is used for all paragraph tags on the front end portion of the RSVP
* rsvpFormField - Class for divs that surround a given form input, which is a combination of a label and at least one form input (could be multiple form inputs)
* rsvpAdditionalAttendee - Class for the div container that holds each additional RSVP attendee you are associated with
* additionalRsvpContainer - The container that holds the plus sign that allows for people to add additional attendees
* rsvpCustomGreeting - ID for the custom greeting div that shows up if that option is enabled
* rsvpBorderTop - Class for setting a top border on certain divs in the main input form
* rsvpCheckboxCustomQ - Class for the div that surrounds each custom question checkbox 
* rsvpClear - A class for div elements that we want to use to set clear both. Currently used only next to rsvpCheckboxCustomQs as they are floated
* rsvpAdditionalAttendeeQuestions - Class name for the div that wraps around all additional attendee questions
* rsvpCustomQuestions - Class name for the div that wraps around all custom questions for each attendee

= I need help = 

I am here to help, contact me at mike AT mde DASH dev.com

= Is there a GitHub Repository? = 

Yes, that is where the main development is done - https://github.com/medelibero/Wordpress-RSVP-Plugin/

= Can I Prefill Attendee Information? = 

Yes, go to the page associated with the RSVP form and add to the querystring the following parameters.

* firstName - For the person's first name
* lastName - For the person's last name
* passcode - If passcode is enabled and/or required this will need to be added as well 

For example if you have a page that is /rsvp for domain example.com your URL might look like - http://www.example.com/rsvp?firstName=test&lastName=test 

== Screenshots ==

1. What a list of attendees looks like
1. The options page
1. The text you need to add for the rsvp front-end

== Changelog ==

= 2.1.4 = 
* Fixed a bug where if the email field was hidden and a new attendee was added it would not save 
* Added the ability to delete the database tables and options when uninstalling

= 2.1.3 = 
* Resurfaced the note field on the admin attendee list screen

= 2.1.2 = 
* Incremented the tested version to 4.3.0
* Surfaced the date when an attendee RSVP'd

= 2.1.1 = 
* Added in a Czech translation, thanks to Radek Strnad for providing the translation.

= 2.1.0 = 
* Fixed an issue with the import process not handling unicode characters when trying to associate attendees

= 2.0.9 = 
* Small fix to correct the problem of additional attendees having slashes in front of apostrophes on the front-end.

= 2.0.8 = 
* Added in a fix for first and last name searching to work better with different naming schemes like Norwegian names, fix provided by Richard Mikalsen

= 2.0.7 = 
* Spanish typo fix contributed by Andres Gomez
* Added a Norwegian translation supplied by Richard Mikalsen

= 2.0.6 = 
* Fixed a problem with the JavaScript change in 2.0.5 custom questions were not showing up.

= 2.0.5 = 
* Fixed a problem where on some themes the JavaScript would stop working correctly on the front end.
* Renamed to the Finnish translation to the more common locale code, thanks to Andres Gomez for this fix
* Small text change to allow for localization, thanks to Henrik Palm for this fix

= 2.0.4 = 
* Fixed a problem when exporting that the sprintf parameters wouldn't always match

= 2.0.3 = 
* Added some more front-end styling to deal with themes making the RSVP form unusable in some cases
* Added an option to disable user searching when a user is not found on the RSVP form

= 2.0.2 = 
* Fixed a small bug that happens in the JavaScript when you have a custom question that has a pipe (|) in it

= 2.0.1 = 
* A few translation bug fixes and a Spanish and Finish translation submitted by Andres Gomez Garcia!

= 2.0.0 = 
* Made the multi-selects easier to use

= 1.9.9 = 
* Added some basic styling to the front-end to try and prevent themes from hiding form elements
* Made it so you can pass in the passcode in the querystring when the passcode only option is enabled
* Added in a Dutch translation of the RSVP plugin, thanks to Marijn Roukensfor providing the translation!

= 1.9.8 =
* Replaced mysql_real_escape with Wordpress specific escaping methods
* Removed all uses of $_SESSION variables
* Added all custom questions for associated attendees to email notifications

= 1.9.7 = 
* Tested the plugin on 4.1.0 
* Added in a French translation of the RSVP plugin, thanks to Beno”t Quentin for providing the translation!

= 1.9.6 = 
* Added in a German translation of the RSVP plugin, thanks to Gernot Weber for providing the translation!

= 1.9.5 = 
* Fixed a bug when emailing people in certain cases it did not have all of the information it was looking for. Thanks to Guillaume De Smedt for pointing out the problem. 

= 1.9.4 = 
* Added the ability to skip the first step if the querystring parameters existed for the form values. 

= 1.9.3 = 
* Added shortcode [rsvp]

= 1.9.2 = 
* Fixed a bug that I introduced with the URL change in 1.9.1

= 1.9.1 = 
* Fixed some internationalization errors pointed out by Alberto Manfrinati
* Changed the URL code to not use the $_SERVER variable as sometimes that does not match with the blog's domain

= 1.9.0 = 
* Fixed an issue where if you hide the email address the next time a person goes to RSVP their email address gets removed
* Added an option to add some custom text to the email sent to attendees
* Changed the editing of RSVPs so even if someone has RSVP'd you can edit their entry if they are associated with the person

= 1.8.9 = 
* Tested to make sure the plugin worked on WordPress 4.0.0
* Fixed a bug where you couldn't deselect an attendee
* Modified the not found text to show something different if we are only asking for a password, thanks to Chris Moncayo for the patch
* Modified the text for the first step of the RSVP, thanks to Chris Moncay for the patch

= 1.8.8 = 
* Fixed a bug with storing answers to custom questions, was using a comma but that interferred with how some answers were written. Will require saving questions again.

= 1.8.7 = 
* Fixed a bug in the import process where non-ASCII characters were not being properly imported.

= 1.8.6 = 
* Added in an option to only show a passcode for when a guest rsvps. When this option is on the passcode has to be unique.
* Fixed a bug where custom questions would not show up in the correct order when viewing the guest list in the admin area. 
* Added all the other information along with custom questions to the emails that are sent to the email specified in the notification email setting.

= 1.8.5 = 
* Added in an option to disable not have guest emails appear to be from the email specified in the notification option. As some hosts will not send out an email with a custom from header. 

= 1.8.4 = 
* Fixed a stupid bug where the option to hide the email address didn't do it everywhere.

= 1.8.3 =
* Modified the guest emails so that it would appear from the email specified in the option.
* Added the RSVP Options page as a sub-menu item under RSVP Plugin
* Added in an option to hide the email address

= 1.8.2 =
* Fixed a bug with the new email field that made it so guests couldn't be added

= 1.8.1 = 
* Tested the plugin against Wordpress 3.9
* Added in the option to send a confirmation to the person RSVP'ing. Go to Settings -> RSVP Options and select "Send email to main guest when they RSVP". Thanks to Reydel Leon for the code contributions.
* Added in an option to specify the number of additional guests a person can add

= 1.8.0 = 
* Added in rsvpCustomQuestions as a div container for custom questions to help with styling
* Added in a fix to stop auto newlines from paragraphs from happening on older versions of Wordpress. I remove wpautop from the_content filter when RSVP content is found. 

= 1.7.9 = 
* Fixed an issue reported by a user where the private custom questions did not get included with a new additional attendee

= 1.7.8 = 
* Changed it so the first and last name are displayed in the question "Will X be attending?" where X is the person's name. It used to only display the person's first name. 

= 1.7.7 = 
* Small layout bugfixes that were introduced in 1.7.6
* Removed a warning reported by a user

= 1.7.6 = 
* Changed the questions for additional people to have paragraph tags wrapped around them. 
* Added the option to remove a guest you just tried to add

= 1.7.5 = 
* Changed how RSVP'ing for associated guests work. You no longer have to select the checkbox but just select the RSVP option (yes or no) for the guest and it will assume you are RSVP'ing for the guest. 
* Changed the default thank you message so it now has the main person's name for RSVP'ing and any additional people that were RSVP'd.
* Made it possible to associate users with private question on import. See the import page for more details.

= 1.7.4 = 
* Fixed an issue with the table sorting for custom questions not working correctly
* Added an the option to change the verbiage for the add additional guest question
* When the email notification is sent now the associated attendees statuses are sent as well

= 1.7.3 = 
* Fixed some issues so notices would not surface
* Fixed some admin encoding issues, fix submitted by David Dinmamma
* Turned off autocomplete on the main RSVP form so people can't see who else has registered from that computer

= 1.7.2 = 
* Improved the queries to find related attendees when a person RSVPs
* Incorporated an SSL check fix found at http://wordpress.org/support/topic/https-problem-1
* Fixed an issue with a warning for an insert query. Notified by Chase Nimmer. 
* Changed the passcode field to a password and made it so was not set to autocomplete for browsers.
* Added in a div container for additional attendee questions so JavaScript modifications could be done.
* Added in an option to not scroll down to the RSVP form when a form post happened.

= 1.7.0 =
* Added the ability to do open registrations
* Added an anchor to the RSVP form to make it work better for single-page designs

= 1.6.5 =
* Fixed another bug so that the RSVP form works with more single page layouts
* Fixed some layout issues related to whitespace in the output
* Added an option to hide the note field from the front-end
* Added passcodes to the export
* Added passcodes to the import 
* Made it so all of the front-end text was translatable 

= 1.6.2 = 
* Fixed a bug so that the rsvp form works with single page layouts
* Fixed a bug where the passcode was not being checked. Thanks to Jency Rijckoort for reporting the issue. 

= 1.6.1 = 
* Fixed a bug with the jQuery validate library that was causing an error with the 3.5.0 media manager. Thanks to Topher Simon for reporting the issue. 

= 1.6.0 =
* Added in internationalization for the front-end piece of the RSVP plugin
* Changed the front-end layout from a table based structure to more divs with classes to be used for styling
* Added in sytling
* Moved some CSS and JavaScript to separate files instead of being included inline

= 1.5.0 =
* Made it so the plugin would only replace the plugin short code and not all of the page's content
* Changed it so when the site is running over SSL the included javascript files would also be loaded over SSL
* Removed deprecated calls to session_unregister so it would work correct on PHP 5.4 and above
* Changed it so on new installs of RSVP fields that have free-form text will always be UTF-8 to minimize issues with unicode characters

= 1.4.1 = 
* Fixed a bug where the passcode field would not always get created when upgrading.  This caused the attendee list to now display in the admin area
* Also added some finishing touches to the passcode feature as it was released a little bit too soon

= 1.4.0 = 
* Added in the option to require a passcode when RSVPing. 

= 1.3.2 =
* Added in the option to change the "welcome" text
* Added in the option to change the "So, how about it?" text
* Fixed an issue with some MySql installations choking on the note field not having a default value

= 1.3.1 =
* Added in a debug option to help identify issues with queries saving to the database
* Changed how the scripts and stylesheets get added so there would be less conflicts with themes

= 1.3 =
* Made it so custom questions showed up on the attendee list page
* Added in a radio button as a custom question type
* Changed the RSVP notification email to include the RSVP status
* Fixed an issue with when searching for people with an apostrophe in it, it would display with the added escaping. Made sure to remove the escaping.  
* Added in the veggie and kids meal total count to the list of attendees in the admin area
* Made it so admins can change the RSVP status
* Fixed an issue with international characters not being displayed correctly on both the admin and public areas of the plugin

= 1.2.1 =
* Fixed a bug that was causing an error on activation for people with servers that did not have short open tags configured

= 1.2.0 =
* Fixed a bug in the adding of additional guests when there are custom questions
* Added the ability to have a question be public or private. If a question is marked as private then only the selected attendees will be able to answer the question

= 1.1.0 =
* Tested the plugin on 3.0.0
* Added in the ability to sort custom questions
* Fixed an issue where you could not mass delete custom questions

= 1.0.0 =
* Removed some default text that pointed to my wedding site, doh.
* Created the ability to not allow additional attendees be added
* Created the ability to be notified via email whenever someone rsvps
* Added the ability to specify custom questions for each rsvp.  

= 0.9.5 =
* Fixed a major bug that would not create the correct sql tables during initial install, thanks to everyone for letting me know. 

= 0.9.0 = 
* Fixed the options page so it works in MU switched from the old options way with using the newer methods that are only for 2.7+.
* Added the option of custom messages for each attendee. 
* Small bug-fixed and code refactoring that I noticed while testing.

= 0.8.0 =
* Did better variable checking on the sorting functions, as warning could show up depending on the server configuration.
* Fixed an issue with the checkbox selector in the attendee list not working in Wordpress 2.9.2
* Added an export button to the attendee list.  When clicking this button the list will export in the same sorting order as the list
* Added the ability to associate attendees on import
* Added in checking when importing so names that already exist don't get imported
* Fixed a warning when session variables were not created on the front-end greeting page

= 0.7.0 =
* Fixed a bug reported by Andrew Moore where when adding a new attendee and an option to hiding an answer the answer would still be visible

= 0.6.0 =
* Fixed a bug reported by Andrew Moore in the import feature that would not allow most files from being uploaded, doh!
* Fixed a few other small warnings and gotchas (also reported by Andrew Moore)

= 0.5.0 =
* Initial release

== Upgrade Notice ==
* To upgrade from 0.5.0 to 0.6.0 just re-upload all of the files and you should be good to go.  Really the only change was to wp-rsvp.php so uploading this changed file is all that is needed.  
* To upgrade to 0.9.0 at minimum copy over wp-rsvp.php and rsvp_frontend.inc.php and go to the attendeees list.  Preferably deactive and reactivate the plugin so it is for sure that the database changes happen. 
* To upgrade to 1.0.0 at minimum copy over wp-rsvp.php and rsvp_frontend.inc.php and deactive and reactivate the plugin to get the latest database changes.  