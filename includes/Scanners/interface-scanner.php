<?php
/**
 * Scanner Interface
 *
 * @package OptionMap
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Scanner Interface
 */
interface OptionMap_Scanner_Interface {
    /**
     * Scan for settings
     *
     * @return array Array of settings
     */
    public function scan();
}

