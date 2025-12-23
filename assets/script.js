/**
 * NB Paste Image - JavaScript
 * Version: 1.1.0 - Added Load URL tab for full-quality images
 *
 * Handles clipboard paste events and image uploading
 */
(function($) {
    'use strict';

    let currentImageData = null;
    let currentImageSource = 'Clipboard';
    let isUploading = false;

    $(document).ready(function() {
        initPasteListener();
        initPasteZone();
        initTabs();
        initUrlLoader();
        initAdminBarTrigger();
        initKeyboardShortcuts();
        initEditorIntegration();
    });

    // ========================================
    // Global Paste Listener
    // ========================================

    function initPasteListener() {
        // Listen for paste events on the entire document
        document.addEventListener('paste', handlePaste, true);
    }

    function handlePaste(e) {
        // Don't interfere with text inputs (unless it's an image)
        const activeElement = document.activeElement;
        const isTextInput = activeElement && (
            activeElement.tagName === 'INPUT' ||
            activeElement.tagName === 'TEXTAREA' ||
            activeElement.isContentEditable
        );

        const clipboardData = e.clipboardData || window.clipboardData;
        if (!clipboardData) return;

        // Check for image data
        const items = clipboardData.items;
        let imageItem = null;

        for (let i = 0; i < items.length; i++) {
            if (items[i].type.indexOf('image') !== -1) {
                imageItem = items[i];
                break;
            }
        }

        if (!imageItem) return;

        // We have an image - prevent default paste
        e.preventDefault();
        e.stopPropagation();

        const blob = imageItem.getAsFile();
        if (!blob) return;

        // Convert to data URL
        const reader = new FileReader();
        reader.onload = function(event) {
            currentImageData = event.target.result;
            currentImageSource = 'Clipboard';
            showPasteZone();
            showPreview(currentImageData, currentImageSource);
        };
        reader.readAsDataURL(blob);
    }

    // ========================================
    // Paste Zone Dialog
    // ========================================

    function initPasteZone() {
        const $zone = $('#nb-paste-zone');

        // Close button
        $zone.find('.nb-paste-zone-close').on('click', closePasteZone);

        // Click outside to close
        $zone.on('click', function(e) {
            if (e.target === this) {
                closePasteZone();
            }
        });

        // File input fallback
        const $fileInput = $('#nbPasteFileInput');
        $('#nbPasteDropzone').on('click', function() {
            $fileInput.click();
        });

        $fileInput.on('change', function() {
            const file = this.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    currentImageData = e.target.result;
                    currentImageSource = 'File: ' + file.name;
                    showPreview(currentImageData, currentImageSource);
                };
                reader.readAsDataURL(file);
            }
        });

        // Action buttons
        $zone.find('.nb-paste-action').on('click', function() {
            const action = $(this).data('action');
            uploadImage(action);
        });

        // Clear preview button
        $('#nbClearPreview').on('click', clearPreview);

        // Show/hide context-specific buttons
        updateActionButtons();
    }

    // ========================================
    // Tabs
    // ========================================

    function initTabs() {
        $('.nb-paste-tab').on('click', function() {
            const tab = $(this).data('tab');

            // Update active state
            $('.nb-paste-tab').removeClass('active');
            $(this).addClass('active');

            // Show/hide content
            $('.nb-paste-tab-content').hide();
            $('#nbTab' + tab.charAt(0).toUpperCase() + tab.slice(1)).show();
        });
    }

    // ========================================
    // URL Loader
    // ========================================

    function initUrlLoader() {
        $('#nbLoadUrl').on('click', loadImageFromUrl);

        // Allow Enter key to trigger load
        $('#nbImageUrl').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                loadImageFromUrl();
            }
        });
    }

    function loadImageFromUrl() {
        const url = $('#nbImageUrl').val().trim();

        if (!url) {
            $('#nbPasteStatus').html('<span class="dashicons dashicons-warning" style="color:orange;"></span> Please enter an image URL');
            return;
        }

        $('#nbLoadUrl').prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Loading...');
        $('#nbPasteStatus').html('<span class="dashicons dashicons-update spin"></span> Fetching full-quality image...');

        $.post(nbPasteImage.ajaxurl, {
            action: 'nb_paste_image_load_url',
            nonce: nbPasteImage.nonce,
            url: url
        })
        .done(function(response) {
            if (response.success) {
                currentImageData = response.data.data_url;
                currentImageSource = response.data.source + ' (' + response.data.size + ')';
                showPreview(currentImageData, currentImageSource);
                $('#nbPasteStatus').html('<span class="dashicons dashicons-yes" style="color:green;"></span> Full-quality image loaded! Choose action below.');
            } else {
                $('#nbPasteStatus').html('<span class="dashicons dashicons-warning" style="color:red;"></span> ' + response.data.message);
            }
        })
        .fail(function() {
            $('#nbPasteStatus').html('<span class="dashicons dashicons-warning" style="color:red;"></span> Failed to load image');
        })
        .always(function() {
            $('#nbLoadUrl').prop('disabled', false).html('<span class="dashicons dashicons-download"></span> Load Image');
        });
    }

    function showPasteZone() {
        $('#nb-paste-zone').fadeIn(200);
        updateActionButtons();
    }

    function closePasteZone() {
        $('#nb-paste-zone').fadeOut(200);
        clearPreview();
        $('#nbImageUrl').val('');
    }

    function clearPreview() {
        currentImageData = null;
        currentImageSource = 'Clipboard';
        $('#nbPastePreview').hide();
        $('#nbTabPaste').show();
        $('#nbPasteDropzone').show();
        $('#nbPasteStatus').html('');
        $('#nbPasteTitle, #nbPasteAlt').val('');
    }

    function showPreview(dataUrl, source) {
        // Hide both tabs content, show preview
        $('.nb-paste-tab-content').hide();
        $('#nbPastePreview').show();
        $('#nbPastePreviewImg').attr('src', dataUrl);

        // Calculate approximate size if not provided in source
        if (!source || source === 'Clipboard') {
            const base64Length = dataUrl.length - dataUrl.indexOf(',') - 1;
            const sizeBytes = Math.round((base64Length * 3) / 4);
            const sizeKB = (sizeBytes / 1024).toFixed(1);
            $('#nbPastePreviewSize').text(sizeKB + ' KB');
            $('#nbPastePreviewSource').text('Source: Clipboard');
        } else {
            $('#nbPastePreviewSize').text('');
            $('#nbPastePreviewSource').text('Source: ' + source);
        }
    }

    function updateActionButtons() {
        const isEditor = nbPasteImage.isEditor;
        const postId = getPostId();

        // Show featured image button only on post editor
        $('#nbPasteFeatured').toggle(isEditor && postId > 0);

        // Show insert button only on post editor
        $('#nbPasteInsert').toggle(isEditor);
    }

    // ========================================
    // Image Upload
    // ========================================

    function uploadImage(action) {
        if (isUploading || !currentImageData) return;

        isUploading = true;
        const $status = $('#nbPasteStatus');
        const $buttons = $('.nb-paste-action');

        $buttons.prop('disabled', true);
        $status.html('<span class="dashicons dashicons-update spin"></span> Uploading...');

        $.post(nbPasteImage.ajaxurl, {
            action: 'nb_paste_image_upload',
            nonce: nbPasteImage.nonce,
            image_data: currentImageData,
            title: $('#nbPasteTitle').val(),
            alt: $('#nbPasteAlt').val(),
            upload_action: action,
            post_id: getPostId()
        })
        .done(function(response) {
            if (response.success) {
                handleUploadSuccess(response.data, action);
            } else {
                $status.html('<span class="dashicons dashicons-warning" style="color:red;"></span> ' + response.data.message);
            }
        })
        .fail(function() {
            $status.html('<span class="dashicons dashicons-warning" style="color:red;"></span> Upload failed. Please try again.');
        })
        .always(function() {
            isUploading = false;
            $buttons.prop('disabled', false);
        });
    }

    function handleUploadSuccess(data, action) {
        const $status = $('#nbPasteStatus');

        let successHtml = '<div class="nb-paste-success">';
        successHtml += '<span class="dashicons dashicons-yes-alt" style="color:green;"></span> ';
        successHtml += '<strong>' + data.message + '</strong><br>';

        if (data.is_featured) {
            successHtml += '⭐ Set as Featured Image<br>';
            // Update the featured image in the editor
            updateFeaturedImage(data);
        }

        successHtml += '<a href="' + data.url + '" target="_blank">View Image</a>';
        successHtml += ' | <a href="' + data.edit_url + '" target="_blank">Edit</a>';
        successHtml += '</div>';

        $status.html(successHtml);

        // If inserting into editor
        if (action === 'insert') {
            insertIntoEditor(data);
        }

        // Show notification
        if (nbPasteImage.showNotifications === '1') {
            showNotification('Image uploaded: ' + data.title, 'success');
        }

        // Reset for next paste after a delay
        setTimeout(function() {
            currentImageData = null;
            $('#nbPastePreview').hide();
            $('#nbPasteDropzone').show();
            $('#nbPasteTitle, #nbPasteAlt').val('');
        }, 2000);
    }

    // ========================================
    // Editor Integration
    // ========================================

    function initEditorIntegration() {
        if (!nbPasteImage.isEditor) return;

        // Listen for paste in Gutenberg editor
        $(document).on('paste', '.block-editor-writing-flow, .editor-styles-wrapper', function(e) {
            // The global handler will catch this
        });
    }

    function getPostId() {
        // Try various methods to get post ID
        if (typeof wp !== 'undefined' && wp.data && wp.data.select) {
            const postId = wp.data.select('core/editor')?.getCurrentPostId?.();
            if (postId) return postId;
        }

        // Fallback to URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        return parseInt(urlParams.get('post')) || 0;
    }

    function insertIntoEditor(data) {
        // Try Gutenberg first
        if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch) {
            const block = wp.blocks.createBlock('core/image', {
                id: data.attachment_id,
                url: data.url,
                alt: data.alt || ''
            });
            wp.data.dispatch('core/block-editor').insertBlocks(block);
            return;
        }

        // Classic editor fallback
        if (typeof send_to_editor === 'function') {
            const img = '<img src="' + data.url + '" alt="' + (data.alt || '') + '" class="alignnone size-full wp-image-' + data.attachment_id + '" />';
            send_to_editor(img);
        }
    }

    function updateFeaturedImage(data) {
        // Update Gutenberg featured image
        if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch) {
            wp.data.dispatch('core/editor').editPost({ featured_media: data.attachment_id });
        }

        // Update classic editor featured image
        const $metabox = $('#postimagediv');
        if ($metabox.length) {
            const html = '<img src="' + data.thumbnail + '" alt=""><a href="#" id="remove-post-thumbnail">Remove featured image</a>';
            $metabox.find('.inside').html(html);
        }
    }

    // ========================================
    // Admin Bar Trigger
    // ========================================

    function initAdminBarTrigger() {
        $(document).on('click', '#wp-admin-bar-nb-paste-image a', function(e) {
            e.preventDefault();
            showPasteZone();
        });
    }

    // ========================================
    // Keyboard Shortcuts
    // ========================================

    function initKeyboardShortcuts() {
        $(document).on('keydown', function(e) {
            // Escape to close
            if (e.key === 'Escape' && $('#nb-paste-zone').is(':visible')) {
                closePasteZone();
            }

            // Ctrl+Shift+V to open paste zone
            if (e.ctrlKey && e.shiftKey && e.key === 'V') {
                e.preventDefault();
                showPasteZone();
            }
        });
    }

    // ========================================
    // Notifications
    // ========================================

    function showNotification(message, type) {
        // Create notification element
        const $notif = $('<div class="nb-paste-notification nb-paste-notification-' + type + '">')
            .html('<span class="dashicons dashicons-' + (type === 'success' ? 'yes' : 'warning') + '"></span> ' + message)
            .appendTo('body');

        // Animate in
        setTimeout(() => $notif.addClass('visible'), 10);

        // Remove after delay
        setTimeout(function() {
            $notif.removeClass('visible');
            setTimeout(() => $notif.remove(), 300);
        }, 3000);
    }

})(jQuery);
