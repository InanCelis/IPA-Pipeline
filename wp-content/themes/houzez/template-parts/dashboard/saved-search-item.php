<?php
global $houzez_search_data;
$search_args = $houzez_search_data->query;
$search_args_decoded = unserialize(base64_decode($search_args));
$search_uri = $houzez_search_data->url;
$search_page = houzez_get_template_link('template/template-search.php');
$search_link = $search_page . '/?' . $search_uri;

// Parse the search URI into parameters
parse_str($search_uri, $search_params);

// Extract meta query values into cleaner array
$meta_query = array();
if (isset($search_args_decoded['meta_query'])) {
    foreach ($search_args_decoded['meta_query'] as $value) {
        if (is_array($value) && isset($value['key'])) {
            $meta_query[] = $value;
        } elseif (is_array($value)) {
            array_walk_recursive($value, function($item) use (&$meta_query) {
                if (is_array($item) && isset($item['key'])) {
                    $meta_query[] = $item;
                }
            });
        }
    }
}

// Helper function to format range values
if (!function_exists('format_range_value')) {
    function format_range_value($value) {
        if (is_array($value)) {
            // Check if array has both index 0 and 1 before accessing
            $start = isset($value[0]) ? esc_attr($value[0]) : '';
            $end = isset($value[1]) ? ' - ' . esc_attr($value[1]) : '';
            return $start . $end;
        }
        return esc_attr($value);
    }
}

?>
<tr>
    <td data-label="<?php esc_html_e('Search Parameters', 'houzez'); ?>">
        <?php 
        // Display keyword if exists
        if (!empty($search_args_decoded['s'])) {
            printf('<strong>%s:</strong> %s / ', 
                esc_html__('Keyword', 'houzez'),
                esc_attr($search_args_decoded['s'])
            );
        }

        // Display location from search URI
        if (!empty($search_params['search_location'])) {
            printf('<strong>%s:</strong> %s / ',
                esc_html__('Location', 'houzez'),
                urldecode($search_params['search_location'])
            );
        }

        // Handle taxonomy queries
        if (isset($search_args_decoded['tax_query'])) {
            $taxonomy_labels = array(
                'property_status' => esc_html__('Status', 'houzez'),
                'property_type' => esc_html__('Type', 'houzez'),
                'property_city' => esc_html__('City', 'houzez'),
                'property_country' => esc_html__('Country', 'houzez'),
                'property_state' => esc_html__('State', 'houzez'),
                'property_area' => esc_html__('Area', 'houzez'),
                'property_label' => esc_html__('Label', 'houzez')
            );

            foreach ($search_args_decoded['tax_query'] as $val) {
                if (isset($val['taxonomy'], $val['terms']) && isset($taxonomy_labels[$val['taxonomy']])) {
                    $term_value = hz_saved_search_term($val['terms'], $val['taxonomy']);
                    if (!empty($term_value)) {
                        printf('<strong>%s:</strong> %s / ',
                            $taxonomy_labels[$val['taxonomy']],
                            esc_attr($term_value)
                        );
                    }
                }
            }
        }

        // Handle meta queries
        if (isset($search_args_decoded['meta_query']) && is_array($search_args_decoded['meta_query'])) {
            $meta_field_labels = array(
                'fave_property_address' => esc_html__('Address', 'houzez'),
                'fave_property_bedrooms' => esc_html__('Bedrooms', 'houzez'),
                'fave_property_bathrooms' => esc_html__('Bathrooms', 'houzez'),
                'fave_property_rooms' => esc_html__('Rooms', 'houzez'),
                'fave_property_garage' => esc_html__('Garage', 'houzez'),
                'fave_property_year' => esc_html__('Year Built', 'houzez'),
                'fave_property_id' => esc_html__('Property ID', 'houzez'),
                'fave_property_price' => esc_html__('Price', 'houzez'),
                'fave_property_size' => esc_html__('Size', 'houzez'),
                'fave_property_land' => esc_html__('Land Area', 'houzez'),
                'fave_property_zip' => esc_html__('Zip Code', 'houzez')
            );

            // Recursively search through meta_query array
            if (!function_exists('search_meta_values')) {
                function search_meta_values($array, $meta_field_labels) {
                    foreach ($array as $key => $value) {
                        if (is_array($value)) {
                            if (isset($value['key']) && $value['key'] !== 'houzez_geolocation_lat' && $value['key'] !== 'houzez_geolocation_long') {
                                // Handle both standard and custom fields
                                $field_label = isset($meta_field_labels[$value['key']]) ? 
                                    $meta_field_labels[$value['key']] : 
                                    ucwords(str_replace(array('fave_', '_'), array('', ' '), $value['key']));
                                
                                if (isset($value['value'])) {
                                    printf('<strong>%s:</strong> %s / ',
                                        $field_label,
                                        format_range_value($value['value'])
                                    );
                                }
                            } else {
                                search_meta_values($value, $meta_field_labels);
                            }
                        }
                    }
                }
            }

            search_meta_values($search_args_decoded['meta_query'], $meta_field_labels);
        }
        ?>
    </td>
    <td class="property-table-actions" data-label="<?php esc_html_e('Actions', 'houzez'); ?>">
        <div class="dropdown property-action-menu">
            <button class="btn btn-primary-outlined dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <?php esc_html_e('Actions', 'houzez'); ?>
            </button>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
                <a class="dropdown-item" target="_blank" href="<?php echo esc_url($search_link); ?>"><?php esc_html_e('View', 'houzez'); ?></a>
                <a data-propertyid='<?php echo intval($houzez_search_data->id); ?>' class="remove-search dropdown-item" href="#"><?php esc_html_e('Delete', 'houzez'); ?></a>
            </div>
        </div>
    </td>
</tr>
