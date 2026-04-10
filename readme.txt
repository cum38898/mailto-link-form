=== Mailto Link Form ===
Contributors: cum38898
Tags: mailto, form, shortcode
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Build configurable mailto forms with select, textarea, text, and checkbox fields.

== Description ==

Mailto Link Form lets you build contact forms that continue in the visitor's email app.

Key features:
* Create and manage multiple forms in wp-admin.
* Configure To, Subject, and Body Template per form.
* Mix `<select>`, `<textarea>`, `<input type="text">`, and `<input type="checkbox">` items in one form.
* Map item values into Subject and Body placeholders such as `{{your_field_key}}`.
* Use Unicode item keys, including Japanese keys such as `{{問い合わせ種別}}`.
* Save partially configured items in wp-admin without losing unfinished input.
* Render only valid frontend items while keeping incomplete rows in the editor.
* Embed forms anywhere with a shortcode.

This plugin does not send email directly from your server.
It generates a `mailto:` URL and opens the user's default email app.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin through the "Plugins" screen in WordPress.

== Usage ==

1. Go to Mailto Forms and create a form.
2. Configure recipient email, subject, body template, submit button label, and help text.
3. Add items as needed with the item table and choose each row type.
4. Use placeholders like `{{company}}` or `{{問い合わせ種別}}` in Subject and Body Template.
5. Place shortcode like `[mailto_link_form id="123"]` into a page.

== Screenshots ==

1. Mailto Form edit screen with the unified item table and mail settings.
2. Subject and Body Template placeholders mapped from configured item keys.
3. Frontend form rendered from shortcode.

== Frequently Asked Questions ==

= Can this plugin send email directly? =

No. This plugin builds a `mailto:` URL and opens the user's default mail client.

= Which field types are supported? =

You can combine `<select>`, `<textarea>`, `<input type="text">`, and `<input type="checkbox">` items in the same form.

= Can I use Japanese or other Unicode item keys? =

Yes. Subject and body placeholders support Unicode keys such as `{{問い合わせ種別}}`.

= Can I save a form before every item is fully configured? =

Yes. The admin screen keeps unfinished rows so you can continue editing later. Only renderable items are shown on the frontend.

= Can the shortcode render only the submit button? =

Yes. If the recipient email is valid and there are no renderable items, the shortcode still renders the submit button.

= The email app did not open. What should I do? =

Check your default email app settings in your browser/OS.
If needed, copy the form details and create the email manually.

== Changelog ==

= 0.2.0 =
* Add mixed item type support for select, textarea, text, and checkbox fields.
* Add Unicode item key support for template placeholders, including Japanese keys.
* Allow subject placeholders as well as body placeholders.
* Keep unfinished admin rows saved while rendering only valid frontend items.
* Show a button-only frontend form when the recipient email is valid.

= 0.1.2 =
* Move frontend inline script to enqueued external file.

= 0.1.1 =
* Fix plugin-specific prefix.

= 0.1.0 =
* Initial functional release.
