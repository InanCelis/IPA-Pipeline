
<style> 
	.sub-price-in-usd {
		color: var(--e-global-color-primary);
		font-size: 20px;
		font-weight: 500;
	}

	@media screen and (max-width: 767px) {
		.sub-price-in-usd { 
			font-size: 14px;
		}
	}
</style>
<ul class="item-price-wrap hide-on-list">
	<?php 

		echo houzez_listing_price_v1(); 
		// Call the local price if not in USD
		$property_id = get_the_ID();
		$currency = get_post_meta($property_id, 'fave_local_currency', true);
		if ($currency !== 'USD') {
			$local_price = get_post_meta($property_id, 'fave_local_property_price', true);
			if ($local_price) {
				echo '<span class="sub-price-in-usd"> '.get_currency_symbol($currency). number_format($local_price). ' '.$currency.'</span>';
			}
		}
	
	?>
</ul>