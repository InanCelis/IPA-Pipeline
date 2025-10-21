<?php
global $post;

$visible_images = houzez_option('block_gallery_visible', 9);
$images_in_row = houzez_option('block_gallery_columns', 3);;

if( empty($visible_images) ) {
    $visible_images = 9;
}

$percentage = 100 / $images_in_row;

$size = 'houzez-item-image-1';
$properties_images = rwmb_meta( 'fave_property_images', 'type=plupload_image&size='.$size, $post->ID );

$i = 0;

if( !empty($properties_images) && count($properties_images)) {

    $total_images = count($properties_images);
    $remaining_images = $total_images - $visible_images;
    $gallery_token = wp_generate_password(5, false, false);
    ?>
<div class="property-gallery-grid property-section-wrap" id="property-gallery-grid">        
    
    <?php
    if( houzez_get_popup_gallery_type() == 'photoswipe' ) { ?>
        <div class="houzez-photoswipe property-gallery-grid-wrap houzez-desktop-layout-3cols houzez-tablet-layout-3cols houzez-mobile-layout-3cols">
            <?php 
            foreach( $properties_images as $image ) { $i++; ?>
            <div class="gallery-grid-item ">
                <?php if( is_plugin_active( 'houzez-studio/houzez-studio.php' ) ) : ?>
                <a data-elementor-open-lightbox="yes" data-elementor-lightbox-slideshow="all-<?php echo $gallery_token; ?>" href="<?php echo esc_url( $image['full_url'] ); ?>" data-size="1170x785" class="<?php if($i == $visible_images && $remaining_images > 0 ){ echo 'more-images'; } elseif($i > $visible_images) {echo 'gallery-hidden'; } ?>">
                <?php else: ?>
                <a href="<?php echo esc_url( $image['full_url'] ); ?>" itemprop="contentUrl" data-size="1170x785" class="<?php if($i == $visible_images && $remaining_images > 0 ){ echo 'more-images'; } elseif($i > $visible_images) {echo 'gallery-hidden'; } ?>">
                <?php endif; ?>

                    <?php if( $i == $visible_images && $remaining_images > 0 ){ echo '<span>'.$remaining_images.'+</span>'; } ?>
                    <img class="img-fluid" src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>">
                </a>
            </div>
            <?php } ?>
        </div>

    <?php
    } else { ?>
    <div class="property-gallery-grid-wrap houzez-desktop-layout-3cols houzez-tablet-layout-3cols houzez-mobile-layout-3cols">
        <?php 
        foreach( $properties_images as $image ) { $i++; ?>
        <a href="#" data-toggle="modal" data-slider-no="<?php echo esc_attr($i); ?>" data-target="#property-lightbox" class="houzez-trigger-popup-slider-js gallery-grid-item <?php if($i == $visible_images && $remaining_images > 0 ){ echo 'more-images'; } elseif($i > $visible_images) {echo 'gallery-hidden'; } ?>">
            <?php if( $i == $visible_images && $remaining_images > 0 ){ echo '<span>'.$remaining_images.'+</span>'; } ?>
            <img class="img-fluid" src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>">
        </a>
        <?php } ?>
    </div>
    <?php } ?>
</div><!-- property-gallery-grid -->
<?php } ?>