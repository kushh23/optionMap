<?php
/**
 * Main Plugin class
 *
 * Orchestrates all plugin components using dependency injection
 *
 * @package OptionMap
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Plugin class
 */
class OptionMap_Plugin {

    /**
     * Plugin instance
     *
     * @var OptionMap_Plugin
     */
    private static $instance = null;

    /**
     * URL Validator
     *
     * @var OptionMap_URL_Validator
     */
    private $url_validator;

    /**
     * Settings Formatter
     *
     * @var OptionMap_Settings_Formatter
     */
    private $formatter;

    /**
     * Settings Aggregator
     *
     * @var OptionMap_Settings_Aggregator
     */
    private $aggregator;

    /**
     * Database Installer
     *
     * @var OptionMap_Database_Installer
     */
    private $db_installer;

    /**
     * Settings Manager
     *
     * @var OptionMap_Settings_Manager
     */
    private $settings_manager;

    /**
     * Scanner Factory
     *
     * @var OptionMap_Scanner_Factory
     */
    private $scanner_factory;

    /**
     * Admin Menu
     *
     * @var OptionMap_Admin_Menu
     */
    private $admin_menu;

    /**
     * Asset Manager
     *
     * @var OptionMap_Asset_Manager
     */
    private $asset_manager;

    /**
     * Chat History Manager
     *
     * @var OptionMap_Chat_History_Manager
     */
    private $history_manager;

    /**
     * Legacy SettingsFinder instance (for backward compatibility during migration)
     *
     * @var SettingsFinder
     */
    private $legacy_finder;

    /**
     * Get singleton instance
     *
     * @return OptionMap_Plugin
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
        $this->init_components();
        $this->init_hooks();
    }

    /**
     * Initialize components with dependency injection
     */
    private function init_components() {
        // Core utilities
        $this->url_validator = new OptionMap_URL_Validator();
        $this->formatter = new OptionMap_Settings_Formatter();
        $this->aggregator = new OptionMap_Settings_Aggregator($this->url_validator);

        // Database
        $this->db_installer = new OptionMap_Database_Installer();

        // Settings
        $this->settings_manager = new OptionMap_Settings_Manager();

        // Scanner Factory
        $this->scanner_factory = new OptionMap_Scanner_Factory($this->url_validator, $this->formatter);
        
        // Register all scanners
        $this->register_scanners();

        // Chat History Manager
        $this->history_manager = new OptionMap_Chat_History_Manager();

        // Legacy compatibility - load old class for methods not yet migrated
        if (class_exists('SettingsFinder')) {
            $this->legacy_finder = SettingsFinder::get_instance();
        }

        // Admin components
        $this->admin_menu = new OptionMap_Admin_Menu($this->legacy_finder);
        $this->asset_manager = new OptionMap_Asset_Manager($this->legacy_finder);

        // AJAX handlers
        new OptionMap_Search_Ajax_Handler();
        new OptionMap_Refresh_Scan_Ajax_Handler();
        new OptionMap_AI_Chat_Ajax_Handler(
            $this->settings_manager,
            $this->scanner_factory,
            $this->aggregator,
            $this->formatter,
            $this->history_manager,
            $this->legacy_finder
        );
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Activation/Deactivation
        register_activation_hook(SF_PLUGIN_BASENAME, array($this, 'activate'));
        register_deactivation_hook(SF_PLUGIN_BASENAME, array($this, 'deactivate'));

        // Admin hooks
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Plugin activation
     */
    public function activate() {
        $this->db_installer->install();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Cleanup if needed
    }

    /**
     * Register admin menu
     */
    public function register_admin_menu() {
        $this->admin_menu->register();
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_assets($hook) {
        $this->asset_manager->enqueue($hook);
    }

    /**
     * Get URL Validator
     *
     * @return OptionMap_URL_Validator
     */
    public function get_url_validator() {
        return $this->url_validator;
    }

    /**
     * Get Settings Formatter
     *
     * @return OptionMap_Settings_Formatter
     */
    public function get_formatter() {
        return $this->formatter;
    }

    /**
     * Get Settings Aggregator
     *
     * @return OptionMap_Settings_Aggregator
     */
    public function get_aggregator() {
        return $this->aggregator;
    }

    /**
     * Get Settings Manager
     *
     * @return OptionMap_Settings_Manager
     */
    public function get_settings_manager() {
        return $this->settings_manager;
    }

    /**
     * Get Scanner Factory
     *
     * @return OptionMap_Scanner_Factory
     */
    public function get_scanner_factory() {
        return $this->scanner_factory;
    }

    /**
     * Get legacy finder (for backward compatibility)
     *
     * @return SettingsFinder|null
     */
    public function get_legacy_finder() {
        return $this->legacy_finder;
    }

    /**
     * Register all scanners
     */
    private function register_scanners() {
        $this->scanner_factory->register('customizer', new OptionMap_Customizer_Scanner($this->url_validator, $this->formatter));
        $this->scanner_factory->register('theme_mods', new OptionMap_Theme_Mods_Scanner($this->url_validator, $this->formatter));
        $this->scanner_factory->register('database_options', new OptionMap_Database_Options_Scanner($this->url_validator, $this->formatter));
        $this->scanner_factory->register('menu_locations', new OptionMap_Menu_Locations_Scanner($this->url_validator, $this->formatter));
        $this->scanner_factory->register('widget_areas', new OptionMap_Widget_Areas_Scanner($this->url_validator, $this->formatter));
        $this->scanner_factory->register('page_templates', new OptionMap_Page_Templates_Scanner($this->url_validator, $this->formatter));
        $this->scanner_factory->register('block_patterns', new OptionMap_Block_Patterns_Scanner($this->url_validator, $this->formatter));
        $this->scanner_factory->register('custom_post_types', new OptionMap_Custom_Post_Types_Scanner($this->url_validator, $this->formatter));
        $this->scanner_factory->register('post_metaboxes', new OptionMap_Post_Metaboxes_Scanner($this->url_validator, $this->formatter));
        $this->scanner_factory->register('theme_support', new OptionMap_Theme_Support_Scanner($this->url_validator, $this->formatter));
        $this->scanner_factory->register('core_settings', new OptionMap_Core_Settings_Scanner($this->url_validator, $this->formatter));
        $this->scanner_factory->register('dashboard_pages', new OptionMap_Dashboard_Pages_Scanner($this->url_validator, $this->formatter));
    }
}

