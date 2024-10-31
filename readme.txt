=== Notify Unconfirmed Subscribers ===
Contributors: keithdsouza
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=keithdsouza@msn.com&currency_code=&amount=&return=&item_name=WordPress+Plugin+Development+Donation+NUS
Tags: feed, rss, atom, pause feeds
Requires at least: 2.3
Tested up to: 2.9.2
Stable tag: 1.3.1

Notify Unconfirmed Subscribers allows users to notify unconfirmed subscribers from FeedBurner email subscriptions.

== Description ==

Important: Version 1.3.1 is an important security update, earlier the file could be directly accessed, now added changes to make sure it is only accessed through WordPress Admin.

Please delete the plugin directory and add the new one if you are upgrading from an older version to 1.3.0. The admin dashboard plugin update will do this automatically for you.

Starting with version 1.3.0, support has been dropped for Old FeedBurner accounts. Please migrate your feeds to a Google Account before using this. See changelog for latest updates and notes about recent fixes.

v1.3.0 will only work with cURL enabled websites for now, will be adding support for other http modes in future.


Many blogs offer email subscriptions to their users, however in case of feedburner the users have to confirm their email address before they can start getting updates from the blog. In many cases, users either forget or do not know that they have to confirm their email address.

Notify Unconfirmed Subscribers aims to solve a problem form blog owners by fetching the uncofirmed email subscribers from their Feed burner account and allows them to send a short message asking them to confirm their email accounts.

NUS does not store any username / password details you provide with for fetching the uncofirmed users from Feedburner account.

NUS is compatible with both Feedburner.com and Google Feedburner accounts.


[Support](http://forum.techie-buzz.com/forum.php?id=6&page) |
[Plugin Release Page](http://techie-buzz.com/wordpress-plugins/notify-unconfirmed-subscribers-plugin-release.html) |
[Plugin Update Page](http://techie-buzz.com/wordpress-plugins/notify-unconfirmed-subscribers-google-feedburner-compatibility.html)
.

== Installation ==

To do a new installation of the plugin, please follow these steps

1. Download the notify-unconfirmed-subscribers.zip file to your local machine.
2. Unzip the file 
3. Upload `notify-unconfirmed-subscribers` folder to the `/wp-content/plugins/` directory
4. Activate the Notify Unconfirmed Subscribers plugin through the 'Plugins' menu in WordPress

If you have already installed the plugin

1. De-activate the plugin
2. Download the latest files
2. Follow the new installation steps

== Changelog ==

= 1.3.1 =
- Version 1.3.1 is an important security update, earlier the file could be directly accessed, now added changes to make sure it is only accessed through WordPress Admin.

= 1.3.0 =
- Dropped support for Old FeedBurner accounts
- Rewrote the module to fix issues with cookies 
- Updated code for better maintenance
- Dropped unwanted modules from the code

= 1.2.3 =
Added supported tags for email message body in sending message page.

= 1.2.2 =
Fixed the formatting of the email message sent out to users.

= 1.2.1 =
* Added support for Google Feedburner accounts

= 1.0 =
* Initial release.

== Frequently Asked Questions ==

Please read the **[Detailed Plugin Information Page](http://techie-buzz.com/wordpress-plugins/notify-unconfirmed-subscribers-plugin-release.html)** and **[Plugin Update Page](http://techie-buzz.com/wordpress-plugins/notify-unconfirmed-subscribers-google-feedburner-compatibility.html)**