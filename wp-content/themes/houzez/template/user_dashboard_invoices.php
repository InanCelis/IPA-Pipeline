<?php
/**
 * Template Name: User Dashboard Invoices
 * Created by PhpStorm.
 * User: waqasriaz
 * Date: 11/09/16
 * Time: 11:00 PM
 */
if ( !is_user_logged_in() ) {
    wp_redirect(  home_url() );
}

global $paged, $houzez_local, $current_user, $dashboard_invoices;
$dashboard_invoices = houzez_get_template_link_2('template/user_dashboard_invoices.php');
$mine_link = add_query_arg( array('mine' => 1 ), $dashboard_invoices );

get_header();

$invoice_status = isset($_GET['invoice_status']) ? sanitize_text_field($_GET['invoice_status']) : '';
$invoice_type = isset($_GET['invoice_type']) ? sanitize_text_field($_GET['invoice_type']) : '';
$startDate = isset($_GET['startDate']) ? sanitize_text_field($_GET['startDate']) : '';
$endDate = isset($_GET['endDate']) ? sanitize_text_field($_GET['endDate']) : '';


$paged = get_query_var('paged') ? get_query_var('paged') : 1;
$meta_query = array();
$date_query = array();

$results_found = false;

$invoices_args = array(
    'post_type' => 'houzez_invoice',
    'posts_per_page' => 20,
    'paged' => $paged
);

if ( ! houzez_is_admin() && ! houzez_is_editor() ) { 
    $meta_query[] = array(
        'key' => 'HOUZEZ_invoice_buyer',
        'value' => get_current_user_id(),
        'compare' => '='
    );
}

if( (isset($_GET['mine']) && $_GET['mine']) ) {
    $invoices_args['author'] = get_current_user_id();
}

if( $invoice_status !='' ){
    $meta_query[] = array(
        'key' => 'invoice_payment_status',
        'value' => $invoice_status,
        'type' => 'NUMERIC',
        'compare' => '=',
    );
    $results_found = true;
}

if( $invoice_type !='' ){

    $meta_query[] = array(
        'key' => 'HOUZEZ_invoice_for',
        'value' => $invoice_type,
        'type' => 'CHAR',
        'compare' => 'LIKE',
    );

    $results_found = true;
}

if( $startDate !='' ) {
    $temp_array = array();
    $temp_array['after'] = $startDate;
    $date_query[] = $temp_array;
    $results_found = true;
}

if( $endDate !='' ){
    $temp_array = array();
    $temp_array['before'] = $endDate;
    $date_query[] = $temp_array;
    $results_found = true;
}

$meta_count = count($meta_query);
$meta_query['relation'] = 'AND';
if ($meta_count > 0) {
    $invoices_args['meta_query'] = $meta_query;
}

if( $date_query ) {
    $invoices_args['date_query'] = $date_query;
}

$invoice_query = new WP_Query($invoices_args);
$total_invoices = $invoice_query->found_posts;
?>
<header class="header-main-wrap dashboard-header-main-wrap">
    <div class="dashboard-header-wrap">
        <div class="d-flex align-items-center">
            <div class="dashboard-header-left flex-grow-1">
                <h1><?php echo houzez_option('dsh_invoices', 'Invoices'); ?></h1>         
            </div><!-- dashboard-header-left -->
            <div class="dashboard-header-right">
            </div><!-- dashboard-header-right -->
        </div><!-- d-flex -->
    </div><!-- dashboard-header-wrap -->
</header><!-- .header-main-wrap -->

