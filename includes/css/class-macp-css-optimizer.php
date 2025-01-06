<?php
class MACP_CSS_Optimizer {
    private $processor;
    private $used_css_table;

    public function __construct() {
        $this->processor = new MACP_CSS_Processor();
        $this->used_css_table = new MACP_Used_CSS_Table();
        
        // Create table if not exists
        add_action('init', [$this->used_css_table, 'create_table']);
    }

     public function optimize($html) {
        if (!$this->should_process()) {
            MACP_Debug::log('Skipping CSS optimization - conditions not met');
            return $html;
        }
       
       try {
            $url = $this->get_current_url();
            MACP_Debug::log('Starting CSS optimization for URL: ' . $url);

            // Extract and process CSS files
            $css_files = $this->extractor->extract_css_files($html);
            MACP_Debug::log('Found CSS files:', $css_files);

            $used_selectors = $this->extractor->extract_used_selectors($html);
            MACP_Debug::log('Found used selectors: ' . count($used_selectors));

            $optimized_css = $this->process_css($url, $html);
            
            if (!empty($optimized_css)) {
                MACP_Debug::log('Successfully optimized CSS');
                $html = $this->replace_css($html, $optimized_css);
            } else {
                MACP_Debug::log('No CSS was optimized');
            }

            return $html;
        } catch (Exception $e) {
            MACP_Debug::log('CSS optimization error: ' . $e->getMessage());
            return $html;
        }


    private function should_process() {
        // Skip if not enabled
        if (!get_option('macp_remove_unused_css', 0)) {
            return false;
        }

        // Skip admin pages
        if (is_admin()) {
            return false;
        }

        // Skip if user is logged in
        if (is_user_logged_in()) {
            return false;
        }

        return true;
    }

    private function get_current_url() {
        global $wp;
        return home_url($wp->request);
    }
}