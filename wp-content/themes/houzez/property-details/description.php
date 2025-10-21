<?php
$attachments = get_post_meta(get_the_ID(), 'fave_attachments', false);
$documents_download = houzez_option('documents_download');
$is_show_more = houzez_option('is_show_more', 0);
?>
<div class="property-description-wrap property-section-wrap <?php if($is_show_more) {?>is-show-more<?php } ?>" id="property-description-wrap">
	<div class="block-wrap">
		<div class="block-title-wrap">
			<h2><?php echo houzez_option('sps_description', 'Description'); ?></h2>	
		</div>
		<div class="block-content-wrap">
			<div class="property-description-content">
				<div class="description-content">
					<?php the_content(); ?>
				</div>
				<?php if($is_show_more) { ?>
				<div class="show-more-less">
					<a class="show-more-less-btn" href="#">
						<span class="btn-text"><?php esc_html_e('Show more', 'houzez'); ?></span>
						<i class="houzez-icon arrow-down-1"></i>
					</a>
				</div>
				<?php } ?>
			</div>

			<?php 
			if(!empty($attachments) && $attachments[0] != "" ) { ?>
				<div class="block-title-wrap block-title-property-doc">
					<h3><?php echo houzez_option('sps_documents', 'Property Documents'); ?></h3>
				</div>

				<?php 
				foreach( $attachments as $attachment_id ) {
					$attachment_meta = houzez_get_attachment_metadata($attachment_id); 

					if(!empty($attachment_meta )) {
					?>
					<div class="property-documents">
						<div class="d-flex justify-content-between">
							<div class="property-document-title">
								<i class="houzez-icon icon-task-list-plain-1 mr-1"></i> <?php echo esc_attr( $attachment_meta->post_title ); ?>
							</div>
							<div class="property-document-link login-link">
								
								<?php if( $documents_download == 1 ) {
				                    if( is_user_logged_in() ) { ?>
				                    <a href="<?php echo esc_url( $attachment_meta->guid ); ?>" target="_blank"><?php esc_html_e( 'Download', 'houzez' ); ?></a>
				                    <?php } else { ?>
				                        <a href="#" data-toggle="modal" data-target="#login-register-form"><?php esc_html_e( 'Download', 'houzez' ); ?></a>
				                    <?php } ?>
				                <?php } else { ?>
				                    <a href="<?php echo esc_url( $attachment_meta->guid ); ?>" target="_blank"><?php esc_html_e( 'Download', 'houzez' ); ?></a>
				                <?php } ?>
							</div>
						</div>
					</div>
				<?php } }?>
				
			<?php } ?>
		</div>
	</div>
</div>

<style>
.is-show-more .property-description-content .description-content {
    max-height: 200px;
    overflow: hidden;
    transition: max-height 0.3s ease-out;
    position: relative;
}

.is-show-more .property-description-content .description-content::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 50px;
    background: linear-gradient(rgba(255,255,255,0), rgba(255,255,255,1));
    pointer-events: none;
}

.is-show-more .property-description-content.show-all .description-content {
    max-height: none;
}

.is-show-more .property-description-content.show-all .description-content::after {
    display: none;
}

.show-more-less {
    text-align: center;
    margin-top: 15px;
}

.show-more-less-btn {
    display: inline-flex;
    align-items: center;
    color: #00aeff;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s ease;
}

.show-more-less-btn:hover {
    color: #33beff;
    text-decoration: none;
}

.show-more-less-btn .houzez-icon {
    font-size: 12px;
    margin-left: 5px;
    transition: transform 0.3s ease;
}

.is-show-more .property-description-content.show-all .show-more-less-btn .houzez-icon {
    transform: rotate(180deg);
}
</style>

<script>
jQuery(document).ready(function($) {
    var wrapper = $('#property-description-wrap');
    var content = $('.property-description-content');
    var btn = $('.show-more-less-btn');
    var btnText = btn.find('.btn-text');
    var descriptionContent = content.find('.description-content');
    
    // Check if description content exists
    if(descriptionContent.length) {
        // Get the actual height of the content
        var actualHeight = descriptionContent[0].scrollHeight;
        
        // Only show the button and apply the max-height if content is taller than 200px
        if(actualHeight <= 200) {
            btn.parent().hide();
            wrapper.removeClass('is-show-more');
            descriptionContent.css({
                'max-height': 'none',
                'overflow': 'visible'
            });
        }
    }
    
    btn.on('click', function(e) {
        e.preventDefault();
        content.toggleClass('show-all');
        
        if(content.hasClass('show-all')) {
            btnText.text('<?php esc_html_e('Show less', 'houzez'); ?>');
        } else {
            btnText.text('<?php esc_html_e('Show more', 'houzez'); ?>');
        }
    });
});
</script>