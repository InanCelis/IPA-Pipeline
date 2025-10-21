<?php
$show_similer = houzez_option('houzez_similer_properties');
$similer_criteria = houzez_option('houzez_similer_properties_type', array('property_type', 'property_city'));
$listing_view = houzez_option('houzez_similer_properties_view');
$similer_count = houzez_option('houzez_similer_properties_count');
$sort_by = houzez_option('similar_order', 'd_date');

$wrap_class = $item_layout = $view_class = $cols_in_row = '';
if($listing_view == 'list-view-v1') {
    $wrap_class = 'listing-v1';
    $item_layout = 'v1';
    $view_class = 'list-view';

} elseif($listing_view == 'grid-view-v1') {
    $wrap_class = 'listing-v1';
    $item_layout = 'v1';
    $view_class = 'grid-view card-deck';

} elseif($listing_view == 'list-view-v2') {
    $wrap_class = 'listing-v2';
    $item_layout = 'v2';
    $view_class = 'list-view';

} elseif($listing_view == 'grid-view-v2') {
    $wrap_class = 'listing-v2';
    $item_layout = 'v2';
    $view_class = 'grid-view card-deck';

} elseif($listing_view == 'grid-view-v3') {
    $wrap_class = 'listing-v3';
    $item_layout = 'v3';
    $view_class = 'grid-view card-deck';
    $have_switcher = false;

} elseif($listing_view == 'grid-view-v4') {
    $wrap_class = 'listing-v4';
    $item_layout = 'v4';
    $view_class = 'grid-view card-deck';
    $have_switcher = false;

} elseif($listing_view == 'list-view-v5') {
    $wrap_class = 'listing-v5';
    $item_layout = 'v5';
    $view_class = 'list-view';

} elseif($listing_view == 'grid-view-v5') {
    $wrap_class = 'listing-v5';
    $item_layout = 'v5';
    $view_class = 'grid-view card-deck';

} elseif($listing_view == 'grid-view-v6') {
    $wrap_class = 'listing-v6';
    $item_layout = 'v6';
    $view_class = 'grid-view card-deck';
    $have_switcher = false;

} elseif($listing_view == 'grid-view-v7') {
    $wrap_class = 'listing-v7';
    $item_layout = 'v7';
    $view_class = 'grid-view card-deck';
    $have_switcher = false;

} elseif($listing_view == 'list-view-v7') {
    $wrap_class = 'listing-v7';
    $item_layout = 'list-v7';
    $view_class = 'list-view';
    $have_switcher = false;
    $card_deck = '';

} else {
    $wrap_class = 'listing-v1';
    $item_layout = 'v1';
    $view_class = 'grid-view card-deck';
}

if($show_similer) {
    $similar_query = houzez_get_similar_properties(null, $similer_criteria, $similer_count, $sort_by);

    if ($similar_query->have_posts()) : ?>
        <div id="similar-listings-wrap" class="similar-property-wrap <?php echo esc_attr($wrap_class); ?>">
            <div class="block-title-wrap">
                <h2><?php echo houzez_option('sps_similar_listings', 'Similar Listings'); ?></h2>
            </div><!-- block-title-wrap -->
            <div class="listing-view <?php echo esc_attr($view_class); ?>">
                <?php
                while ($similar_query->have_posts()) : $similar_query->the_post();
                    get_template_part('template-parts/listing/item', $item_layout);
                endwhile;
                wp_reset_postdata();
                ?> 
            </div><!-- listing-view -->
        </div><!-- similar-property-wrap -->
    <?php
    endif;
}
?>