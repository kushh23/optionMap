<?php
/**
 * Chat History Manager class
 *
 * Manages AI chat history persistence
 *
 * @package OptionMap
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Chat History Manager class
 */
class OptionMap_Chat_History_Manager {

    /**
     * Get chat history for current user
     *
     * @return array Chat history
     */
    public function get_history() {
        $history = get_user_meta(get_current_user_id(), 'sf_ai_chat_history', true);
        return is_array($history) ? $history : array();
    }

    /**
     * Save chat history for current user
     *
     * @param array $history Chat history
     * @return bool Success status
     */
    public function save_history($history) {
        // Keep only last 50 conversations
        if (count($history) > 50) {
            $history = array_slice($history, -50);
        }
        
        return update_user_meta(get_current_user_id(), 'sf_ai_chat_history', $history);
    }

    /**
     * Add conversation to history
     *
     * @param string $user_message User message
     * @param string $bot_response Bot response
     * @param array $urls URLs from response
     * @return bool Success status
     */
    public function add_conversation($user_message, $bot_response, $urls = array()) {
        $history = $this->get_history();
        
        $history[] = array(
            'user' => $user_message,
            'bot' => $bot_response,
            'urls' => $urls,
            'timestamp' => current_time('timestamp')
        );
        
        return $this->save_history($history);
    }

    /**
     * Clear chat history for current user
     *
     * @return bool Success status
     */
    public function clear_history() {
        return delete_user_meta(get_current_user_id(), 'sf_ai_chat_history');
    }
}

