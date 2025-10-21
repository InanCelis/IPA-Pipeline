<?php 
global $post; 
$listing_agent_info = houzez20_property_contact_form();

$agent_info = $listing_agent_info['agent_info'];

if(houzez_option('disable_agent', 1) && !empty( $agent_info[0] )) { ?>
<div class="item-author">
	<img class="img-fluid" src="<?php echo $agent_info[0]['picture']; ?>" alt="">
	<?php echo $agent_info[0]['agent_name']; ?>
</div><!-- item-author -->
<?php } ?>