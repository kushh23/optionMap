<?php
/**
 * AI Provider Interface
 *
 * @package OptionMap
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI Provider Interface
 */
interface OptionMap_AI_Provider_Interface {
    /**
     * Send message to AI and get response
     *
     * @param string $user_message User's message
     * @param string $context Context/settings data
     * @return array|WP_Error Response data or error
     */
    public function chat($user_message, $context);
}