<section class="dashboard-content-wrap">
    <div class="dashboard-content-inner-wrap">
        <div class="dashboard-content-block-wrap">
            <?php 
            if( isset( $_GET['invoice_id']) && !empty($_GET['invoice_id']) ) {
                get_template_part('template-parts/dashboard/invoice/detail');

            } else { ?>

            <h2><?php echo esc_html__('Search Invoices', 'houzez'); ?></h2>
            <div class="dashboard-content-block">
                <div class="row">
                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label><?php echo $houzez_local['start_date']; ?></label>
                            <div class="input-group date">
                                <div class="input-group-prepend">
                                    <div class="input-group-text"><i class="houzez-icon icon-calendar-3"></i></div>
                                </div>
                                <input id="startDate" type="text" class="form-control db_input_date" placeholder="<?php echo esc_html__('Select a date', 'houzez'); ?>" value="<?php echo $startDate; ?>" readonly>
                            </div><!-- input-group -->
                        </div><!-- form-group -->
                    </div><!-- col-md-3 col-sm-12 -->
                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label><?php echo $houzez_local['end_date']; ?></label>
                            <div class="input-group date">
                                <div class="input-group-prepend">
                                    <div class="input-group-text"><i class="houzez-icon icon-calendar-3"></i></div>
                                </div>
                                <input id="endDate" type="text" class="form-control db_input_date" placeholder="<?php echo esc_html__('Select a date', 'houzez'); ?>" value="<?php echo $endDate; ?>" readonly>
                            </div><!-- input-group -->
                        </div><!-- form-group -->
                    </div><!-- col-md-3 col-sm-12 -->
                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label for="invoice_type"><?php echo $houzez_local['invoice_type']; ?></label>
                            <select class="selectpicker form-control bs-select-hidden" id="invoice_type" data-live-search="false">
                                <option value=""><?php echo $houzez_local['any']; ?></option>
                                <option <?php selected( $invoice_type, 'Listing' ); ?> value="Listing"><?php echo $houzez_local['invoice_listing']; ?></option>
                                <option <?php selected( $invoice_type, 'package' ); ?> value="package"><?php echo $houzez_local['invoice_package']; ?></option>
                                <option <?php selected( $invoice_type, 'Listing with Featured' ); ?> value="Listing with Featured"><?php echo $houzez_local['invoice_feat_list']; ?></option>
                                <option <?php selected( $invoice_type, 'Upgrade to Featured' ); ?> value="Upgrade to Featured"><?php echo $houzez_local['invoice_upgrade_list']; ?></option>
                            </select>
                        </div><!-- form-group -->
                    </div><!-- col-md-3 col-sm-12 -->
                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label for="invoice_status"><?php echo $houzez_local['invoice_status']; ?></label>
                            <select class="selectpicker form-control bs-select-hidden" id="invoice_status" data-live-search="false">
                                <option value=""><?php echo $houzez_local['any']; ?></option>
                                <option <?php selected( $invoice_status, '1' ); ?>  value="1"><?php echo $houzez_local['paid']; ?></option>
                                <option <?php selected( $invoice_status, '0' ); ?> value="0"><?php echo $houzez_local['not_paid']; ?></option>
                            </select>
                        </div><!-- form-group -->
                    </div><!-- col-md-3 col-sm-12 -->
                </div><!-- row -->
            </div><!-- dashboard-content-block -->

            <div class="dashboard-tool-block">
                <div class="dashboard-tool-buttons-block">
                    <div class="dashboard-tool-button">
                        <div class="btn">
                            <?php echo esc_attr($total_invoices); ?> <?php esc_html_e('Results Found', 'houzez'); ?>
                        </div>
                    </div>
                </div><!-- dashboard-tool-buttons-block -->
            </div><!-- dashboard-tool-block -->

            <table class="dashboard-table table-lined table-hover responsive-table">
                <thead>
                    <tr>
                        <th><?php echo $houzez_local['order']; ?></th>
                        <th><?php echo $houzez_local['date']; ?></th>
                        <th><?php echo $houzez_local['billing_for']; ?></th>
                        <th><?php echo $houzez_local['billing_type']; ?></th>
                        <th><?php echo $houzez_local['invoice_status']; ?></th>
                        <th><?php echo $houzez_local['payment_method']; ?></th>
                        <th><?php echo $houzez_local['total']; ?></th>
                        <th class="action-col"><?php echo esc_html__('Actions', 'houzez'); ?></th>
                    </tr>
                </thead>
                <tbody id="invoices_content">
                    <?php
                    if ($invoice_query->have_posts()) :
                        while ($invoice_query->have_posts()) : $invoice_query->the_post();

                            get_template_part('template-parts/dashboard/invoice/invoice-item'); 

                        endwhile; 
                    endif;
                    wp_reset_postdata();
                    ?>
                </tbody>
            </table><!-- dashboard-table -->

            <?php houzez_pagination( $invoice_query->max_num_pages ); ?>
            <?php } ?>

        </div><!-- dashboard-content-block-wrap -->
    </div><!-- dashboard-content-inner-wrap -->
</section><!-- dashboard-content-wrap -->
<section class="dashboard-side-wrap">
    <?php get_template_part('template-parts/dashboard/side-wrap'); ?>
</section>

<?php get_footer(); ?>