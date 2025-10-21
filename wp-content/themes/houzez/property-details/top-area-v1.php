<div class="property-top-wrap">
    <div class="container">
        <div class="property-banner">
			<div class="container hidden-on-mobile">
				<?php get_template_part('property-details/partials/banner-nav'); ?>
			</div><!-- container -->
			<div class="tab-content" id="pills-tabContent">
				<?php 
				global $top_area;
				$top_area = 'v1';
				get_template_part('property-details/partials/media-tabs'); 
				?>
			</div><!-- tab-content -->
		</div><!-- property-banner -->
    </div><!-- container -->

    <?php 
	if( houzez_get_popup_gallery_type() == 'photoswipe' ) {
		get_template_part( 'property-details/photoswipe');
	} ?>

</div><!-- property-top-wrap -->