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
            return $html;
        }

        $url = $this->get_current_url();
        $optimized_css = $this->processor->process($url, $html);
        
        if (!empty($optimized_css)) {
            // Replace all CSS links with optimized CSS
            $html = preg_replace(
                '/<link[^>]*rel=["\']stylesheet["\'][^>]*>/i',
                '',
                $html
            );

            // Add optimized CSS to head
            $css_tag = sprintf(
                '<style id="macp-optimized-css">%s</style>',
                $optimized_css
            );

            $html = str_replace(
                '</head>',
                $css_tag . '</head>',
                $html
            );
        }

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