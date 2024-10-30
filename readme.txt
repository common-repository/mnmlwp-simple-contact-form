=== Plugin Name ===
Contributors: shonert
Donate link: https://minimalwordpress.com
Tags: contact, form
Requires at least: 4.9
Tested up to: 5.6.1
Requires PHP: 7.1
Stable tag: 0.2.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
 
This plugin provides a simple contact form including shortcode, basic form fields, and spam prevention.

== Description ==
 
This plugin provides a simple contact form including shortcode, basic form fields, spam prevention, and customizer settings.
 
* Name
* E-mail
* E-mail validation
* Message
* Phone (optional)
* Address (optional)
* Pre-defined message text
* Multiple recipients
* HTML5 form field validation
* Success Message (customizer)
* E-mail signature (customizer)
* Auto-reply text (customizer)
* Token preventing multiple submits (customizer)
* Security number field (customizer)
* GDPR checkbox (customizer)
* Translations: EN, DE
 
== Installation ==
 
This section describes how to install the plugin and get it working.

1. Search and install the mnmlWP Simple Contact Form plugin from your WordPress dashboard (Plugins > Add New). Alternatively, upload the plugin zip-file to your plugins directory.
2. Activate the plugin through the 'Plugins' menu in your WordPress dashboard.
3. Configure the contact form customizer options according to your needs.
4. Use the [contact] shortcode with your preferred attributes (cf. Details).

== Screenshots ==

1. Contact form
2. Customizer options

== Frequently Asked Questions ==

= How do I enable the phone/address fields? =
 
You can add the respective fields by adding them as attributes to your shortcode:

[contact phone="1" address="1"]

Please note: you may have to replace the quotes in the example with simple double quotes.

= How can I add some predefined text to the message field? =
 
You can add pre-defined message text by adding it as an attribute to your shortcode:

[contact message="Message can be inserted into the form as an attribute."]

Please note: you may have to replace the quotes in the example with simple double quotes.

= Where do I set the sender/reply e-mail? =

This plugin uses the admin e-mail address of your WordPress website (Settings > General > Email Address).

= How can I send the form to multiple recipients? =

You can use the "mailto" shortcode attribute in order to forward the form to selected email addresses, e.g.

[contact mailto="john.doe@gmail.com,jane.smith@yahoo.com"]

Please note: you may have to replace the quotes in the example with simple double quotes.

= Where can I find a demo of the contact form? =

Please visit [https://minimalwordpress.de/contact/](https://minimalwordpress.de/contact/) to have a look at a demo of the contact form.

= Known Issues =

This plugin optionally uses a token to verify that a form will only be submitted once per click on the submit button. If you experience any difficulties using the plugin you might have to disable the token in the plugin's customizer options (checkbox).

If you need to use the token instead of the default JavaScript solution, simply stop caching the page that displays the contact form. 

If you use the security number field, please also make sure your web server supports PHP sessions.

== Changelog ==

= 0.2.7 =

* Strip slashes
* Updated readme.txt

= 0.2.4 =

readme.txt

= 0.2.3 =

* Removed input field placeholders
* Tested with WP 5.6.1

= 0.2.2 =

Fix: close PHP session

= 0.2.1 =

Tested with WP 5.3

= 0.2.0 =

Tested with WP 5.2.1

= 0.1.9 =

* GDPR checkbox added (customizer)

= 0.1.8 =

* Shortcode attribute "mailto"

= 0.1.7 =

* Minor fixes

= 0.1.6 =

* Success message (customizer)
* Translation fixes

= 0.1.5 =

* Security number field option (customizer)
* Form token option (customizer)
 
= 0.1.4 =

* initial release