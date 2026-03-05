# WordPress.org Submission Checklist

## Before first submit
- [ ] Replace `Contributors:` in `readme.txt` with your WordPress.org username.
- [ ] Replace `Author:` in `mailto-link-form.php` with your public author name.
- [ ] Ensure plugin slug is stable: `mailto-link-form`.
- [ ] Ensure `Version` in plugin header and `Stable tag` in `readme.txt` match.
- [ ] Rebuild release ZIP: `npm run release:zip`.

## Security and guideline checks
- [ ] Install and run **Plugin Check** on a local WP site and resolve `Error`/`Warning` items.
- [ ] Verify nonce checks on all form handlers.
- [ ] Verify capability checks on all admin-side writes.
- [ ] Verify sanitize on input and escape on output.
- [ ] Verify no external requests are made without explicit disclosure.

## Readme and assets
- [ ] Confirm `Requires at least`, `Tested up to`, `Requires PHP` are accurate.
- [ ] Add plugin assets for WordPress.org (`icon-128x128.png`, `icon-256x256.png`, optional banners/screenshots).
- [ ] Confirm description, FAQ, and changelog are user-facing and concise.

## Submission process
- [ ] Submit via https://wordpress.org/plugins/developers/add/
- [ ] After approval, commit code to provided SVN repository (`trunk/`, `assets/`, `tags/`).
- [ ] Tag release in SVN matching your plugin version.
