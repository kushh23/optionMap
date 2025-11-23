<?php
/**
 * Admin Menu class
 *
 * Handles admin menu registration
 *
 * @package OptionMap
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Menu class
 */
class OptionMap_Admin_Menu {

    /**
     * Legacy SettingsFinder instance
     *
     * @var SettingsFinder
     */
    private $legacy_finder;

    /**
     * Constructor
     *
     * @param SettingsFinder $legacy_finder Legacy finder instance
     */
    public function __construct($legacy_finder = null) {
        $this->legacy_finder = $legacy_finder;
    }

    /**
     * Register admin menu
     */
    public function register() {
        // Use legacy method for now - will be migrated to separate page classes
        if ($this->legacy_finder) {
            $this->legacy_finder->add_admin_menu();
        } else {
            // Fallback if legacy finder not available
            add_menu_page(
                __('Option Map', 'option-map'),
                __('Option Map', 'option-map'),
                'manage_options',
                'settings-finder',
                array($this, 'render_main_page'),
                'dashicons-search',
                75
            );
        }
    }

    /**
     * Render main page (fallback)
     */
    public function render_main_page() {
        echo '<div class="wrap"><h1>Option Map</h1><p>Plugin is initializing...</p></div>';
    }
}

