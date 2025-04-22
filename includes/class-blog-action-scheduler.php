<?php
/**
 * Blog Action Scheduler Class
 *
 * Handles the scheduling and execution of blog-related actions.
 *
 * @package Blog_Action_Scheduler
 * @author vapvarun
 * @copyright 2025 Wbcom Designs
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main class for handling action scheduling
 */
class Blog_Action_Scheduler {

    /**
     * Initialize the scheduler
     */
    public static function init() {
        // Register hooks
        add_action('transition_post_status', array(__CLASS__, 'schedule_notification_on_publish'), 10, 3);
        
        // Register the action handlers
        add_action('blog_send_publish_notification', array(__CLASS__, 'send_publish_notification'), 10, 2);
        add_action('blog_send_share_reminder', array(__CLASS__, 'send_share_reminder'), 10, 2);
        
        // Add settings page
        add_action('admin_menu', array(__CLASS__, 'add_settings_page'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
    }

    /**
     * Add plugin settings page
     */
    public static function add_settings_page() {
        add_options_page(
            __('Blog Action Scheduler Settings', 'blog-action-scheduler'),
            __('Blog Actions', 'blog-action-scheduler'),
            'manage_options',
            'blog-action-scheduler',
            array(__CLASS__, 'settings_page_callback')
        );
    }

    /**
     * Register plugin settings
     */
    public static function register_settings() {
        register_setting('blog_action_scheduler_settings', 'bas_reminder_delay', array(
            'type' => 'integer',
            'default' => 24,
            'sanitize_callback' => 'absint'
        ));
        
        register_setting('blog_action_scheduler_settings', 'bas_enable_publish_notification', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => function($value) { return (bool) $value; }
        ));
        
        register_setting('blog_action_scheduler_settings', 'bas_enable_share_reminder', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => function($value) { return (bool) $value; }
        ));
        
        add_settings_section(
            'bas_general_settings',
            __('General Settings', 'blog-action-scheduler'),
            array(__CLASS__, 'settings_section_callback'),
            'blog-action-scheduler'
        );
        
        add_settings_field(
            'bas_enable_publish_notification',
            __('Enable Publish Notification', 'blog-action-scheduler'),
            array(__CLASS__, 'enable_publish_notification_callback'),
            'blog-action-scheduler',
            'bas_general_settings'
        );
        
        add_settings_field(
            'bas_enable_share_reminder',
            __('Enable Share Reminder', 'blog-action-scheduler'),
            array(__CLASS__, 'enable_share_reminder_callback'),
            'blog-action-scheduler',
            'bas_general_settings'
        );
        
        add_settings_field(
            'bas_reminder_delay',
            __('Reminder Delay (hours)', 'blog-action-scheduler'),
            array(__CLASS__, 'reminder_delay_callback'),
            'blog-action-scheduler',
            'bas_general_settings'
        );
    }

    /**
     * Settings section callback
     */
    public static function settings_section_callback() {
        echo '<p>' . __('Configure how blog action notifications work.', 'blog-action-scheduler') . '</p>';
    }

    /**
     * Enable publish notification field callback
     */
    public static function enable_publish_notification_callback() {
        $value = get_option('bas_enable_publish_notification', true);
        echo '<input type="checkbox" name="bas_enable_publish_notification" value="1" ' . checked(1, $value, false) . '/>';
        echo '<p class="description">' . __('Send an email to authors when their posts are published.', 'blog-action-scheduler') . '</p>';
    }

    /**
     * Enable share reminder field callback
     */
    public static function enable_share_reminder_callback() {
        $value = get_option('bas_enable_share_reminder', true);
        echo '<input type="checkbox" name="bas_enable_share_reminder" value="1" ' . checked(1, $value, false) . '/>';
        echo '<p class="description">' . __('Send a reminder to authors to share their published posts on social media.', 'blog-action-scheduler') . '</p>';
    }

    /**
     * Reminder delay field callback
     */
    public static function reminder_delay_callback() {
        $value = get_option('bas_reminder_delay', 24);
        echo '<input type="number" min="1" max="168" name="bas_reminder_delay" value="' . esc_attr($value) . '"/>';
        echo '<p class="description">' . __('Number of hours to wait before sending the share reminder.', 'blog-action-scheduler') . '</p>';
    }

    /**
     * Settings page callback
     */
    public static function settings_page_callback() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Blog Action Scheduler Settings', 'blog-action-scheduler'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('blog_action_scheduler_settings');
                do_settings_sections('blog-action-scheduler');
                submit_button();
                ?>
            </form>
            
            <div class="card">
                <h2><?php echo esc_html__('Action Status', 'blog-action-scheduler'); ?></h2>
                <p>
                    <?php 
                    $publish_count = as_get_scheduled_actions(
                        array('hook' => 'blog_send_publish_notification', 'status' => 'pending'),
                        'count'
                    );
                    
                    $reminder_count = as_get_scheduled_actions(
                        array('hook' => 'blog_send_share_reminder', 'status' => 'pending'),
                        'count'
                    );
                    
