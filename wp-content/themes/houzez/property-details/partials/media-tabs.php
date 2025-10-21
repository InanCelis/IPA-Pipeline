<?php
global $post, $top_area, $map_street_view;
$featured_image = houzez_get_image_url('full');
$featured_image_url = $featured_image[0];
$property_gallery_popup_type = houzez_get_popup_gallery_type(); 
if( ! has_post_thumbnail( $post->ID ) || get_the_post_thumbnail($post->ID) == "" ) {
	$featured_image_url = houzez_get_image_placeholder_url('full');
}
$gallery_token = wp_generate_password(5, false, false);

$gallery_active = $map_active = $street_active = $virtual_active = $video_active = "";
$active_tab = houzez_option('prop_default_active_tab', 'image_gallery');
$media_tabs = houzez_get_media_tabs();

// Get available tabs
$available_tabs = array();
foreach ($media_tabs as $key => $value) {
    if ($key == 'video') {
        $prop_video_url = houzez_get_listing_data('video_url');
        if (!empty($prop_video_url)) {
            $available_tabs[] = 'video';
        }
    } elseif ($key == '360_virtual_tour') {
        $virtual_tour = houzez_get_listing_data('virtual_tour');
        if (!empty($virtual_tour)) {
            $available_tabs[] = '360_virtual_tour';
        }
    } elseif ($key == 'gallery') {
        if (has_post_thumbnail($post->ID) || houzez_get_listing_data('gallery')) {
            $available_tabs[] = 'gallery';
        }
    } elseif ($key == 'map') {
        $available_tabs[] = 'map_view';
    } elseif ($key == 'street_view') {
        $available_tabs[] = 'street_view';
    }
}

// If current active tab is not available, select the first available tab
if (!in_array($active_tab, $available_tabs) && !empty($available_tabs)) {
    $active_tab = $available_tabs[0];
}

if ($active_tab == 'map_view') {
    $map_active = 'show active';
} elseif ($active_tab == 'street_view') {
    $street_active = 'show active';
} elseif ($active_tab == '360_virtual_tour') {
    $virtual_active = 'show active';
} elseif ($active_tab == 'video') {
    $video_active = 'show active';
} else {
    $gallery_active = 'show active';
}

