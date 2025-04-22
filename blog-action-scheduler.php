<?php
/**
 * Plugin Name: Blog Action Scheduler
 * Plugin URI: https://wbcomdesigns.com/plugins/blog-action-scheduler
 * Description: Schedules actions for blog posts such as notifying authors when posts are published and reminding them to share on social media.
 * Version: 1.0.0
 * Author: vapvarun
 * Author URI: https://wbcomdesigns.com
 * Text Domain: blog-action-scheduler
 * License: GPL v2 or later
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main plugin class
 */
class Blog_Action_Scheduler_Plugin {

    /**
     * Plugin instance
     */
    private static $instance = null;

    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Load dependencies
        $this->load_dependencies();
        
        // Initialize the scheduler
        add_action('plugins_loaded', array($this, 'init_scheduler'), 20);

        // Register activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Load Action Scheduler and other dependencies
     */
    private function load_dependencies() {
        // Include Action Scheduler if it's not already included by another plugin
        if (!class_exists('ActionScheduler')) {
            require_once plugin_dir_path(__FILE__) . 'includes/action-scheduler/action-scheduler.php';
        }
    }

    /**
     * Initialize the scheduler
     */
    public function init_scheduler() {
        require_once plugin_dir_path(__FILE__) . 'includes/class-blog-action-scheduler.php';
        Blog_Action_Scheduler::init();
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Make sure Action Scheduler's data store is initialized
        if (class_exists('ActionScheduler_HybridStore')) {
            ActionScheduler_HybridStore::instance()->init();
        }
        
        // Create a log directory if it doesn't exist
        $log_dir = plugin_dir_path(__FILE__) . 'logs';
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
            file_put_contents($log_dir . '/index.php', '<?php // Silence is golden');
        }
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear all our scheduled actions
        if (function_exists('as_unschedule_all_actions')) {
            as_unschedule_all_actions('blog_send_publish_notification');
            as_unschedule_all_actions('blog_send_share_reminder');
        }
    }
}

// Initialize the plugin
function blog_action_scheduler_plugin_init() {
    Blog_Action_Scheduler_Plugin::get_instance();
}
add_action('plugins_loaded', 'blog_action_scheduler_plugin_init', 5);