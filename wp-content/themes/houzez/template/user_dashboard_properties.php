<?php
/**
 * Template Name: User Dashboard Properties
 * Created by PhpStorm.
 * User: waqasriaz
 * Date: 15/10/15
 * Time: 3:33 PM
 */
if ( !is_user_logged_in() || !houzez_check_role() ) {
    wp_redirect(  home_url() );
}

global $houzez_local, $prop_featured, $current_user, $post;

wp_get_current_user();
$userID         = get_current_user_id();
$user_login     = $current_user->user_login;
$paid_submission_type = esc_html ( houzez_option('enable_paid_submission','') );
$packages_page_link = houzez_get_template_link('template/template-packages.php');
$dashboard_add_listing = houzez_get_template_link_2('template/user_dashboard_submit.php');

$dashboard_listings = houzez_get_template_link_2('template/user_dashboard_properties.php');
$all = add_query_arg( 'prop_status', 'all', $dashboard_listings );
$mine_link = add_query_arg( 'prop_status', 'mine', $dashboard_listings );

get_header();

// Get 'prop_status' parameter from URL and set 'qry_status' accordingly
$prop_status = isset($_GET['prop_status']) ? $_GET['prop_status'] : null;
switch ($prop_status) {
    case 'approved':
        $qry_status = 'publish';
        break;
    case 'pending':
    case 'expired':
    case 'disapproved':
    case 'draft':
    case 'on_hold':
        $qry_status = $prop_status;
        break;
    default:
        $qry_status = 'any';
}

// Get 'sortby' parameter if set
$sortby = isset($_GET['sortby']) ? $_GET['sortby'] : '';

// Default number of properties and page number
$no_of_prop = 12;
$paged = get_query_var('paged') ?: get_query_var('page') ?: 1;

// Define the initial args for the WP query
$args = [
    'post_type'      => 'property',
    'paged'          => $paged,
    'posts_per_page' => $no_of_prop,
    'post_status'    => [$qry_status],
    'suppress_filters' => false
];

$args = houzez_prop_sort ( $args );

if( houzez_is_admin() || houzez_is_editor() ) {
    if( isset( $_GET['user'] ) && $_GET['user'] != '' ) {
        $args['author'] = intval($_GET['user']);

    } else if( isset( $_GET['prop_status'] ) && $_GET['prop_status'] == 'mine' ) {
        $args['author'] = $userID;
    }
} else if( houzez_is_agency() ) {
    $agents = houzez_get_agency_agents($userID);
    
    if( isset( $_GET['user'] ) && $_GET['user'] != '' ) {
        $requested_user = intval($_GET['user']);
        // Only set author if requested user is current user or one of their agents
        if($requested_user === $userID || in_array($requested_user, $agents)) {
            $args['author'] = $requested_user;
        } else {
            // If requested user is not authorized, show no properties
            $args['author'] = -1; // This will return no results
        }
    } else if( isset( $_GET['prop_status'] ) && $_GET['prop_status'] == 'mine' ) {
        $args['author'] = $userID;
    } else if( $agents ) {
        if (!in_array($userID, $agents)) {
            $agents[] = $userID;
        }
        $args['author__in'] = $agents;
    } else {
        $args['author'] = $userID;
    }
} else {
    $args['author'] = $userID;
}


// Add keyword search to args if set
if (!empty($_GET['keyword'])) {
    $args['s'] = trim($_GET['keyword']);
}

// Add property ID to meta query if set
if (!empty($_GET['property_id'])) {
    
    $meta_query[] = array(
        'key' => 'fave_property_id',
        'value' => $_GET['property_id'],
        'type' => 'CHAR',
        'compare' => '=',
    );
    
    $meta_count = count($meta_query);

    if( $meta_count > 1 ) {
        $meta_query['relation'] = 'AND';
    }

    if ($meta_count > 0) {
        $args['meta_query'] = $meta_query;
    }
}
?>

<header class="header-main-wrap dashboard-header-main-wrap">
    <div class="dashboard-header-wrap">
        <div class="d-flex align-items-center">
            <div class="dashboard-header-left flex-grow-1">
                <h1><?php echo houzez_option('dsh_props', 'Properties'); ?></h1>         
            </div><!-- dashboard-header-left -->

            <?php if(!empty($dashboard_add_listing)) { ?>
            <div class="dashboard-header-right">
                <a class="btn btn-primary" href="<?php echo esc_url($dashboard_add_listing); ?>"><?php echo houzez_option('dsh_create_listing', 'Create a Listing'); ?></a>
            </div><!-- dashboard-header-right -->
            <?php } ?>
        </div><!-- d-flex -->
    </div><!-- dashboard-header-wrap -->
</header><!-- .header-main-wrap -->
<section class="dashboard-content-wrap">
    <div class="dashboard-content-inner-wrap">
        <div class="dashboard-content-block-wrap">

            <div class="dashboard-property-search-wrap">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <div class="dashboard-property-search">
                            <?php get_template_part('template-parts/dashboard/property/search'); ?>
                        </div>
                    </div>
                    <div class="dashboard-property-sort-by">
                        <?php get_template_part('template-parts/listing/listing-sort-by'); ?>  
                    </div>
                </div>
            </div><!-- dashboard-property-search -->

            <?php
            $prop_qry = new WP_Query($args); 
            if( $prop_qry->have_posts() ): ?>
                <div id="dash-prop-msg"></div>
                 <!-- Add your chosen count design here, before the table -->
                <div class="count-badge-in">
                    Total Properties Found: 
                    <span class="number">
                        <?php 
                            $total_properties = $prop_qry->found_posts;
                            echo number_format($total_properties); 
                        ?>
                    </span>
                </div>
                <table class="dashboard-table dashboard-table-properties table-lined table-hover responsive-table">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Thumbnail', 'houzez'); ?></th>
                        <th><?php echo esc_html__('Title', 'houzez'); ?></th>
                        <th></th>
                        <th><?php echo esc_html__('Type', 'houzez'); ?></th>
                        <th><?php echo esc_html__('Status', 'houzez'); ?></th>
                        <th><?php echo esc_html__('Price', 'houzez'); ?></th>
                        <th><?php echo esc_html__('Featured', 'houzez'); ?></th>
                        <th><?php echo esc_html__('Posted', 'houzez'); ?></th>
                        <th class="action-col"><?php echo esc_html__('Actions', 'houzez'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    while ($prop_qry->have_posts()): $prop_qry->the_post();

                        get_template_part('template-parts/dashboard/property/property-item');

                    endwhile; 
                    ?>

                </tbody>
                </table><!-- dashboard-table -->

                <?php houzez_pagination( $prop_qry->max_num_pages ); ?>

            <?php    
            else: 

                if(isset($_GET['keyword'])) {

                    echo '<div class="dashboard-content-block">
                        '.esc_html__("No results found", 'houzez').'
                    </div>';

                } else {
                    echo '<div class="dashboard-content-block">
                        '.esc_html__("You don't have any property listed.", 'houzez').' <a href="'.esc_url($dashboard_add_listing).'"><strong>'.esc_html__('Create a listing', 'houzez').'</strong></a>
                    </div>';
                }
                

            endif;
            ?>
        
        </div><!-- dashboard-content-block-wrap -->
    </div><!-- dashboard-content-inner-wrap -->
</section><!-- dashboard-content-wrap -->
<section class="dashboard-side-wrap">
    <?php get_template_part('template-parts/dashboard/side-wrap'); ?>
</section>

<?php get_footer(); ?>