<?php
class MACP_Installer {
    public static function install() {
        self::create_database_tables();
        self::create_directories();
        self::set_default_options();
    }

    private static function create_database_tables() {
        $used_css_table = new MACP_Used_CSS_Table();
        $used_css_table->create_table();
        
        MACP_Debug::log('Database tables created');
    }

    private static function create_directories() {
        $dirs = [
            WP_CONTENT_DIR . '/cache/macp',
            WP_CONTENT_DIR . '/cache/macp/used-css',
            WP_CONTENT_DIR . '/cache/min'
        ];

        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
                file_put_contents($dir . '/index.php', '<?php // Silence is golden');
                chmod($dir, 0755);
                MACP_Debug::log('Created directory: ' . $dir);
            }
        }
    }

    private static function set_default_options() {
        $defaults = [
            'macp_enable_html_cache' => 1,
            'macp_enable_gzip' => 1,
            'macp_enable_redis' => 1,
            'macp_minify_html' => 0,
            'macp_enable_js_defer' => 0,
            'macp_enable_js_delay' => 0,
            'macp_enable_varnish' => 0,
            'macp_remove_unused_css' => 0
        ];

        foreach ($defaults as $key => $value) {
            add_option($key, $value);
        }
        
        MACP_Debug::log('Default options set');
    }
}