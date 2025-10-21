<?php
global $post;

$size = 'houzez-top-v7';

$listing_images = rwmb_meta( 'fave_property_images', 'type=plupload_image&size='.$size, $post->ID );
$i = 0; $j = 0;
$images_ids = get_post_meta($post->ID, 'fave_property_images', false);
$total_images = count($images_ids);
$property_gallery_popup_type = houzez_get_popup_gallery_type();
$gallery_token = wp_generate_password(5, false, false);

$photoswipte_css_class = '';
$builtin_gallery_class = 'houzez-trigger-popup-slider-js';
$dataModal = 'href="#" data-toggle="modal" data-target="#property-lightbox"';
if( $property_gallery_popup_type == 'photoswipe' ) {
	$photoswipte_css_class = 'houzez-photoswipe';
	$dataModal = '';
	$builtin_gallery_class = '';
}
$layout = houzez_option('property_blocks');
$layout = $layout['enabled'];
?>
<div class="property-top-wrap">
    <div class="property-banner">
		<div class="visible-on-mobile">
			<div class="tab-content" id="pills-tabContent">
				<?php get_template_part('property-details/partials/media-tabs'); ?>
			</div><!-- tab-content -->
		</div><!-- visible-on-mobile -->

		<div class="container hidden-on-mobile">
			<div class="row">
				<div class="col-md-12">
					<div id="houzez-photoswipe-gallery-<?php echo esc_attr($gallery_token); ?>" class="property-banner-grid-wrap <?php echo esc_attr($photoswipte_css_class);?>" itemscope itemtype="http://schema.org/ImageGallery">
					<?php
					if(!empty($listing_images)) {
						foreach( $listing_images as $image ) { $i++; 

							

							if( $property_gallery_popup_type == 'photoswipe' ) {
								$image_id = $images_ids[$i-1];

								$image_data = wp_get_attachment_image_src( $image_id, 'full' );

								$width  = $image_data[1] ?? 1170; // Image width
								$height = $image_data[2] ?? 750; 
								$dataModal = 'href="'.esc_url($image['full_url']).'" data-gallery-item data-pswp-width="'.esc_attr($width).'" data-pswp-height="'.esc_attr($height).'"';
							}
						
							if($i == 1) { ?>
							<div class="property-banner-inner-left">
								<div class="property-banner-item" itemprop="associatedMedia" itemscope itemtype="http://schema.org/ImageObject">
									<a data-slider-no="<?php echo esc_attr($i); ?>" data-image="<?php echo esc_attr($j); ?>" itemprop="contentUrl" <?php echo $dataModal; ?> class="<?php echo esc_attr($builtin_gallery_class); ?> img-wrap-1">
										<img class="img-fluid" src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>" itemprop="thumbnail">
									</a>
								</div>
							</div><!-- col-md-8 -->
							<?php } elseif( $i == 2 || $i == 3  || $i == 4  || $i == 5 ) { ?>

							<?php if($i == 2) { ?>
							<div class="property-banner-inner-rght">
							<?php } ?>
								<div class="property-banner-item" itemprop="associatedMedia" itemscope itemtype="http://schema.org/ImageObject">
									<a data-slider-no="<?php echo esc_attr($i); ?>" data-image="<?php echo esc_attr($j); ?>" itemprop="contentUrl" <?php echo $dataModal; ?> class="<?php echo esc_attr($builtin_gallery_class); ?> img-wrap-<?php echo esc_attr($i); ?>">
										<?php if($total_images > 5 && $i == 5) { ?>
										<div class="img-wrap-3-text"><i class="houzez-icon icon-picture-sun mr-1"></i> <?php echo $total_images-5; ?> <?php echo esc_html__('More', 'houzez'); ?></div>
										<?php } ?>

										<img class="img-fluid" src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>" itemprop="thumbnail">
									</a>
								</div>
							<?php if( ($i == 5 && $total_images == 5) || ($i == 4 && $total_images == 4) || ($i == 3 && $total_images == 3) || ( $i == 2 && $total_images == 2 ) || ( $i == 1 && $total_images == 1 ) || $i == 5 ) { ?>
							</div><!-- property-banner-inner-rght -->
							<?php } ?>
							<?php } else { ?>
								<div itemprop="associatedMedia" itemscope itemtype="http://schema.org/ImageObject" class="gallery-hidden">
									<a data-slider-no="<?php echo esc_attr($i); ?>" data-image="<?php echo esc_attr($j); ?>" itemprop="contentUrl" <?php echo $dataModal; ?>>
										<img class="img-fluid" src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>" itemprop="thumbnail">
									</a>
								</div>
							<?php
							}
							$j++;
						}
					}?>
					</div><!-- .property-banner-grid-wrap -->
				</div><!-- col-md-12 -->
				
				<?php 
				if( ! array_key_exists( 'overview-v2', $layout ) ) { ?>
				<div class="col-md-12">
					<div class="block-wrap">
						<div class="d-flex property-overview-data">
							<?php get_template_part('property-details/partials/overview-data'); ?>
						</div><!-- d-flex -->
					</div><!-- block-wrap -->
				</div><!-- col-md-12 -->
				<?php } ?>
			</div><!-- row -->
		</div><!-- hidden-on-mobile -->
	</div><!-- property-banner -->

	<?php 
	if( $property_gallery_popup_type == 'photoswipe' ) {
		get_template_part( 'property-details/photoswipe');
	 } ?>

</div><!-- property-top-wrap -->
