<?php
/**
 * Scanner Factory class
 *
 * Creates and manages scanner instances
 *
 * @package OptionMap
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Scanner Factory class
 */
class OptionMap_Scanner_Factory {

    /**
     * URL Validator instance
     *
     * @var OptionMap_URL_Validator
     */
    private $url_validator;

    /**
     * Settings Formatter instance
     *
     * @var OptionMap_Settings_Formatter
     */
    private $formatter;

    /**
     * Registered scanners
     *
     * @var array
     */
    private $scanners = array();

    /**
     * Constructor
     *
     * @param OptionMap_URL_Validator $url_validator URL validator instance
     * @param OptionMap_Settings_Formatter $formatter Settings formatter instance
     */
    public function __construct(OptionMap_URL_Validator $url_validator, OptionMap_Settings_Formatter $formatter) {
        $this->url_validator = $url_validator;
        $this->formatter = $formatter;
    }

    /**
     * Register a scanner
     *
     * @param string $name Scanner name/identifier
     * @param OptionMap_Scanner_Interface $scanner Scanner instance
     */
    public function register($name, OptionMap_Scanner_Interface $scanner) {
        $this->scanners[$name] = $scanner;
    }

    /**
     * Get a scanner by name
     *
     * @param string $name Scanner name
     * @return OptionMap_Scanner_Interface|null
     */
    public function get($name) {
        return isset($this->scanners[$name]) ? $this->scanners[$name] : null;
    }

    /**
     * Get all registered scanners
     *
     * @return array
     */
    public function get_all() {
        return $this->scanners;
    }

    /**
     * Run all scanners and return aggregated results
     *
     * @return array Array of scanner results
     */
    public function scan_all() {
        $results = array();
        
        foreach ($this->scanners as $name => $scanner) {
            try {
                $results[$name] = $scanner->scan();
            } catch (Exception $e) {
                // Log error but continue with other scanners
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('OptionMap Scanner Error (' . $name . '): ' . $e->getMessage());
                }
                $results[$name] = array();
            }
        }
        
        return $results;
    }
}

