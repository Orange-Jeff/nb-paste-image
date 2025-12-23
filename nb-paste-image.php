<?php
/**
 * Plugin Name: NB Paste Image
 * Plugin URI: https://netbound.ca/plugins/nb-paste-image
 * Description: Paste images directly from clipboard to WordPress Media Library. Works with Bitmoji, Giphy, screenshots, and any copied image.
 * Version: 1.0.2
 * Author: Orange Jeff
 * Author URI: https://netbound.ca
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: nb-paste-image
 * Requires at least: 5.0
 * Requires PHP: 7.4
 *
 * Version: 1.0.2 - 2024-12-23
 *   - Added menu icon
 *
 * Version: 1.0.1 - 2024-12-23
 *   - Fixed menu to use NetBound Tools instead of Settings
 *
 * Version: 1.0.0 - 2024-12-22
 *   - Initial release
 *   - Paste to Media Library
 *   - Paste as Featured Image
 *   - Paste into Block Editor
 *   - Floating paste zone
 *   - Admin bar quick access
 */

if (!defined('ABSPATH')) {
    exit;
}

class NB_Paste_Image {

    private static $instance = null;
    const VERSION = '1.1.0';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Admin assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // AJAX handlers
        add_action('wp_ajax_nb_paste_image_upload', [$this, 'ajax_upload_image']);
        add_action('wp_ajax_nb_paste_image_load_url', [$this, 'ajax_load_url']);

        // Admin bar menu
        add_action('admin_bar_menu', [$this, 'add_admin_bar_menu'], 100);

        // Add paste zone to admin pages
        add_action('admin_footer', [$this, 'render_paste_zone']);

        // Add to media upload tabs
        add_filter('media_upload_tabs', [$this, 'add_media_tab']);

        // Settings
        add_action('admin_menu', [$this, 'add_settings_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_assets($hook) {
        // Load on all admin pages for paste functionality
        wp_enqueue_style(
            'nb-paste-image',
            plugin_dir_url(__FILE__) . 'assets/style.css',
            [],
            self::VERSION
        );

        wp_enqueue_script(
            'nb-paste-image',
            plugin_dir_url(__FILE__) . 'assets/script.js',
            ['jquery'],
            self::VERSION,
            true
        );

        wp_localize_script('nb-paste-image', 'nbPasteImage', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nb_paste_image'),
            'isEditor' => in_array($hook, ['post.php', 'post-new.php']),
            'isMediaLibrary' => $hook === 'upload.php',
            'defaultPrefix' => get_option('nb_paste_image_prefix', 'pasted-image'),
            'autoInsert' => get_option('nb_paste_image_auto_insert', 'ask'),
            'showNotifications' => get_option('nb_paste_image_notifications', '1'),
        ]);
    }

    /**
     * Add admin bar menu for quick paste
     */
    public function add_admin_bar_menu($wp_admin_bar) {
        if (!current_user_can('upload_files')) {
            return;
        }

        $wp_admin_bar->add_node([
            'id' => 'nb-paste-image',
            'title' => '<span class="ab-icon dashicons dashicons-clipboard"></span> Paste Image',
            'href' => '#',
            'meta' => [
                'class' => 'nb-paste-image-trigger',
                'title' => 'Click to open paste zone, or just Ctrl+V anywhere',
            ],
        ]);
    }

