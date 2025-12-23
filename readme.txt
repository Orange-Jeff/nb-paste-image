=== NB Paste Image ===
Contributors: orangejeff
Donate link: https://netbound.ca/donate
Tags: paste, clipboard, image, upload, media library, bitmoji, giphy, screenshot
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Paste images directly from clipboard to WordPress. Screenshots, Bitmoji, Giphy, copied images - Ctrl+V and done!

== Description ==

**Stop the download-upload dance!** NB Paste Image lets you paste images directly from your clipboard into WordPress - anywhere in the admin.

Copy an image anywhere → Press Ctrl+V in WordPress → Image is uploaded.

= Works With Everything =

* **Screenshots** - Windows Snipping Tool, macOS Screenshot, any screen capture
* **Bitmoji** - Copy from browser extension or mobile app
* **Giphy** - Right-click and copy GIF
* **Any copied image** - Right-click "Copy Image" from any website
* **Image editors** - Paste from Photoshop, Paint, GIMP, etc.
* **AI-generated images** - Copy previews from DALL-E, Midjourney, etc.
* **Slack/Discord** - Copy images from chat apps
* **Twitter/X** - Copy images from tweets
* **Google Images** - Copy any search result
* **Email attachments** - Copy images from Gmail, Outlook
* **PDF viewers** - Copy images from documents
* **PowerPoint/Keynote** - Copy slides or graphics
* **Canva** - Copy designs directly
* **Figma** - Copy frames or assets

= Three Upload Destinations =

1. **Media Library** - Just save the image for later use
2. **Featured Image** - Set as the post's featured image in one click
3. **Insert in Post** - Paste and insert directly into your content

= How It Works =

**Paste Tab (Clipboard):**
1. Copy any image (screenshot, right-click copy, etc.)
2. Go to any WordPress admin page
3. Press **Ctrl+V** (or click "Paste Image" in admin bar)
4. Add title/alt text (optional)
5. Choose destination: Media Library, Featured Image, or Insert
6. Done!

**Load URL Tab (Full Quality):**
1. Right-click an image → Copy image address (not Copy Image)
2. Open paste zone, switch to "Load URL" tab
3. Paste the URL and click Load
4. Full-quality image downloads and previews
5. Choose destination and upload!

*Why Load URL? Copied images are often thumbnails. The URL gets you the full-res original.*

= Features =

* **Two input modes** - Paste from clipboard OR load from URL
* **Full-quality downloads** - URL mode fetches original, not thumbnail
* **Global paste listener** - Works on any admin page
* **Admin bar quick access** - Click to open paste zone
* **Live preview** - See image before uploading
* **Source detection** - Shows where image came from (DALL-E, Giphy, etc.)
* **Custom filename prefix** - Organize your pasted images
* **Gutenberg integration** - Insert blocks directly
* **Classic editor support** - Works with TinyMCE too
* **Keyboard shortcuts** - Ctrl+V to paste, Ctrl+Shift+V to open zone

= Use Cases =

* Content creators adding screenshots to tutorials
* Social media managers using Bitmoji/stickers
* Developers documenting with quick screenshots
* Anyone tired of downloading images just to re-upload them

== Installation ==

1. Upload the `nb-paste-image` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu
3. Start pasting! Press Ctrl+V anywhere in WordPress admin

== Frequently Asked Questions ==

= What image formats are supported? =
PNG, JPG/JPEG, GIF, and WebP. The format is preserved from your clipboard.

= Do I need to be in the Media Library to paste? =
No! You can paste anywhere in the WordPress admin. The paste zone dialog will appear.

= Can I paste GIFs? =
Yes! GIFs work just like any other image. Animation is preserved.

= Where are the settings? =
Go to Settings → Paste Image to configure filename prefix and behavior.

= Does it work with Gutenberg? =
Yes! When you choose "Insert in Post", it creates an Image block in the editor.

= Does it work with the Classic Editor? =
Yes! It uses the `send_to_editor` function for classic editor compatibility.

== Screenshots ==

1. Paste zone dialog with tabs
2. Load URL tab for full-quality images
3. Admin bar quick access button
4. Successfully uploaded image
5. Settings page

== Changelog ==

= 1.1.0 =
* **NEW: Load URL tab!** Paste image URLs to download full-quality originals
* Two input modes: Clipboard paste OR URL load
* Source detection (identifies DALL-E, Giphy, Unsplash, etc.)
* Clear preview button to start over
* Shows image source and size in preview
* Fixed: Thumbnails vs full-quality issue solved

= 1.0.0 =
* Initial release
* Global clipboard paste listener
* Admin bar trigger
* Paste zone dialog with preview
* Three destinations: Media Library, Featured Image, Insert in Post
* Gutenberg and Classic Editor support
* Settings page with customizable prefix
* Keyboard shortcuts

== Upgrade Notice ==

= 1.1.0 =
New! Load URL tab lets you paste image URLs to get full-quality downloads instead of thumbnails.
