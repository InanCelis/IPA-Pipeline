<?php 
global $post, $random_token, $ele_thumbnail_size, $image_size, $listing_agent_info, $buttonsComposer; 
$listing_agent_info = houzez20_property_contact_form();

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

// Agent contact information
$agent_mobile = $listing_agent_info['agent_mobile'] ?? '';
$agent_whatsapp = $listing_agent_info['agent_whatsapp'] ?? '';
$agent_telegram = $listing_agent_info['agent_telegram'] ?? '';
$agent_lineapp = $listing_agent_info['agent_lineapp'] ?? '';
$agent_email = $listing_agent_info['agent_email'] ?? '';

$totalButtonsCount = 0;

// Check each button and increment the count if the corresponding agent info is available
foreach ($buttonsComposer as $key => $value) {
    if (($key == 'call' && $agent_mobile != '') ||
        ($key == 'email' && $agent_email != '') ||
        ($key == 'whatsapp' && $agent_whatsapp != '') ||
        ($key == 'telegram' && $agent_telegram != '') ||
        ($key == 'lineapp' && $agent_lineapp != '')
    ) {
        $totalButtonsCount++;
    }
}

// Limit the total buttons to a maximum of 4
$totalButtonsCount = min($totalButtonsCount, 4);
$totalButtonsClass = 'items-btns-count-' . $totalButtonsCount;


if( houzez_is_fullwidth_2cols_custom_width() ) {
	$image_size = 'houzez-item-image-4';
} else {
	$image_size = 'houzez-item-image-1';
}
?>
<div class="item-listing-wrap hz-item-gallery-js card" data-hz-id="hz-<?php esc_attr_e($post->ID); ?>" <?php houzez_property_gallery($image_size); ?>>
	<div class="item-wrap item-wrap-v9 item-wrap-no-frame h-100">
		<div class="d-flex align-items-center h-100">
			<div class="item-header">
				<?php get_template_part('template-parts/listing/partials/item-featured-label'); ?>
				<?php get_template_part('template-parts/listing/partials/item-labels'); ?>
				<?php get_template_part('template-parts/listing/partials/item-tools'); ?>
				<?php get_template_part('template-parts/listing/partials/item-image'); ?>
				<div class="preview_loader"></div>
			</div><!-- item-header -->	
			<div class="item-body flex-grow-1">
				<ul class="item-amenities item-amenities-with-icons">
					<?php get_template_part('template-parts/listing/partials/type'); ?>
				</ul>

				<?php get_template_part('template-parts/listing/partials/item-price'); ?>
				<?php get_template_part('template-parts/listing/partials/item-title'); ?>
				<?php get_template_part('template-parts/listing/partials/item-address'); ?>
				<?php get_template_part('template-parts/listing/partials/item-features-v7'); ?>
			</div><!-- item-body -->
		
			<div class="item-footer <?php echo esc_attr($totalButtonsClass); ?> clearfix">
				<div class="item-buttons-wrap">
					<?php get_template_part('template-parts/listing/partials/item-btns-cew'); ?>		
				</div><!-- item-buttons-wrap --> 
			</div>
		</div><!-- d-flex -->
	</div><!-- item-wrap -->
<?php get_template_part('template-parts/listing/partials/modal-phone-number'); ?>
<?php get_template_part('template-parts/listing/partials/modal-agent-contact-form'); ?>
</div><!-- item-listing-wrap -->