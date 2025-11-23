<?php
/**
 * Autoloader for Option Map plugin
 *
 * @package OptionMap
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Autoloader class
 */
class OptionMap_Autoloader {

    /**
     * Register autoloader
     */
    public static function register() {
        spl_autoload_register(array(__CLASS__, 'autoload'));
    }

    /**
     * Autoload classes
     *
     * @param string $class_name Class name to load
     */
    public static function autoload($class_name) {
        // Only handle our classes
        if (strpos($class_name, 'OptionMap_') !== 0) {
            return;
        }

        // Remove prefix
        $class_name = str_replace('OptionMap_', '', $class_name);
        
        // Convert class name to file path
        // Handle different naming patterns
        $file_name = 'class-' . str_replace('_', '-', strtolower($class_name)) . '.php';
        
        // Define search directories
        $directories = array(
            SF_PLUGIN_DIR . 'includes/',
            SF_PLUGIN_DIR . 'includes/Core/',
            SF_PLUGIN_DIR . 'includes/Database/',
            SF_PLUGIN_DIR . 'includes/Scanners/',
            SF_PLUGIN_DIR . 'includes/Admin/',
            SF_PLUGIN_DIR . 'includes/Admin/Pages/',
            SF_PLUGIN_DIR . 'includes/Admin/Ajax/',
            SF_PLUGIN_DIR . 'includes/AI/',
            SF_PLUGIN_DIR . 'includes/Settings/',
        );

        // Search for file
        foreach ($directories as $directory) {
            $file_path = $directory . $file_name;
            if (file_exists($file_path)) {
                require_once $file_path;
                return;
            }
        }

        // Try interface files
        $interface_name = 'interface-' . str_replace('_', '-', strtolower($class_name)) . '.php';
        foreach ($directories as $directory) {
            $file_path = $directory . $interface_name;
            if (file_exists($file_path)) {
                require_once $file_path;
                return;
            }
        }

        // Try abstract files
        $abstract_name = 'abstract-class-' . str_replace('_', '-', strtolower($class_name)) . '.php';
        foreach ($directories as $directory) {
            $file_path = $directory . $abstract_name;
            if (file_exists($file_path)) {
                require_once $file_path;
                return;
            }
        }
    }
}

