<?php
/**
 * Handles background processing of CSS optimization
 */
class MACP_CSS_Queue_Processor {
    private $table_name;
    private $batch_size = 5;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'macp_used_css';
        
        add_action('macp_process_css_queue', [$this, 'process_queue']);
        
        if (!wp_next_scheduled('macp_process_css_queue')) {
            wp_schedule_event(time(), 'hourly', 'macp_process_css_queue');
        }
    }

    public function process_queue() {
        global $wpdb;

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            WHERE status = 'pending' 
            AND retries < 3 
            LIMIT %d",
            $this->batch_size
        ));

        if (empty($items)) {
            return;
        }

        foreach ($items as $item) {
            try {
                $optimizer = new MACP_CSS_Optimizer();
                $response = wp_remote_get($item->url);
                
                if (is_wp_error($response)) {
                    $this->update_status($item->id, 'error', $response->get_error_message());
                    continue;
                }

                $html = wp_remote_retrieve_body($response);
                $optimized_css = $optimizer->optimize($html);

                if ($optimized_css) {
                    $wpdb->update(
                        $this->table_name,
                        [
                            'css' => $optimized_css,
                            'status' => 'completed',
                            'modified' => current_time('mysql')
                        ],
                        ['id' => $item->id]
                    );
                } else {
                    $this->update_status($item->id, 'error', 'Failed to optimize CSS');
                }
            } catch (Exception $e) {
                $this->update_status($item->id, 'error', $e->getMessage());
            }
        }
    }

    private function update_status($id, $status, $error = '') {
        global $wpdb;
        
        $wpdb->update(
            $this->table_name,
            [
                'status' => $status,
                'error_message' => $error,
                'retries' => new Raw('retries + 1'),
                'modified' => current_time('mysql')
            ],
            ['id' => $id]
        );
    }
}