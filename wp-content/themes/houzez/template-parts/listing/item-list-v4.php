<?php 
global $post, $random_token, $ele_thumbnail_size, $image_size, $listing_agent_info, $buttonsComposer; 

$random_token = houzez_random_token();

$defaultButtons = array(
    'enabled' => array(
        'call' => 'Call',
        'email' => 'Email',
        'whatsapp' => 'WhatsApp',
        // Add other buttons as needed
    )
);

$listingButtonsComposer = houzez_option('listing_buttons_composer', $defaultButtons);

// Ensure that 'enabled' index exists
$buttonsComposer = isset($listingButtonsComposer['enabled']) ? $listingButtonsComposer['enabled'] : [];
// Remove the 'placebo' element
unset($buttonsComposer['placebo']);

$listing_agent_info = houzez20_property_contact_form();

$video_url = houzez_get_listing_data('video_url');
$virtual_tour = houzez_get_listing_data('virtual_tour');

$agent_info = $listing_agent_info['agent_info'] ?? '';

$image_size = 'houzez-item-image-1';
$thumbnail_size = $ele_thumbnail_size ?? $image_size;

$thumb_id = get_post_thumbnail_id($post->ID);

$image_01 = $image_02 = $alt_text_01 = $alt_text_02 = '';
$gallery_ids = get_post_meta($post->ID, 'fave_property_images', false);

// Ensure $gallery_ids is a flat array
$gallery_ids = !empty($gallery_ids) && is_array($gallery_ids) ? array_values($gallery_ids) : [];

// Exclude $thumb_id from $gallery_ids
$gallery_ids = array_diff($gallery_ids, [$thumb_id]);

if (!empty($gallery_ids)) {
    $images_ids = array_slice($gallery_ids, 0, 2);

    if (!empty($images_ids[0])) {
        $image_01 = wp_get_attachment_image_url($images_ids[0], $thumbnail_size);
        $alt_text_01 = get_post_meta($images_ids[0], '_wp_attachment_image_alt', true);
    }

    if (!empty($images_ids[1])) {
        $image_02 = wp_get_attachment_image_url($images_ids[1], $thumbnail_size);
        $alt_text_02 = get_post_meta($images_ids[1], '_wp_attachment_image_alt', true);
    }
}
?>
<div class="item-listing-wrap card item-wrap-v10" data-hz-id="hz-<?php esc_attr_e($post->ID); ?>">
	<div class="item-wrap item-wrap-no-frame h-100">
		<div class="item-header-wrap">
			<div class="item-header-wrap-left">
				<div class="item-header item-header-1">
					<?php get_template_part('template-parts/listing/partials/item-featured-label'); ?>
					<?php get_template_part('template-parts/listing/partials/item-labels'); ?>
					<?php get_template_part('template-parts/listing/partials/item-tools', 'v2'); ?>
					<a href="<?php the_permalink(); ?>" class="item-v10-image">
						<?php
					    if( has_post_thumbnail( $post->ID ) && get_the_post_thumbnail($post->ID) != '' ) {
					        the_post_thumbnail( $thumbnail_size, array('class' => 'img-fluid') );
					    }else{
					        houzez_image_placeholder( $thumbnail_size );
					    }
					    ?>
					</a><!-- hover-effect -->
					<div class="preview_loader"></div>
				</div><!-- item-header-1 -->
			</div><!-- item-header-wrap-left -->
			<div class="item-header-wrap-right">
				<div class="item-header-2 item-header-with-button">
					<a <?php houzez_listing_link_target(); ?> href="<?php the_permalink(); ?>" class="item-v10-image">
						
						<?php if( $virtual_tour ) { ?>
						<span class="btn btn-360"><i class="houzez-icon icon-view"></i> <?php echo esc_html__('360° Tour', 'houzez');?></span>
						<?php } ?>
						<?php
					    if( $image_01 != '' ) {
					        ?>
					        <img class="img-fluid" src="<?php echo $image_01; ?>" alt="<?php echo $alt_text_01; ?>">
					        <?php
					    }else{
					        houzez_image_placeholder( $thumbnail_size );
					    }
					    ?>
					</a><!-- hover-effect -->
				</div><!-- item-header-2 -->
				<div class="item-header-2 item-header-with-button">
					<a <?php houzez_listing_link_target(); ?> href="<?php the_permalink(); ?>" class="item-v10-image">
						<?php if( $video_url ) { ?>
						<span class="btn btn-video"><i class="houzez-icon icon-video-player-movie-1"></i> <?php echo esc_html__('Video', 'houzez');?></span>
						<?php } ?>
						
						<?php
					    if( $image_02 != '' ) {
					        ?>
					        <img class="img-fluid" src="<?php echo $image_02; ?>" alt="<?php echo $alt_text_02; ?>">
					        <?php
					    }else{
					        houzez_image_placeholder( $thumbnail_size );
					    }
					    ?>
					</a><!-- hover-effect -->
				</div><!-- item-header-2 -->	
			</div><!-- item-header-wrap-right -->
		</div><!-- item-header-wrap -->
		<div class="item-body-wrap">
			<div class="item-body">
				<?php get_template_part('template-parts/listing/partials/item-labels'); ?>
				<?php get_template_part('template-parts/listing/partials/item-title'); ?>
				<?php get_template_part('template-parts/listing/partials/item-address'); ?>
				<ul class="item-price-wrap">
					<?php echo houzez_listing_price_v1(); ?>
				</ul>
				
				<?php get_template_part('template-parts/listing/partials/item-features-v1'); ?>
			</div><!-- item-body -->

			<?php if( !empty( $agent_info[0] ) ) { ?>
			<div class="item-footer-author-tool-wrap">
				<div class="item-author-wrap">
					<?php 
					if(houzez_option('disable_agent', 1)) { ?>
					<div class="item-author">
						<img class="img-fluid" src="<?php echo $agent_info[0]['picture']; ?>" alt="">
						<?php echo $agent_info[0]['agent_name']; ?>
					</div><!-- item-author -->
					<?php } ?>	
				</div><!-- item-author-wrap -->
				<div class="item-buttons-wrap">	
					<?php get_template_part('template-parts/listing/partials/item-btns-cew-v2'); ?>		
				</div><!-- item-buttons-wrap --> 
			</div><!-- item-footer-button-wrap -->
			<?php } ?>
		</div><!-- item-body -->
	</div><!-- item-wrap -->
	<?php get_template_part('template-parts/listing/partials/modal-phone-number'); ?>
	<?php get_template_part('template-parts/listing/partials/modal-agent-contact-form'); ?>
</div><!-- item-listing-wrap -->