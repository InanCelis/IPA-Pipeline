<?php
global $listings_tabs;
$listings_tabs = get_post_meta( $post->ID, 'fave_listings_tabs', true );
$mb = '';
$wrapClass = 'listing-tools-inner-wrap';
if( $listings_tabs != 'enable' ) {
    $mb = 'mb-2';
    $wrapClass = '';
}
?>
<div class="listing-tools-wrap">
    <div class="d-flex align-items-center <?php echo esc_attr($wrapClass); ?> <?php echo esc_attr($mb); ?>">
        <?php get_template_part('template-parts/listing/listing-tabs'); ?>    
        <?php get_template_part('template-parts/listing/listing-sort-by'); ?>      
    </div><!-- d-flex -->
</div><!-- listing-tools-wrap -->   