                    printf(
                        __('Pending notifications: %d | Pending reminders: %d', 'blog-action-scheduler'),
                        $publish_count,
                        $reminder_count
                    );
                    ?>
                </p>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=action-scheduler'); ?>" class="button">
                        <?php echo esc_html__('View Action Scheduler', 'blog-action-scheduler'); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Schedule notification when a post is published
     *
     * @param string $new_status New post status
     * @param string $old_status Old post status
     * @param WP_Post $post Post object
     */
    public static function schedule_notification_on_publish($new_status, $old_status, $post) {
        // Only proceed if this is a blog post being published for the first time
        if ($new_status === 'publish' && $old_status !== 'publish' && $post->post_type === 'post') {
            // Schedule immediate notification to the author
            if (get_option('bas_enable_publish_notification', true)) {
                self::schedule_publish_notification($post->ID, $post->post_author);
            }
            
            // Schedule a reminder to share on social media 
            if (get_option('bas_enable_share_reminder', true)) {
                $delay_hours = get_option('bas_reminder_delay', 24);
                self::schedule_share_reminder($post->ID, $post->post_author, $delay_hours * HOUR_IN_SECONDS);
            }
        }
    }

    /**
     * Schedule an author notification for a published post
     *
     * @param int $post_id Post ID
     * @param int $author_id Author ID
     */
    public static function schedule_publish_notification($post_id, $author_id) {
        // Check if we already have this action scheduled
        $existing = as_get_scheduled_actions(
            array(
                'hook' => 'blog_send_publish_notification',
                'args' => array($post_id, $author_id),
                'status' => 'pending',
            )
        );
        
        // If no existing action is found, schedule it
        if (empty($existing)) {
            as_schedule_single_action(
                time(), // Run immediately
                'blog_send_publish_notification',
                array($post_id, $author_id),
                'blog-notifications'
            );
            
            self::log("Scheduled publish notification for post ID {$post_id}, author ID {$author_id}");
        }
    }

    /**
     * Schedule a reminder to share on social media
     *
     * @param int $post_id Post ID
     * @param int $author_id Author ID
     * @param int $delay Time in seconds to delay the reminder
     */
    public static function schedule_share_reminder($post_id, $author_id, $delay = 86400) {
        // Check if we already have this action scheduled
        $existing = as_get_scheduled_actions(
            array(
                'hook' => 'blog_send_share_reminder',
                'args' => array($post_id, $author_id),
                'status' => 'pending',
            )
        );
        
        // If no existing action is found, schedule it
        if (empty($existing)) {
            as_schedule_single_action(
                time() + $delay,
                'blog_send_share_reminder',
                array($post_id, $author_id),
                'blog-reminders'
            );
            
            self::log("Scheduled share reminder for post ID {$post_id}, author ID {$author_id}, delay {$delay} seconds");
        }
    }

    /**
     * Send notification to author when their post is published
     *
     * @param int $post_id Post ID
     * @param int $author_id Author ID
     */
    public static function send_publish_notification($post_id, $author_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            self::log("Failed to send publish notification: Post ID {$post_id} not found");
            return;
        }
        
        $author = get_userdata($author_id);
        if (!$author) {
            self::log("Failed to send publish notification: Author ID {$author_id} not found");
            return;
        }
        
        $subject = sprintf(
            __('[%s] Your blog post "%s" has been published', 'blog-action-scheduler'),
            get_bloginfo('name'),
            $post->post_title
        );
        
        $message = sprintf(
            __('Hi %s,<br><br>Your blog post "%s" has been published and is now live on the site.<br><br>View it here: %s<br><br>Thank you for your contribution!', 'blog-action-scheduler'),
            $author->display_name,
            $post->post_title,
            get_permalink($post_id)
        );
        
        // Send the email
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $sent = wp_mail($author->user_email, $subject, $message, $headers);
        
        // Log this action
        if ($sent) {
            self::log("Publish notification sent to author {$author->user_email} for post ID {$post_id}");
        } else {
            self::log("Failed to send publish notification to author {$author->user_email} for post ID {$post_id}");
        }
    }

    /**
     * Send reminder to author to share their post on social media
     *
     * @param int $post_id Post ID
     * @param int $author_id Author ID
     */
    public static function send_share_reminder($post_id, $author_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            self::log("Failed to send share reminder: Post ID {$post_id} not found");
            return;
        }
        
        $author = get_userdata($author_id);
        if (!$author) {
            self::log("Failed to send share reminder: Author ID {$author_id} not found");
            return;
        }
        
        $subject = sprintf(
            __('[%s] Reminder: Share your blog post on social media', 'blog-action-scheduler'),
            get_bloginfo('name')
        );
        
        $message = sprintf(
            __('Hi %s,<br><br>This is a friendly reminder to share your blog post "%s" on your social media platforms.<br><br>Post link: %s<br><br>Sharing helps increase visibility and engagement for your content!', 'blog-action-scheduler'),
            $author->display_name,
            $post->post_title,
            get_permalink($post_id)
        );
        
        // Send the email
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $sent = wp_mail($author->user_email, $subject, $message, $headers);
        
        // Log this action
        if ($sent) {
            self::log("Share reminder sent to author {$author->user_email} for post ID {$post_id}");
        } else {
            self::log("Failed to send share reminder to author {$author->user_email} for post ID {$post_id}");
        }
    }

    /**
     * Log an action for debugging
     *
     * @param string $message The log message
     */
    private static function log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Blog Action Scheduler] {$message}");
        }
        
        $log_file = plugin_dir_path(dirname(__FILE__)) . 'logs/actions.log';
        $timestamp = date('Y-m-d H:i:s');
        
        file_put_contents(
            $log_file,
            "{$timestamp} - {$message}\n",
            FILE_APPEND
        );
    }
}