if ($media_tabs): foreach ($media_tabs as $key=>$value) {
	switch($key) {

    case 'gallery': 
        if(in_array('gallery', $available_tabs)) {
            if($top_area == 'v2') { ?>
                <div class="tab-pane <?php echo esc_attr($gallery_active); ?>" id="pills-gallery" role="tabpanel" aria-labelledby="pills-gallery-tab" style="background-image: url(<?php echo esc_url($featured_image_url); ?>);">
                    <?php get_template_part('property-details/partials/image-count'); ?>	
                    <div class="d-flex page-title-wrap page-label-wrap">
                        <div class="container">
                        <?php get_template_part('property-details/partials/item-labels'); ?>
                        </div>
                    </div>
                    <?php get_template_part('property-details/property-title'); ?> 
                    <?php if( $property_gallery_popup_type == "photoswipe" ) { ?>
                        <div id="houzez-photoswipe-gallery-<?php echo esc_attr($gallery_token); ?>" class="houzez-photoswipe" itemscope itemtype="http://schema.org/ImageGallery">
                            <a class="property-banner-trigger" data-gallery-item href="<?php echo esc_url($featured_image_url); ?>" data-pswp-width="1170" data-pswp-height="785" itemprop="contentUrl">
                                <div class="gallery-visible-overlay"></div>
                            </a>
                            <?php
                            $properties_images = rwmb_meta( 'fave_property_images', 'type=plupload_image&size=full', $post->ID );
                            if(!empty($properties_images)) {
                                $first_image = reset($properties_images);
                                $skip_first = ($first_image['full_url'] == $featured_image_url);
                                
                                foreach( $properties_images as $prop_image_id => $prop_image_meta ) {
                                    // Skip if it's the first image and matches featured image
                                    if($skip_first && $prop_image_meta['full_url'] == $featured_image_url) {
                                        $skip_first = false; // Reset flag after skipping
                                        continue;
                                    }

                                    $width = isset($prop_image_meta['width']) ? $prop_image_meta['width'] : 1170;
                                    $height = isset($prop_image_meta['height']) ? $prop_image_meta['height'] : 785;
                                    ?>
                                    <div itemprop="associatedMedia" itemscope itemtype="http://schema.org/ImageObject" class="gallery-hidden">
                                        <a href="<?php echo esc_url($prop_image_meta['full_url']); ?>" data-gallery-item itemprop="contentUrl" data-pswp-width="<?php echo esc_attr($width); ?>" data-pswp-height="<?php echo esc_attr($height); ?>">
                                            <img class="img-fluid" src="<?php echo esc_url($prop_image_meta['url']); ?>" alt="<?php echo esc_attr($prop_image_meta['alt']); ?>" itemprop="thumbnail">
                                        </a>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                    <?php } else { ?>
                        <a class="property-banner-trigger" data-toggle="modal" data-target="#property-lightbox" href="#"></a>
                    <?php } ?>
                </div>
            <?php } elseif($top_area == 'v3' || $top_area == 'v4') { ?>

                <div class="tab-pane <?php echo esc_attr($gallery_active); ?>" id="pills-gallery" role="tabpanel" aria-labelledby="pills-gallery-tab">
                    <?php get_template_part('property-details/partials/gallery'); ?>
                </div>

            <?php } elseif($top_area == 'v5') { ?>

                <div class="tab-pane <?php echo esc_attr($gallery_active); ?>" id="pills-gallery" role="tabpanel" aria-labelledby="pills-gallery-tab">
                    <?php get_template_part('property-details/partials/image-count'); ?>
                    <?php get_template_part('property-details/partials/gallery-variable-width'); ?>
                </div>

            <?php } elseif( $top_area == 'v1' || $top_area == 'v6' || $top_area == 'v7' ) { 
                ?>

                <div class="tab-pane <?php echo esc_attr($gallery_active); ?>" id="pills-gallery" role="tabpanel" aria-labelledby="pills-gallery-tab" style="background-image: url(<?php echo esc_url($featured_image_url); ?>);">
                    <?php get_template_part('property-details/partials/image-count'); ?>
                    
                    <?php if( $property_gallery_popup_type == "photoswipe" ) { ?>
                        <div id="houzez-photoswipe-gallery-<?php echo esc_attr($gallery_token); ?>" class="houzez-photoswipe" itemscope itemtype="http://schema.org/ImageGallery">
                            <a class="property-banner-trigger" data-gallery-item href="<?php echo esc_url($featured_image_url); ?>" data-pswp-width="1170" data-pswp-height="785" itemprop="contentUrl">
                                <div class="gallery-visible-overlay"></div>
                            </a>
                            <?php
                            $properties_images = rwmb_meta( 'fave_property_images', 'type=plupload_image&size=full', $post->ID );
                            if(!empty($properties_images)) {
                                $first_image = reset($properties_images);
                                $skip_first = ($first_image['full_url'] == $featured_image_url);
                                
                                foreach( $properties_images as $prop_image_id => $prop_image_meta ) {
                                    // Skip if it's the first image and matches featured image
                                    if($skip_first && $prop_image_meta['full_url'] == $featured_image_url) {
                                        $skip_first = false; // Reset flag after skipping
                                        continue;
                                    }

                                    $width = isset($prop_image_meta['width']) ? $prop_image_meta['width'] : 1170;
                                    $height = isset($prop_image_meta['height']) ? $prop_image_meta['height'] : 785;
                                    ?>
                                    <div itemprop="associatedMedia" itemscope itemtype="http://schema.org/ImageObject" class="gallery-hidden">
                                        <a href="<?php echo esc_url($prop_image_meta['full_url']); ?>" data-gallery-item itemprop="contentUrl" data-pswp-width="<?php echo esc_attr($width); ?>" data-pswp-height="<?php echo esc_attr($height); ?>">
                                            <img class="img-fluid" src="<?php echo esc_url($prop_image_meta['url']); ?>" alt="<?php echo esc_attr($prop_image_meta['alt']); ?>" itemprop="thumbnail">
                                        </a>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                    <?php } else { ?>
                        <a class="property-banner-trigger" data-toggle="modal" data-target="#property-lightbox" href="#"></a>
                    <?php } ?>

                    <?php 
                    if(houzez_option('agent_form_above_image') && $top_area == 'v1' ) {
                        get_template_part('property-details/agent-form'); 
                    }?>
                </div>

            <?php }
        }
        break;

    case 'map':
        if(in_array('map_view', $available_tabs)) { ?>
            <div class="tab-pane <?php echo esc_attr($map_active); ?>" id="pills-map" role="tabpanel" aria-labelledby="pills-map-tab">
                <?php get_template_part('property-details/partials/map'); ?>
            </div>
        <?php }
        break;

    case 'street_view':
        if(in_array('street_view', $available_tabs)) { ?>
            <div class="tab-pane <?php echo esc_attr($street_active); ?>" id="pills-street-view" role="tabpanel" aria-labelledby="pills-street-view-tab">
            </div>
        <?php }
        break;

    case '360_virtual_tour': 
        if(in_array('360_virtual_tour', $available_tabs)) { ?>
            <div class="tab-pane houzez-360-virtual-tour <?php echo esc_attr($virtual_active); ?>" id="pills-360tour" role="tabpanel" aria-labelledby="pills-360tour-tab">
                <div class="loader-360" style="display: none; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1;">
                    <div class="loading-overlay" style="text-align: center; background: rgba(255,255,255,0.8); padding: 20px; border-radius: 5px;">
                        <div class="loader-ripple">
                            <div></div>
                            <div></div>
                        </div>
                        <p style="margin-top: 10px;">Loading Virtual Tour...</p>
                    </div>
                </div>
                <div id="virtual-tour-iframe-container" style="height: 100%; width: 100%;">
                <?php 
                $virtual_tour = houzez_get_listing_data('virtual_tour');
                if (strpos($virtual_tour, '<iframe') !== false || strpos($virtual_tour, '<embed') !== false) {
                    $virtual_tour = str_replace('<iframe', '<iframe onload="jQuery(\'.loader-360\').hide();"', houzez_ensure_iframe_closing_tag($virtual_tour));
                    echo $virtual_tour;
                } else { 
                    echo '<iframe onload="jQuery(\'.loader-360\').hide();" src="'.$virtual_tour.'" frameborder="0" allowfullscreen="allowfullscreen"></iframe>';
                }
                ?>
                </div>
            </div>
            <style>
            .loader-ripple {
                display: inline-block;
                position: relative;
                width: 80px;
                height: 80px;
            }
            .loader-ripple div {
                position: absolute;
                border: 4px solid #00aeff;
                opacity: 1;
                border-radius: 50%;
                animation: loader-ripple 1s cubic-bezier(0, 0.2, 0.8, 1) infinite;
            }
            .loader-ripple div:nth-child(2) {
                animation-delay: -0.5s;
            }
            @keyframes loader-ripple {
                0% {
                    top: 36px;
                    left: 36px;
                    width: 0;
                    height: 0;
                    opacity: 0;
                }
                4.9% {
                    top: 36px;
                    left: 36px;
                    width: 0;
                    height: 0;
                    opacity: 0;
                }
                5% {
                    top: 36px;
                    left: 36px;
                    width: 0;
                    height: 0;
                    opacity: 1;
                }
                100% {
                    top: 0px;
                    left: 0px;
                    width: 72px;
                    height: 72px;
                    opacity: 0;
                }
            }
            </style>
            <script>
            jQuery(document).ready(function($) {
                var firstClick = true;
                $('#pills-360tour-tab').on('shown.bs.tab', function (e) {
                    if(firstClick) {
                        $('.loader-360').show();
                        var container = $('#virtual-tour-iframe-container');
                        var currentContent = container.html();
                        container.html(currentContent);
                        firstClick = false;
                    }
                });
            });
            </script>
        <?php }
        break;

    case 'video': 
        if(in_array('video', $available_tabs)) { ?>
            <div class="tab-pane houzez-top-area-video <?php echo esc_attr($video_active); ?>" id="pills-video" role="tabpanel" aria-labelledby="pills-video-tab">
                <?php 
                $prop_video_url = houzez_get_listing_data('video_url');
                $embed_code = wp_oembed_get($prop_video_url); 
                echo $embed_code; 
                ?>
            </div>
        <?php }
        break;
    }
}
endif;
?>






