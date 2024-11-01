=== SimpleForm Contact Form Submissions ===
Contributors: simpleform, gianpic
Donate link: paypal.me/simpleformdonation
Tags: contact form, emails manager, comments, database, simpleform addon
Requires at least: 5.9
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 2.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A data storage plugin created specifically for SimpleForm. It allows you to save data in the database, and manage the messages from the dashboard.

== Description ==

Thanks to this lightweight plugin, you can view all data sent through the contact form in a sortable table form, or retrieve only data you need from the WordPress database by using the date filter or the search box. You can permanently delete the messages, move the messages to the trash or restore them from the trash. You can mark messages as spam, as unread or answered. Select columns and pagination from “Screen Options”. No configuration required. Once activated, you will find the new “Data Storing” option in the Settings page. Make sure to keep this option enabled, and you no longer need to worry about losing important messages, since each new form submission will be stored in your WordPress database!

For more detailed information refer to the [SimpleForm](https://wordpress.org/plugins/simpleform/) plugin page.

https://www.youtube.com/watch?v=38lNMqAFf4s&rel=0

In the video you see some features referring to SimpleForm 2.0 version that will be released soon!

== Installation ==

Activating the SimpleForm Contact Form Submissions plugin is just like any other plugin.

= Using the WordPress Dashboard =

1. Navigate to “Add New” in the plugins dashboard
2. Search for “SimpleForm Contact Form Submissions”
3. Click “Install Now”
4. Activate the plugin in the Plugin dashboard

= Uploading into WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select “simpleform-submissions.zip” in your computer
4. Click “Install Now”
5. Activate the plugin in the Plugin dashboard

= Using FTP =

1. Download “simpleform-submissions.zip”
2. Extract the “simpleform-submissions” directory in your computer
3. Upload the “simpleform-submissions” directory to the /wp-content/plugins/ directory
4. Activate the plugin in the Plugin dashboard

== Frequently Asked Questions ==

= Can I install this plugin without SimpleForm? =

No. You cannot. You need SimpleForm to activate this plugin.

= Why is this feature not integrated into the SimpleForm plugin? =

We have designed SimpleForm to be a minimal, lightweight, fast and privacy-respectful plugin, and we want to keep it that way. You can choose to enable this feature at any time, and with a single click, depending on your needs.

= Where can I check my form submissions? =

Make sure you have selected the “Data Storing” option in the Settings page within the General tab, then open the Submissions page.

= How can I disable storing submissions to the WordPress database? =

Go to the Settings page within the General tab and uncheck the “Data Storing” option. The submissions list in the dashboard will be removed. User data storing will be disabled, and this data will be included only within the notification email.

= Can I disable only the IP address storing? =

Of course. You can select this option in the Settings page within the General tab.

= How to make SimpleForm meet the GDPR conditions? = 

This plugin stores personal data collected through the contact form. Enable the “Consent Field” in the Form Editor page and make it a required field for requesting the user’s explicit consent. 

= What is the name of the table where the data is stored? = 

The table name is "wp_sform_submissions", but if you changed your WordPress MySQL table prefix from the default "wp_" to something else, this table will also have that prefix.

== Screenshots ==

1. Settings page
2. Submissions page
3. Submitted message page
4. Consent field for GDPR compliance

== Changelog ==

= 2.1.0 (25 July 2024) =
* Changed: code refactoring
* Fixed: general errors
* Added: entry status column

= 2.0.2 (30 March 2022) =
* Fixed: PHP error while deactivating the plugin

= 2.0.1 (29 March 2022) =
* Fixed: the line breaks are preserved in the message
* Fixed: backslashes removed in the message
* Fixed: incorrect display of the number of entries when entries have been moved
* Fixed: incorrect displaying of form status
* Fixed: deprecated jQuery functions warning
* Changed: restyling of entry data page
* Changed: last entries data are recovered upon the plugin activation

= 2.0 (25 January 2022) =
* Changed: minor improvements
* Changed: message data page
* Added: compatibility with SimpleForm 2.1 version

= 1.6.5 (12 August 2021) =
* Fixed: admin styles issues
* Fixed: undefined index errors
* Added: option for showing a mailto button to activate the default mail program for sending a reply
* Added: button to permanently remove a form from forms list

= 1.6.4 (2 June 2021) =
* Changed: code cleaning
* Changed: minor improvements
* Fixed: failure to open the message page when the form data storage option has been disabled for the main form
* Fixed: compatibility with SimpleForm 2.0 version
* Fixed: unread count option styling issue

= 1.6.3 (24 May 2021) =
* Added: compatibility with SimpleForm 2.0 version

= 1.6.2 (12 April 2021) =
* Fixed: PHP error while deactivating the plugin in a WordPress multisite network
* Fixed: inaccuracy of notification bubble in the editor page and settings page
* Fixed: inaccuracy in the counting of messages when data storing option is disabled
* Fixed: loss of settings while deactivating the plugin
* Added: option for deleting messages from the database when uninstalling the plugin
* Added: support link in the plugins page

= 1.6.1 (7 April 2021) =
* Fixed: PHP error while deactivating the plugin
* Fixed: typo errors

= 1.6 (7 April 2021) =
* Added: answered messages view
* Added: status option in the submitted message page

= 1.5.1 (22 March 2021) =
* Changed: code cleaning
* Fixed: failure to perform a bulk action if selected from the dropdown below the table
* Fixed: failure to perform the deletion action when the junk view is selected

= 1.5 (19 January 2021) =
* Fixed: typo errors
* Added: junk view for the submissions
* Added: compatibility with SimpleForm Akismet plugin

= 1.4.4 (04 December 2020) =
* Changed: activation function
* Changed: plugin is no longer deactivated if core plugin is missing

= 1.4.3 (26 November 2020) =
* Fixed: database update error on updating
* Fixed: notification bubble error

= 1.4.2 (26 November 2020) =
* Added: compatibility with SimpleForm 1.10 version

= 1.4.1 (16 November 2020) =
* Changed: minor issues in code
* Fixed: error during plugin deactivation if SimpleForm plugin is missing
* Fixed: error during messages status update if plugin is already active

= 1.4 (14 November 2020) =
* Changed: code cleaning
* Added: unread view for the submissions
* Added: option for change the columns that must be displayed in the submissions table
* Added: option for add a notification bubble to contacts menu item for unread messages
* Added: action links in the plugins page

= 1.3.3 (19 September 2020) =
* Fixed: error loading the language packs for translation

= 1.3.2 (18 September 2020) =
* Fixed: error during saving settings

= 1.3.1 (11 August 2020) =
* Fixed: security issue
* Fixed: typo errors

= 1.3 (27 June 2020) =
* Added: trash status and related view for the submissions list
* Fixed: pagination error when the number of submissions per page is changed

= 1.2.1 (10 May 2020) =
* Fixed: database update error on updating
* Fixed: minor issues in code

= 1.2 (1 May 2020) =
* Added: date filter
* Fixed: minor issues in code

= 1.1.4 (11 April 2020) =
* Fixed: search for last name and phone missing
* Fixed: unexpected output error during plugin activation
 
= 1.1.3 (1 April 2020) =
* Fixed: database update error on updating

= 1.1.2 (31 March 2020) =
* Fixed: database update errors
* Fixed: undefined index errors

= 1.1.1 (27 March 2020) =
* Added: compatibility with SimpleForm 1.5 version
* Fixed: SQL injection vulnerability issues
* Fixed: minor issues in code

= 1.1 (15 February 2020) =
* Added: search box

= 1.0 (15 January 2020) =
* Initial release

[See changelog for all versions](https://plugins.svn.wordpress.org/simpleform-contact-form-submissions/trunk/changelog.txt).

== Upgrade Notice ==

This version requires SimpleForm version 2.2.0 or greater installed. If you have not yet done so, please update SimpleForm to make it work properly!

== Demo ==
 
You don’t know SimpleForm yet? Check out our [Demo]() and find out how it works.

== Credits ==
 
We wish to thank everyone who has contributed to SimpleForm Contact Form Submissions translations. We really appreciate your time, your help and your suggestions.

[See all contributors]().