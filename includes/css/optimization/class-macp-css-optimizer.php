<?php
/**
 * Main CSS optimization controller
 */
class MACP_CSS_Optimizer {
    /** @var MACP_CSS_Extractor */
    private $extractor;
    
    /** @var MACP_CSS_Minifier */
    private $minifier;
    
    /** @var MACP_CSS_Fetcher */
    private $fetcher;
    
    /** @var MACP_Used_CSS_Storage */
    private $storage;

    public function __construct() {
        $this->extractor = new MACP_CSS_Extractor();
        $this->minifier = new MACP_CSS_Minifier();
        $this->fetcher = new MACP_CSS_Fetcher();
        $this->storage = new MACP_Used_CSS_Storage();
    }

    /**
     * Optimize CSS in HTML content
     */
    public function optimize(string $html): string {
        if (!$this->should_process()) {
            return $html;
        }

        $url = $this->get_current_url();
        $optimized_css = $this->process_css($url, $html);
        
        if (!empty($optimized_css)) {
            $html = $this->replace_css($html, $optimized_css);
        }

        return $html;
    }

    /**
     * Process CSS optimization
     */
    private function process_css(string $url, string $html): string {
        $css_files = $this->extractor->extract_css_files($html);
        $used_selectors = $this->extractor->extract_used_selectors($html);
        
        $optimized_css = '';
        foreach ($css_files as $file) {
            $css_content = $this->fetcher->get_css_content($file);
            if (!$css_content) {
                continue;
            }
            
            $optimized_css .= $this->minifier->remove_unused_css(
                $css_content,
                $used_selectors
            );
        }

        if (!empty($optimized_css)) {
            $this->storage->save($url, $optimized_css);
        }

        return $optimized_css;
    }

    /**
     * Replace original CSS with optimized version
     */
    private function replace_css(string $html, string $optimized_css): string {
        // Remove original CSS links
        $html = preg_replace(
            '/<link[^>]*rel=["\']stylesheet["\'][^>]*>/i',
            '',
            $html
        );

        // Add optimized CSS
        $css_tag = sprintf(
            '<style id="macp-optimized-css">%s</style>',
            $optimized_css
        );

        return str_replace('</head>', $css_tag . '</head>', $html);
    }

    /**
     * Check if optimization should run
     */
    private function should_process(): bool {
        return get_option('macp_remove_unused_css', 0) 
            && !is_admin() 
            && !is_user_logged_in();
    }

    /**
     * Get current page URL
     */
    private function get_current_url(): string {
        global $wp;
        return home_url($wp->request);
    }
}