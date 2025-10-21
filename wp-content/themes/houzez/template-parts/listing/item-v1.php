<?php 
global $post, $ele_thumbnail_size, $image_size; 
if( houzez_is_fullwidth_2cols_custom_width() ) {
	$image_size = 'houzez-item-image-4';
} else {
	$image_size = 'houzez-item-image-1';
}

$terms = wp_get_object_terms( get_the_ID(), 'property_country' );
if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
    // If you just want the first country name
    $country = $terms[0]->name;
} 
if (!function_exists('get_country_code_from_api')) {
    function get_country_code_from_api($country_name) {
        // Define country variations with their corresponding codes
        $country_variations = [
            ['United Arab Emirates (UAE)', 'ae'],
            ['United Arab Emirates', 'ae'],
            ['United Kingdom', 'gb'],
            // ['US', 'ae'],
			// ['Philippines', 'ph'],
			// ['Cyprus', 'cy'],
        ];
        
        // Normalize the input
        $normalized_input = trim($country_name);
        
        // Check against our variations first
        foreach ($country_variations as $variation) {
            if (strcasecmp($normalized_input, $variation[0]) === 0) {
                return $variation[1]; // Return the corresponding country code
            }
        } 
 
        // For countries not in our list, use the API
        $response = wp_remote_get('https://restcountries.com/v3.1/name/' . urlencode($country_name));
        if (!is_wp_error($response)) {
            $data = json_decode($response['body'], true);
            if (!empty($data) && isset($data[0]['cca2'])) {
                return strtolower($data[0]['cca2']);
            }
        }
        
        return ''; // Return empty if no match found
    }
}


$image_size = !empty($ele_thumbnail_size) ? $ele_thumbnail_size : $image_size;
?>
<div class="item-listing-wrap hz-item-gallery-js card" data-hz-id="hz-<?php esc_attr_e($post->ID); ?>" <?php houzez_property_gallery($image_size); ?>>
	<div class="item-wrap item-wrap-v1 item-wrap-no-frame h-100">
		<div class="d-flex align-items-center h-100">
			<div class="item-header">
				<?php get_template_part('template-parts/listing/partials/item-featured-label'); ?>
				<?php get_template_part('template-parts/listing/partials/item-labels'); ?>
				<?php get_template_part('template-parts/listing/partials/item-price'); ?>
				<?php get_template_part('template-parts/listing/partials/item-tools'); ?>
				<?php get_template_part('template-parts/listing/partials/item-image'); ?>
				<div class="preview_loader"></div>
			</div><!-- item-header -->	
			<div class="item-body flex-grow-1">
				<?php get_template_part('template-parts/listing/partials/item-labels'); ?>
				<?php get_template_part('template-parts/listing/partials/item-title'); ?>
				<?php get_template_part('template-parts/listing/partials/item-price'); ?>
				<?php get_template_part('template-parts/listing/partials/item-address'); ?>
				<?php 
				if( houzez_option('des_item_v1', 0) ) {
					get_template_part('template-parts/listing/partials/item-description'); 
				}?>
				<?php get_template_part('template-parts/listing/partials/item-features-v1'); ?>
				<?php get_template_part('template-parts/listing/partials/item-btn-v1'); ?>
				<?php get_template_part('template-parts/listing/partials/item-author'); ?>
				<?php get_template_part('template-parts/listing/partials/item-date'); ?>
			</div><!-- item-body -->

			<?php if(houzez_option('disable_date', 1) || houzez_option('disable_agent', 1)) { ?>
			<div class="item-footer clearfix">
				<?php get_template_part('template-parts/listing/partials/item-author'); ?>
				<?php get_template_part('template-parts/listing/partials/item-date'); ?>
			</div>
			<div class="hz-country-flag" style="position: absolute; z-index: 99; left: 20px; top: 15px; background: #ffffff6b; padding: 2px 6px; border-radius: 5px;">
				<?php 
				$country_code = get_country_code_from_api($country);
				// echo $country_code;
				echo '<span class="property-flag flag-icon flag-icon-'.strtolower($country_code).'"></span>';
				?>
			</div>
			<?php } ?>
		</div><!-- d-flex -->
	</div><!-- item-wrap -->
</div><!-- item-listing-wrap -->