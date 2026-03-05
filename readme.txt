=== Mailto Link Form ===
Contributors: cum38898
Tags: mailto, form, shortcode
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Build configurable forms that redirect to a mailto URL.

== Description ==

Create multiple mailto forms in wp-admin and embed each form via shortcode.
Each field value is merged into a configurable body template using placeholders like {{field_key}}.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin through the "Plugins" screen in WordPress.

== Usage ==

1. Go to Mailto Forms and create a form.
2. Configure recipient email, subject, body template, and select fields.
3. Place shortcode like `[mailto_link_form id="123"]` into a page.

== Frequently Asked Questions ==

= Can this plugin send email directly? =

No. This plugin builds a `mailto:` URL and opens the user's default mail client.


== Development ==

Local development uses plain Docker commands (no `wp-env` / `wp-playground-cli`).

1. Make sure Docker Desktop is running.
2. Install dependencies: `npm install`
3. Start local env: `npm run dev:start`
4. Open site: `http://localhost:8888`
5. Open wp-admin: `http://localhost:8888/wp-admin`
6. Login:
   - user: `admin`
   - password: `password`
7. Stop env: `npm run dev:stop`

Useful commands:
- `npm run docker:logs` to follow WordPress logs.
- `npm run wp:cli` to verify WP-CLI with the same Docker network/volumes.
- `npm run dev:reset` to remove DB/site volumes and start fresh.
- `npm run release:zip` to build a distributable plugin zip in `dist/`.

== Changelog ==

= 0.1.0 =
* Initial functional release.
