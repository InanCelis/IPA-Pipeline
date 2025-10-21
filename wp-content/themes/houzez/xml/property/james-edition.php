<?php
header('Content-Type: application/xml; charset=' . get_option('blog_charset'), true);
echo '<?xml version="1.0" encoding="' . get_option('blog_charset') . '"?>';
require_once get_template_directory() . '/xml/property/functions/XMLHelper.php';

class PropertyXMLFeed {
    private $country_slug;
    private $count;
    private $page;
    private $query;

    public function __construct() {
        $this->helper = new XMLHelper();
        @ob_end_clean(); // Clear existing output buffer
        ob_implicit_flush(true); // Enable real-time output
        $this->country_slug = isset($_GET['country']) ? strtolower(str_replace('-', ' ', sanitize_text_field($_GET['country']))) : '';
        $this->page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
        // Handle count parameter
        $this->count = -1; // Default to all
        if (isset($_GET['count'])) {
            $count_param = intval($_GET['count']);
            if ($count_param > 0) {
                $this->count = $count_param;
            }
        }
        $this->build_query();
        $this->render_xml();
    }

    private function build_query() {
        $tax_query = [];

        if (!empty($this->country_slug)) {
            $tax_query[] = [
                'taxonomy' => 'property_country',
                'field'    => 'slug',
                'terms'    => $this->country_slug,
            ];
        }

        $args = [
            'post_type'      => 'property',
            'posts_per_page' => $this->count,
            'paged'          => $this->page,
            'post_status'    => 'publish',
            'orderby'        => 'ID',
            'order'          => 'DESC',
        ];

        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }

