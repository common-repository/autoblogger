<?php
function autoblogger_fetch_posts()
{
    $client = new AutoBloggerAPIClient();
    $posts = $client->fetchPosts();

    if (!$posts || is_wp_error($posts)) {
        return;
    }

    foreach ($posts as $post_data) {
        if (!autoblogger_post_exists($post_data['id'])) {
            autoblogger_insert_post($post_data);
        }
    }

    update_option('autoblogger_last_sync', current_time('mysql'));
}

function autoblogger_post_exists($external_id) {
    global $wpdb;

    $cache_key = 'autoblogger_post_exists_' . md5($external_id);

    $post_id = wp_cache_get($cache_key, 'autoblogger_cache');

    if ($post_id !== false) {
        return true;
    }

    $post_id = $wpdb->get_var($wpdb->prepare("
        SELECT post_id 
        FROM {$wpdb->postmeta} 
        WHERE meta_key = 'external_id' 
        AND meta_value = %s 
        LIMIT 1
    ", $external_id));

    if ($post_id) {
        wp_cache_set($cache_key, $post_id, 'autoblogger_cache', HOUR_IN_SECONDS);
        return true;
    }

    return false;
}

function autoblogger_insert_post($data)
{
    // Fetch plugin options
    $options = get_option('autoblogger_settings');

    // Initialize content with image if provided
    $html_content = '';
    if (!empty($data['image_url'])) {
        // Prepare image HTML using the provided image URL and post title as alt text
        $image_alt = esc_attr($data['title']);
        $image_html = "<img src='" . esc_url($data['image_url']) . "' alt='" . $image_alt . "' style='width: 100%; height: auto;' />";
        $html_content .= $image_html;
    }

    $html_content .= $data['html_body'];

    // Prepare post data
    $post_type = $options['post_type'] ?? 'post';
    $post_author = $options['default_author'] ?? get_current_user_id();
    $post_status = $options['post_status'] ?? 'draft';

    // Initialize meta_input array with external ID
    $meta_input = [
        'external_id' => sanitize_text_field($data['id']),
    ];

    // Insert post into the database
    $post_id = wp_insert_post([
        'post_type'    => $post_type,
        'post_title'   => sanitize_text_field($data['title']),
        'post_content' => wp_kses_post($html_content),
        'post_status'  => $post_status,
        'post_author'  => $post_author,
        'meta_input'   => $meta_input,
    ]);

    // Check if a meta description is provided and add it to meta_input if available
    if (!empty($data['meta_description'])) {
        $meta_description = sanitize_text_field($data['meta_description']);

        // Add meta description for SEO plugins if applicable
        if (defined('WPSEO_VERSION')) {
            // Yoast SEO plugin
            update_post_meta($post_id, '_yoast_wpseo_metadesc', $meta_description);
        } elseif (defined('AIOSEOP_VERSION')) {
            // All in One SEO Pack plugin
            update_post_meta($post_id, '_aioseop_description', $meta_description);
        } elseif (class_exists('RankMath')) {
            // Rank Math SEO plugin
            update_post_meta($post_id, 'rank_math_description', $meta_description);
        } elseif (class_exists('SEOPress')) {
            // SEOPress plugin
            update_post_meta($post_id, '_seopress_analysis_description', $meta_description);
        } elseif (class_exists('The_SEO_Framework\Init')) {
            // The SEO Framework plugin
            update_post_meta($post_id, '_genesis_title_description', $meta_description);
        } elseif (class_exists('WP_Meta_SEO')) {
            // WP Meta SEO plugin
            update_post_meta($post_id, '_metaseo_meta_desc', $meta_description);
        }

        // Add custom meta description to the post
        update_post_meta($post_id, 'meta_description', $meta_description);
    }

    // Log success or failure
    if (is_wp_error($post_id)) {
        autoblogger_log('Failed to insert post: ' . $post_id->get_error_message(), 'error');
    } else {
        autoblogger_log("Post inserted successfully (ID: $post_id, External ID: {$data['id']})", 'info');
    }
}

add_action('wp_head', 'autoblogger_add_custom_meta_description');

function autoblogger_add_custom_meta_description() {
    if (is_singular()) {
        global $post;
        $meta_description = get_post_meta($post->ID, 'meta_description', true);
        if ($meta_description) {
            echo '<meta name="description" content="' . esc_attr($meta_description) . '">' . "\n";
        } else {
            // Fallback to an excerpt if meta description is not set
            $excerpt = wp_strip_all_tags(get_the_excerpt($post));
            echo '<meta name="description" content="' . esc_attr($excerpt) . '">' . "\n";
        }
    }
}

function autoblogger_log($message, $type = 'info') {
    $log_entry = "[" . gmdate('Y-m-d H:i:s') . "] $type: $message\n";

    require_once(ABSPATH . 'wp-admin/includes/file.php');
    
    $wp_filesystem = null;
    WP_Filesystem();

    $log_file = WP_CONTENT_DIR . '/autoblogger_logs.txt';

    if ($wp_filesystem) {
        $wp_filesystem->put_contents($log_file, $log_entry, FS_CHMOD_APPEND);
    }
}

function autoblogger_import_old_posts()
{
    check_ajax_referer('autoblogger_nonce', 'security');
    if (!current_user_can('manage_options')) {
        wp_send_json_error('You do not have permission to perform this action.', 403);
    }

    $client = new AutoBloggerAPIClient();
    $posts = $client->fetchPosts();

    if (is_wp_error($posts)) {
        wp_send_json_error('Failed to fetch posts from the API.', 500);
    }

    $imported_count = 0;
    foreach ($posts as $post_data) {
        if (!autoblogger_post_exists($post_data['id'])) {
            autoblogger_insert_post($post_data);
            $imported_count++;
        }
    }

    update_option('autoblogger_last_sync', current_time('mysql'));
    wp_send_json_success("Imported $imported_count posts successfully.");
}

add_filter('cron_schedules', 'autoblogger_custom_cron_interval');

function autoblogger_custom_cron_interval($schedules)
{
    $schedules['every_5_minutes'] = array(
        'interval' => 300, // 5 minutes in seconds
        'display' => __('Every 5 Minutes', "autoblogger")
    );
    return $schedules;
}

?>
