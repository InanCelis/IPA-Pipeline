<?php
// Prevent any output before XML declaration
ob_start();

// Suppress all PHP warnings and notices that could break XML
error_reporting(0);
ini_set('display_errors', 0);

// Clear any existing output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Set XML headers
header('Content-Type: application/xml; charset=UTF-8', true);
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

// Start clean output buffer
ob_start();

class PropertyXMLFeed {
    private $country_slug;
    private $count;
    private $page;
    private $query;
    private $price;
    private $batch_size = 50;
    private $memory_limit;
    private $start_time;
    private $max_execution_time = 300;
    private $output_started = false;
    private $apits_mode = false;
    private $apits_configs = array();
    
    public function __construct() {
        // Set up memory and execution time limits
        $this->setup_limits();
        
        // Initialize timing
        $this->start_time = time();

        // Check for APITS mode
        $this->check_apits_mode();
        
        // Initialize parameters
        $this->initialize_parameters();
        
        // Start XML output
        $this->start_xml_output();
        
        // Build and execute query
        // $this->build_query();
        // $this->render_xml();

        // Build and execute query
        if ($this->apits_mode) {
            $this->render_apits_xml();
        } else {
            $this->build_query();
            $this->render_xml();
        }
        
        // End XML output
        $this->end_xml_output();
    }


    private function check_apits_mode() {
        if (isset($_GET['apits']) && $_GET['apits'] === 'true') {
            $this->apits_mode = true;
            $this->setup_apits_configs();
        }
    }

    private function setup_apits_configs() {
        $this->apits_configs = array(
            // array('United Arab Emirates', 310, 'a_price'),     
            // array('Cambodia', 40, 'd_price'),   
            array('Cyprus', 2000, 'a_price'),     
            // array('Greece', 11, 'a_price'),     
            // array('Indonesia', 300, 'a_price'),     
            // array('Maldives', 10, 'a_price'),     
            // array('Mexico', 170, 'a_price'),     
            // array('New Zealand', 4, 'a_price'),     
            // array('Oman', 5, 'a_price'),     
            // array('Philippines', 80, 'd_price'),     
            // array('Saudi Arabia', 2, 'a_price'),     
            // array('Thailand', 300, 'd_price'),     
            // array('Turkey', 328, 'a_price'),     
            // array('United Kingdom', 40, 'a_price'),     
            // array('Egypt', 100, 'a_price'),     
        );
    }

