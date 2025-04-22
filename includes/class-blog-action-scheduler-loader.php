<?php
/**
 * Action Scheduler Loader
 * 
 * Handles downloading and loading the Action Scheduler library.
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
 * Class for loading Action Scheduler
 */
class Blog_Action_Scheduler_Loader {
    
    /**
     * The Action Scheduler version to download
     */
    const AS_VERSION = '3.5.4';
    
    /**
     * The download URL for Action Scheduler
     */
    const AS_DOWNLOAD_URL = 'https://github.com/woocommerce/action-scheduler/archive/refs/tags/';
    
    /**
     * Initialize the loader
     */
    public static function init() {
        // Check if Action Scheduler is already loaded
        if (self::is_action_scheduler_loaded()) {
            return;
        }
        
        // Try to load from the includes directory
        if (self::load_from_includes()) {
            return;
        }
        
        // If we get here, we need to download Action Scheduler
        self::download_action_scheduler();
        
        // Try to load again
        self::load_from_includes();
    }
    
    /**
     * Check if Action Scheduler is already loaded
     * 
     * @return bool True if already loaded
     */
    private static function is_action_scheduler_loaded() {
        return class_exists('ActionScheduler') || class_exists('Action_Scheduler');
    }
    
    /**
     * Load Action Scheduler from the includes directory
     * 
     * @return bool True if loaded successfully
     */
    private static function load_from_includes() {
        $as_file = plugin_dir_path(dirname(__FILE__)) . 'includes/action-scheduler/action-scheduler.php';
        
        if (file_exists($as_file)) {
            require_once $as_file;
            return true;
        }
        
        return false;
    }
    
    /**
     * Download and extract Action Scheduler
     */
    private static function download_action_scheduler() {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        
        // Create directories if they don't exist
        $includes_dir = plugin_dir_path(dirname(__FILE__)) . 'includes';
        if (!file_exists($includes_dir)) {
            wp_mkdir_p($includes_dir);
        }
        
        // Download the Action Scheduler ZIP file
        $download_url = self::AS_DOWNLOAD_URL . self::AS_VERSION . '.zip';
        $tmp_file = download_url($download_url);
        
        if (is_wp_error($tmp_file)) {
            error_log('[Blog Action Scheduler] Failed to download Action Scheduler: ' . $tmp_file->get_error_message());
            return;
        }
        
        // Extract the ZIP file
        global $wp_filesystem;
        
        if (empty($wp_filesystem)) {
            require_once ABSPATH . '/wp-admin/includes/file.php';
            WP_Filesystem();
        }
        
        $extract_dir = plugin_dir_path(dirname(__FILE__)) . 'includes';
        $unzip_result = unzip_file($tmp_file, $extract_dir);
        
        // Clean up the temporary file
        @unlink($tmp_file);
        
        if (is_wp_error($unzip_result)) {
            error_log('[Blog Action Scheduler] Failed to extract Action Scheduler: ' . $unzip_result->get_error_message());
            return;
        }
        
        // Rename the extracted directory
        $extracted_dir = $extract_dir . '/action-scheduler-' . self::AS_VERSION;
        $target_dir = $extract_dir . '/action-scheduler';
        
        // Remove the target directory if it already exists
        if (file_exists($target_dir)) {
            $wp_filesystem->rmdir($target_dir, true);
        }
        
        // Rename the extracted directory
        $wp_filesystem->move($extracted_dir, $target_dir);
    }
}

// Initialize the loader
Blog_Action_Scheduler_Loader::init();