    /**
     * Render floating paste zone
     */
    public function render_paste_zone() {
        if (!current_user_can('upload_files')) {
            return;
        }
        ?>
        <div id="nb-paste-zone" class="nb-paste-zone" style="display:none;">
            <div class="nb-paste-zone-inner">
                <button type="button" class="nb-paste-zone-close">&times;</button>

                <div class="nb-paste-zone-header">
                    <span class="dashicons dashicons-clipboard"></span>
                    <h2>Paste Image</h2>
                </div>

                <div class="nb-paste-zone-content">
                    <!-- Tab switcher -->
                    <div class="nb-paste-tabs">
                        <button type="button" class="nb-paste-tab active" data-tab="paste">
                            <span class="dashicons dashicons-clipboard"></span> Paste
                        </button>
                        <button type="button" class="nb-paste-tab" data-tab="url">
                            <span class="dashicons dashicons-admin-links"></span> Load URL
                        </button>
                    </div>

                    <!-- Paste Tab -->
                    <div class="nb-paste-tab-content" id="nbTabPaste">
                        <div class="nb-paste-dropzone" id="nbPasteDropzone">
                            <span class="dashicons dashicons-format-image"></span>
                            <p><strong>Ctrl+V</strong> to paste image<br>or <strong>click</strong> to browse</p>
                            <input type="file" id="nbPasteFileInput" accept="image/*" style="display:none;">
                        </div>
                    </div>

                    <!-- URL Tab -->
                    <div class="nb-paste-tab-content" id="nbTabUrl" style="display:none;">
                        <div class="nb-url-input-wrap">
                            <label for="nbImageUrl">Image URL:</label>
                            <input type="url" id="nbImageUrl" placeholder="https://example.com/image.png">
                            <small>Paste URL for full-quality image (thumbnails on screen are often lower quality)</small>
                            <button type="button" class="button" id="nbLoadUrl">
                                <span class="dashicons dashicons-download"></span> Load Image
                            </button>
                        </div>
                    </div>

                    <!-- Preview (shared) -->
                    <div class="nb-paste-preview" id="nbPastePreview" style="display:none;">
                        <img id="nbPastePreviewImg" src="" alt="Preview">
                        <div class="nb-paste-preview-info">
                            <span id="nbPastePreviewSize"></span>
                            <span id="nbPastePreviewSource"></span>
                        </div>
                        <button type="button" class="button nb-preview-clear" id="nbClearPreview">
                            <span class="dashicons dashicons-no"></span> Clear
                        </button>
                    </div>

                    <div class="nb-paste-options">
                        <div class="nb-paste-option-row">
                            <label for="nbPasteTitle">Image Title:</label>
                            <input type="text" id="nbPasteTitle" placeholder="Leave blank for auto-generated">
                        </div>

                        <div class="nb-paste-option-row">
                            <label for="nbPasteAlt">Alt Text:</label>
                            <input type="text" id="nbPasteAlt" placeholder="Describe the image">
                        </div>

                        <div class="nb-paste-option-row nb-paste-actions-row">
                            <label>Action:</label>
                            <div class="nb-paste-action-buttons">
                                <button type="button" class="button nb-paste-action" data-action="library">
                                    <span class="dashicons dashicons-admin-media"></span>
                                    Media Library
                                </button>
                                <button type="button" class="button nb-paste-action" data-action="featured" id="nbPasteFeatured" style="display:none;">
                                    <span class="dashicons dashicons-star-filled"></span>
                                    Featured Image
                                </button>
                                <button type="button" class="button nb-paste-action" data-action="insert" id="nbPasteInsert" style="display:none;">
                                    <span class="dashicons dashicons-editor-insertmore"></span>
                                    Insert in Post
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="nb-paste-status" id="nbPasteStatus"></div>
                </div>

                <div class="nb-paste-zone-footer">
                    <small>Works with: Screenshots, Bitmoji, Giphy, any copied image</small>
                </div>
            </div>
        </div>

        <!-- Global paste listener indicator -->
        <div id="nb-paste-indicator" class="nb-paste-indicator" style="display:none;">
            <span class="dashicons dashicons-upload"></span>
            <span>Release to upload image...</span>
        </div>
        <?php
    }