        $this->query = new WP_Query($args);
    }

    private function render_xml() {
        if ($this->query->have_posts()) {
            echo '<jameslist_feed version="3.5">';
            echo '<adverts>';

            while ($this->query->have_posts()) {
                $this->query->the_post();
                $this->render_property();

                // Stream each post's output to browser
                @ob_flush();
                @flush();
            }
            echo '</adverts>';
            echo '</jameslist_feed>';
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

            //country
            $country = get_post_meta($post_id, 'fave_property_country', true);
            $country_terms = wp_get_object_terms($post_id, 'property_country', true);

            $address = get_post_meta($post_id, 'fave_property_address', true);
            //province
            $state = get_post_meta($post_id, 'fave_property_state', true);
            $state_terms = wp_get_object_terms($post_id, 'property_state', true);
            //postcode
            $zip = get_post_meta($post_id, 'fave_property_zip', true);
            $feature = wp_get_object_terms($post_id, 'property_feature', true);

            $size_prefix = get_post_meta($post_id, 'fave_property_size_prefix', true);
            $land_prefix = get_post_meta($post_id, 'fave_property_size_prefix', true);
            ?>

            
            <advert reference="<?php echo $this->helper->clean(get_post_meta($post_id, 'fave_property_id', true)); ?>" category="real estate">
                <mls_number></mls_number>
                <preowned>no</preowned>
                <type>sale</type>
                <year><?php echo $this->helper->clean($year); ?></year>
                <price_on_request>no</price_on_request>
                <sale_price currency="<?php echo $this->helper->clean(get_post_meta($post_id, 'fave_currency', true)); ?>"><?php echo $this->helper->clean(str_replace(',', '', get_post_meta($post_id, 'fave_property_price', true))); ?></sale_price>
                <price currency="<?php echo $this->helper->clean(get_post_meta($post_id, 'fave_currency', true)); ?>" vat_included="no"><?php echo $this->helper->clean(str_replace(',', '', get_post_meta($post_id, 'fave_property_price', true))); ?></price>
                <vat></vat>
                <location>
                    <country><?php echo esc_xml($country_terms[0]->name) ?></country>
                    <region><?php echo !empty($state) ? esc_xml($state) : (!empty($state_terms[0]) ? esc_xml($state_terms[0]->name) : ''); ?></region>
                    <city><?php echo !empty($city) ? esc_xml($city) : (!empty($city_terms[0]) ? esc_xml($city_terms[0]->name) : ''); ?></city>
                    <zip><?php echo $this->helper->clean($zip); ?></zip>
                    <address><?php echo $this->helper->clean($address); ?></address>
                    <longitude><?php echo $this->helper->clean(get_post_meta($post_id, 'houzez_geolocation_long', true)); ?></longitude>
                    <latitude><?php echo $this->helper->clean(get_post_meta($post_id, 'houzez_geolocation_lat', true)); ?></latitude>
                </location>
                <headline><?php echo $this->helper->clean(get_the_title()); ?></headline>
                <description><![CDATA[<?php echo $this->helper->clean_text(get_the_content()); ?>]]></description>
                <real_estate_type><?php echo $this->helper->clean(esc_xml($type[0]->name)) ?></real_estate_type>
                <bedrooms><?php echo $this->helper->clean(get_post_meta($post_id, 'fave_property_bedrooms', true)); ?></bedrooms>
                <bathrooms><?php echo $this->helper->clean(get_post_meta($post_id, 'fave_property_bathrooms', true)); ?></bathrooms>
                <floors></floors>
                <living_area unit="<?php echo $this->helper->clean(!empty($size_prefix) ? $size_prefix : 'sqm'); ?>"><?php echo $this->helper->clean(get_post_meta($post_id, 'fave_property_size', true)); ?></living_area>
                <land_area unit="<?php echo $this->helper->clean($land_prefix); ?>"><?php echo $this->helper->clean(get_post_meta($post_id, 'fave_property_land', true)); ?></land_area>
                <emission_rating></emission_rating>
                <consumption_rating></consumption_rating>
                <licence_id></licence_id>
                <amenities>
                    <mountain_view></mountain_view>
                </amenities>
                <media>
                    <?php
                        echo '<images>';
                        $image_ids = get_post_meta($post_id, 'fave_property_images', false);
                        // Normalize to array
                        if (!is_array($image_ids)) {
                            $image_ids = array();
                        }
                        foreach ($image_ids as $image_id) {
                            // Ensure it's a valid numeric ID
                            if (is_numeric($image_id)) {
                                $url = wp_get_attachment_url($image_id);
                                if ($url) {
                                    echo '<image_url>' . esc_url($url) . '</image_url>';
                                }
                            }
                        }
                        echo '</images>';

                    ?>
                    <video>
                        <video_url><?php echo $this->helper->clean(get_post_meta($post_id, 'fave_video_url', true)); ?></video_url>
                    </video>
                    <virtual_tour_link><?php echo $this->helper->clean(get_post_meta($post_id, 'fave_virtual_tour', true)); ?></virtual_tour_link>
                </media>
                <contact_person>
                    <name>International Property Alerts</name>
                    <title>Agent</title>
                    <email>office@internationalpropertyalerts.com</email>
                    <phone>+442036270106</phone>
                    <cell>+442036270106</cell>
                    <fax></fax>
                    <address>20 Wenlock Road, London, England, N1 7GU</address>
                    <reference></reference>
                    <image><?php echo esc_url('https://internationalpropertyalerts.com/wp-content/uploads/2025/03/site-logo-white-2-v2-1.png'); ?></image>
                    <language_codes>spa, eng, rus, </language_codes>
                    <linkedin><?php echo esc_url('https://www.linkedin.com/company/international-property-alerts/'); ?></linkedin>
                    <instagram><?php echo esc_url('https://www.instagram.com/internationalpropertyalerts/'); ?></instagram>
                    <facebook><?php echo esc_url('https://www.facebook.com/profile.php?id=61573041554187&sk=about&_rdc=1&_rdr#'); ?></facebook>
                    <tiktok></tiktok>
                    <biography><![CDATA[International Property Alerts is your trusted gateway to global real estate. We connect buyers, investors, and real estate professionals with high-quality property opportunities across top international markets.
                        Our mission is simple: to make discovering and securing international properties easier, smarter, and more transparent. Whether you're looking for a vacation home, a rental investment, or a permanent move abroad, our platform delivers a curated selection of properties from regions including the Philippines, Mexico, Spain, Cyprus, the UAE, the USA, and more.]]></biography>
                </contact_person>
            </advert>
            <?php
        
        } catch (Exception $e) {
            error_log('Error rendering property ID ' . get_the_ID() . ': ' . $e->getMessage());
        }
    }
}

new PropertyXMLFeed();