    private function setup_limits() {
        // Increase memory limit if possible
        $current_memory = ini_get('memory_limit');
        if ($current_memory && $current_memory !== '-1') {
            $memory_in_bytes = $this->convert_to_bytes($current_memory);
            $desired_memory = max($memory_in_bytes, 512 * 1024 * 1024); // At least 512MB
            @ini_set('memory_limit', $desired_memory);
        }
        
        // Set maximum execution time
        if (!ini_get('safe_mode')) {
            @set_time_limit($this->max_execution_time);
        }
        
        // Store current memory limit for monitoring
        $this->memory_limit = $this->convert_to_bytes(ini_get('memory_limit'));
        
        // Disable gzip compression for streaming
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
        }
        @ini_set('zlib.output_compression', 0);
        @ini_set('implicit_flush', 1);
    }

    private function convert_to_bytes($memory_limit) {
        if ($memory_limit === '-1') return PHP_INT_MAX;
        
        $unit = strtolower(substr($memory_limit, -1));
        $value = (int) $memory_limit;
        
        switch ($unit) {
            case 'g': return $value * 1024 * 1024 * 1024;
            case 'm': return $value * 1024 * 1024;
            case 'k': return $value * 1024;
            default: return $value;
        }
    }

    private function initialize_parameters() {
        $this->country_slug = isset($_GET['country']) ? 
            strtolower(str_replace('-', ' ', sanitize_text_field($_GET['country']))) : '';
        $this->price = isset($_GET['price']) ? 
            strtolower(str_replace('-', ' ', sanitize_text_field($_GET['price']))) : '';
        $this->page = isset($_GET['page']) && is_numeric($_GET['page']) ? 
            intval($_GET['page']) : 1;
        
        // Handle count parameter with reasonable limits
        $this->count = -1; // Default to all
        if (isset($_GET['count'])) {
            $count_param = intval($_GET['count']);
            if ($count_param > 0) {
                $this->count = min($count_param, 10000); // Cap at 10,000 for safety
            }
        }

        // Adjust batch size based on count
        if ($this->count > 0 && $this->count < $this->batch_size) {
            $this->batch_size = $this->count;
        }
    }

    private function start_xml_output() {
        // Clear any accumulated output
        if (ob_get_contents()) {
            ob_clean();
        }
        
        // Output XML declaration
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $this->output_started = true;
        $this->flush_output();
    }

    private function end_xml_output() {
        $this->flush_output();
        if (ob_get_level()) {
            ob_end_flush();
        }
    }

    private function flush_output() {
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }


    private function render_apits_xml() {
        try {
            $all_properties = array();
            $total_count = 0;

            // Process each country configuration
            foreach ($this->apits_configs as $config) {
                $country = $config[0];
                $count = $config[1];
                $price_order = $config[2];

                // Get properties for this configuration
                $properties = $this->get_properties_by_config($country, $count, $price_order);
                
                if (!empty($properties)) {
                    $all_properties = array_merge($all_properties, $properties);
                    $total_count += count($properties);
                }

                // Check resource limits
                if ($this->check_resource_limits()) {
                    break;
                }
            }

            if (empty($all_properties)) {
                http_response_code(404);
                echo '<NotFound>No properties found</NotFound>';
                return;
            }

            echo '<Properties Total="' . esc_attr($total_count) . '">' . "\n";
            $this->flush_output();

            // Output all collected properties
            foreach ($all_properties as $property_data) {
                $this->output_property_xml(
                    $property_data['post_id'],
                    $property_data['meta'],
                    $property_data['taxonomy']
                );
                
                // Force output every 10 properties
                static $output_counter = 0;
                $output_counter++;
                if ($output_counter % 10 === 0) {
                    $this->flush_output();
                }
            }

            echo '</Properties>' . "\n";
            $this->flush_output();

        } catch (Exception $e) {
            echo '<Error>Feed generation failed</Error>' . "\n";
        }
    }

    private function get_properties_by_config($country, $count, $price_order) {
        $properties = array();
        $country_slug = strtolower(str_replace(' ', '-', $country));

        // Determine price order
        $orderby = 'meta_value_num';
        $meta_key = 'fave_property_price';
        // $order = ($price_order === 'a_price') ? 'ASC' : 'DESC';

        if($price_order === 'a_price') {
            $order = 'ASC';
        } else if($price_order === 'd_price') {
            $order = 'DESC';
        } else {
            $order = 'rand';
            $order = '';
            $meta_key = ''; 
        }

        // Build meta query to exclude 0 prices
        $meta_query = array(
            array(
                'key' => 'fave_property_price',
                'value' => 0,
                'compare' => '>',
                'type' => 'NUMERIC'
            )
        );

        $args = array(
            'post_type'      => 'property',
            'posts_per_page' => $count,
            'post_status'    => 'publish',
            'orderby'        => $orderby,
            'meta_key'       => $meta_key,
            'order'          => $order,
            'meta_query'     => $meta_query,
            'tax_query'      => array(
                array(
                    'taxonomy' => 'property_country',
                    'field'    => 'slug',
                    'terms'    => $country_slug,
                )
            ),
            'no_found_rows'  => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        );

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();

                $properties[] = array(
                    'post_id' => $post_id,
                    'meta' => $this->get_property_meta($post_id),
                    'taxonomy' => $this->get_property_taxonomies($post_id)
                );
            }
        }

        wp_reset_postdata();
        $this->cleanup_memory();

        return $properties;
    }

    private function build_query() {
        $tax_query = array();

        if (!empty($this->country_slug)) {
            $tax_query[] = array(
                'taxonomy' => 'property_country',
                'field'    => 'slug',
                'terms'    => $this->country_slug,
            );
        }

        // Default order settings
        $orderby = 'ID';
        $order = 'DESC';
        $meta_key = '';

        // Handle price sorting
        if (!empty($_GET['price'])) {
            $price_order = strtolower($_GET['price']);
            if ($price_order === 'asc' || $price_order === 'desc') {
                $orderby = 'meta_value_num';
                $meta_key = 'fave_property_price';
                $order = strtoupper($price_order);
            }
        }

        $args = array(
            'post_type'      => 'property',
            'posts_per_page' => $this->batch_size,
            'paged'          => $this->page,
            'post_status'    => 'publish',
            'orderby'        => $orderby,
            'order'          => $order,
            'no_found_rows'  => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        );

        if ($meta_key) {
            $args['meta_key'] = $meta_key;
        }

        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }

        $this->query = new WP_Query($args);
    }

    private function render_xml() {
        try {
            if (!$this->query->have_posts()) {
                http_response_code(404);
                echo '<NotFound>No properties found</NotFound>';
                return;
            }

            $total_properties = $this->query->found_posts;
            $processed_count = 0;
            $current_page = $this->page;
            
            // Apply count limit to total if specified
            if ($this->count > 0) {
                $total_properties = min($total_properties, $this->count);
            }

            echo '<Properties Total="' . esc_attr($total_properties) . '">' . "\n";
            $this->flush_output();
            
            // Process properties in batches
            do {
                // Check memory usage
                if ($this->check_resource_limits()) {
                    break;
                }

                while ($this->query->have_posts()) {
                    $this->query->the_post();
                    
                    // Check if we've reached the count limit
                    if ($this->count > 0 && $processed_count >= $this->count) {
                        break 2; // Break out of both loops
                    }
                    
                    $this->render_property();
                    $processed_count++;
                    
                    // Force output every 10 properties
                    if ($processed_count % 10 === 0) {
                        $this->flush_output();
                    }
                }

                // Clean up memory after each batch
                wp_reset_postdata();
                $this->cleanup_memory();

                // Check if we need to load more batches
                if ($this->count === -1 || $processed_count < $this->count) {
                    $current_page++;
                    
                    // Build new query for next batch
                    $this->page = $current_page;
                    $this->build_query();
                    
                    // Break if no more posts
                    if (!$this->query->have_posts()) {
                        break;
                    }
                } else {
                    break; // We've reached our count limit
                }

            } while (true);
            
            echo '</Properties>' . "\n";
            
            // Final cleanup
            wp_reset_postdata();
            $this->flush_output();
            
        } catch (Exception $e) {
            echo '<Error>Feed generation failed</Error>' . "\n";
        }
    }

    private function check_resource_limits() {
        // Check execution time
        if (time() - $this->start_time > ($this->max_execution_time - 30)) {
            return true;
        }

        // Check memory usage
        if ($this->memory_limit !== PHP_INT_MAX) {
            $current_memory = memory_get_usage(true);
            if ($current_memory > ($this->memory_limit * 0.8)) {
                return true;
            }
        }

        return false;
    }

    private function cleanup_memory() {
        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
        
        // Clear WordPress object cache if available
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }

    private function render_property() {
        try {
            $post_id = get_the_ID();
            
            // Get all data in one go to minimize database queries
            $meta_data = $this->get_property_meta($post_id);
            $taxonomy_data = $this->get_property_taxonomies($post_id);
            
            $this->output_property_xml($post_id, $meta_data, $taxonomy_data);
            
        } catch (Exception $e) {
            // Continue processing other properties
        }
    }

    private function get_property_meta($post_id) {
        // Get all meta data in one query
        return array(
            'property_id' => get_post_meta($post_id, 'fave_property_id', true),
            'bedrooms' => get_post_meta($post_id, 'fave_property_bedrooms', true),
            'bathrooms' => get_post_meta($post_id, 'fave_property_bathrooms', true),
            'size' => get_post_meta($post_id, 'fave_property_size', true),
            'size_prefix' => get_post_meta($post_id, 'fave_property_size_prefix', true),
            'address' => get_post_meta($post_id, 'fave_property_address', true),
            'year' => get_post_meta($post_id, 'fave_property_year', true),
            'city' => get_post_meta($post_id, 'fave_property_city', true),
            'state' => get_post_meta($post_id, 'fave_property_state', true),
            'country' => get_post_meta($post_id, 'fave_property_country', true),
            'zip' => get_post_meta($post_id, 'fave_property_zip', true),
            'price' => get_post_meta($post_id, 'fave_property_price', true),
            'currency' => get_post_meta($post_id, 'fave_currency', true),
            'price_postfix' => get_post_meta($post_id, 'fave_property_price_postfix', true),
            'price_prefix' => get_post_meta($post_id, 'fave_property_price_prefix', true),
            'location' => get_post_meta($post_id, 'fave_property_location', true),
            'latitude' => get_post_meta($post_id, 'houzez_geolocation_lat', true),
            'longitude' => get_post_meta($post_id, 'houzez_geolocation_long', true),
            'images' => get_post_meta($post_id, 'fave_property_images', false),
        );
    }

    private function get_property_taxonomies($post_id) {
        return array(
            'status' => wp_get_object_terms($post_id, 'property_status'),
            'type' => wp_get_object_terms($post_id, 'property_type'),
            'label' => wp_get_object_terms($post_id, 'property_label'),
            'feature' => wp_get_object_terms($post_id, 'property_feature'),
            'area' => wp_get_object_terms($post_id, 'property_area'),
            'city_terms' => wp_get_object_terms($post_id, 'property_city'),
            'state_terms' => wp_get_object_terms($post_id, 'property_state'),
            'country_terms' => wp_get_object_terms($post_id, 'property_country'),
        );
    }

    private function output_property_xml($post_id, $meta, $taxonomy) {
        // Remove commas from the price
        $price = str_replace(',', '', $meta['price']);
        
        echo '    <Property>' . "\n";
        echo '        <ListingId>' . $this->clean($meta['property_id']) . '</ListingId>' . "\n";
        echo '        <Title>' . $this->clean(get_the_title()) . '</Title>' . "\n";
        echo '        <Url>' . esc_url(get_permalink()) . '</Url>' . "\n";
        echo '        <Excerpt><![CDATA[' . $this->clean(get_the_excerpt()) . ']]></Excerpt>' . "\n";
        echo '        <PublishDate>' . $this->clean(get_the_date('Y-m-d')) . '</PublishDate>' . "\n";
        echo '        <LastModified>' . $this->clean(get_the_modified_date('Y-m-d')) . '</LastModified>' . "\n";
        echo '        <Description><![CDATA[' . $this->remove_html(get_the_content()) . ']]></Description>' . "\n";
        echo '        <YearBuilt>' . $this->clean($meta['year']) . '</YearBuilt>' . "\n";
        echo '        <Bedrooms>' . $this->clean($meta['bedrooms']) . '</Bedrooms>' . "\n";
        echo '        <Bathrooms>' . $this->clean($meta['bathrooms']) . '</Bathrooms>' . "\n";
        echo '        <Size prefix="' . $this->clean($meta['size_prefix']) . '">' . $this->clean($meta['size']) . '</Size>' . "\n";
        
        echo '        <AddressDetails>' . "\n";
        echo '            <address>' . $this->clean($meta['address']) . '</address>' . "\n";
        echo '            <area>' . (!empty($taxonomy['area']) ? $this->clean($taxonomy['area'][0]->name) : '') . '</area>' . "\n";
        echo '            <city>' . (!empty($meta['city']) ? $this->clean($meta['city']) : (!empty($taxonomy['city_terms'][0]) ? $this->clean($taxonomy['city_terms'][0]->name) : '')) . '</city>' . "\n";
        echo '            <state>' . (!empty($meta['state']) ? $this->clean($meta['state']) : (!empty($taxonomy['state_terms'][0]) ? $this->clean($taxonomy['state_terms'][0]->name) : '')) . '</state>' . "\n";
        echo '            <country>' . (!empty($taxonomy['country_terms'][0]) ? $this->clean($taxonomy['country_terms'][0]->name) : '') . '</country>' . "\n";
        echo '            <zip>' . $this->clean($meta['zip']) . '</zip>' . "\n";
        echo '            <location>' . $this->clean($meta['location']) . '</location>' . "\n";
        echo '            <latitude>' . $this->clean($meta['latitude']) . '</latitude>' . "\n";
        echo '            <longtitude>' . $this->clean($meta['longitude']) . '</longtitude>' . "\n";
        echo '        </AddressDetails>' . "\n";
        
        echo '        <Statuses>' . "\n";
        $this->render_taxonomy_values($taxonomy['status'], 'status');
        echo '        </Statuses>' . "\n";
        
        echo '        <Types>' . "\n";
        $this->render_taxonomy_values($taxonomy['type'], 'type');
        echo '        </Types>' . "\n";
        
        echo '        <Labels>' . "\n";
        $this->render_taxonomy_values($taxonomy['label'], 'label');
        echo '        </Labels>' . "\n";
        
        echo '        <Price>' . "\n";
        echo '            <value>' . $this->clean($price) . '</value>' . "\n";
        echo '            <currency>' . $this->clean($meta['currency']) . '</currency>' . "\n";
        echo '            <postfix>' . $this->clean($meta['price_postfix']) . '</postfix>' . "\n";
        echo '            <prefix>' . $this->clean($meta['price_prefix']) . '</prefix>' . "\n";
        echo '        </Price>' . "\n";
        
        echo '        <images>' . "\n";
        $image_ids = $meta['images'];
        if (is_array($image_ids)) {
            foreach ($image_ids as $image_id) {
                if (is_numeric($image_id)) {
                    $url = wp_get_attachment_url($image_id);
                    if ($url) {
                        echo '            <image>' . esc_url($url) . '</image>' . "\n";
                    }
                }
            }
        }
        echo '        </images>' . "\n";
        
        echo '        <Features>' . "\n";
        $this->render_taxonomy_values($taxonomy['feature'], 'feature');
        echo '        </Features>' . "\n";
        
        echo '    </Property>' . "\n";
    }

    private function render_taxonomy_values($terms, $xml_term) {
        if (!empty($terms) && is_array($terms)) {
            foreach ($terms as $term) {
                if (is_object($term) && isset($term->name)) {
                    echo '            <' . $xml_term . '>' . $this->clean($term->name) . '</' . $xml_term . '>' . "\n";
                }
            }
        }
    }

    private function remove_html($content) {
        $content = wp_strip_all_tags($content);
        $content = html_entity_decode($content, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $content = preg_replace("/[\r\n]+/", "\n", $content);
        $content = nl2br(trim($content), false);
        return $content;
    }

    private function clean($value) {
        if (empty($value)) return '';
        $value = (string) $value;
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        $value = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $value);
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}

// Initialize the feed
new PropertyXMLFeed();