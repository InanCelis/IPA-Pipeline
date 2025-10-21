<?php
// Tell browser this is XML content:
header('Content-Type: application/xml; charset=' . get_option('blog_charset'), true);

// XML declaration:
echo '<?xml version="1.0" encoding="' . get_option('blog_charset') . '"?' . '>';
?>

<Properties>
<?php
// Query your posts (change args as needed):
$args = array(
    'post_type'      => 'property',
    'posts_per_page' => 1500,
    'post_status'    => 'publish',
);


$query = new WP_Query($args);

if ($query->have_posts()) {
    while ($query->have_posts()) {
        $query->the_post();

       // Get array of image attachment IDs
        $image_ids = get_post_meta(get_the_ID(), 'fave_property_images', false);
        // $listing_id = 
        ?>
        <Property>
            <propertyid><?php echo get_post_meta(get_the_ID(), 'fave_property_id', true) ?></propertyid>
            <title><?php echo get_the_title() ?></title>
            <url><?php echo esc_url(get_permalink()); ?></url>
            <publishDate><?php echo get_the_date('r'); ?></publishDate>
            <description><![CDATA[<?php echo apply_filters('the_content', get_the_content()); ?>]]></description>
              <images>
                <?php
                if (!empty($image_ids) && is_array($image_ids)) {
                    foreach ($image_ids as $image_id) {
                        $url = wp_get_attachment_url($image_id);
                        if ($url) {
                            echo '<image>' . esc_url($url) . '</image>';
                        }
                    }
                }
                ?>
            </images>
        </Property>
        <?php
    }
    wp_reset_postdata();
}
?>
</Properties>
