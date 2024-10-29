# AutoBlogger

Contributors: aspatari, ogaponcic
Requires at least: 5.0
Tested up to: 6.6.2
Stable tag: 1.4.4
Requires PHP: 7.2
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.html

Automate content creation by fetching posts from an external API with AutoBlogger, offering seamless integration and customization options.

## Description
AutoBlogger is a plugin that fetches posts automatically from an external API and integrates them into your WordPress site. This plugin relies on an external service to retrieve content, which is hosted at `https://autoblogger-api.otherweb.com`. 

## External Services
This plugin uses the following external service:

- **AutoBlogger API**: The plugin makes requests to `https://autoblogger-api.otherweb.com` to fetch content that is automatically imported into your WordPress site.

## Privacy Policy & Terms of Service
By using this plugin, content is fetched from the AutoBlogger API. Please review the external service's terms and privacy policies:

- **AutoBlogger API Terms of Service**: [Link to Terms](https://otherweb.com/terms)
- **AutoBlogger API Privacy Policy**: [Link to Privacy Policy](https://otherweb.com/privacy)

Please note that the use of this external service is required for the plugin's functionality. Users should ensure they comply with the terms and privacy policies of this service.
## Features

- **Automated Content Updates**: Automatically fetch and post content from external sources every hour at HH:05, ensuring your site stays up-to-date.
- **Configurable Post Status**: Choose whether imported posts are automatically published, saved as drafts, or set to pending review.
- **Custom Post Types Support**: Configure the default post type for imported posts to match your content strategy.
- **Default Author Selection**: Assign a default author for all imported posts, ensuring consistent attribution.
- **Manual Import**: Manually trigger the import of posts through the plugin settings page for immediate content updates.
- **Next Scheduled Sync**: View the timing for the next automatic sync directly from the admin settings page.
- **API Key Validation**: Automatically validate your API key upon entering the settings page to ensure seamless integration.


## Installation

1. Download the latest version of the plugin: `autoblogger-plugin.zip`.
2. Navigate to **Plugins > Add New > Upload Plugin** in your WordPress admin panel.
3. Select the downloaded file and click **Install Now**.
4. Activate the plugin through the **Plugins** menu.

## Configuration

To configure the plugin, navigate to **AutoBlogger** settings in the WordPress admin area:

- **API Token**: Enter your API key to connect the plugin to the external content source.
- **Post Defaults**: Set the default post status, author, and post type for all imported posts.

## Frequently Asked Questions
### When are the posts loaded?
The posts are fetched and imported on every hour at HH:05.
### Is it possible to sync manually the posts?
If needed, you can manually sync posts through the settings page by clicking the "Sync Latest Posts" button. 

## Troubleshooting

- **API Key Issues**: Ensure the API token entered is correct and valid. The plugin will display the status of your token on the settings page.
- **Cron Job Verification**: Check that WordPress cron jobs are functioning properly. You can use plugins like WP Crontrol to monitor scheduled events.
- **Error Logs**: Review the logs located in the `wp-content/autoblogger_logs.txt` file for error messages and warnings that can help diagnose issues.

## Stay Updated

Keep your plugin up-to-date to benefit from the latest features and improvements. For support or to provide feedback, please contact us through the appropriate channels.

## Technical Details

1. **Custom Cron Interval**: The plugin defines a custom 'hourly' cron interval to ensure that content updates occur at regular intervals.
2. **Activation Hook**: The `autoblogger_activate` function schedules the cron event to start at the next HH:05, setting the stage for regular updates.
3. **Deactivation Hook**: The `autoblogger_deactivate` function cleans up by unscheduling the event when the plugin is deactivated, ensuring no orphaned tasks are left behind.

This setup guarantees that your content fetches are consistent and reliable, running every hour precisely at HH:05.

## Screenshots
1. Desktop view

## Changelog
**Version 1.4.4**
- Bug fixes
**Version 1.0.0**
- Initial release.