    /**
     * AJAX: Upload pasted image
     */
    public function ajax_upload_image() {
        check_ajax_referer('nb_paste_image', 'nonce');

        if (!current_user_can('upload_files')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $image_data = isset($_POST['image_data']) ? $_POST['image_data'] : '';
        $title = sanitize_text_field($_POST['title'] ?? '');
        $alt = sanitize_text_field($_POST['alt'] ?? '');
        $action = sanitize_key($_POST['upload_action'] ?? 'library');
        $post_id = intval($_POST['post_id'] ?? 0);

        if (empty($image_data)) {
            wp_send_json_error(['message' => 'No image data received']);
        }

        // Parse the data URL
        if (preg_match('/^data:image\/(\w+);base64,(.+)$/', $image_data, $matches)) {
            $extension = $matches[1];
            $binary_data = base64_decode($matches[2]);
        } else {
            wp_send_json_error(['message' => 'Invalid image data format']);
        }

        if (!$binary_data) {
            wp_send_json_error(['message' => 'Failed to decode image data']);
        }

        // Map extension
        $extension_map = [
            'jpeg' => 'jpg',
            'png' => 'png',
            'gif' => 'gif',
            'webp' => 'webp',
        ];
        $extension = $extension_map[$extension] ?? 'png';

        // Generate filename
        $prefix = get_option('nb_paste_image_prefix', 'pasted-image');
        $filename = $prefix . '-' . date('Y-m-d-His') . '.' . $extension;

        // Upload
        $upload = wp_upload_bits($filename, null, $binary_data);

        if ($upload['error']) {
            wp_send_json_error(['message' => 'Upload failed: ' . $upload['error']]);
        }

        // Get mime type
        $mime_types = [
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        ];
        $mime_type = $mime_types[$extension] ?? 'image/png';

        // Create attachment
        $attachment = [
            'post_mime_type' => $mime_type,
            'post_title' => $title ?: ucfirst(str_replace('-', ' ', $prefix)) . ' ' . date('M j, Y g:i A'),
            'post_content' => '',
            'post_status' => 'inherit',
        ];

        $attach_id = wp_insert_attachment($attachment, $upload['file'], $post_id);

        if (is_wp_error($attach_id)) {
            wp_send_json_error(['message' => 'Failed to create attachment']);
        }

        // Generate metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        // Set alt text
        if ($alt) {
            update_post_meta($attach_id, '_wp_attachment_image_alt', $alt);
        }

        // Mark as pasted image
        update_post_meta($attach_id, '_nb_pasted_image', '1');
        update_post_meta($attach_id, '_nb_pasted_date', current_time('mysql'));

        // Handle featured image action
        if ($action === 'featured' && $post_id) {
            set_post_thumbnail($post_id, $attach_id);
        }

        // Get image for response
        $attachment_url = wp_get_attachment_url($attach_id);
        $thumbnail = wp_get_attachment_image_src($attach_id, 'thumbnail');

        wp_send_json_success([
            'message' => 'Image uploaded successfully!',
            'attachment_id' => $attach_id,
            'url' => $attachment_url,
            'thumbnail' => $thumbnail ? $thumbnail[0] : $attachment_url,
            'title' => get_the_title($attach_id),
            'edit_url' => admin_url('post.php?post=' . $attach_id . '&action=edit'),
            'action' => $action,
            'is_featured' => $action === 'featured',
        ]);
    }

    /**
     * AJAX: Load image from URL (for full-quality downloads)
     */
    public function ajax_load_url() {
        check_ajax_referer('nb_paste_image', 'nonce');

        if (!current_user_can('upload_files')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $url = esc_url_raw($_POST['url'] ?? '');

        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            wp_send_json_error(['message' => 'Invalid URL']);
        }

        // Fetch the image
        $response = wp_remote_get($url, [
            'timeout' => 30,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Failed to fetch image: ' . $response->get_error_message()]);
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            wp_send_json_error(['message' => 'Failed to fetch image: HTTP ' . $response_code]);
        }

        $body = wp_remote_retrieve_body($response);
        $content_type = wp_remote_retrieve_header($response, 'content-type');

        // Validate it's an image
        if (strpos($content_type, 'image/') !== 0) {
            wp_send_json_error(['message' => 'URL does not point to an image (got: ' . $content_type . ')']);
        }

        // Convert to base64 data URL
        $base64 = base64_encode($body);
        $data_url = 'data:' . $content_type . ';base64,' . $base64;

        // Get file size
        $size_kb = round(strlen($body) / 1024, 1);

        // Detect source
        $source = $this->detect_image_source($url);

        wp_send_json_success([
            'data_url' => $data_url,
            'size' => $size_kb . ' KB',
            'source' => $source,
            'content_type' => $content_type,
        ]);
    }

    /**
     * Detect image source from URL
     */
    private function detect_image_source($url) {
        $url_lower = strtolower($url);

        if (strpos($url_lower, 'oaidalleapiprodscus') !== false || strpos($url_lower, 'openai') !== false) {
            return 'DALL-E / ChatGPT';
        }
        if (strpos($url_lower, 'midjourney') !== false) {
            return 'Midjourney';
        }
        if (strpos($url_lower, 'stability') !== false || strpos($url_lower, 'stablediffusion') !== false) {
            return 'Stable Diffusion';
        }
        if (strpos($url_lower, 'leonardo') !== false) {
            return 'Leonardo.ai';
        }
        if (strpos($url_lower, 'giphy') !== false) {
            return 'Giphy';
        }
        if (strpos($url_lower, 'bitmoji') !== false) {
            return 'Bitmoji';
        }
        if (strpos($url_lower, 'unsplash') !== false) {
            return 'Unsplash';
        }
        if (strpos($url_lower, 'pexels') !== false) {
            return 'Pexels';
        }

        // Extract domain
        $domain = parse_url($url, PHP_URL_HOST);
        return $domain ?: 'URL';
    }

    /**
     * Add settings menu - Add to NetBound Tools
     */
    public function add_settings_menu() {
        // Check if NetBound Tools menu exists, create if not
        global $menu;
        $netbound_exists = false;
        foreach ($menu as $item) {
            if (isset($item[2]) && $item[2] === 'netbound-tools') {
                $netbound_exists = true;
                break;
            }
        }

        if (!$netbound_exists) {
            add_menu_page(
                'NetBound Tools',
                'NetBound Tools',
                'manage_options',
                'netbound-tools',
                [$this, 'render_settings_page'],
                'dashicons-superhero',
                30
            );
        }

        add_submenu_page(
            'netbound-tools',
            'Paste Image',
            '📋 Paste Image',
            'manage_options',
            'nb-paste-image',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('nb_paste_image', 'nb_paste_image_prefix');
        register_setting('nb_paste_image', 'nb_paste_image_auto_insert');
        register_setting('nb_paste_image', 'nb_paste_image_notifications');
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><span class="dashicons dashicons-clipboard" style="font-size:30px;height:30px;width:30px;margin-right:10px;"></span> NB Paste Image Settings</h1>

            <form method="post" action="options.php">
                <?php settings_fields('nb_paste_image'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">Filename Prefix</th>
                        <td>
                            <input type="text" name="nb_paste_image_prefix"
                                   value="<?php echo esc_attr(get_option('nb_paste_image_prefix', 'pasted-image')); ?>"
                                   class="regular-text">
                            <p class="description">Prefix for uploaded files (e.g., "pasted-image" → pasted-image-2024-12-22-143052.png)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Auto-Insert in Editor</th>
                        <td>
                            <select name="nb_paste_image_auto_insert">
                                <option value="ask" <?php selected(get_option('nb_paste_image_auto_insert', 'ask'), 'ask'); ?>>Always ask</option>
                                <option value="insert" <?php selected(get_option('nb_paste_image_auto_insert'), 'insert'); ?>>Auto-insert in post</option>
                                <option value="library" <?php selected(get_option('nb_paste_image_auto_insert'), 'library'); ?>>Just save to library</option>
                            </select>
                            <p class="description">What to do when pasting in the post editor</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Show Notifications</th>
                        <td>
                            <label>
                                <input type="checkbox" name="nb_paste_image_notifications" value="1"
                                       <?php checked(get_option('nb_paste_image_notifications', '1'), '1'); ?>>
                                Show success/error notifications
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <hr>

            <h2>How to Use</h2>
            <ol>
                <li><strong>Copy any image</strong> - Screenshot (Win+Shift+S), Bitmoji, Giphy, right-click "Copy Image"</li>
                <li><strong>Press Ctrl+V</strong> - Anywhere in WordPress admin</li>
                <li><strong>Choose action</strong> - Media Library, Featured Image, or Insert in Post</li>
            </ol>

            <h3>Works With</h3>
            <ul>
                <li>✅ Screenshots (Windows Snipping Tool, macOS, etc.)</li>
                <li>✅ Bitmoji from Chrome extension or app</li>
                <li>✅ Giphy - right-click and copy GIF</li>
                <li>✅ Any image - right-click "Copy Image" in browser</li>
                <li>✅ Image editors - Copy from Photoshop, Paint, etc.</li>
                <li>✅ AI images - Copy from DALL-E, Midjourney previews</li>
            </ul>

            <h3>Keyboard Shortcuts</h3>
            <table class="widefat" style="max-width:400px;">
                <tr><td><code>Ctrl+V</code></td><td>Paste image anywhere</td></tr>
                <tr><td><code>Ctrl+Shift+V</code></td><td>Open paste zone dialog</td></tr>
                <tr><td><code>Escape</code></td><td>Close paste zone</td></tr>
            </table>
        </div>
        <?php
    }

    /**
     * Add tab to media upload
     */
    public function add_media_tab($tabs) {
        $tabs['nb_paste'] = 'Paste Image';
        return $tabs;
    }
}

// Initialize
NB_Paste_Image::get_instance();
