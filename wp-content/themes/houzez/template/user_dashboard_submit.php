<?php
/**
 * Template Name: User Dashboard Create Listing
 * Created by PhpStorm.
 * User: waqasriaz
 * Date: 06/10/15
 * Time: 3:49 PM
 */
global $houzez_local, $properties_page, $hide_prop_fields, $is_multi_steps;

$current_user = wp_get_current_user();
$userID = get_current_user_id();
$packageUserId = $userID;

$agent_agency_id = houzez_get_agent_agency_id( $userID );
if( $agent_agency_id ) {
    $packageUserId = $agent_agency_id;
}

// if( is_user_logged_in() && !houzez_check_role() ) {
//     wp_redirect(  home_url() );
// }

if( is_user_logged_in() && !houzez_check_role() ) {
    // DEBUG: Remove this after testing
    echo "User logged in: " . (is_user_logged_in() ? 'Yes' : 'No') . "<br>";
    echo "Role check result: " . (houzez_check_role() ? 'Passed' : 'Failed') . "<br>";
    echo "Current user roles: " . implode(', ', wp_get_current_user()->roles) . "<br>";
    echo "Current user ID: " . get_current_user_id() . "<br>";
    exit(); // Stop here to see the debug info
    
    wp_redirect(  home_url() );
}

$user_email = $current_user->user_email;
$admin_email =  get_bloginfo('admin_email');
$panel_class = '';

$invalid_nonce = false;
$submitted_successfully = false;
$updated_successfully = false;
$dashboard_listings = houzez_dashboard_listings();
$hide_prop_fields = houzez_option('hide_add_prop_fields');
$enable_paid_submission = houzez_option('enable_paid_submission');
$payment_page_link = houzez_get_template_link('template/template-payment.php');
$thankyou_page_link = houzez_get_template_link('template/template-thankyou.php');
$select_packages_link = houzez_get_template_link('template/template-packages.php');
$submit_property_link = houzez_get_template_link('template/user_dashboard_submit.php');

$create_listing_login_required = houzez_option('create_listing_button');

$sticky_sidebar = houzez_option('sticky_sidebar');
$allowed_html = array();
$submit_form_type = houzez_option('submit_form_type');

if( $submit_form_type == 'one_step' ) {
    $submit_form_main_class = 'houzez-one-step-form';
    $is_multi_steps = 'active';
} else {
    $submit_form_main_class = 'houzez-m-step-form';
    $is_multi_steps = 'form-step';
}

