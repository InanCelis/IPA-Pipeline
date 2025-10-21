<?php
header('Content-Type: application/xml; charset=' . get_option('blog_charset'), true);
echo '<?xml version="1.0" encoding="' . get_option('blog_charset') . '"?>';
require_once get_template_directory() . '/xml/property/functions/XMLHelper.php';

class PropertyXMLFeed {
    // private $count;
    // private $page;
    private $query;
    private $helper;

    public function __construct() {
        $this->helper = new XMLHelper();
        
        @ob_end_clean(); // Clear existing output buffer
        ob_implicit_flush(true); // Enable real-time output
        // $this->page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
        // // Handle count parameter
        // $this->count = -1; // Default to all
        // if (isset($_GET['count'])) {
        //     $count_param = intval($_GET['count']);
        //     if ($count_param > 0) {
        //         $this->count = $count_param;
        //     }
        // }
        $this->build_query();
        $this->render_xml();
    }

    private function build_query() {
        $tax_query[] = [
            'taxonomy' => 'property_country',
            'field'    => 'slug',
            'terms'    => 'Spain',
        ];

        $args = [
            'post_type'      => 'property',
            'posts_per_page' => 100,
            'post_status'    => 'publish',
            'orderby'        => 'meta_value_num',
            'meta_key'       => 'fave_local_property_price',
            'order'          => 'DESC',
        ];

        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }

        $this->query = new WP_Query($args);
    }

    private function render_xml() {
        if ($this->query->have_posts()) {
            echo '<root>';
            ?>
                <thinkspain>
                    <import_version>1.16</import_version>
                </thinkspain>

                <agent>
                    <name>International Property Alerts</name>
                </agent>
            <?php
            while ($this->query->have_posts()) {
                $this->query->the_post();
                $this->render_property();

                // Stream each post's output to browser
                @ob_flush();
                @flush();
            }
            echo '</root>';
            wp_reset_postdata();
        } else {
            http_response_code(404);
            echo '<NotFound>No properties found</NotFound>';
        }
    }

    private function render_property() {
        try {

            $post_id = get_the_ID();
            $type = wp_get_object_terms($post_id, 'property_type', true);
            $year = get_post_meta($post_id, 'fave_property_year', true);
            //town
            $city = get_post_meta($post_id, 'fave_property_city', true);
            $city_terms = wp_get_object_terms($post_id, 'property_city', true);
            $area = wp_get_object_terms($post_id, 'property_area', true);
            $address = get_post_meta($post_id, 'fave_property_address', true);
            //province
            $state = get_post_meta($post_id, 'fave_property_state', true);
            $state_terms = wp_get_object_terms($post_id, 'property_state', true);
            //postcode
            $zip = get_post_meta($post_id, 'fave_property_zip', true);
            $feature = wp_get_object_terms($post_id, 'property_feature', true);
            ?>

            <property>
                <last_amended_date><?php echo $this->helper->clean(get_the_date('Y-m-d H:i:s')); ?></last_amended_date>
                <unique_id><?php echo $this->helper->clean(get_the_ID()); ?></unique_id>
                <agent_ref><?php echo $this->helper->clean(get_post_meta($post_id, 'fave_property_id', true)); ?></agent_ref>
                <euro_price><?php echo $this->helper->clean(str_replace(',', '', get_post_meta($post_id, 'fave_local_property_price', true))); ?></euro_price>
                <euro_price_high></euro_price_high>
                <currency><?php echo $this->helper->clean(get_post_meta($post_id, 'fave_local_currency', true)); ?></currency>
                <sale_type>sale</sale_type>
                <property_type><?php echo $this->helper->clean(esc_xml($type[0]->name)) ?></property_type>
                <new_build></new_build>
                <year_built><?php echo $this->helper->clean($year); ?></year_built>
                <street_name></street_name>
                <street_number></street_number>
                <floor_number></floor_number>
                <door_number></door_number>
                <town><?php echo !empty($city) ? esc_xml($city) : (!empty($city_terms[0]) ? esc_xml($city_terms[0]->name) : ''); ?></town>
                <location_detail><?php echo !empty($area) ? $this->helper->clean($area[0]->name) : ''; ?></location_detail>
                <province><?php echo !empty($state) ? esc_xml($state) : (!empty($state_terms[0]) ? esc_xml($state_terms[0]->name) : ''); ?></province>
                <postcode><?php echo $this->helper->clean($zip); ?></postcode>
                <full_address><?php echo $this->helper->clean($address); ?></full_address>
                <display_address>1</display_address>
                <catastral></catastral>
                <location>
                    <latitude><?php echo $this->helper->clean(get_post_meta($post_id, 'houzez_geolocation_lat', true)); ?></latitude>
                    <longitude><?php echo $this->helper->clean(get_post_meta($post_id, 'houzez_geolocation_long', true)); ?></longitude>
                    <geoapprox>1</geoapprox>
                </location>
                <url><?php echo esc_url(get_permalink()); ?></url>
                <description>
                    <en><![CDATA[<?php echo $this->helper->remove_html(get_the_content()); ?>]]></en>
                </description>
                <?php
                    echo '<images>';
                    $image_ids = get_post_meta($post_id, 'fave_property_images', false);
                    $id_count = 1;
                    // Normalize to array
                    if (!is_array($image_ids)) {
                        $image_ids = array();
                    }
                    foreach ($image_ids as $image_id) {
                        // Ensure it's a valid numeric ID
                        if (is_numeric($image_id)) {
                            $url = wp_get_attachment_url($image_id);
                            if ($url) {
                                echo '<photo id="' . $id_count++ . '">';
                                echo '<url>' . esc_url($url) . '</url>';
                                echo '</photo>';
                            }
                        }
                    }
                    echo '</images>';

                ?>
                <media>
                    <video provider="youtube"><?php echo $this->helper->clean(get_post_meta($post_id, 'fave_video_url', true)); ?></video>
                    <virtualtour provider="eyespy360"><?php echo $this->helper->clean(get_post_meta($post_id, 'fave_virtual_tour', true)); ?></virtualtour>
                    <floorplan title="Ground Floor"></floorplan>
                    <floorplan title="First Floor"></floorplan>
                </media>
                <bedrooms><?php echo $this->helper->clean(get_post_meta($post_id, 'fave_property_bedrooms', true)); ?></bedrooms>
                <bathrooms><?php echo $this->helper->clean(get_post_meta($post_id, 'fave_property_bathrooms', true)); ?></bathrooms>
                <toilets></toilets>
                <living_area></living_area>
                <plot_size><?php echo $this->helper->clean(get_post_meta($post_id, 'fave_property_size', true)); ?></plot_size>
                <pool></pool>
                <aircon></aircon>
                <heating></heating>
                <garage></garage>
                <levels></levels>
                <features>
                     <?php $this->helper->render_taxonomy_values($feature, 'feature'); ?>
                </features>
                <energy_rating>
                    <consumption></consumption>
                    <emissions></emissions>
                </energy_rating>
                <km_golf></km_golf>
                <km_town></km_town>
                <km_airport></km_airport>
                <km_beach></km_beach>
                <km_marina></km_marina>
                <km_countryside></km_countryside>
            </property>
            <?php
        
        } catch (Exception $e) {
            error_log('Error rendering property ID ' . get_the_ID() . ': ' . $e->getMessage());
        }
    }
}

new PropertyXMLFeed();
