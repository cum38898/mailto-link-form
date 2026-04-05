# WordPress.org Assets Guide (for mailto-link-form)

## 1) Plugin directory assets (SVN `assets/`)
Place these files in the plugin directory SVN `assets/` folder (not in plugin ZIP):

- `icon-128x128.png`
- `icon-256x256.png`
- `banner-772x250.png` (optional)
- `banner-1544x500.png` (optional, retina)

Recommended image style:
- Clean, high-contrast icon for small sizes.
- Banner text should stay short and readable.
- Avoid tiny UI text in banners.

## 2) Screenshots for WordPress.org (SVN `assets/`)
Keep screenshots in this plugin repo `mailto-link-form-assets/` folder.
These files are uploaded to WordPress.org SVN `assets/` and are not part of plugin runtime code:

- `screenshot-1.png`
- `screenshot-2.png`
- `screenshot-3.png`

Current readme screenshot mapping:
1. Mailto Form edit screen (Form Settings and Mail Settings).
2. Placeholder mapping in Body Template.
3. Frontend form rendered from shortcode.

## 3) Short description candidates
Use one concise line in readme header area:

- "Create mailto forms that continue in the visitor's email app."
- "Build configurable mailto forms with placeholders and shortcode embedding."

## 4) Submission-ready checklist
- [ ] Add icon files (`128`, `256`).
- [ ] Add banner files (`772x250`, optional `1544x500`).
- [ ] Add screenshots (`screenshot-1..3`) to `mailto-link-form-assets/` matching readme captions.
- [ ] Confirm screenshots do not expose private data.
- [ ] Rebuild ZIP: `npm run release:zip`.
