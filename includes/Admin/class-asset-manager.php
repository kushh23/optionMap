<?php
/**
 * Asset Manager class
 *
 * Handles CSS and JavaScript asset enqueuing
 *
 * @package OptionMap
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Asset Manager class
 */
class OptionMap_Asset_Manager {

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
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue($hook) {
        // Use legacy method for now
        if ($this->legacy_finder) {
            $this->legacy_finder->enqueue_admin_assets($hook);
        }
    }
}

