<?php
add_action('admin_menu', 'autoblogger_add_admin_menu');

function autoblogger_add_admin_menu()
{
    $svg_icon_base64 = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 116 116"><path fill="#FF3F1D" d="M20.3 29.2c8.8 4 18.6 4 27 .6A30 30 0 0 1 87.8 53c1.5 10 7.3 19.3 16.7 24.7l6.3 3.6a57.6 57.6 0 0 0 5-23.4A57.7 57.7 0 0 0 11.3 24zm78 59.2a34 34 0 0 0-29.7-2.2A29.8 29.8 0 0 1 28.3 63c-1.3-9-6.2-17.6-14-23.2l-9-5.1A57.6 57.6 0 0 0 .3 58a57.7 57.7 0 0 0 104.3 34z"/></svg>');
    add_menu_page('AutoBlogger Settings', 'AutoBlogger', 'manage_options', 'autoblogger', 'autoblogger_admin_page', $svg_icon_base64, 6);
}

function autoblogger_admin_page() {
    $client = new AutoBloggerAPIClient();
    $tokenStatus = $client->validateApiKey() ? 'Valid' : 'Invalid';
    update_option('autoblogger_token_status', $tokenStatus);
    
    $lastTokenCheck = current_time('mysql');
    update_option('autoblogger_last_token_check', $lastTokenCheck);

    $lastSync = get_option('autoblogger_last_sync', 'Never');
    $nextScheduled = wp_next_scheduled('autoblogger_fetch_posts_hook');

    $lastSyncUTC = $lastSync !== 'Never' ? get_gmt_from_date($lastSync, 'Y-m-d H:i:s') : 'Never';
    $nextScheduledUTC = $nextScheduled ? gmdate('Y-m-d H:i:s', $nextScheduled) : 'Not scheduled';

    autoblogger_enqueue_admin_script($lastSyncUTC, $nextScheduledUTC, $tokenStatus, $lastTokenCheck);

    ?>
    <div class="wrap">
        <div style="display: flex; align-items: center;">
            <h1>AutoBlogger Plugin</h1>
            <svg style="height: 32px; width: 32px; margin-left: 8px;" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 116 116" width="32" height="32">
                <path fill="#FF3F1D"
                    d="M20.3 29.2c8.8 4 18.6 4 27 .6A30 30 0 0 1 87.8 53c1.5 10 7.3 19.3 16.7 24.7l6.3 3.6a57.6 57.6 0 0 0 5-23.4A57.7 57.7 0 0 0 11.3 24zm78 59.2a34 34 0 0 0-29.7-2.2A29.8 29.8 0 0 1 28.3 63c-1.3-9-6.2-17.6-14-23.2l-9-5.1A57.6 57.6 0 0 0 .3 58a57.7 57.7 0 0 0 104.3 34z" />
            </svg>
        </div>
        <form method="post" action="options.php">
            <?php
            settings_fields('autoblogger_options');
            do_settings_sections('autoblogger');
            submit_button('Save Changes');
            ?>
        </form>
        <p>Last Token Check: <strong id="lastTokenCheck"><?php echo esc_html($lastTokenCheck); ?></strong></p>
        <p>Token Status: <strong id="tokenStatus"><?php echo esc_html($tokenStatus); ?></strong></p>
        <p>Last Sync: <strong id="lastSync"><?php echo esc_html($lastSyncUTC); ?></strong></p>
        <p>Next Scheduled Sync: <strong id="nextScheduled"><?php echo esc_html($nextScheduledUTC); ?></strong></p>
        <button id="manualSync" class="button button-primary">Sync Latest Posts</button>
    </div>
    <?php
}

