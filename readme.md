# Blog Action Scheduler

A lightweight WordPress plugin that schedules notifications and reminders for blog authors.

**Author:** [vapvarun](https://profiles.wordpress.org/vapvarun/)  
**Company:** [Wbcom Designs](https://wbcomdesigns.com)

## Features

- **Publish Notifications**: Automatically notifies authors when their blog posts are published
- **Share Reminders**: Sends authors a reminder to share their posts on social media
- **Configurable Settings**: Adjust delays and enable/disable features through the admin interface
- **Action Monitoring**: View pending actions and their status
- **Logging**: Detailed logs for troubleshooting

## Installation

1. Download the plugin ZIP file
2. Go to WordPress Admin > Plugins > Add New
3. Click "Upload Plugin" and select the downloaded ZIP file
4. Click "Install Now" and then "Activate"

## Configuration

After activation, go to Settings > Blog Actions to configure the plugin:

- **Enable Publish Notification**: Turn on/off the notification sent when a post is published
- **Enable Share Reminder**: Turn on/off the reminder to share posts on social media
- **Reminder Delay**: Set how many hours to wait before sending the share reminder

## How It Works

The plugin uses Action Scheduler, a background processing library for WordPress, to reliably schedule and execute notifications:

1. When a post is published, the plugin immediately schedules the notification and reminder actions
2. The publish notification is sent immediately to the author
3. The share reminder is sent after the configured delay (default: 24 hours)

## Advanced Usage

### Action Status

You can monitor all scheduled actions by visiting Settings > Blog Actions and clicking "View Action Scheduler".

### Logs

Logs are stored in the plugin's `logs` directory and can be useful for troubleshooting.

### Developers

The plugin provides hooks for customization:

```php
// Modify the publish notification email
add_filter('blog_action_scheduler_publish_email', function($message, $post, $author) {
    // Customize message
    return $message;
}, 10, 3);

// Modify the share reminder email
add_filter('blog_action_scheduler_reminder_email', function($message, $post, $author) {
    // Customize message
    return $message;
}, 10, 3);
```

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher

## Frequently Asked Questions

**Q: Can I customize the email templates?**
A: Yes, you can use the provided filters to customize the email content.

**Q: Does this work with custom post types?**
A: By default, it only works with standard posts. To add support for custom post types, you can use the `blog_action_scheduler_post_types` filter.

**Q: Will this slow down my site?**
A: No, Action Scheduler uses WordPress cron to run actions in the background, so it won't affect your site's performance.

## Support

If you need assistance, please create an issue on the plugin's repository.

## License

This plugin is licensed under the GPL v2 or later.
