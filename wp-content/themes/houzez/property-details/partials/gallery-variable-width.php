<?php
global $post;
$size = 'houzez-variable-gallery';
$properties_images = rwmb_meta( 'fave_property_images', 'type=plupload_image&size='.$size, $post->ID );
$gallery_caption = houzez_option('gallery_caption', 0); 
$property_gallery_popup_type = houzez_get_popup_gallery_type(); 
$gallery_token = wp_generate_password(5, false, false);
$output = '';

if( !empty($properties_images) && count($properties_images)) {
?>
<div class="top-gallery-section top-gallery-variable-width-section">
	
	<?php 
    if( $property_gallery_popup_type == "photoswipe" ) { ?>

    	<div id="houzez-photoswipe-gallery-<?php echo esc_attr($gallery_token); ?>" class="listing-slider-variable-width houzez-photoswipe houzez-all-slider-wrap" itemscope itemtype="http://schema.org/ImageGallery">
			<?php
	        foreach( $properties_images as $prop_image_id => $prop_image_meta ) {

				$width = isset($prop_image_meta['width']) ? $prop_image_meta['width'] : 1170;
				$height = isset($prop_image_meta['height']) ? $prop_image_meta['height'] : 785;
	  			
				$output .= '<div itemprop="associatedMedia" itemscope itemtype="http://schema.org/ImageObject">';
				$output .= '<a data-gallery-item href="'.esc_attr( $prop_image_meta['full_url'] ).'" itemprop="contentUrl" data-pswp-width="'.esc_attr($width).'" data-pswp-height="'.esc_attr($height).'">';
				$output .= '<img class="img-responsive" data-lazy="'.esc_attr( $prop_image_meta['url'] ).'" src="'.esc_attr( $prop_image_meta['url'] ).'" alt="'.esc_attr($prop_image_meta['alt']).'" title="'.esc_attr($prop_image_meta['title']).'">';
				$output .= '</a>';

				if( !empty($prop_image_meta['caption']) && $gallery_caption != 0 ) {
					$output .= '<span class="hz-image-caption">'.esc_attr($prop_image_meta['caption']).'</span>';
				}

				$output .= '</div>';  
	        }
	        echo $output; 
	        ?>
		</div>

		<?php get_template_part( 'property-details/photoswipe'); ?>

    <?php } else { ?>	
		<div class="listing-slider-variable-width houzez-all-slider-wrap">
			<?php
			$j = 0;
	        foreach( $properties_images as $prop_image_id => $prop_image_meta ) { $j++;
	  			
				echo '<div>
						<a rel="gallery-1" href="#" data-slider-no="'.esc_attr($j).'" class="houzez-trigger-popup-slider-js swipebox" data-toggle="modal" data-target="#property-lightbox">
							<img class="img-responsive" data-lazy="'.esc_attr( $prop_image_meta['url'] ).'" src="'.esc_attr( $prop_image_meta['url'] ).'" alt="'.esc_attr($prop_image_meta['alt']).'" title="'.esc_attr($prop_image_meta['title']).'">
						</a>';

						if( !empty($prop_image_meta['caption']) && $gallery_caption != 0 ) {
			               echo '<span class="hz-image-caption">'.esc_attr($prop_image_meta['caption']).'</span>';
			            }

					echo '</div>';
	        }
	        ?>
		</div>
	<?php } ?>
</div><!-- top-gallery-section -->
<?php } ?>