if( isset( $_POST['action'] ) ) {

    $submission_action = $_POST['action'];
    $is_draft = $_POST['houzez_draft'] ?? '';

    $new_property = array(
        'post_type'	=> 'property'
    );

    if( $enable_paid_submission == 'per_listing' ) {

        if ( !is_user_logged_in() ) { 
            $email = wp_kses( $_POST['user_email'], $allowed_html );
            if( email_exists( $email ) ) {
                $errors[] = $houzez_local['email_already_registerd'];
            }

            if( !is_email( $email ) ) {
                $errors[] = $houzez_local['invalid_email'];
            }

            if( empty($errors) ) {
                $username = explode("@", $email);

                if( username_exists( $username[0] ) ) {
                    $username = $username[0].rand(5, 999);
                } else {
                    $username = $username[0];
                }

                $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
                $user_id = wp_create_user( $username, $random_password, $email );

                if( !is_wp_error( $user_id ) ) {
                    $user = get_user_by( 'id', $user_id );

                    houzez_update_profile( $user_id );
                    houzez_wp_new_user_notification( $user_id, $random_password );
                    $user_as_agent = houzez_option('user_as_agent');
                    if( $user_as_agent == 'yes' ) {
                        houzez_register_as_agent ( $username, $email, $user_id );
                    }

                    if( !is_wp_error($user) ) {
                        wp_clear_auth_cookie();
                        wp_set_current_user( $user->ID, $user->user_login );
                        wp_set_auth_cookie( $user->ID );
                        do_action( 'wp_login', $user->user_login );

                        $property_id = apply_filters( 'houzez_submit_listing', $new_property );


                        if( houzez_is_woocommerce() ) {
                            if( ( $submission_action != 'update_property' ) || ( isset($_POST['houzez_draft']) && $_POST['houzez_draft'] == 'draft') ) {

                                do_action('houzez_per_listing_woo_payment', $property_id);

                            } else {
                                if (!empty($submit_property_link)) {
                                    $submit_property_link = add_query_arg( 'edit_property', $property_id, $submit_property_link );
                                    $separator = (parse_url($submit_property_link, PHP_URL_QUERY) == NULL) ? '?' : '&';
                                    $parameter = 'updated=1';
                                    wp_redirect($submit_property_link . $separator . $parameter);
                                }
                            }

                        } else {
                            if (!empty($payment_page_link)) {
                                $separator = (parse_url($payment_page_link, PHP_URL_QUERY) == NULL) ? '?' : '&';
                                $parameter = 'prop-id=' . $property_id;
                                wp_redirect($payment_page_link . $separator . $parameter);

                            } elseif( !empty($payment_page_link) && isset($_POST['houzez_draft']) ) {
                                $separator = (parse_url($payment_page_link, PHP_URL_QUERY) == NULL) ? '?' : '&';
                                $parameter = 'prop-id=' . $property_id;
                                wp_redirect($payment_page_link . $separator . $parameter);

                            } else {
                                if (!empty($dashboard_listings)) {
                                    $separator = (parse_url($dashboard_listings, PHP_URL_QUERY) == NULL) ? '?' : '&';
                                    $parameter = ($updated_successfully) ? '' : '';
                                    wp_redirect($dashboard_listings . $separator . $parameter);
                                }
                            }
                        }
                        exit();
                    }

                }

            }

        } else {
            $property_id = apply_filters('houzez_submit_listing', $new_property);

            if( houzez_is_woocommerce() ) {
                if( ( $submission_action != 'update_property' ) || ( isset($_POST['houzez_draft']) && $_POST['houzez_draft'] == 'draft') ) {

                    do_action('houzez_per_listing_woo_payment', $property_id);

                } else {
                    if (!empty($submit_property_link)) {
                        $submit_property_link = add_query_arg( 'edit_property', $property_id, $submit_property_link );
                        $separator = (parse_url($submit_property_link, PHP_URL_QUERY) == NULL) ? '?' : '&';
                        $parameter = 'updated=1';
                        wp_redirect($submit_property_link . $separator . $parameter);
                    }
                }

            } else {

                if (!empty($payment_page_link) && $submission_action != 'update_property' ) {
                    $separator = (parse_url($payment_page_link, PHP_URL_QUERY) == NULL) ? '?' : '&';
                    $parameter = 'prop-id=' . $property_id;
                    wp_redirect($payment_page_link . $separator . $parameter);

                } elseif( !empty($payment_page_link) && isset($_POST['houzez_draft']) ) {
                    $separator = (parse_url($payment_page_link, PHP_URL_QUERY) == NULL) ? '?' : '&';
                    $parameter = 'prop-id=' . $property_id;
                    wp_redirect($payment_page_link . $separator . $parameter);
                } else {
                    if (!empty($submit_property_link)) {
                        $submit_property_link = add_query_arg( 'edit_property', $property_id, $submit_property_link );
                        $separator = (parse_url($submit_property_link, PHP_URL_QUERY) == NULL) ? '?' : '&';
                        $parameter = 'updated=1';
                        wp_redirect($submit_property_link . $separator . $parameter);
                    }
                }
            }
        }

        if( $submission_action == 'update_property' && houzez_option('edit_listings_admin_approved') == 'yes' ) {

            $args = array(
                'listing_title'  =>  get_the_title($property_id),
                'listing_id'     =>  $property_id,
                'listing_url'    =>  get_permalink($property_id)
            );
            houzez_email_type( $admin_email, 'admin_update_listing', $args);
        }
    // End per listing if
    } else if( $enable_paid_submission == 'membership' ) {

        if ( !is_user_logged_in() ) {
            $email = wp_kses( $_POST['user_email'], $allowed_html );
            if( email_exists( $email ) ) {
                $errors[] = $houzez_local['email_already_registerd'];
            }

            if( !is_email( $email ) ) {
                $errors[] = $houzez_local['invalid_email'];
            }

            if( empty($errors) ) {
                $username = explode("@", $email);

                if( username_exists( $username[0] ) ) {
                    $username = $username[0].rand(5, 999);
                } else {
                    $username = $username[0];
                }

                $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
                $user_id = wp_create_user( $username, $random_password, $email );

                if( !is_wp_error( $user_id ) ) {

                    $user = get_user_by( 'id', $user_id );

                    houzez_update_profile( $user_id );
                    houzez_wp_new_user_notification( $user_id, $random_password );
                    $user_as_agent = houzez_option('user_as_agent');
                    if( $user_as_agent == 'yes' ) {
                        houzez_register_as_agent ( $username, $email, $user_id );
                    }

                    if( !is_wp_error($user) ) {
                        wp_clear_auth_cookie();
                        wp_set_current_user( $user->ID, $user->user_login );
                        wp_set_auth_cookie( $user->ID );
                        do_action( 'wp_login', $user->user_login );

                        $property_id = apply_filters( 'houzez_submit_listing', $new_property );

                        $args = array(
                            'listing_title'  =>  get_the_title($property_id),
                            'listing_id'     =>  $property_id,
                            'listing_url'    =>  get_permalink($property_id),
                        );

                        /*
                         * Send email
                         * */
                        if( $submission_action != 'update_property' ) {
                            houzez_email_type( $user_email, 'free_submission_listing', $args);
                            houzez_email_type( $admin_email, 'admin_free_submission_listing', $args);
                            
                        } else if($submission_action == 'update_property' && houzez_option('edit_listings_admin_approved') == 'yes' ) {
                            houzez_email_type( $admin_email, 'admin_update_listing', $args);
                        }

                        $separator = (parse_url($select_packages_link, PHP_URL_QUERY) == NULL) ? '?' : '&';
                        $parameter = '';//'prop-id=' . $property_id;
                        wp_redirect($select_packages_link . $separator . $parameter);
                        exit();
                    }

                }

            }

        // end is_user_logged_in if
        } else {
            
            $property_id = apply_filters('houzez_submit_listing', $new_property);
            $args = array(
                'listing_title'  =>  get_the_title($property_id),
                'listing_id'     =>  $property_id,
                'listing_url'    =>  get_permalink($property_id)
            );

            /*
             * Send email
             * */
            if( $submission_action != 'update_property' ) {
                houzez_email_type( $user_email, 'free_submission_listing', $args);
                houzez_email_type( $admin_email, 'admin_free_submission_listing', $args);

            } else if( $submission_action == 'update_property' && houzez_option('edit_listings_admin_approved') == 'yes' ) {
                houzez_email_type( $admin_email, 'admin_update_listing', $args);
            }

            if (houzez_user_has_membership($packageUserId)) {
                
                if (!empty($submit_property_link)) {
                    $submit_property_link = add_query_arg( 'edit_property', $property_id, $submit_property_link );
                    $separator = (parse_url($submit_property_link, PHP_URL_QUERY) == NULL) ? '?' : '&';

                    $parameter = 'success=1';
                    if($submission_action == 'update_property') {
                        $parameter = 'updated=1';
                    }
                    
                    wp_redirect($submit_property_link . $separator . $parameter);
                }

            } // end membership check
            else {
                $separator = (parse_url($select_packages_link, PHP_URL_QUERY) == NULL) ? '?' : '&';
                $parameter = '';//'prop-id=' . $property_id;
                wp_redirect($select_packages_link . $separator . $parameter);
                exit();
            }
        }

    // End membership else if
    } else {

        if ( !is_user_logged_in() ) {
            $email = wp_kses( $_POST['user_email'], $allowed_html );
            if( email_exists( $email ) ) {
                $errors[] = $houzez_local['email_already_registerd'];
            }

            if( !is_email( $email ) ) {
                $errors[] = $houzez_local['invalid_email'];
            }

            if( empty($errors) ) {
                $username = explode("@", $email);

                if( username_exists( $username[0] ) ) {
                    $username = $username[0].rand(5, 999);
                } else {
                    $username = $username[0];
                }

                $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
                $user_id = wp_create_user( $username, $random_password, $email );

                if( !is_wp_error( $user_id ) ) {

                    $user = get_user_by( 'id', $user_id );

                    houzez_update_profile( $user_id );
                    houzez_wp_new_user_notification( $user_id, $random_password );
                    $user_as_agent = houzez_option('user_as_agent');
                    if( $user_as_agent == 'yes' ) {
                        houzez_register_as_agent ( $username, $email, $user_id );
                    }

                    if( !is_wp_error($user) ) {
                        wp_clear_auth_cookie();
                        wp_set_current_user( $user->ID, $user->user_login );
                        wp_set_auth_cookie( $user->ID );
                        do_action( 'wp_login', $user->user_login );

                        $property_id = apply_filters( 'houzez_submit_listing', $new_property );

                        $args = array(
                            'listing_title'  =>  get_the_title($property_id),
                            'listing_id'     =>  $property_id,
                            'listing_url'    =>  get_permalink($property_id)
                        );

                        /*
                         * Send email
                         * */
                        if( $submission_action != 'update_property' || ( $submission_action == 'update_property' && $is_draft == 'draft' ) ) {
                            houzez_email_type( $user_email, 'free_submission_listing', $args);
                            houzez_email_type( $admin_email, 'admin_free_submission_listing', $args);

                        } else if( $submission_action == 'update_property' && houzez_option('edit_listings_admin_approved') == 'yes' ) {
                            houzez_email_type( $admin_email, 'admin_update_listing', $args);
                        }

                        if (!empty($thankyou_page_link)) {
                            wp_redirect($thankyou_page_link);

                        } else {
                            if (!empty($dashboard_listings)) {
                                $separator = (parse_url($dashboard_listings, PHP_URL_QUERY) == NULL) ? '?' : '&';
                                $parameter = ($updated_successfully) ? '' : '';
                                wp_redirect($dashboard_listings . $separator . $parameter);
                            }
                        }
                        exit();
                    }

                }

            }

        } else {

            $property_id = apply_filters('houzez_submit_listing', $new_property);

            $args = array(
                'listing_title'  =>  get_the_title($property_id),
                'listing_id'     =>  $property_id,
                'listing_url'    =>  get_permalink($property_id)
            );

            /*
             * Send email
             * */
            if( $submission_action != 'update_property' || ( $submission_action == 'update_property' && $is_draft == 'draft' ) ) {
                houzez_email_type( $user_email, 'free_submission_listing', $args);
                houzez_email_type( $admin_email, 'admin_free_submission_listing', $args);

            } else if( $submission_action == 'update_property' && houzez_option('edit_listings_admin_approved') == 'yes' ) {
                houzez_email_type( $admin_email, 'admin_update_listing', $args);
            }

            if (!empty($submit_property_link)) {
                $submit_property_link = add_query_arg( 'edit_property', $property_id, $submit_property_link );
                $separator = (parse_url($submit_property_link, PHP_URL_QUERY) == NULL) ? '?' : '&';

                $parameter = 'success=1';
                if($submission_action == 'update_property') {
                    $parameter = 'updated=1';
                }
                
                wp_redirect($submit_property_link . $separator . $parameter);
            }

        }
    }

}

