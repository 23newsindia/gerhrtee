<?php
/**
 * Manages CSS processing and optimization
 */
class MACP_CSS_Processor_Manager {
    private $optimizer;
    private $debug;

    public function __construct() {
        $this->optimizer = new MACP_CSS_Optimizer();
        $this->debug = new MACP_Debug();
        
        $this->init_hooks();
    }

    private function init_hooks() {
        // Only run on frontend
        if (!is_admin()) {
            add_action('template_redirect', [$this, 'start_buffer'], -9999);
            add_action('shutdown', [$this, 'end_buffer'], 9999999);
        }
    }

    public function start_buffer() {
        if ($this->should_process()) {
            ob_start([$this, 'process_output']);
        }
    }

    public function end_buffer() {
        if ($this->should_process() && ob_get_level()) {
            ob_end_flush();
        }
    }

    public function process_output($html) {
        if (empty($html)) {
            return $html;
        }

        try {
            return $this->optimizer->optimize($html);
        } catch (Exception $e) {
            $this->debug->log('CSS processing error: ' . $e->getMessage());
            return $html;
        }
    }

    private function should_process() {
        return get_option('macp_remove_unused_css', 0) && 
               !is_admin() && 
               !is_user_logged_in() &&
               !$this->is_excluded_url();
    }

    private function is_excluded_url() {
        $current_url = $_SERVER['REQUEST_URI'];
        $excluded_patterns = [
            '/wp-admin',
            '/wp-login.php',
            '/wp-cron.php',
            '/wp-json',
            '/feed',
            '/sitemap'
        ];

        foreach ($excluded_patterns as $pattern) {
            if (strpos($current_url, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }
}