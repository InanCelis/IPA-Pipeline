<?php
global $post;
/*$google_map_address = get_post_meta($post->ID, 'fave_property_location', true);
$google_map_lat = get_post_meta($post->ID, 'houzez_geolocation_lat', true);
$google_map_lng = get_post_meta($post->ID, 'houzez_geolocation_long', true);
$google_map_address_url     =   "https://maps.google.com/?q=".$google_map_lat.','.$google_map_lng;*/
$google_map_address = houzez_get_listing_data('property_map_address');
$google_map_address_url = "http://maps.google.com/?q=".$google_map_address;
?>
<div class="property-address-wrap property-section-wrap" id="property-address-wrap">
	<div class="block-wrap">
		<div class="block-title-wrap d-flex justify-content-between align-items-center">
			<h2><?php echo houzez_option('sps_address', 'Address'); ?></h2>

			<?php if( !empty($google_map_address) ) { ?>
			<a class="btn btn-primary btn-slim" href="<?php echo esc_url($google_map_address_url); ?>" target="_blank"><i class="houzez-icon icon-maps mr-1"></i> <?php echo houzez_option('spl_ogm', 'Open on Google Maps' ); ?></a>
			<?php } ?>

		</div><!-- block-title-wrap -->
		<div class="block-content-wrap">
			<ul class="<?php echo houzez_option('prop_address_cols', 'list-2-cols'); ?> list-unstyled">
				<?php get_template_part('property-details/partials/address-data'); ?>
			</ul>	
		</div><!-- block-content-wrap -->

		<?php if(houzez_map_in_section() && houzez_get_listing_data('property_map')) { ?>
		<div id="houzez-single-listing-map" class="block-map-wrap">
		</div><!-- block-map-wrap -->
		<?php } 
		
		$property_location = get_post_meta( get_the_ID(), 'fave_property_location', true );
		if( !empty($property_location) ) {
			$lat_lng = explode(',', $property_location);
			$lat = isset($lat_lng[0]) ? $lat_lng[0] : '';
			$lng = isset($lat_lng[1]) ? $lat_lng[1] : '';
			
			if( !empty($lat) && !empty($lng) ) {
				echo '<div class="houzez-get-directions" style="display: flex;justify-content: flex-end; margin-top: 20px;">';
				echo '<a href="https://www.google.com/maps/dir/?api=1&destination='.$lat.','.$lng.'" target="_blank" class="btn btn-primary btn-sm">';
				echo esc_html__('Get Directions', 'houzez');
				echo '</a>';
				echo '</div>';
			}
		}
		?>

	</div><!-- block-wrap -->
</div><!-- property-address-wrap -->