get_header(); 

$houzez_loggedin = false;
if ( is_user_logged_in() ) {
    $houzez_loggedin = true;
}

$dash_main_class = "dashboard-add-new-listing";
if (houzez_edit_property()) { 
    $dash_main_class = "dashboard-edit-listing";
}

if( is_user_logged_in() ) { ?> 

    <header class="header-main-wrap dashboard-header-main-wrap">
        <div class="dashboard-header-wrap">
            <div class="d-flex align-items-center">
                <div class="dashboard-header-left flex-grow-1">
                    <?php get_template_part('template-parts/dashboard/submit/partials/snake-nav'); ?>
                    <h1><?php echo houzez_option('dsh_create_listing', 'Create a Listing'); ?></h1>
                </div><!-- dashboard-header-left -->
                <div class="dashboard-header-right">
                    <?php 
                    if(houzez_edit_property()) { 
                        $view_link = isset($_GET['edit_property']) ? get_permalink($_GET['edit_property']) : '';
                    ?>
                    <a class="btn btn-primary-outlined" target="_blank" href="<?php echo esc_url($view_link); ?>"><?php echo houzez_option('fal_view_property', esc_html__('View Property', 'houzez')); ?></a>

                    <?php if( get_post_status( $_GET['edit_property'] ) == 'draft' ) { ?>
                    <button id="save_as_draft" class="btn btn-primary-outlined fave-load-more">
                        <?php get_template_part('template-parts/loader'); ?>
                        <?php echo houzez_option('fal_save_draft', esc_html__('Save as Draft', 'houzez')); ?>        
                    </button>
                    <?php } ?>

                    <?php } else { ?>

                    <button id="save_as_draft" class="btn btn-primary-outlined fave-load-more">
                        <?php get_template_part('template-parts/loader'); ?>
                        <?php echo houzez_option('fal_save_draft', esc_html__('Save as Draft', 'houzez')); ?>        
                    </button>

                    <?php } ?>

                </div><!-- dashboard-header-right -->
            </div><!-- d-flex -->
        </div><!-- dashboard-header-wrap -->
    </header><!-- .header-main-wrap -->
    <section class="dashboard-content-wrap <?php echo esc_attr($dash_main_class); ?>">
        <style>
            .guidelines-trigger-btn {
                background: linear-gradient(135deg, #0d2942 0%, #1a4d7a 100%);
                color: white;
                border: none;
                padding: 14px 28px;
                border-radius: 8px;
                font-size: 15px;
                font-weight: 600;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 10px;
                box-shadow: 0 4px 12px rgba(13, 41, 66, 0.3);
                transition: all 0.3s ease;
                margin: 30px 30px 0 30px;
            }

            .guidelines-trigger-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(13, 41, 66, 0.4);
            }

            .guidelines-trigger-btn:active {
                transform: translateY(0);
            }

            .btn-icon {
                font-size: 18px;
                animation: pulse 2s ease-in-out infinite;
            }

            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.1); }
            }

            .modal-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.6);
                backdrop-filter: blur(4px);
                z-index: 9998;
                animation: fadeIn 0.3s ease;
            }

            .modal-overlay.active {
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }

            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }

            .guidelines-modal {
                background: white;
                border-radius: 12px;
                max-width: 900px;
                width: 100%;
                max-height: 90vh;
                overflow-y: auto;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                animation: slideUp 0.4s ease;
                position: relative;
            }

            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .modal-close-btn {
                position: absolute;
                top: 20px;
                right: 20px;
                background: rgba(0, 0, 0, 0.1);
                border: none;
                width: 36px;
                height: 36px;
                border-radius: 50%;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 20px;
                color: white;
                transition: all 0.2s;
                z-index: 10;
            }

            .modal-close-btn:hover {
                background: rgba(0, 0, 0, 0.2);
                transform: rotate(90deg);
            }

            .guidelines-section {
                width: 100%;
                margin: 0 auto;
                background: white;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
                overflow: hidden;
            }

            .section-header-main {
                background: linear-gradient(135deg, #0d2942 0%, #1a4d7a 100%);
                color: white;
                padding: 32px;
                position: relative;
                overflow: hidden;
            }

            .section-header-main::before {
                content: "";
                position: absolute;
                top: -50%;
                right: -50%;
                width: 200%;
                height: 200%;
                background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
                animation: shimmer 3s ease-in-out infinite;
            }

            @keyframes shimmer {
                0%, 100% { transform: translate(0, 0); }
                50% { transform: translate(-20px, -20px); }
            }

            .header-title {
                font-size: 24px;
                font-weight: 700;
                margin-bottom: 8px;
                position: relative;
                z-index: 1;
            }

            .header-subtitle {
                font-size: 15px;
                opacity: 0.95;
                line-height: 1.6;
                position: relative;
                z-index: 1;
            }

            .card-section {
                padding: 15px 32px;
                border-bottom: 1px solid #e5e5e5;
                animation: fadeInUp 0.5s ease;
            }

            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .card-section:last-child {
                border-bottom: none;
            }

            .section-header {
                display: flex;
                align-items: center;
                gap: 12px;
                margin-bottom: 20px;
            }

            .section-icon {
                width: 42px;
                height: 42px;
                background: linear-gradient(135deg, #c8a063 0%, #d4af78 100%);
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 20px;
                box-shadow: 0 4px 12px rgba(200, 160, 99, 0.3);
                animation: iconPop 0.6s ease;
            }

            @keyframes iconPop {
                0% { transform: scale(0); }
                50% { transform: scale(1.1); }
                100% { transform: scale(1); }
            }

            .section-title {
                font-size: 20px;
                font-weight: 700;
                color: #0d2942;
            }

            .guideline-list {
                list-style: none;
                padding: 0;
            }

            .guideline-item {
                padding: 5px 0;
                padding-left: 32px;
                position: relative;
                color: #555;
                line-height: 1.7;
                font-size: 15px;
                transition: all 0.2s ease;
            }

            .guideline-item:hover {
                color: #0d2942;
                padding-left: 36px;
            }

            .guideline-item:before {
                content: "";
                position: absolute;
                left: 0;
                top: 20px;
                width: 8px;
                height: 8px;
                background: linear-gradient(135deg, #c8a063 0%, #d4af78 100%);
                border-radius: 50%;
                box-shadow: 0 2px 6px rgba(200, 160, 99, 0.4);
                transition: all 0.2s ease;
            }

            .guideline-item:hover:before {
                transform: scale(1.3);
            }

            .warning-section {
                background: linear-gradient(135deg, #fff9f0 0%, #fffbf5 100%);
                border: 2px solid #ffe5b4;
                border-radius: 12px;
                padding: 24px;
                margin-top: 28px;
                box-shadow: 0 4px 12px rgba(255, 152, 0, 0.1);
                animation: warningPulse 2s ease-in-out infinite;
            }

            @keyframes warningPulse {
                0%, 100% { box-shadow: 0 4px 12px rgba(255, 152, 0, 0.1); }
                50% { box-shadow: 0 4px 16px rgba(255, 152, 0, 0.2); }
            }

            .warning-header {
                display: flex;
                align-items: center;
                gap: 12px;
                margin-bottom: 10px;
            }

            .warning-icon {
                color: #ff9800;
                font-size: 24px;
                animation: shake 3s ease-in-out infinite;
            }

            @keyframes shake {
                0%, 100% { transform: rotate(0deg); }
                10%, 30%, 50%, 70%, 90% { transform: rotate(-5deg); }
                20%, 40%, 60%, 80% { transform: rotate(5deg); }
            }

            .warning-title {
                font-weight: 700;
                color: #d97706;
                font-size: 16px;
            }

            .warning-text {
                color: #92400e;
                font-size: 15px;
                line-height: 1.7;
                margin-left: 36px;
                font-weight: 500;
            }

            @media (max-width: 768px) {
                .guidelines-trigger-btn {
                    margin: 10px 15px;
                    padding: 12px 20px;
                    font-size: 14px;
                }

                .guidelines-modal {
                    margin: 10px;
                }

                .section-header-main {
                    padding: 24px;
                }

                .card-section {
                    padding: 24px;
                }

                .header-title {
                    font-size: 20px;
                }

                .section-title {
                    font-size: 18px;
                }

                .guideline-item {
                    font-size: 14px;
                }

                .warning-text {
                    margin-left: 0;
                }
            }
        </style>
        <!-- Trigger Button -->
        <button class="guidelines-trigger-btn pull-right" onclick="openGuidelinesModal()">
            <span class="btn-icon">📋</span>
            <span>View Submission Requirements</span>
        </button>

        <!-- Modal Overlay -->
        <div class="modal-overlay" id="guidelinesModal" onclick="closeGuidelinesModalOnOverlay(event)">
            <div class="guidelines-modal" onclick="event.stopPropagation()">
                <button class="modal-close-btn" onclick="closeGuidelinesModal()">✕</button>
                
                <div class="guidelines-section">
                    <div class="section-header-main">
                        <h2 class="header-title">Before You Submit Your Listing, Please Review These Guidelines</h2>
                        <p class="header-subtitle">To ensure your property listing is approved and published smoothly, make sure your submission follows the requirements below</p>
                    </div>

                    <div class="card-section">
                        <div class="section-header">
                            <div class="section-icon">📸</div>
                            <h3 class="section-title">Images</h3>
                        </div>
                        <ul class="guideline-list">
                            <li class="guideline-item">Must not contain watermarks</li>
                            <li class="guideline-item">Must not exceed 900 KB per image</li>
                            <li class="guideline-item">Limit to a maximum of 10 photos per listing — choose your best shots</li>
                            <li class="guideline-item">Do not add text, logos, or any details to the images</li>
                        </ul>
                    </div>

                    <div class="card-section">
                        <div class="section-header">
                            <div class="section-icon">📝</div>
                            <h3 class="section-title">Property Description</h3>
                        </div>
                        <ul class="guideline-list">
                            <li class="guideline-item">Must not include contact details (phone numbers, emails, links, etc.)</li>
                            <li class="guideline-item">The developer's name should not be included in the listing description to maintain a uniform presentation and direct all client inquiries through our platform</li>
                            <li class="guideline-item">Ensure confidential details are uniform across all listings</li>
                            <li class="guideline-item">Only "For Sale" properties are accepted</li>
                            <li class="guideline-item">Prices must be entered without commas (e.g., "250000" not "250,000")</li>
                            <li class="guideline-item">Credential Details: Owned By - Enter your company name. If you are an Independent Agent or Broker, Individual Owner, enter your full name. This helps us identify who posted the listing and keep everything consistent. This field must be uniform in all your listings.</li>  
                        </ul>

                        <div class="warning-section">
                            <div class="warning-header">
                                <span class="warning-icon">⚠️</span>
                                <span class="warning-title">Important Reminder</span>
                            </div>
                            <p class="warning-text">Listings that do not comply with these rules will not be approved. Please double-check before submitting.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

         <script>
            const MODAL_STORAGE_KEY = 'guidelinesModalLastShown';
            const TWENTY_FOUR_HOURS = 24 * 60 * 60 * 1000; // 24 hours in milliseconds

            function shouldShowModal() {
                const lastShown = localStorage.getItem(MODAL_STORAGE_KEY);
                
                if (!lastShown) {
                    return true; // Never shown before
                }
                
                const lastShownTime = parseInt(lastShown, 10);
                const currentTime = Date.now();
                const timeDifference = currentTime - lastShownTime;
                
                return timeDifference >= TWENTY_FOUR_HOURS;
            }

            function saveModalShownTime() {
                localStorage.setItem(MODAL_STORAGE_KEY, Date.now().toString());
            }

            function openGuidelinesModal() {
                document.getElementById('guidelinesModal').classList.add('active');
                document.body.style.overflow = 'hidden';
                saveModalShownTime();
            }

            function closeGuidelinesModal() {
                document.getElementById('guidelinesModal').classList.remove('active');
                document.body.style.overflow = 'auto';
            }

            function closeGuidelinesModalOnOverlay(event) {
                if (event.target === event.currentTarget) {
                    closeGuidelinesModal();
                }
            }

            // Close modal with Escape key
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeGuidelinesModal();
                }
            });

            // Auto-open modal on page load (only if 24 hours have passed)
            window.addEventListener('load', function() {
                if (shouldShowModal()) {
                    setTimeout(openGuidelinesModal, 500);
                }
            });
        </script>
        <div class="d-flex">
            <div class="order-2">
                <?php
                if( houzez_edit_property() ) {
                    get_template_part('template-parts/dashboard/submit/partials/menu-edit-property');
                } else { 
                    echo '<div class="menu-edit-property-wrap">';
                    get_template_part( 'template-parts/dashboard/submit/partials/author');
                    echo '</div>';
                }?>
            </div><!-- order-2 -->
            <div class="order-1 flex-grow-1">
        

                <div class="dashboard-content-inner-wrap">
                    
                    <?php
                    if (is_plugin_active('houzez-theme-functionality/houzez-theme-functionality.php')) {
                        if (houzez_edit_property()) {

                            get_template_part('template-parts/dashboard/submit/edit-property-form');

                        } else {

                            get_template_part('template-parts/dashboard/submit/submit-property-form');

                        } /* end of add/edit property*/
                    } else {
                        echo $houzez_local['houzez_plugin_required'];
                    }
                    
                    ?>
                    
                </div><!-- dashboard-content-inner-wrap -->

            </div><!-- order-1 -->
        </div><!-- d-flex -->
        
    </section><!-- dashboard-content-wrap -->

    <section class="dashboard-side-wrap">
        <?php get_template_part('template-parts/dashboard/side-wrap'); ?>
    </section>

<?php
} else { // End if user logged-in ?>

<section class="frontend-submission-page dashboard-content-inner-wrap">
    
    <div class="container">
        <div class="row">
            <div class="col-12">
                <?php 
                if( $create_listing_login_required == 'yes' ) {

                    get_template_part('template-parts/dashboard/submit/partials/login-required');

                } else {

                    get_template_part('template-parts/dashboard/submit/submit-property-form');
                     
                } ?>
            </div>
        </div><!-- row -->
    </div><!-- container -->
</section><!-- frontend-submission-page -->

<?php
} // End logged-in else


if(houzez_get_map_system() == 'google') {
    if(houzez_option('googlemap_api_key') != "") {
        wp_enqueue_script('houzez-submit-google-map',  get_theme_file_uri('/js/submit-property-google-map.js'), array('jquery'), HOUZEZ_THEME_VERSION, true);
    }
    
} else {
    wp_enqueue_script('houzez-submit-osm', get_theme_file_uri('/js/submit-property-osm.js'), array('jquery'), HOUZEZ_THEME_VERSION, true);
}
?>

<?php get_footer();?>