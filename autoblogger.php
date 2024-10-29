<?php
/*
Plugin Name: AutoBlogger
Description: Automatically creates posts from an external API, simplifying content updates.
Version: 1.4.4
Author: Artur Spatari
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.html
*/

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/api-client.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/functions.php';

register_activation_hook(__FILE__, 'autoblogger_activate');
register_deactivation_hook(__FILE__, 'autoblogger_deactivate');

function autoblogger_activate()
{
    // Calculate the next HH:05
    $current_time = current_time('timestamp');
    $next_hour = strtotime('+1 hour', $current_time);
    $next_hour_five = strtotime(gmdate('Y-m-d H:05:00', $next_hour));

    if (!wp_next_scheduled('autoblogger_fetch_posts_hook')) {
        wp_schedule_event($next_hour_five, 'hourly', 'autoblogger_fetch_posts_hook');
    }
}

function autoblogger_deactivate()
{
    $timestamp = wp_next_scheduled('autoblogger_fetch_posts_hook');
    wp_unschedule_event($timestamp, 'autoblogger_fetch_posts_hook');
}

add_action('autoblogger_fetch_posts_hook', 'autoblogger_fetch_posts');
add_action('wp_ajax_autoblogger_import_old_posts', 'autoblogger_import_old_posts');

function autoblogger_custom_cron_intervals($schedules)
{
    $schedules['hourly'] = array(
        'interval' => 3600,
        'display' => __('Every Hour', "autoblogger")
    );
    return $schedules;
}

add_filter('cron_schedules', 'autoblogger_custom_cron_intervals');
?>
