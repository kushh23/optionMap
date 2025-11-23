<?php
/**
 * Repository Interface
 *
 * @package OptionMap
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Repository Interface
 */
interface OptionMap_Repository_Interface {
    /**
     * Get all settings
     *
     * @return array
     */
    public function get_all();

    /**
     * Get settings by category
     *
     * @param string $category Category name
     * @return array
     */
    public function get_by_category($category);

    /**
     * Search settings
     *
     * @param string $query Search query
     * @return array
     */
    public function search($query);
}