function autoblogger_enqueue_admin_script($lastSyncUTC, $nextScheduledUTC, $tokenStatus, $lastTokenCheck) {
    wp_enqueue_script('jquery');
    
    wp_register_script('autoblogger-admin-js', false, [], '1.0.0', true);

    wp_localize_script('autoblogger-admin-js', 'autobloggerData', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('autoblogger_nonce'),
        'lastSync' => $lastSyncUTC,
        'nextScheduled' => $nextScheduledUTC,
        'tokenStatus' => $tokenStatus,
        'lastTokenCheck' => $lastTokenCheck,
    ));

    $inline_script = "
        jQuery(document).ready(function ($) {
            const formatLocalTime = (utcString) => {
                if (utcString === 'Never' || utcString === 'Not scheduled') {
                    return utcString;
                }
                const utcDate = new Date(utcString + ' UTC');
                return utcDate.toLocaleString();
            };

            // Convert UTC times to local times
            $('#lastSync').text(formatLocalTime(autobloggerData.lastSync));
            $('#nextScheduled').text(formatLocalTime(autobloggerData.nextScheduled));

            $('#manualSync').click(function () {
                $.ajax({
                    url: autobloggerData.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'autoblogger_import_old_posts',
                        security: autobloggerData.nonce
                    },
                    success: function (response) {
                        if (response.success) {
                            alert('Posts synchronized successfully.');
                        } else {
                            alert('Failed to synchronize posts. Check console for details.');
                            console.error(response.data);
                        }
                    },
                    error: function (xhr) {
                        console.error('Sync Failed:', xhr.responseText);
                        alert('Failed to synchronize posts. Check console for details.');
                    }
                });
            });
        });
    ";
    wp_add_inline_script('autoblogger-admin-js', $inline_script);

    wp_enqueue_script('autoblogger-admin-js');
}

add_action('admin_init', 'autoblogger_admin_init');

function autoblogger_admin_init()
{
    register_setting('autoblogger_options', 'autoblogger_settings');
    add_settings_section('autoblogger_main', 'Settings', 'autoblogger_section_text', 'autoblogger');
    add_settings_field('api_key', 'API Token', 'autoblogger_api_key_field', 'autoblogger', 'autoblogger_main');
    add_settings_field('post_status', 'Default Post Status', 'autoblogger_post_status_field', 'autoblogger', 'autoblogger_main');
    add_settings_field('default_author', 'Default Author', 'autoblogger_default_author_field', 'autoblogger', 'autoblogger_main');
    add_settings_field('post_type', 'Default Post Type', 'autoblogger_post_type_field', 'autoblogger', 'autoblogger_main');
}

function autoblogger_section_text()
{
    echo '<p>Enter your settings below:</p>';
}

function autoblogger_api_key_field()
{
    $options = get_option('autoblogger_settings');
    echo "<input id='api_key' name='autoblogger_settings[api_key]' size='40' type='text' value='" . esc_attr($options['api_key'] ?? '') . "' />";
}

function autoblogger_post_status_field()
{
    $options = get_option('autoblogger_settings');
    $post_status = $options['post_status'] ?? 'draft';
    echo "<select id='post_status' name='autoblogger_settings[post_status]'>";
    echo "<option value='publish'" . selected($post_status, 'publish', false) . ">Publish</option>";
    echo "<option value='draft'" . selected($post_status, 'draft', false) . ">Draft</option>";
    echo "</select>";
}

function autoblogger_default_author_field()
{
    $options = get_option('autoblogger_settings');
    $users = get_users(array('fields' => array('ID', 'display_name')));
    echo "<select id='default_author' name='autoblogger_settings[default_author]'>";
    foreach ($users as $user) {
        $selected = selected($options['default_author'] ?? '', $user->ID, false);
        echo "<option value='" . esc_attr($user->ID) . "'" . ($selected ? ' selected' : '') . ">" . esc_html($user->display_name) . "</option>";
    }
    echo "</select>";
}

function autoblogger_post_type_field()
{
    $options = get_option('autoblogger_settings');
    $post_types = get_post_types(array('public' => true), 'objects');
    echo "<select id='post_type' name='autoblogger_settings[post_type]'>";
    foreach ($post_types as $post_type) {
        $selected = selected($options['post_type'] ?? 'post', $post_type->name, false);
        echo "<option value='" . esc_attr($post_type->name) . "'" . ($selected ? ' selected' : '') . ">" . esc_html($post_type->label) . "</option>";
    }
    echo "</select>";
}
?>
