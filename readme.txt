=== Mailto Link Form ===
Contributors: cum38898
Tags: mailto, form, shortcode
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Build configurable forms that redirect to a mailto URL.

== Description ==

Mailto Link Form lets you build contact forms that continue in the visitor's email app.

Key features:
* Create and manage multiple forms in wp-admin.
* Configure To, Subject, and Body Template per form.
* Map `<select>` values into body placeholders such as `{{your_field_key}}`.
* Embed forms anywhere with a shortcode.

This plugin does not send email directly from your server.
It generates a `mailto:` URL and opens the user's default email app.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin through the "Plugins" screen in WordPress.

== Usage ==

1. Go to Mailto Forms and create a form.
2. Configure recipient email, subject, body template, and select fields.
3. Place shortcode like `[mailto_link_form id="123"]` into a page.

== Screenshots ==

1. Mailto Form edit screen (Form Settings and Mail Settings).
2. Placeholder mapping in Body Template.
3. Frontend form rendered from shortcode.

== Frequently Asked Questions ==

= Can this plugin send email directly? =

No. This plugin builds a `mailto:` URL and opens the user's default mail client.

= The email app did not open. What should I do? =

Check your default email app settings in your browser/OS.
If needed, copy the form details and create the email manually.

== Changelog ==

= 0.1.2 =
* Move frontend inline script to enqueued external file.

= 0.1.1 =
* Fix plugin-specific prefix.

= 0.1.0 =
* Initial functional release.
