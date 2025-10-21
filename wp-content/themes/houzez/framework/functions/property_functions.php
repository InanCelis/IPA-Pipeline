<?php
/**
 * Created by PhpStorm.
 * User: waqasriaz
 * Date: 07/10/15
 * Time: 11:31 AM
 */

if( !function_exists('houzez_listing_expire')) {
    function houzez_listing_expire() {
        global $post;

        //If manual expire date set
        $manual_expire = get_post_meta( $post->ID, 'houzez_manual_expire', true );
        if( !empty( $manual_expire )) {
            $expiration_date = get_post_meta( $post->ID,'_houzez_expiration_date',true );
            echo ( $expiration_date ? get_date_from_gmt(gmdate('Y-m-d H:i:s', $expiration_date), get_option('date_format').' '.get_option('time_format')) : __('Never', 'houzez'));
        } else {
            $submission_type = houzez_option('enable_paid_submission');
            // Per listing
            if( $submission_type == 'per_listing' || $submission_type == 'free_paid_listing' || $submission_type == 'no' ) {
                $per_listing_expire_unlimited = houzez_option('per_listing_expire_unlimited');
                if( $per_listing_expire_unlimited != 0 ) {
                    $per_listing_expire = houzez_option('per_listing_expire');

                    $publish_date = $post->post_date;
                    echo date_i18n( get_option('date_format').' '.get_option('time_format'), strtotime( $publish_date. ' + '.$per_listing_expire.' days' ) );
                }
            } elseif( $submission_type == 'membership' ) {
                $post_author = get_post_field( 'post_author', $post->ID );
                $agent_agency_id = houzez_get_agent_agency_id( $post_author );
                if( $agent_agency_id ) {
                    $post_author = $agent_agency_id;
                }

                $package_id = get_user_meta( $post_author, 'package_id', true );

                if( !empty($package_id) ) {
                    $billing_time_unit = get_post_meta( $package_id, 'fave_billing_time_unit', true );
                    $billing_unit = get_post_meta( $package_id, 'fave_billing_unit', true );

                    if( $billing_time_unit == 'Day')
                        $billing_time_unit = 'days';
                    elseif( $billing_time_unit == 'Week')
                        $billing_time_unit = 'weeks';
                    elseif( $billing_time_unit == 'Month')
                        $billing_time_unit = 'months';
                    elseif( $billing_time_unit == 'Year')
                        $billing_time_unit = 'years';

                    $pack_date =  get_user_meta( $post_author, 'package_activation',true );
                    $expired_date = strtotime($pack_date. ' + '.$billing_unit.' '.$billing_time_unit);
                    echo date_i18n( get_option('date_format').' '.get_option('time_format'),  $expired_date ); 
                }
            }
        }
    }
}

if( !function_exists('houzez_featured_listing_expire')) {
    function houzez_featured_listing_expire() {
        global $post;

        $submission_type = houzez_option('enable_paid_submission');
        $prop_featured_date = get_post_meta( $post->ID, 'houzez_featured_listing_date', true );
        // Per listing
        if( ( $submission_type == 'free_paid_listing' || $submission_type == 'no' ) && ( $prop_featured_date != '' ) ) {
            
            $featured_listing_expire = intval ( fave_option('featured_listing_expire', 30) );

            echo '<br>'.esc_html__('Featured Expiration:', 'houzez'); echo ' '.date_i18n( get_option('date_format').' '.get_option('time_format'), strtotime( $prop_featured_date. ' + '.$featured_listing_expire.' days' ) );
            
        }
        
    }
}

if( ! function_exists('houzez_get_image_size_dimensions') ) {
    function houzez_get_image_size_dimensions($size_name) {
        // Define image sizes
        $image_sizes = array(
            'houzez-item-image-1' => array('width' => 592, 'height' => 444),
            'houzez-item-image-4' => array('width' => 758, 'height' => 564),
            'houzez-item-image-6' => array('width' => 584, 'height' => 438),
        );

        // Return the width and height if size exists, otherwise return default dimensions
        return isset($image_sizes[$size_name]) ? $image_sizes[$size_name] : array('width' => 592, 'height' => 444);
    }
}

if( !function_exists('houzez_get_property_gallery') ) {
    function houzez_get_property_gallery($size = 'thumbnail') {
        global $post;

        if ( ! houzez_option('disable_property_gallery', 1) ) {
            return;
        }

        $default_size = houzez_get_image_size_dimensions($size);

        $gallery_ids = get_post_meta( $post->ID, 'fave_property_images', false );

        if ( has_post_thumbnail() || $gallery_ids ) {
            $images = [];
            
            if ( has_post_thumbnail() && houzez_option('featured_img_in_gallery', 0) != 1 ) {
                $thumb_id = get_post_thumbnail_id($post);
                $thumb_meta = wp_get_attachment_metadata($thumb_id);
                $temp_array = []; // fresh array for the featured image
                $temp_array['image'] = get_the_post_thumbnail_url($post, $size);
                $temp_array['alt'] = get_post_meta( $thumb_id, '_wp_attachment_image_alt', true );

                if ($thumb_meta) {
                    $temp_array['width'] = isset($thumb_meta['sizes'][$size]['width']) ? $thumb_meta['sizes'][$size]['width'] : $default_size['width'];
                    $temp_array['height'] = isset($thumb_meta['sizes'][$size]['height']) ? $thumb_meta['sizes'][$size]['height'] : $default_size['height'];
                } else {
                    $temp_array['width'] = $default_size['width'];
                    $temp_array['height'] = $default_size['height'];
                }

                $images[] = $temp_array;
            }

            if ( empty($gallery_ids) ) {
                return;
            }

            foreach ( $gallery_ids as $id ) {
                $temp_array = []; // Reinitialize here for each gallery image
                $img = wp_get_attachment_image_url($id, $size);
                $img_meta = wp_get_attachment_metadata($id);
                $alt_text = get_post_meta( $id, '_wp_attachment_image_alt', true );
                
                if ( $img ) {
                    $temp_array['image'] = $img;
                    $temp_array['alt'] = $alt_text;
                    
                    if ($img_meta) {
                        $temp_array['width'] = isset($img_meta['sizes'][$size]['width']) ? $img_meta['sizes'][$size]['width'] : $default_size['width'];
                        $temp_array['height'] = isset($img_meta['sizes'][$size]['height']) ? $img_meta['sizes'][$size]['height'] : $default_size['height'];
                    } else {
                        $temp_array['width'] = $default_size['width'];
                        $temp_array['height'] = $default_size['height'];
                    }
                    $images[] = $temp_array;
                }
            }

            return 'data-images="' . esc_attr(json_encode($images)) . '"';
        }
    }
}

if( !function_exists('houzez_property_gallery') ) {
    function houzez_property_gallery($size = 'thumbnail') {
       echo houzez_get_property_gallery($size);
    }
}


if( !function_exists('houzez_get_gallery_class') ) {
    function houzez_get_gallery_class() {
        global $post;

        $class = '';
        if ( houzez_option('disable_property_gallery', 1) ) {
            $class = "hz-item-gallery-js";
        }

        
    }
}

if( !function_exists('houzez_property_gallery_class') ) {
    function houzez_property_gallery_class() {
       echo houzez_get_gallery_class();
    }
}

/*-----------------------------------------------------------------------------------*/
// Submit Property filter
/*-----------------------------------------------------------------------------------*/
add_filter('houzez_submit_listing', 'houzez_submit_listing');

if( !function_exists('houzez_submit_listing') ) {
    function houzez_submit_listing($new_property) {

        $userID = get_current_user_id();
        $post_author = $userID;
        $userIdPackage = $userID;
        $listings_admin_approved = houzez_option('listings_admin_approved');
        $edit_listings_admin_approved = houzez_option('edit_listings_admin_approved');
        $enable_paid_submission = houzez_option('enable_paid_submission');

        $agent_agency_id = houzez_get_agent_agency_id( $userID );
        if( $agent_agency_id ) {
            $userIdPackage = $agent_agency_id;
        }

        // Title
        if( isset( $_POST['prop_title']) ) {
            $new_property['post_title'] = sanitize_text_field( $_POST['prop_title'] );
        }

        if( $enable_paid_submission == 'membership' ) {
            $user_submit_has_no_membership = isset($_POST['user_submit_has_no_membership']) ? $_POST['user_submit_has_no_membership'] : '';
        } else {
            $user_submit_has_no_membership = 'no';
        }

        // Description
        if( isset( $_POST['prop_des'] ) ) {
            $new_property['post_content'] = wp_kses_post( wpautop( wptexturize( $_POST['prop_des'] ) ) );
        }

        if( isset($_POST['property_author']) && ! empty( $_POST['property_author'] ) ) {
            $post_author = $_POST['property_author'];
        }

        $new_property['post_author'] = $post_author;

        $submission_action = $_POST['action'];
        $prop_id = 0;

        if( $submission_action == 'add_property' ) {

            if( houzez_is_admin() ) {
                $new_property['post_status'] = 'publish';
            } else {
                if( $listings_admin_approved != 'yes' && ( $enable_paid_submission == 'no' || $enable_paid_submission == 'free_paid_listing' || $enable_paid_submission == 'membership' ) ) {
                    if( $user_submit_has_no_membership == 'yes' ) {
                        $new_property['post_status'] = 'draft';
                    } else {
                        $new_property['post_status'] = 'publish';
                    }
                } else {
                    if( $user_submit_has_no_membership == 'yes' && $enable_paid_submission = 'membership' ) {
                        $new_property['post_status'] = 'draft';
                    } else {
                        $new_property['post_status'] = 'pending';
                    }
                }
            }

            /*
             * Filter submission arguments before insert into database.
             */
            $new_property = apply_filters( 'houzez_before_submit_property', $new_property );
            $prop_id = wp_insert_post( $new_property );

            if( $prop_id > 0 ) {
                $submitted_successfully = true;
                if( $enable_paid_submission == 'membership'){ // update package status
                    houzez_update_package_listings( $userIdPackage );
                }
            }

        } else if( $submission_action == 'update_property' ) {

            $new_property['ID'] = intval( $_POST['prop_id'] );

            if( get_post_status( intval( $_POST['prop_id'] ) ) == 'draft' ) {
                if( $enable_paid_submission == 'membership') {
                    houzez_update_package_listings($userIdPackage);
                }
                if( $listings_admin_approved != 'yes' && ( $enable_paid_submission == 'no' || $enable_paid_submission == 'free_paid_listing' || $enable_paid_submission == 'membership' ) ) {
                    $new_property['post_status'] = 'publish';
                } else {
                    $new_property['post_status'] = 'pending';
                }
            } elseif( $edit_listings_admin_approved == 'yes' ) {
                    $new_property['post_status'] = 'pending';
            }

            if( ! houzez_user_has_membership($userIdPackage) && $enable_paid_submission == 'membership' ) {
                $new_property['post_status'] = 'draft';

            }

            if( houzez_is_admin() ) {
                $new_property['post_status'] = 'publish';
            }

            /*
             * Filter submission arguments before update property.
             */
            $new_property = apply_filters( 'houzez_before_update_property', $new_property );
            $prop_id = wp_update_post( $new_property );

        }

        if( $prop_id > 0 ) {


            if(class_exists('Houzez_Fields_Builder')) {
                $fields_array = Houzez_Fields_Builder::get_form_fields();
                if(!empty($fields_array)):
                    foreach ( $fields_array as $value ):
                        $field_name = $value->field_id;
                        $field_type = $value->type;

                        if( isset( $_POST[$field_name] ) && !empty( $_POST[$field_name] ) ) {

                            if( $field_type == 'checkbox_list' || $field_type == 'multiselect' ) {
                                delete_post_meta( $prop_id, 'fave_'.$field_name );
                                foreach ( $_POST[ $field_name ] as $value ) {
                                    add_post_meta( $prop_id, 'fave_'.$field_name, sanitize_text_field( $value ) );
                                }
                            } else {
                                update_post_meta( $prop_id, 'fave_'.$field_name, sanitize_text_field( $_POST[$field_name] ) );
                            }

                        } else {
                            delete_post_meta( $prop_id, 'fave_'.$field_name );
                        }

                    endforeach; 
                endif;
            }


            if( $user_submit_has_no_membership == 'yes' ) {
                update_user_meta( $userID, 'user_submit_has_no_membership', $prop_id );
                update_user_meta( $userID, 'user_submitted_without_membership', 'yes' );
            }

            // Add price post meta
            if( isset( $_POST['prop_price'] ) ) {
                update_post_meta( $prop_id, 'fave_property_price', sanitize_text_field( $_POST['prop_price'] ) );

                //added by webdev IPA 9/6/2025
                update_post_meta( $prop_id, 'fave_local_property_price', '' );
                update_post_meta( $prop_id, 'fave_local_currency', '' );

                if( isset( $_POST['prop_label'] ) ) {
                    update_post_meta( $prop_id, 'fave_property_price_postfix', sanitize_text_field( $_POST['prop_label']) );
                }
            }


            // Show Price Placeholder
            update_post_meta($prop_id, 'fave_show_price_placeholder', 0);

            if (isset($_POST['show_price_placeholder'])) {
                $show_placeholder = $_POST['show_price_placeholder'];
                if ($show_placeholder == 'on') {
                    $show_placeholder = 1;
                }

                update_post_meta($prop_id, 'fave_show_price_placeholder', sanitize_text_field($show_placeholder));
            }

            //price placeholder
            if( isset( $_POST['prop_price_placeholder'] ) ) {
                update_post_meta( $prop_id, 'fave_property_price_placeholder', sanitize_text_field( $_POST['prop_price_placeholder']) );
            }

            //price prefix
            if( isset( $_POST['prop_price_prefix'] ) ) {
                update_post_meta( $prop_id, 'fave_property_price_prefix', sanitize_text_field( $_POST['prop_price_prefix']) );
            }

            // Second Price
            if( isset( $_POST['prop_sec_price'] ) ) {
                update_post_meta( $prop_id, 'fave_property_sec_price', sanitize_text_field( $_POST['prop_sec_price'] ) );
            }

            // currency
            if( isset( $_POST['currency'] ) ) {
                update_post_meta( $prop_id, 'fave_currency', sanitize_text_field( $_POST['currency'] ) );

                //added by webdev IPA 9/6/2025
                update_post_meta( $prop_id, 'fave_local_property_price', '' );
                update_post_meta( $prop_id, 'fave_local_currency', '' );

                if(class_exists('Houzez_Currencies')) {
                    $currencies = Houzez_Currencies::get_property_currency_2($prop_id, $_POST['currency']);

                    update_post_meta( $prop_id, 'fave_currency_info', $currencies );
                }
            }


            // Area Size
            if( isset( $_POST['prop_size'] ) ) {
                update_post_meta( $prop_id, 'fave_property_size', sanitize_text_field( $_POST['prop_size'] ) );
            }

            // Area Size Prefix
            if( isset( $_POST['prop_size_prefix'] ) ) {
                update_post_meta( $prop_id, 'fave_property_size_prefix', sanitize_text_field( $_POST['prop_size_prefix'] ) );
            }

            // Land Area Size
            if( isset( $_POST['prop_land_area'] ) ) {
                update_post_meta( $prop_id, 'fave_property_land', sanitize_text_field( $_POST['prop_land_area'] ) );
            }

            // Land Area Size Prefix
            if( isset( $_POST['prop_land_area_prefix'] ) ) {
                update_post_meta( $prop_id, 'fave_property_land_postfix', sanitize_text_field( $_POST['prop_land_area_prefix'] ) );
            }

            // Bedrooms
            if( isset( $_POST['prop_beds'] ) ) {
                update_post_meta( $prop_id, 'fave_property_bedrooms', sanitize_text_field( $_POST['prop_beds'] ) );
            }

            // Rooms
            if( isset( $_POST['prop_rooms'] ) ) {
                update_post_meta( $prop_id, 'fave_property_rooms', sanitize_text_field( $_POST['prop_rooms'] ) );
            }

            // Bathrooms
            if( isset( $_POST['prop_baths'] ) ) {
                update_post_meta( $prop_id, 'fave_property_bathrooms', sanitize_text_field( $_POST['prop_baths'] ) );
            }

            // Garages
            if( isset( $_POST['prop_garage'] ) ) {
                update_post_meta( $prop_id, 'fave_property_garage', sanitize_text_field( $_POST['prop_garage'] ) );
            }

            // Garages Size
            if( isset( $_POST['prop_garage_size'] ) ) {
                update_post_meta( $prop_id, 'fave_property_garage_size', sanitize_text_field( $_POST['prop_garage_size'] ) );
            }

            // Virtual Tour
            if( isset( $_POST['virtual_tour'] ) ) {
                update_post_meta( $prop_id, 'fave_virtual_tour', $_POST['virtual_tour'] );
            }

            // Year Built
            if( isset( $_POST['prop_year_built'] ) ) {
                update_post_meta( $prop_id, 'fave_property_year', sanitize_text_field( $_POST['prop_year_built'] ) );
            }

            // Property ID
            $auto_property_id = houzez_option('auto_property_id');
            if( $auto_property_id != 1 ) {
                if (isset($_POST['property_id'])) {
                    update_post_meta($prop_id, 'fave_property_id', sanitize_text_field($_POST['property_id']));
                }
            } else {
                    update_post_meta($prop_id, 'fave_property_id', $prop_id );
            }

            // Property Video Url
            if( isset( $_POST['prop_video_url'] ) ) {
                update_post_meta( $prop_id, 'fave_video_url', sanitize_text_field( $_POST['prop_video_url'] ) );
            }

            // property video image - in case of update
            $property_video_image = "";
            $property_video_image_id = 0;
            if( $submission_action == "update_property" ) {
                $property_video_image_id = get_post_meta( $prop_id, 'fave_video_image', true );
                if ( ! empty ( $property_video_image_id ) ) {
                    $property_video_image_src = wp_get_attachment_image_src( $property_video_image_id, 'houzez-property-detail-gallery' );
                    if( $property_video_image_src ) {
                        $property_video_image = $property_video_image_src[0];
                    }
                }
            }

            // clean up the old meta information related to images when property update
            if( $submission_action == "update_property" ){
                delete_post_meta( $prop_id, 'fave_property_images' );
                delete_post_meta( $prop_id, 'fave_attachments' );
                delete_post_meta( $prop_id, 'fave_agents' );
                delete_post_meta( $prop_id, 'fave_property_agency' );
                delete_post_meta( $prop_id, '_thumbnail_id' );
            }

            // Property Images
            if( isset( $_POST['propperty_image_ids'] ) ) {
                if (!empty($_POST['propperty_image_ids']) && is_array($_POST['propperty_image_ids'])) {
                    $property_image_ids = array();
                    foreach ($_POST['propperty_image_ids'] as $prop_img_id ) {
                        $property_image_ids[] = intval( $prop_img_id );
                        add_post_meta($prop_id, 'fave_property_images', $prop_img_id);

                        // Update the post_parent field for each attachment
                        wp_update_post(array(
                            'ID' => $prop_img_id,
                            'post_parent' => $prop_id
                        ));
                    }

                    // featured image
                    if( isset( $_POST['featured_image_id'] ) ) {
                        $featured_image_id = intval( $_POST['featured_image_id'] );
                        if( in_array( $featured_image_id, $property_image_ids ) ) {
                            update_post_meta( $prop_id, '_thumbnail_id', $featured_image_id );

                            /* if video url is provided but there is no video image then use featured image as video image */
                            if ( empty( $property_video_image ) && !empty( $_POST['prop_video_url'] ) ) {
                                update_post_meta( $prop_id, 'fave_video_image', $featured_image_id );
                            }
                        }
                    } elseif ( ! empty ( $property_image_ids ) ) {
                        update_post_meta( $prop_id, '_thumbnail_id', $property_image_ids[0] );

                        /* if video url is provided but there is no video image then use featured image as video image */
                        if ( empty( $property_video_image ) && !empty( $_POST['prop_video_url'] ) ) {
                            update_post_meta( $prop_id, 'fave_video_image', $property_image_ids[0] );
                        }
                    }
                }
            }

            if( isset( $_POST['propperty_attachment_ids'] ) ) {
                    $property_attach_ids = array();
                    foreach ($_POST['propperty_attachment_ids'] as $prop_atch_id ) {
                        $property_attach_ids[] = intval( $prop_atch_id );
                        add_post_meta($prop_id, 'fave_attachments', $prop_atch_id);
                    }
            }
 

            // Add property type
            if( isset( $_POST['prop_type'] ) && ( $_POST['prop_type'] != '-1' ) ) {
                $type = array_map( 'intval', $_POST['prop_type'] );
                wp_set_object_terms( $prop_id, $type, 'property_type' );
            } else {
                wp_set_object_terms( $prop_id, '', 'property_type' );
            }

            // Add property status
            if( isset( $_POST['prop_status'] ) && ( $_POST['prop_status'] != '-1' ) ) {
                $prop_status = array_map( 'intval', $_POST['prop_status'] );
                wp_set_object_terms( $prop_id, $prop_status, 'property_status' );
            } else {
                wp_set_object_terms( $prop_id, '', 'property_status' );
            }

            // Add property status
            if( isset( $_POST['prop_labels'] ) ) {
                $prop_labels = array_map( 'intval', $_POST['prop_labels'] );
                wp_set_object_terms( $prop_id, $prop_labels, 'property_label' );
            } else {
                wp_set_object_terms( $prop_id, '', 'property_label' );
            }

            // Country
            if( isset( $_POST['country'] ) ) {
                $property_country = sanitize_text_field( $_POST['country'] );
                $country_id = wp_set_object_terms( $prop_id, $property_country, 'property_country' );
            } else {
                $default_country = houzez_option('default_country');
                $country_id = wp_set_object_terms( $prop_id, $default_country, 'property_country' );
            }
            
            // Postal Code
            if( isset( $_POST['postal_code'] ) ) {
                update_post_meta( $prop_id, 'fave_property_zip', sanitize_text_field( $_POST['postal_code'] ) );
            }

            
            if( isset( $_POST['locality'] ) ) {
                $property_city = sanitize_text_field( $_POST['locality'] );
                $city_id = wp_set_object_terms( $prop_id, $property_city, 'property_city' );

                $houzez_meta = array();
                $houzez_meta['parent_state'] = isset( $_POST['administrative_area_level_1'] ) ? $_POST['administrative_area_level_1'] : '';
                if( !empty( $city_id) && isset( $_POST['administrative_area_level_1'] ) ) {
                    update_option('_houzez_property_city_' . $city_id[0], $houzez_meta);
                }
            }

            if( isset( $_POST['neighborhood'] ) ) {
                $property_area = sanitize_text_field( $_POST['neighborhood'] );
                $area_id = wp_set_object_terms( $prop_id, $property_area, 'property_area' );

                $houzez_meta = array();
                $houzez_meta['parent_city'] = isset( $_POST['locality'] ) ? $_POST['locality'] : '';
                if( !empty( $area_id) && isset( $_POST['locality'] ) ) {
                    update_option('_houzez_property_area_' . $area_id[0], $houzez_meta);
                }
            }


            // Add property state
            if( isset( $_POST['administrative_area_level_1'] ) ) {
                $property_state = sanitize_text_field( $_POST['administrative_area_level_1'] );
                $state_id = wp_set_object_terms( $prop_id, $property_state, 'property_state' );

                $houzez_meta = array();
                $country_short = isset( $_POST['country'] ) ? $_POST['country'] : '';
                if(!empty($country_short)) {
                   $country_short = strtoupper($country_short); 
                } else {
                    $country_short = '';
                }
                
                $houzez_meta['parent_country'] = $country_short;
                if( !empty( $state_id) ) {
                    update_option('_houzez_property_state_' . $state_id[0], $houzez_meta);
                }
            }
           
            // Add property features
            if( isset( $_POST['prop_features'] ) ) {
                $features_array = array();
                foreach( $_POST['prop_features'] as $feature_id ) {
                    $features_array[] = intval( $feature_id );
                }
                wp_set_object_terms( $prop_id, $features_array, 'property_feature' );
            }

            // additional details
            if( isset( $_POST['additional_features'] ) ) {
                $additional_features = $_POST['additional_features'];
                if( ! empty( $additional_features ) ) {
                    update_post_meta( $prop_id, 'additional_features', $additional_features );
                    update_post_meta( $prop_id, 'fave_additional_features_enable', 'enable' );
                }
            } else {
                update_post_meta( $prop_id, 'additional_features', '' );
            }

            //Floor Plans
            if( isset( $_POST['floorPlans_enable'] ) ) {
                $floorPlans_enable = $_POST['floorPlans_enable'];
                if( ! empty( $floorPlans_enable ) ) {
                    update_post_meta( $prop_id, 'fave_floor_plans_enable', $floorPlans_enable );
                }
            }

            if( isset( $_POST['floor_plans'] ) ) {
                $floor_plans_post = $_POST['floor_plans'];
                if( ! empty( $floor_plans_post ) ) {
                    update_post_meta( $prop_id, 'floor_plans', $floor_plans_post );
                }
            } else {
                update_post_meta( $prop_id, 'floor_plans', '');
            }

            //Multi-units / Sub-properties
            if( isset( $_POST['multiUnits'] ) ) {
                $multiUnits_enable = $_POST['multiUnits'];
                if( ! empty( $multiUnits_enable ) ) {
                    update_post_meta( $prop_id, 'fave_multiunit_plans_enable', $multiUnits_enable );
                }
            }

            if( isset( $_POST['fave_multi_units'] ) ) {
                $fave_multi_units = $_POST['fave_multi_units'];
                if( ! empty( $fave_multi_units ) ) {
                    update_post_meta( $prop_id, 'fave_multi_units', $fave_multi_units );
                }
            } else {
                update_post_meta( $prop_id, 'fave_multi_units', '');
            }

            // Make featured
            if( isset( $_POST['prop_featured'] ) ) {
                $featured = intval( $_POST['prop_featured'] );
                update_post_meta( $prop_id, 'fave_featured', $featured );
            }

            // fave_loggedintoview
            if( isset( $_POST['login-required'] ) ) {
                $featured = intval( $_POST['login-required'] );
                update_post_meta( $prop_id, 'fave_loggedintoview', $featured );
            }

            // Mortgage
            if( $submission_action == 'add_property' ) {
                update_post_meta( $prop_id, 'fave_mortgage_cal', 0 );
                
            }

            // Private Note
            if( isset( $_POST['private_note'] ) ) {
                $private_note = wp_kses_post( $_POST['private_note'] );
                update_post_meta( $prop_id, 'fave_private_note', $private_note );
            }

            // disclaimer 
            if( isset( $_POST['property_disclaimer'] ) ) {
                $property_disclaimer = wp_kses_post( $_POST['property_disclaimer'] );
                update_post_meta( $prop_id, 'fave_property_disclaimer', $property_disclaimer );
            }

            //Energy Class
            if(isset($_POST['energy_class'])) {
                $energy_class = sanitize_text_field($_POST['energy_class']);
                update_post_meta( $prop_id, 'fave_energy_class', $energy_class );
            }
            if(isset($_POST['energy_global_index'])) {
                $energy_global_index = sanitize_text_field($_POST['energy_global_index']);
                update_post_meta( $prop_id, 'fave_energy_global_index', $energy_global_index );
            }
            if(isset($_POST['renewable_energy_global_index'])) {
                $renewable_energy_global_index = sanitize_text_field($_POST['renewable_energy_global_index']);
                update_post_meta( $prop_id, 'fave_renewable_energy_global_index', $renewable_energy_global_index );
            }
            if(isset($_POST['energy_performance'])) {
                $energy_performance = sanitize_text_field($_POST['energy_performance']);
                update_post_meta( $prop_id, 'fave_energy_performance', $energy_performance );
            }
            if(isset($_POST['epc_current_rating'])) {
                $epc_current_rating = sanitize_text_field($_POST['epc_current_rating']);
                update_post_meta( $prop_id, 'fave_epc_current_rating', $epc_current_rating );
            }
            if(isset($_POST['epc_potential_rating'])) {
                $epc_potential_rating = sanitize_text_field($_POST['epc_potential_rating']);
                update_post_meta( $prop_id, 'fave_epc_potential_rating', $epc_potential_rating );
            }


            // Property Payment
            if( isset( $_POST['prop_payment'] ) ) {
                $prop_payment = sanitize_text_field( $_POST['prop_payment'] );
                update_post_meta( $prop_id, 'fave_payment_status', $prop_payment );
            }


            if( isset( $_POST['fave_agent_display_option'] ) ) {

                $prop_agent_display_option = sanitize_text_field( $_POST['fave_agent_display_option'] );

                if( $prop_agent_display_option == 'agent_info' ) {

                    $prop_agent = isset( $_POST['fave_agents'] ) ? $_POST['fave_agents'] : '';
 
                    if(is_array($prop_agent)) {
                        foreach ($prop_agent as $agent) {
                            add_post_meta($prop_id, 'fave_agents', intval($agent) );
                        }
                    }
                    update_post_meta( $prop_id, 'fave_agent_display_option', $prop_agent_display_option );

                    if (houzez_is_agency()) {
                        $user_agency_id = get_user_meta( $userID, 'fave_author_agency_id', true );
                        if( !empty($user_agency_id)) {
                            update_post_meta($prop_id, 'fave_property_agency', $user_agency_id);
                        }
                    }

                } elseif( $prop_agent_display_option == 'agency_info' ) {

                    $user_agency_ids = isset($_POST['fave_property_agency']) ? $_POST['fave_property_agency'] : '';

                    if (houzez_is_agency()) {
                        $user_agency_id = get_user_meta( $userID, 'fave_author_agency_id', true );
                        if( !empty($user_agency_id)) {
                            update_post_meta($prop_id, 'fave_property_agency', $user_agency_id);
                            update_post_meta($prop_id, 'fave_agent_display_option', $prop_agent_display_option);
                        } else {
                            update_post_meta( $prop_id, 'fave_agent_display_option', 'author_info' );
                        }

                    } else {

                        if(is_array($user_agency_ids)) {
                            foreach ($user_agency_ids as $agency) {
                                add_post_meta($prop_id, 'fave_property_agency', intval($agency) );
                            }
                        }
                        update_post_meta($prop_id, 'fave_agent_display_option', $prop_agent_display_option);
                    }
                    
                    
                } else {
                    update_post_meta( $prop_id, 'fave_agent_display_option', $prop_agent_display_option );
                }

            } else {

                if (houzez_is_agency()) {
                    $user_agency_id = get_user_meta( $userID, 'fave_author_agency_id', true );
                    if( !empty($user_agency_id) ) {
                        update_post_meta($prop_id, 'fave_agent_display_option', 'agency_info');
                        update_post_meta($prop_id, 'fave_property_agency', $user_agency_id);
                    } else {
                        update_post_meta( $prop_id, 'fave_agent_display_option', 'author_info' );
                    }

                } elseif(houzez_is_agent()){
                    $user_agent_id = get_user_meta( $userID, 'fave_author_agent_id', true );

                    if ( !empty( $user_agent_id ) ) {

                        update_post_meta($prop_id, 'fave_agent_display_option', 'agent_info');
                        update_post_meta($prop_id, 'fave_agents', $user_agent_id);

                    } else {
                        update_post_meta($prop_id, 'fave_agent_display_option', 'author_info');
                    }

                } else {
                    update_post_meta($prop_id, 'fave_agent_display_option', 'author_info');
                }
            }

            // Address
            if( isset( $_POST['property_map_address'] ) ) {
                update_post_meta( $prop_id, 'fave_property_map_address', sanitize_text_field( $_POST['property_map_address'] ) );
                update_post_meta( $prop_id, 'fave_property_address', sanitize_text_field( $_POST['property_map_address'] ) );
            }

            if( ( isset($_POST['lat']) && !empty($_POST['lat']) ) && (  isset($_POST['lng']) && !empty($_POST['lng'])  ) ) {
                $lat = sanitize_text_field( $_POST['lat'] );
                $lng = sanitize_text_field( $_POST['lng'] );
                $streetView = isset( $_POST['prop_google_street_view'] ) ? sanitize_text_field( $_POST['prop_google_street_view'] ) : '';
                $lat_lng = $lat.','.$lng;

                update_post_meta( $prop_id, 'houzez_geolocation_lat', $lat );
                update_post_meta( $prop_id, 'houzez_geolocation_long', $lng );
                update_post_meta( $prop_id, 'fave_property_location', $lat_lng );
                update_post_meta( $prop_id, 'fave_property_map', '1' );
                update_post_meta( $prop_id, 'fave_property_map_street_view', $streetView );

            }
            

            if( $submission_action == 'add_property' ) {
                do_action( 'houzez_after_property_submit', $prop_id );

                if( houzez_option('add_new_property') == 1 ) {
                    houzez_webhook_post( $_POST, 'houzez_add_new_property' );
                }

            } else if ( $submission_action == 'update_property' ) {
                do_action( 'houzez_after_property_update', $prop_id );

                if( houzez_option('add_new_property') == 1 ) {
                    houzez_webhook_post( $_POST, 'houzez_update_property' );
                }
            }

        return $prop_id;
        }
    }
}

if( !function_exists('houzez_update_property_from_draft') ) {
    function houzez_update_property_from_draft( $property_id ) {
        $listings_admin_approved = houzez_option('listings_admin_approved');

        if( $listings_admin_approved != 'yes' ) {
            $prop_status = 'publish';
        } else {
            $prop_status = 'pending';
        }

        $updated_property = array(
            'ID' => $property_id,
            'post_type' => 'property',
            'post_status' => $prop_status
        );
        $prop_id = wp_update_post( $updated_property );
    }
}

add_action('wp_ajax_houzez_relist_free', 'houzez_relist_free');
if( !function_exists('houzez_relist_free') ) {
    function houzez_relist_free() {
        $listings_admin_approved = houzez_option('listings_admin_approved');

        if( $listings_admin_approved != 'yes' ) {
            $prop_status = 'publish';
        } else {
            $prop_status = 'pending';
        }
        
        $propID = $_POST['propID'];
        $updated_property = array(
            'ID' => $propID,
            'post_type' => 'property',
            'post_status' => $prop_status,
            'post_date'     => current_time( 'mysql' ),
        );
        $post_id = wp_update_post( $updated_property );
    }
}

/*-----------------------------------------------------------------------------------*/
// validate Email
/*-----------------------------------------------------------------------------------*/
add_action('wp_ajax_save_as_draft', 'save_property_as_draft');
if( !function_exists('save_property_as_draft') ) {
    function save_property_as_draft() {
        global $current_user;

        wp_get_current_user();
        $userID = $current_user->ID;

        $new_property = array(
            'post_type' => 'property'
        );

        $submission_action = isset($_POST['update_property']) ? $_POST['update_property'] : '';

        // Title
        if( isset( $_POST['prop_title']) ) {
            $new_property['post_title'] = sanitize_text_field( $_POST['prop_title'] );
        }
        // Description
        if( isset( $_POST['description'] ) ) {
            $new_property['post_content'] = wp_kses_post( $_POST['description'] );
        }

        $new_property['post_author'] = $userID;

        $prop_id = 0;
        $new_property['post_status'] = 'draft';

        if( isset($_POST['draft_prop_id']) && !empty( $_POST['draft_prop_id'] ) ) {
            $new_property['ID'] = $_POST['draft_prop_id'];
            $prop_id = wp_update_post( $new_property );
        } else {
            $prop_id = wp_insert_post( $new_property );
        }


        if( $prop_id > 0 ) {
            
            //Custom Fields
            if(class_exists('Houzez_Fields_Builder')) {
                $fields_array = Houzez_Fields_Builder::get_form_fields();
                if(!empty($fields_array)):
                    foreach ( $fields_array as $value ):
                        $field_name = $value->field_id;

                        if( isset( $_POST[$field_name] ) ) {
                            update_post_meta( $prop_id, 'fave_'.$field_name, sanitize_text_field( $_POST[$field_name] ) );
                        }

                    endforeach; endif;
            }
            
            // Add price post meta
            if( isset( $_POST['prop_price'] ) ) {
                update_post_meta( $prop_id, 'fave_property_price', sanitize_text_field( $_POST['prop_price'] ) );

                if( isset( $_POST['prop_label'] ) ) {
                    update_post_meta( $prop_id, 'fave_property_price_postfix', sanitize_text_field( $_POST['prop_label']) );
                }
            }

            // currency
            if( isset( $_POST['currency'] ) ) {
                update_post_meta( $prop_id, 'fave_currency', sanitize_text_field( $_POST['currency'] ) );
                if(class_exists('Houzez_Currencies')) {
                    $currencies = Houzez_Currencies::get_property_currency_2($prop_id, $_POST['currency']);

                    update_post_meta( $prop_id, 'fave_currency_info', $currencies );
                }
            }

            //price prefix
            if( isset( $_POST['prop_price_prefix'] ) ) {
                update_post_meta( $prop_id, 'fave_property_price_prefix', sanitize_text_field( $_POST['prop_price_prefix']) );
            }

            // Second Price
            if( isset( $_POST['prop_sec_price'] ) ) {
                update_post_meta( $prop_id, 'fave_property_sec_price', sanitize_text_field( $_POST['prop_sec_price'] ) );
            }

            // Area Size
            if( isset( $_POST['prop_size'] ) ) {
                update_post_meta( $prop_id, 'fave_property_size', sanitize_text_field( $_POST['prop_size'] ) );
            }

            // Area Size Prefix
            if( isset( $_POST['prop_size_prefix'] ) ) {
                update_post_meta( $prop_id, 'fave_property_size_prefix', sanitize_text_field( $_POST['prop_size_prefix'] ) );
            }
            // Land Area Size
            if( isset( $_POST['prop_land_area'] ) ) {
                update_post_meta( $prop_id, 'fave_property_land', sanitize_text_field( $_POST['prop_land_area'] ) );
            }

            // Land Area Size Prefix
            if( isset( $_POST['prop_land_area_prefix'] ) ) {
                update_post_meta( $prop_id, 'fave_property_land_postfix', sanitize_text_field( $_POST['prop_land_area_prefix'] ) );
            }

            // Bedrooms
            if( isset( $_POST['prop_beds'] ) ) {
                update_post_meta( $prop_id, 'fave_property_bedrooms', sanitize_text_field( $_POST['prop_beds'] ) );
            }

            // Bathrooms
            if( isset( $_POST['prop_baths'] ) ) {
                update_post_meta( $prop_id, 'fave_property_bathrooms', sanitize_text_field( $_POST['prop_baths'] ) );
            }

            // Rooms
            if( isset( $_POST['prop_rooms'] ) ) {
                update_post_meta( $prop_id, 'fave_property_rooms', sanitize_text_field( $_POST['prop_rooms'] ) );
            }

            // Garages
            if( isset( $_POST['prop_garage'] ) ) {
                update_post_meta( $prop_id, 'fave_property_garage', sanitize_text_field( $_POST['prop_garage'] ) );
            }

            // Garages Size
            if( isset( $_POST['prop_garage_size'] ) ) {
                update_post_meta( $prop_id, 'fave_property_garage_size', sanitize_text_field( $_POST['prop_garage_size'] ) );
            }

            // Virtual Tour
            if( isset( $_POST['virtual_tour'] ) ) {
                update_post_meta( $prop_id, 'fave_virtual_tour', $_POST['virtual_tour'] );
            }

            // Year Built
            if( isset( $_POST['prop_year_built'] ) ) {
                update_post_meta( $prop_id, 'fave_property_year', sanitize_text_field( $_POST['prop_year_built'] ) );
            }

            // Property ID
            $auto_property_id = houzez_option('auto_property_id');
            if( $auto_property_id != 1 ) {
                if (isset($_POST['property_id'])) {
                    update_post_meta($prop_id, 'fave_property_id', sanitize_text_field($_POST['property_id']));
                }
            } else {
                update_post_meta($prop_id, 'fave_property_id', $prop_id );
            }

            // Property Video Url
            if( isset( $_POST['prop_video_url'] ) ) {
                update_post_meta( $prop_id, 'fave_video_url', sanitize_text_field( $_POST['prop_video_url'] ) );
            }

            // property video image - in case of update
            $property_video_image = "";
            $property_video_image_id = 0;
            if( $submission_action == "update_property" ) {
                $property_video_image_id = get_post_meta( $prop_id, 'fave_video_image', true );
                if ( ! empty ( $property_video_image_id ) ) {
                    $property_video_image_src = wp_get_attachment_image_src( $property_video_image_id, 'houzez-property-detail-gallery' );
                    $property_video_image = $property_video_image_src[0];
                }
            }

            // clean up the old meta information related to images when property update
            if( $submission_action == "update_property" ){
                delete_post_meta( $prop_id, 'fave_property_images' );
                delete_post_meta( $prop_id, 'fave_attachments' );
                delete_post_meta( $prop_id, 'fave_agents' );
                delete_post_meta( $prop_id, 'fave_property_agency' );
                delete_post_meta( $prop_id, '_thumbnail_id' );
            }

            // Property Images
            if( isset( $_POST['propperty_image_ids'] ) ) {
                if (!empty($_POST['propperty_image_ids']) && is_array($_POST['propperty_image_ids'])) {
                    $property_image_ids = array();
                    foreach ($_POST['propperty_image_ids'] as $prop_img_id ) {
                        $property_image_ids[] = intval( $prop_img_id );
                        add_post_meta($prop_id, 'fave_property_images', $prop_img_id);
                    }

                    // featured image
                    if( isset( $_POST['featured_image_id'] ) ) {
                        $featured_image_id = intval( $_POST['featured_image_id'] );
                        if( in_array( $featured_image_id, $property_image_ids ) ) {
                            update_post_meta( $prop_id, '_thumbnail_id', $featured_image_id );

                            /* if video url is provided but there is no video image then use featured image as video image */
                            if ( empty( $property_video_image ) && !empty( $_POST['prop_video_url'] ) ) {
                                update_post_meta( $prop_id, 'fave_video_image', $featured_image_id );
                            }
                        }
                    } elseif ( ! empty ( $property_image_ids ) ) {
                        update_post_meta( $prop_id, '_thumbnail_id', $property_image_ids[0] );

                        /* if video url is provided but there is no video image then use featured image as video image */
                        if ( empty( $property_video_image ) && !empty( $_POST['prop_video_url'] ) ) {
                            update_post_meta( $prop_id, 'fave_video_image', $property_image_ids[0] );
                        }
                    }
                }
            }

            if( isset( $_POST['propperty_attachment_ids'] ) ) {
                    $property_attach_ids = array();
                    foreach ($_POST['propperty_attachment_ids'] as $prop_atch_id ) {
                        $property_attach_ids[] = intval( $prop_atch_id );
                        add_post_meta($prop_id, 'fave_attachments', $prop_atch_id);
                    }
            }


            // Add property type
            if( isset( $_POST['prop_type'] ) && ( $_POST['prop_type'] != '-1' ) ) {
                $type = array_map( 'intval', $_POST['prop_type'] );
                wp_set_object_terms( $prop_id, $type, 'property_type' );
            }

            // Add property status
            if( isset( $_POST['prop_status'] ) && ( $_POST['prop_status'] != '-1' ) ) {
                $prop_status = array_map( 'intval', $_POST['prop_status'] );
                wp_set_object_terms( $prop_id, $prop_status, 'property_status' );
            }

            // Add property label
            if( isset( $_POST['prop_labels'] ) ) {
                $prop_labels = array_map( 'intval', $_POST['prop_labels'] );
                wp_set_object_terms( $prop_id, $prop_labels, 'property_label' );
            }


            // Country
            if( isset( $_POST['country'] ) ) {
                $property_country = sanitize_text_field( $_POST['country'] );
                $country_id = wp_set_object_terms( $prop_id, $property_country, 'property_country' );
            } else {
                $default_country = houzez_option('default_country');
                $country_id = wp_set_object_terms( $prop_id, $default_country, 'property_country' );
            }

            
            // Postal Code
            if( isset( $_POST['postal_code'] ) ) {
                update_post_meta( $prop_id, 'fave_property_zip', sanitize_text_field( $_POST['postal_code'] ) );
            }

            
            if( isset( $_POST['locality'] ) ) {
                $property_city = sanitize_text_field( $_POST['locality'] );
                $city_id = wp_set_object_terms( $prop_id, $property_city, 'property_city' );

                $houzez_meta = array();
                $houzez_meta['parent_state'] = isset( $_POST['administrative_area_level_1'] ) ? $_POST['administrative_area_level_1'] : '';
                if( !empty( $city_id) ) {
                    update_option('_houzez_property_city_' . $city_id[0], $houzez_meta);
                }
            }

            if( isset( $_POST['neighborhood'] ) ) {
                $property_area = sanitize_text_field( $_POST['neighborhood'] );
                $area_id = wp_set_object_terms( $prop_id, $property_area, 'property_area' );

                $houzez_meta = array();
                $houzez_meta['parent_city'] = isset( $_POST['locality'] ) ? $_POST['locality'] : '';
                if( !empty( $area_id) ) {
                    update_option('_houzez_property_area_' . $area_id[0], $houzez_meta);
                }
            }


            // Add property state
            if( isset( $_POST['administrative_area_level_1'] ) ) {
                $property_state = sanitize_text_field( $_POST['administrative_area_level_1'] );
                $state_id = wp_set_object_terms( $prop_id, $property_state, 'property_state' );

                $houzez_meta = array();
                $country_short = isset( $_POST['country'] ) ? $_POST['country'] : '';
                if(!empty($country_short)) {
                   $country_short = strtoupper($country_short); 
                } else {
                    $country_short = '';
                }
                
                $houzez_meta['parent_country'] = $country_short;
                if( !empty( $state_id) ) {
                    update_option('_houzez_property_state_' . $state_id[0], $houzez_meta);
                }
            }

            // Add property features
            if( isset( $_POST['prop_features'] ) ) {
                $features_array = array();
                foreach( $_POST['prop_features'] as $feature_id ) {
                    $features_array[] = intval( $feature_id );
                }
                wp_set_object_terms( $prop_id, $features_array, 'property_feature' );
            }

            // additional details
            if( isset( $_POST['additional_features'] ) ) {
                $additional_features = $_POST['additional_features'];
                if( ! empty( $additional_features ) ) {
                    update_post_meta( $prop_id, 'additional_features', $additional_features );
                    update_post_meta( $prop_id, 'fave_additional_features_enable', 'enable' );
                }
            }

            //Floor Plans
            if( isset( $_POST['floorPlans_enable'] ) ) {
                $floorPlans_enable = $_POST['floorPlans_enable'];
                if( ! empty( $floorPlans_enable ) ) {
                    update_post_meta( $prop_id, 'fave_floor_plans_enable', $floorPlans_enable );
                }
            }

            if( isset( $_POST['floor_plans'] ) ) {
                $floor_plans_post = $_POST['floor_plans'];
                if( ! empty( $floor_plans_post ) ) {
                    update_post_meta( $prop_id, 'floor_plans', $floor_plans_post );
                }
            }

            //Multi-units / Sub-properties
            if( isset( $_POST['multiUnits'] ) ) {
                $multiUnits_enable = $_POST['multiUnits'];
                if( ! empty( $multiUnits_enable ) ) {
                    update_post_meta( $prop_id, 'fave_multiunit_plans_enable', $multiUnits_enable );
                }
            }

            if( isset( $_POST['fave_multi_units'] ) ) {
                $fave_multi_units = $_POST['fave_multi_units'];
                if( ! empty( $fave_multi_units ) ) {
                    update_post_meta( $prop_id, 'fave_multi_units', $fave_multi_units );
                }
            }

            // Make featured
            if( isset( $_POST['prop_featured'] ) ) {
                $featured = intval( $_POST['prop_featured'] );
                update_post_meta( $prop_id, 'fave_featured', $featured );
            }

            // Private Note
            if( isset( $_POST['private_note'] ) ) {
                $private_note = wp_kses_post( $_POST['private_note'] );
                update_post_meta( $prop_id, 'fave_private_note', $private_note );
            }

            // disclaimer 
            if( isset( $_POST['property_disclaimer'] ) ) {
                $property_disclaimer = wp_kses_post( $_POST['property_disclaimer'] );
                update_post_meta( $prop_id, 'fave_property_disclaimer', $property_disclaimer );
            }

            // Property Payment
            if( isset( $_POST['prop_payment'] ) ) {
                $prop_payment = sanitize_text_field( $_POST['prop_payment'] );
                update_post_meta( $prop_id, 'fave_payment_status', $prop_payment );
            }

             //Energy Class
            if(isset($_POST['energy_class'])) {
                $energy_class = sanitize_text_field($_POST['energy_class']);
                update_post_meta( $prop_id, 'fave_energy_class', $energy_class );
            }
            if(isset($_POST['energy_global_index'])) {
                $energy_global_index = sanitize_text_field($_POST['energy_global_index']);
                update_post_meta( $prop_id, 'fave_energy_global_index', $energy_global_index );
            }
            if(isset($_POST['renewable_energy_global_index'])) {
                $renewable_energy_global_index = sanitize_text_field($_POST['renewable_energy_global_index']);
                update_post_meta( $prop_id, 'fave_renewable_energy_global_index', $renewable_energy_global_index );
            }
            if(isset($_POST['energy_performance'])) {
                $energy_performance = sanitize_text_field($_POST['energy_performance']);
                update_post_meta( $prop_id, 'fave_energy_performance', $energy_performance );
            }

            // Property Agent
            if( isset( $_POST['fave_agent_display_option'] ) ) {

                $prop_agent_display_option = sanitize_text_field( $_POST['fave_agent_display_option'] );
                if( $prop_agent_display_option == 'agent_info' ) {

                    $prop_agent = sanitize_text_field( $_POST['fave_agents'] );
                    update_post_meta( $prop_id, 'fave_agent_display_option', $prop_agent_display_option );
                    update_post_meta( $prop_id, 'fave_agents', $prop_agent );

                } else {
                    update_post_meta( $prop_id, 'fave_agent_display_option', $prop_agent_display_option );
                }

            } else {
                update_post_meta( $prop_id, 'fave_agent_display_option', 'author_info' );
            }

            // Address
            if( isset( $_POST['property_map_address'] ) ) {
                update_post_meta( $prop_id, 'fave_property_map_address', sanitize_text_field( $_POST['property_map_address'] ) );
                update_post_meta( $prop_id, 'fave_property_address', sanitize_text_field( $_POST['property_map_address'] ) );
            }

            if( ( isset($_POST['lat']) && !empty($_POST['lat']) ) && (  isset($_POST['lng']) && !empty($_POST['lng'])  ) ) {
                $lat = sanitize_text_field( $_POST['lat'] );
                $lng = sanitize_text_field( $_POST['lng'] );
                $streetView = sanitize_text_field( $_POST['prop_google_street_view'] );
                $lat_lng = $lat.','.$lng;

                update_post_meta( $prop_id, 'houzez_geolocation_lat', $lat );
                update_post_meta( $prop_id, 'houzez_geolocation_long', $lng );
                update_post_meta( $prop_id, 'fave_property_location', $lat_lng );
                update_post_meta( $prop_id, 'fave_property_map', '1' );
                update_post_meta( $prop_id, 'fave_property_map_street_view', $streetView );

            }
            
        }
        echo json_encode( array( 'success' => true, 'property_id' => $prop_id, 'msg' => esc_html__('Successfull', 'houzez') ) );
        wp_die();
    }
}

/*-----------------------------------------------------------------------------------*/
// validate Email
/*-----------------------------------------------------------------------------------*/
add_action('wp_ajax_houzez_check_email', 'houzez_check_email');
add_action('wp_ajax_nopriv_houzez_check_email', 'houzez_check_email');

if( !function_exists('houzez_check_email') ) {
    function houzez_check_email() {
        $allowed_html = array();
        $email = wp_kses( $_POST['useremail'], $allowed_html );

        if( email_exists( $email ) ) {
            echo json_encode( array( 'success' => false, 'msg' => esc_html__('This email address is already registered.', 'houzez') ) );
            wp_die();
        
        } elseif( !is_email( $email ) ) {
            echo json_encode( array( 'success' => false, 'msg' => esc_html__('Invalid email address.', 'houzez') ) );
            wp_die();
        } else {
            echo json_encode( array( 'success' => true, 'msg' => esc_html__('Successfull', 'houzez') ) );
            wp_die();
        }

        wp_die();
    }
}

/*-----------------------------------------------------------------------------------*/
// Add custom post status Expired
/*-----------------------------------------------------------------------------------*/
if ( ! function_exists('houzez_custom_post_status') ) {
    function houzez_custom_post_status() {

        $args = array(
            'label'                     => _x( 'Expired', 'Status General Name', 'houzez' ),
            'label_count'               => _n_noop( 'Expired (%s)',  'Expired (%s)', 'houzez' ),
            'public'                    => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'exclude_from_search'       => false,
        );
        register_post_status( 'expired', $args );

    }
    add_action( 'init', 'houzez_custom_post_status', 1 );
}

/*-----------------------------------------------------------------------------------*/
// Add custom post status DisApproved
/*-----------------------------------------------------------------------------------*/
if ( ! function_exists('houzez_custom_post_disapproved') ) {
    function houzez_custom_post_disapproved() {

        $args = array(
            'label'                     => _x( 'Disapproved', 'Status General Name', 'houzez' ),
            'label_count'               => _n_noop( 'Disapproved (%s)',  'Disapproved (%s)', 'houzez' ),
            'public'                    => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'exclude_from_search'       => false,
        );
        register_post_status( 'disapproved', $args );

    }
    add_action( 'init', 'houzez_custom_post_disapproved', 1 );
}

/*-----------------------------------------------------------------------------------*/
// Add custom post status Hold
/*-----------------------------------------------------------------------------------*/
if ( ! function_exists('houzez_custom_post_status_on_hold') ) {
    function houzez_custom_post_status_on_hold() {

        $args = array(
            'label'                     => _x( 'On Hold', 'Status General Name', 'houzez' ),
            'label_count'               => _n_noop( 'On Hold (%s)',  'On Hold (%s)', 'houzez' ),
            'public'                    => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'exclude_from_search'       => false,
        );
        register_post_status( 'on_hold', $args );

    }
    add_action( 'init', 'houzez_custom_post_status_on_hold', 1 );
}

/*-----------------------------------------------------------------------------------*/
// Add custom post status Sold
/*-----------------------------------------------------------------------------------*/
if ( ! function_exists('houzez_custom_post_status_sold') ) {
    function houzez_custom_post_status_sold() {

        if( ! fave_option('enable_mark_as_sold', 0) ) {
            return;
        }
        $args = array(
            'label'                     => _x( 'Sold', 'Status General Name', 'houzez' ),
            'label_count'               => _n_noop( 'Sold (%s)',  'Sold (%s)', 'houzez' ),
            'public'                    => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'exclude_from_search'       => false,
        );
        register_post_status( 'houzez_sold', $args );

    }

    add_action( 'init', 'houzez_custom_post_status_sold', 1 );
    add_action( 'admin_init', 'houzez_custom_post_status_sold', 1 );
}

add_action( 'wp_ajax_houzez_save_search', 'houzez_save_search' );
if( !function_exists('houzez_save_search') ) {
    function houzez_save_search() {

        $nonce = $_REQUEST['houzez_save_search_ajax'];
        if( !wp_verify_nonce( $nonce, 'houzez-save-search-nounce' ) ) {
            echo json_encode(array(
                'success' => false,
                'msg' => esc_html__( 'Unverified Nonce!', 'houzez')
            ));
            wp_die();
        }

        global $wpdb, $current_user;

        wp_get_current_user();
        $userID       =  $current_user->ID;
        $userEmail    =  $current_user->user_email;
        $search_args  =  $_REQUEST['search_args'];
        $table_name   = $wpdb->prefix . 'houzez_search';
        $request_url  = $_REQUEST['search_URI'];

        $wpdb->insert(
            $table_name,
            array(
                'auther_id' => $userID,
                'query'     => $search_args,
                'email'     => $userEmail,
                'url'       => $request_url,
                'time'      => current_time( 'mysql' )
            ),
            array(
                '%d',
                '%s',
                '%s',
                '%s',
                '%s'
            )
        );

        echo json_encode( array( 'success' => true, 'msg' => esc_html__('Search is saved. You will receive an email notification when new properties matching your search will be published', 'houzez') ) );
        wp_die();
    }
}

/*-----------------------------------------------------------------------------------*/
/*     Remove Search
/*-----------------------------------------------------------------------------------*/

add_action('wp_ajax_houzez_delete_search', 'houzez_delete_search');
if(!function_exists('houzez_delete_search') ) {
    function houzez_delete_search () {
        global $current_user;
        wp_get_current_user();
        $userID = $current_user->ID;

        $property_id = intval( $_POST['property_id']);

        if( !is_numeric( $property_id ) ){
            echo json_encode( array(
                'success' => false,
                'msg' => esc_html__('you don\'t have the right to delete this', 'houzez')
            ));
            wp_die();
        }else{

            global $wpdb;

            $table_name     = $wpdb->prefix . 'houzez_search';
            $results        = $wpdb->get_row( 'SELECT * FROM ' . $table_name . ' WHERE id = ' . $property_id );
            if ( $userID != $results->auther_id ) :

                echo json_encode( array(
                    'success' => false,
                    'msg' => esc_html__('you don\'t have the right to delete this', 'houzez')
                ));

                wp_die();

            else :

                $wpdb->delete( $table_name, array( 'id' => $property_id ), array( '%d' ) );

                echo json_encode( array(
                    'success' => true,
                    'msg' => esc_html__('Deleted Successfully', 'houzez')
                ));

                wp_die();

            endif;
        }
    }
}


/* -----------------------------------------------------------------------------------------------------------
 *  Resend Property for Approval per listing
 -------------------------------------------------------------------------------------------------------------*/
add_action( 'wp_ajax_nopriv_houzez_resend_for_approval_perlisting', 'houzez_resend_for_approval_perlisting' );
add_action( 'wp_ajax_houzez_resend_for_approval_perlisting', 'houzez_resend_for_approval_perlisting' );

if( !function_exists('houzez_resend_for_approval_perlisting') ):

    function houzez_resend_for_approval_perlisting() {

        global $current_user;
        $prop_id = intval($_POST['propid']);

        wp_get_current_user();
        $userID = $current_user->ID;
        $post   = get_post($prop_id);

        if( $post->post_author != $userID){
            wp_die('get out of my cloud');
        }

        $time = current_time('mysql');
        $prop = array(
            'ID'            => $prop_id,
            'post_type'     => 'property',
            'post_status'   => 'pending',
            'post_date'     => current_time( 'mysql' ),
            'post_date_gmt' => get_gmt_from_date( $time )
        );
        wp_update_post( $prop );
        update_post_meta( $prop_id, 'fave_featured', 0 );
        update_post_meta( $prop_id, 'houzez_featured_listing_date', '' );
        update_post_meta( $prop_id, 'fave_payment_status', 'not_paid' );

        echo json_encode( array( 'success' => true, 'msg' => esc_html__('Sent for approval','houzez') ) );

        $submit_title =   get_the_title( $prop_id) ;

        $args = array(
            'submission_title' =>  $submit_title,
            'submission_url'   =>  get_permalink( $prop_id )
        );
        houzez_email_type( get_option('admin_email'), 'admin_expired_listings', $args );

        wp_die();



    }

endif; // end

/*-----------------------------------------------------------------------------------*/
// Houzez sold status 
/*-----------------------------------------------------------------------------------*/

add_filter('houzez_sold_status_filter', 'houzez_sold_status_filter_callback');
if( !function_exists('houzez_sold_status_filter_callback') ) {
    function houzez_sold_status_filter_callback( $query_args ) {
        if( houzez_option('show_sold_listings', 1) ) {
            $query_args['post_status'] = array('publish', 'houzez_sold');
        }

        return $query_args;
    }
}

/*-----------------------------------------------------------------------------------*/
// Houzez listings template filter for version 2.0 and above
/*-----------------------------------------------------------------------------------*/
add_filter('houzez20_property_filter', 'houzez20_property_filter_callback');
if( !function_exists('houzez20_property_filter_callback') ) {
    function houzez20_property_filter_callback( $property_query_args ) {
        global $paged;

        $page_id = get_the_ID();
        $tax_query = array();
        $meta_query = array();

        $paged = 1;
        if ( get_query_var( 'paged' ) ) {
            $paged = get_query_var( 'paged' );
        } elseif ( get_query_var( 'page' ) ) { // if is static front page
            $paged = get_query_var( 'page' );
        }

        $property_query_args['paged'] = $paged;

        $property_query_args = apply_filters('houzez_sold_status_filter', $property_query_args);

        $fave_prop_no = get_post_meta( $page_id, 'fave_prop_no', true );
        $fave_listings_tabs = get_post_meta( $page_id, 'fave_listings_tabs', true );


        if(!$fave_prop_no){
            $property_query_args[ 'posts_per_page' ]  = 9;
        } else {
            $property_query_args[ 'posts_per_page' ] = $fave_prop_no;
        }


        if ( isset( $_GET['tab'] ) ) {
            $tax_query[] = array(
                'taxonomy' => 'property_status',
                'field' => 'slug',
                'terms' => esc_attr($_GET['tab'])
            );
        }

        $countries = get_post_meta( $page_id, 'fave_countries', false );
        if ( ! empty( $countries ) && is_array( $countries ) ) {
            $tax_query[] = array(
                'taxonomy' => 'property_country',
                'field' => 'slug',
                'terms' => $countries
            );
        }

        $states = get_post_meta( $page_id, 'fave_states', false );
        if ( ! empty( $states ) && is_array( $states ) ) {
            $tax_query[] = array(
                'taxonomy' => 'property_state',
                'field' => 'slug',
                'terms' => $states
            );
        }

        $locations = get_post_meta( $page_id, 'fave_locations', false );
        if ( ! empty( $locations ) && is_array( $locations ) ) {
            $tax_query[] = array(
                'taxonomy' => 'property_city',
                'field' => 'slug',
                'terms' => $locations
            );
        }

        $types = get_post_meta( $page_id, 'fave_types', false );
        if ( ! empty( $types ) && is_array( $types ) ) {
            $tax_query[] = array(
                'taxonomy' => 'property_type',
                'field' => 'slug',
                'terms' => $types
            );
        }

        $labels = get_post_meta( $page_id, 'fave_labels', false );
        if ( ! empty( $labels ) && is_array( $labels ) ) {
            $tax_query[] = array(
                'taxonomy' => 'property_label',
                'field' => 'slug',
                'terms' => $labels
            );
        }

        $fave_areas = get_post_meta( $page_id, 'fave_area', false );
        if ( ! empty( $fave_areas ) && is_array( $fave_areas ) ) {
            $tax_query[] = array(
                'taxonomy' => 'property_area',
                'field' => 'slug',
                'terms' => $fave_areas
            );
        }

        $features = get_post_meta( $page_id, 'fave_features', false );
        if ( ! empty( $features ) && is_array( $features ) ) {
            $tax_query[] = array(
                'taxonomy' => 'property_feature',
                'field' => 'slug',
                'terms' => $features
            );
        }

        if( !isset( $_GET['tab'] ) ) {
            $status = get_post_meta($page_id, 'fave_status', false);
            if (!empty($status) && is_array($status)) {
                $tax_query[] = array(
                    'taxonomy' => 'property_status',
                    'field' => 'slug',
                    'terms' => $status
                );
            }
        }

        $min_price = get_post_meta( $page_id, 'fave_min_price', true );
        $max_price = get_post_meta( $page_id, 'fave_max_price', true );

        // min and max price logic
        if (!empty($min_price) && !empty($max_price)) {
            $min_price = doubleval(houzez_clean($min_price));
            $max_price = doubleval(houzez_clean($max_price));

            if ($min_price >= 0 && $max_price > $min_price) {
                $meta_query[] = array(
                    'key' => 'fave_property_price',
                    'value' => array($min_price, $max_price),
                    'type' => 'NUMERIC',
                    'compare' => 'BETWEEN',
                );
            }
        } else if (!empty($min_price)) {
            $min_price = doubleval(houzez_clean($min_price));
            if ($min_price >= 0) {
                $meta_query[] = array(
                    'key' => 'fave_property_price',
                    'value' => $min_price,
                    'type' => 'NUMERIC',
                    'compare' => '>=',
                );
            }
        } else if (!empty($max_price)) {
            $max_price = doubleval(houzez_clean($max_price));
            if ($max_price >= 0) {
                $meta_query[] = array(
                    'key' => 'fave_property_price',
                    'value' => $max_price,
                    'type' => 'NUMERIC',
                    'compare' => '<=',
                );
            }
        }


        $min_beds = get_post_meta( $page_id, 'fave_properties_min_beds', true );
        $max_beds = get_post_meta( $page_id, 'fave_properties_max_beds', true );
        if ( !empty($min_beds) && !empty($max_beds) ) {
            $min_beds = intval($min_beds);
            $max_beds = intval($max_beds);

            if ($min_beds > 0 && $max_beds >= $min_beds) {
                $meta_query[] = array(
                    'key' => 'fave_property_bedrooms',
                    'value' => array($min_beds, $max_beds),
                    'type' => 'NUMERIC',
                    'compare' => 'BETWEEN',
                );
            }
        } else if ( !empty($min_beds) ) {
            $min_beds = intval($min_beds);
            if ($min_beds > 0) {
                $meta_query[] = array(
                    'key' => 'fave_property_bedrooms',
                    'value' => $min_beds,
                    'type' => 'NUMERIC',
                    'compare' => '>=',
                );
            }
        } else if ( ! empty($max_beds) ) {
            $max_beds = intval($max_beds);
            if ($max_beds > 0) {
                $meta_query[] = array(
                    'key' => 'fave_property_bedrooms',
                    'value' => $max_beds,
                    'type' => 'NUMERIC',
                    'compare' => '<=',
                );
            }
        }

        $min_baths = get_post_meta( $page_id, 'fave_properties_min_baths', true );
        $max_baths = get_post_meta( $page_id, 'fave_properties_max_baths', true );

        if ( !empty($min_baths) && !empty($max_baths) ) {
            $min_baths = intval($min_baths);
            $max_baths = intval($max_baths);

            if ($min_baths > 0 && $max_baths >= $min_baths) {
                $meta_query[] = array(
                    'key' => 'fave_property_bathrooms',
                    'value' => array($min_baths, $max_baths),
                    'type' => 'NUMERIC',
                    'compare' => 'BETWEEN',
                );
            }
        } else if ( !empty($min_baths) ) {
            $min_baths = intval($min_baths);
            if ($min_baths > 0) {
                $meta_query[] = array(
                    'key' => 'fave_property_bathrooms',
                    'value' => $min_baths,
                    'type' => 'NUMERIC',
                    'compare' => '>=',
                );
            }
        } else if ( !empty($max_baths) ) {
            $max_baths = intval($max_baths);
            if ($max_baths > 0) {
                $meta_query[] = array(
                    'key' => 'fave_property_bathrooms',
                    'value' => $max_baths,
                    'type' => 'NUMERIC',
                    'compare' => '<=',
                );
            }
        }


        $agents = array_filter( get_post_meta( $page_id, 'fave_properties_by_agents', false ) );
        if ( count( $agents ) >= 1 ) {
            $meta_query[] = array(
                'key'     => 'fave_agents',
                'value'   => $agents,
                'compare' => 'IN',
            );
            $meta_query[] = array(
                'key'     => 'fave_agent_display_option',
                'value'   => 'agent_info',
                'compare' => '=',
            );
        }

        $agencies = array_filter( get_post_meta( $page_id, 'fave_properties_by_agency', false ) );
        if ( count( $agencies ) >= 1 ) {
            $meta_query[] = array(
                'key'     => 'fave_property_agency',
                'value'   => $agencies,
                'compare' => 'IN',
            );
            $meta_query[] = array(
                'key'     => 'fave_agent_display_option',
                'value'   => 'agency_info',
                'compare' => '=',
            );
        }

        $meta_count = count($meta_query);
        if( $meta_count > 1 ) {
            $meta_query['relation'] = 'AND';
        }
        if ($meta_count > 0) {
            $property_query_args['meta_query'] = $meta_query;
        }


        $tax_count = count( $tax_query );
        if( $tax_count > 1 ) {
            $tax_query['relation'] = 'AND';
        }
        if( $tax_count > 0 ) {
            $property_query_args['tax_query'] = $tax_query;
        }
        return $property_query_args;
    }
}


/*-----------------------------------------------------------------------------------*/
// Simple property filter - deprecated
/*-----------------------------------------------------------------------------------*/
if( !function_exists('houzez_property_filter') ) {
    function houzez_property_filter( $property_query_args ) {
        return $property_query_args;   
    }
}
add_filter('houzez_property_filter', 'houzez_property_filter');

/*-----------------------------------------------------------------------------------*/
// Featured property filter - deprecated
/*-----------------------------------------------------------------------------------*/
if( !function_exists('houzez_featured_property_filter') ) {
    function houzez_featured_property_filter( $property_query_args ) {
        

        return $property_query_args;
    }
}
add_filter('houzez_featured_property_filter', 'houzez_featured_property_filter');


/*-----------------------------------------------------------------------------------*/
// Properties search 2 - deprecated since v2.0
/*-----------------------------------------------------------------------------------*/
if( !function_exists('houzez_property_search_2') ) {
    function houzez_property_search_2($search_query)
    {

        
        return $search_query;
    }
}
add_filter('houzez_search_parameters_2', 'houzez_property_search_2');


/*-----------------------------------------------------------------------------------*/
/*  Get Properties for Header Map
/*-----------------------------------------------------------------------------------*/
add_action( 'wp_ajax_nopriv_houzez_header_map_listings', 'houzez_header_map_listings' );
add_action( 'wp_ajax_houzez_header_map_listings', 'houzez_header_map_listings' );
if( !function_exists('houzez_header_map_listings') ) {
    function houzez_header_map_listings() {
       
        $meta_query = array();
        $tax_query = array();
        $date_query = array();
        $allowed_html = array();
        $keyword_array =  '';

        
        $dummy_array = array();

        $custom_fields_values = isset($_POST['custom_fields_values']) ? $_POST['custom_fields_values'] : '';
        
        if(!empty($custom_fields_values)) {
            foreach ($custom_fields_values as $value) {
               $dummy_array[] = $value;
            }
        }


        $initial_city = isset($_POST['initial_city']) ? $_POST['initial_city'] : '';
        $features = isset($_POST['features']) ? $_POST['features'] : '';
        $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : '';
        $country = isset($_POST['country']) ? ($_POST['country']) : '';
        $state = isset($_POST['state']) ? ($_POST['state']) : '';
        $location = isset($_POST['location']) ? ($_POST['location']) : '';
        $area = isset($_POST['area']) ? ($_POST['area']) : '';
        $status = isset($_POST['status']) ? ($_POST['status']) : '';
        $type = isset($_POST['type']) ? ($_POST['type']) : '';
        $label = isset($_POST['label']) ? ($_POST['label']) : '';
        $property_id = isset($_POST['property_id']) ? ($_POST['property_id']) : '';
        $bedrooms = isset($_POST['bedrooms']) ? ($_POST['bedrooms']) : '';
        $bathrooms = isset($_POST['bathrooms']) ? ($_POST['bathrooms']) : '';
        $min_price = isset($_POST['min_price']) ? ($_POST['min_price']) : '';
        $max_price = isset($_POST['max_price']) ? ($_POST['max_price']) : '';
        $currency = isset($_POST['currency']) ? ($_POST['currency']) : '';
        $min_area = isset($_POST['min_area']) ? ($_POST['min_area']) : '';
        $max_area = isset($_POST['max_area']) ? ($_POST['max_area']) : '';
        $publish_date = isset($_POST['publish_date']) ? ($_POST['publish_date']) : '';

        $search_location = isset( $_POST[ 'search_location' ] ) ? esc_attr( $_POST[ 'search_location' ] ) : false;
        $use_radius = isset( $_POST[ 'use_radius' ] ) && 'on' == $_POST[ 'use_radius' ];
        $search_lat = isset($_POST['search_lat']) ? (float) $_POST['search_lat'] : false;
        $search_long = isset($_POST['search_long']) ? (float) $_POST['search_long'] : false;
        $search_radius = isset($_POST['search_radius']) ? (int) $_POST['search_radius'] : false;


        $prop_locations = array();
        houzez_get_terms_array( 'property_city', $prop_locations );

        $keyword_field = houzez_option('keyword_field');
        $beds_baths_search = houzez_option('beds_baths_search');
        $property_id_prefix = houzez_option('property_id_prefix');

        $property_id = str_replace($property_id_prefix, "", $property_id);

        $search_criteria = '=';
        if( $beds_baths_search == 'greater') {
            $search_criteria = '>=';
        }

        $query_args = array(
            'post_type' => 'property',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        );

        $query_args = apply_filters('houzez_radius_filter', $query_args, $search_lat, $search_long, $search_radius, $use_radius, $search_location );

        $keyword = stripcslashes($keyword);

        if ( $keyword != '') {

            if( $keyword_field == 'prop_address' ) {
                $meta_keywork = wp_kses($keyword, $allowed_html);
                $address_array = array(
                    'key' => 'fave_property_map_address',
                    'value' => $meta_keywork,
                    'type' => 'CHAR',
                    'compare' => 'LIKE',
                );

                $street_array = array(
                    'key' => 'fave_property_address',
                    'value' => $meta_keywork,
                    'type' => 'CHAR',
                    'compare' => 'LIKE',
                );

                $zip_array = array(
                    'key' => 'fave_property_zip',
                    'value' => $meta_keywork,
                    'type' => 'CHAR',
                    'compare' => '=',
                );
                $propid_array = array(
                    'key' => 'fave_property_id',
                    'value' => str_replace($property_id_prefix, "", $meta_keywork),
                    'type' => 'CHAR',
                    'compare' => '=',
                );

                $keyword_array = array(
                    'relation' => 'OR',
                    $address_array,
                    $street_array,
                    $propid_array,
                    $zip_array
                );
            } else if( $keyword_field == 'prop_city_state_county' ) {
                $taxlocation[] = sanitize_title (  esc_html( wp_kses($keyword, $allowed_html) ) );

                $_tax_query = Array();
                $_tax_query['relation'] = 'OR';

                $_tax_query[] = array(
                    'taxonomy'     => 'property_area',
                    'field'        => 'slug',
                    'terms'        => $taxlocation
                );

                $_tax_query[] = array(
                    'taxonomy'     => 'property_city',
                    'field'        => 'slug',
                    'terms'        => $taxlocation
                );

                $_tax_query[] = array(
                    'taxonomy'      => 'property_state',
                    'field'         => 'slug',
                    'terms'         => $taxlocation
                );
                $tax_query[] = $_tax_query;

            } else {
                $keyword = trim( $keyword );
                if ( ! empty( $keyword ) ) {
                    $query_args['s'] = $keyword;
                }
            }
        }

        //Date Query
        if( !empty($publish_date) ) {
            $publish_date = explode('/', $publish_date);
            $query_args['date_query'] = array(
                array(
                    'year' => $publish_date[2],
                    'compare'   => '>=',
                ),
                array(
                    'month' => $publish_date[1],
                    'compare'   => '>=',
                ),
                array(
                    'day' => $publish_date[0],
                    'compare'   => '>=',
                )
            );
        }


        //Custom Fields
        if(class_exists('Houzez_Fields_Builder')) {
            $fields_array = Houzez_Fields_Builder::get_form_fields();
            if(!empty($fields_array)):
                
                foreach ( $fields_array as $key => $value ):
                    $field_title = $value->label;
                    $field_name = $value->field_id;
                    $is_search = $value->is_search;

                    if( $is_search == 'yes' ) {
                        if(!empty($dummy_array[$key])) {
                            $meta_query[] = array(
                                'key' => 'fave_'.$field_name,
                                'value' => $dummy_array[$key],
                                'type' => 'CHAR',
                                'compare' => '=',
                            );
                        }
                    }
                endforeach; endif;
        }

        if(!empty($currency)) {
            $meta_query[] = array(
                'key' => 'fave_currency',
                'value' => $currency,
                'type' => 'CHAR',
                'compare' => '=',
            );
        }

        // Meta Queries
        $meta_query[] = array(
            'key' => 'fave_property_map_address',
            'compare' => 'EXISTS',
        );

        // Property ID
        if( !empty( $property_id )  ) {
            $meta_query[] = array(
                'key' => 'fave_property_id',
                'value'   => $property_id,
                'type'    => 'char',
                'compare' => '=',
            );
        }

        if( !empty($location) && $location != 'all' ) {
            $tax_query[] = array(
                'taxonomy' => 'property_city',
                'field' => 'slug',
                'terms' => $location
            );

        } else {
            if( $location == 'all' ) {
                /*$tax_query[] = array(
                    'taxonomy' => 'property_city',
                    'field' => 'slug',
                    'terms' => $prop_locations
                );*/
            } else {
                if (!empty($initial_city)) {
                    $tax_query[] = array(
                        'taxonomy' => 'property_city',
                        'field' => 'slug',
                        'terms' => $initial_city
                    );
                }
            }
        }

        if( !empty($area) ) {
            $tax_query[] = array(
                'taxonomy' => 'property_area',
                'field' => 'slug',
                'terms' => $area
            );
        }
        if( !empty($state) ) {
            $tax_query[] = array(
                'taxonomy'      => 'property_state',
                'field'         => 'slug',
                'terms'         => $state
            );
        }

        if( !empty( $country ) ) {
            $meta_query[] = array(
                'key' => 'fave_property_country',
                'value'   => $country,
                'type'    => 'CHAR',
                'compare' => '=',
            );
        }

        if( !empty($status) ) {
            $tax_query[] = array(
                'taxonomy' => 'property_status',
                'field' => 'slug',
                'terms' => $status
            );
        }
        if( !empty($type) ) {
            $tax_query[] = array(
                'taxonomy' => 'property_type',
                'field' => 'slug',
                'terms' => $type
            );
        }

        if ( !empty($label) ) {
            $tax_query[] = array(
                'taxonomy' => 'property_label',
                'field' => 'slug',
                'terms' => $label
            );
        }

        if( !empty( $features ) ) {

            foreach ($features as $feature):
                $tax_query[] = array(
                    'taxonomy' => 'property_feature',
                    'field' => 'slug',
                    'terms' => $feature
                );
            endforeach;
        }

        // bedrooms logic
        if( !empty( $bedrooms ) && $bedrooms != 'any'  ) {
            $bedrooms = sanitize_text_field($bedrooms);
            $meta_query[] = array(
                'key' => 'fave_property_bedrooms',
                'value'   => $bedrooms,
                'type'    => 'CHAR',
                'compare' => $search_criteria,
            );
        }

        // bathrooms logic
        if( !empty( $bathrooms ) && $bathrooms != 'any'  ) {
            $bathrooms = sanitize_text_field($bathrooms);
            $meta_query[] = array(
                'key' => 'fave_property_bathrooms',
                'value'   => $bathrooms,
                'type'    => 'CHAR',
                'compare' => $search_criteria,
            );
        }

        // min and max price logic
        if( !empty( $min_price ) && $min_price != 'any' && !empty( $max_price ) && $max_price != 'any' ) {
            $min_price = doubleval( houzez_clean( $min_price ) );
            $max_price = doubleval( houzez_clean( $max_price ) );

            if( $min_price > 0 && $max_price > $min_price ) {
                $meta_query[] = array(
                    'key' => 'fave_property_price',
                    'value' => array($min_price, $max_price),
                    'type' => 'NUMERIC',
                    'compare' => 'BETWEEN',
                );
            }
        } else if( !empty( $min_price ) && $min_price != 'any'  ) {
            $min_price = doubleval( houzez_clean( $min_price ) );
            if( $min_price > 0 ) {
                $meta_query[] = array(
                    'key' => 'fave_property_price',
                    'value' => $min_price,
                    'type' => 'NUMERIC',
                    'compare' => '>=',
                );
            }
        } else if( !empty( $max_price ) && $max_price != 'any'  ) {
            $max_price = doubleval( houzez_clean( $max_price ) );
            if( $max_price > 0 ) {
                $meta_query[] = array(
                    'key' => 'fave_property_price',
                    'value' => $max_price,
                    'type' => 'NUMERIC',
                    'compare' => '<=',
                );
            }
        }

        // min and max area logic
        if( !empty( $min_area ) && !empty( $max_area ) ) {
            $min_area = intval( $min_area );
            $max_area = intval( $max_area );

            if( $min_area > 0 && $max_area > $min_area ) {
                $meta_query[] = array(
                    'key' => 'fave_property_size',
                    'value' => array($min_area, $max_area),
                    'type' => 'NUMERIC',
                    'compare' => 'BETWEEN',
                );
            }

        } else if( !empty( $max_area ) ) {
            $max_area = intval( $max_area );
            if( $max_area > 0 ) {
                $meta_query[] = array(
                    'key' => 'fave_property_size',
                    'value' => $max_area,
                    'type' => 'NUMERIC',
                    'compare' => '<=',
                );
            }
        } else if( !empty( $min_area ) ) {
            $min_area = intval( $min_area );
            if( $min_area > 0 ) {
                $meta_query[] = array(
                    'key' => 'fave_property_size',
                    'value' => $min_area,
                    'type' => 'NUMERIC',
                    'compare' => '>=',
                );
            }
        }

        $meta_count = count($meta_query);

        if( $meta_count > 0 || !empty($keyword_array)) {
            $query_args['meta_query'] = array(
                'relation' => 'AND',
                $keyword_array,
                array(
                    'relation' => 'AND',
                    $meta_query
                ),
            );
        }

        $tax_count = count($tax_query);


        $tax_query['relation'] = 'AND';


        if( $tax_count > 0 ) {
            $query_args['tax_query']  = $tax_query;
        }


        $query_args = new WP_Query( $query_args );


        $properties = array();

        while( $query_args->have_posts() ): $query_args->the_post();

            $post_id = get_the_ID();
            $property_location = get_post_meta( get_the_ID(),'fave_property_location',true);
            $lat_lng = explode(',', $property_location);
            $prop_images        = get_post_meta( get_the_ID(), 'fave_property_images', false );
            $prop_featured       = get_post_meta( get_the_ID(), 'fave_featured', true );
            $prop_type = wp_get_post_terms( get_the_ID(), 'property_type', array("fields" => "ids") );

            $prop = new stdClass();

            $prop->id = $post_id;
            $prop->title = get_the_title();
            $prop->sanitizetitle = sanitize_title(get_the_title());
            $prop->lat = $lat_lng[0];
            $prop->lng = $lat_lng[1];
            $prop->bedrooms = get_post_meta( get_the_ID(), 'fave_property_bedrooms', true );
            $prop->bathrooms = get_post_meta( get_the_ID(), 'fave_property_bathrooms', true );
            $prop->address = get_post_meta( get_the_ID(), 'fave_property_map_address', true );
            $prop->thumbnail = get_the_post_thumbnail( $post_id, 'houzez-property-thumb-image' );
            $prop->url = get_permalink();
            $prop->prop_meta = houzez_listing_meta_v1();
            $prop->type = houzez_taxonomy_simple('property_type');
            $prop->images_count = count( $prop_images );
            $prop->price = houzez_listing_price_v1();
            $prop->pricePin = houzez_listing_price_map_pins();

            foreach( $prop_type as $term_id ) {
                $icon = get_tax_meta( $term_id, 'fave_prop_type_icon');
                $retinaIcon = get_tax_meta( $term_id, 'fave_prop_type_icon_retina');

                $prop->term_id = $term_id;

                if( !empty($icon['src']) ) {
                    $prop->icon = $icon['src'];
                } else {
                    $prop->icon = get_template_directory_uri() . '/images/map/pin-single-family.png';
                }
                if( !empty($retinaIcon['src']) ) {
                    $prop->retinaIcon = $retinaIcon['src'];
                } else {
                    $prop->retinaIcon = get_template_directory_uri() . '/images/map/pin-single-family.png';
                }
            }

            array_push($properties, $prop);

        endwhile;

        wp_reset_postdata();

        if( count($properties) > 0 ) {
            echo json_encode( array( 'getProperties' => true, 'properties' => $properties ) );
            exit();
        } else {
            echo json_encode( array( 'getProperties' => false ) );
            exit();
        }
        die();
    }
}


/*-----------------------------------------------------------------------------------*/
/*  Get Properties for Half Map listings - deprecated since v2.0
/*-----------------------------------------------------------------------------------*/
if( !function_exists('houzez_half_map_listings') ) {
    function houzez_half_map_listings() {
        
    }
}

/*-----------------------------------------------------------------------------------*/
// Add to favorite
/*-----------------------------------------------------------------------------------*/
// add_action( 'wp_ajax_houzez_add_to_favorite', 'houzez_favorites' );
// if( !function_exists( 'houzez_favorites' ) ) {
//     // a:1:{i:0;i:543;}
//     function houzez_favorites () {
//         $userID      =   get_current_user_id();
//         $fav_option = 'houzez_favorites-'.$userID;
//         $property_id = intval( $_POST['listing_id'] );
//         $current_prop_fav = get_option( 'houzez_favorites-'.$userID );

        

//         // Check if empty or not
//         if( empty( $current_prop_fav ) ) {
//             $prop_fav = array();
//             $prop_fav['1'] = $property_id;
//             update_option( $fav_option, $prop_fav );
//             $arr = array( 'added' => true, 'response' => esc_html__('Added', 'houzez') );
//         } else {
//             if(  ! in_array ( $property_id, $current_prop_fav )  ) {
//                 $current_prop_fav[] = $property_id;
//                 update_option( $fav_option,  $current_prop_fav );
//                 $arr = array( 'added' => true, 'response' => esc_html__('Added', 'houzez') );
//             } else {
//                 $key = array_search( $property_id, $current_prop_fav );

//                 if( $key != false ) {
//                     unset( $current_prop_fav[$key] );
//                 }

//                 update_option( $fav_option, $current_prop_fav );
//                 $arr = array( 'added' => false, 'response' => esc_html__('Removed', 'houzez') );
//             }
//         }

//         echo json_encode($arr);
//         do_action('houzez_track_favorites', $userID, $property_id);
//         wp_die();
//     }
// }

// AJAX action registration.
add_action( 'wp_ajax_houzez_add_to_favorite', 'houzez_favorites' );

if ( ! function_exists( 'houzez_favorites' ) ) {
    function houzez_favorites() {
        // Optionally verify a nonce for security.
        // check_ajax_referer( 'houzez_favorites_nonce', 'security' );

        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            wp_send_json_error( array( 'message' => esc_html__( 'User not logged in.', 'houzez' ) ) );
        }

        $property_id = isset( $_POST['listing_id'] ) ? intval( $_POST['listing_id'] ) : 0;
        if ( ! $property_id ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Invalid property ID.', 'houzez' ) ) );
        }

        $result = houzez_process_favorites( $user_id, $property_id );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }
        wp_send_json_success( $result );
    }
}


/**
 * Toggle property in user's favorites.
 *
 * @param int $user_id Current user ID.
 * @param int $property_id Property ID.
 * @return array|WP_Error Returns array with the result or a WP_Error on failure.
 */
if( ! function_exists('houzez_process_favorites') ) {
    function houzez_process_favorites( $user_id, $property_id ) {
        $property_id = intval( $property_id );
        if ( ! $property_id ) {
            return new WP_Error( 'invalid_property', esc_html__( 'Invalid property ID.', 'houzez' ) );
        }

        // Retrieve current favorites from user meta.
        $favorites = get_user_meta( $user_id, 'houzez_favorites', true );
        if ( empty( $favorites ) || ! is_array( $favorites ) ) {
            $favorites = array();
        }

        // Toggle property in favorites.
        if ( ! in_array( $property_id, $favorites, true ) ) {
            $favorites[] = $property_id;
            $message   = esc_html__( 'Added', 'houzez' );
            $added     = true;
        } else {
            $key = array_search( $property_id, $favorites, true );
            if ( false !== $key ) {
                unset( $favorites[ $key ] );
                // Re-index the array.
                $favorites = array_values( $favorites );
            }
            $message = esc_html__( 'Removed', 'houzez' );
            $added   = false;
        }

        // Save updated favorites.
        update_user_meta( $user_id, 'houzez_favorites', $favorites );

        // Trigger any additional actions.
        do_action( 'houzez_track_favorites', $user_id, $property_id );

        return array( 'added' => $added, 'response' => $message );
    }
}


/*-----------------------------------------------------------------------------------*/
/*  Properties sorting
/*-----------------------------------------------------------------------------------*/
if( !function_exists( 'houzez_prop_sort' ) ){

    function houzez_prop_sort( $query_args ) {
        $sort_by = '';
        if ( isset( $_GET['sortby'] ) ) {
            $sort_by = $_GET['sortby'];
        } else {

            if ( houzez_is_listings_template() ) {
                $sort_by = get_post_meta( get_the_ID(), 'fave_properties_sort', true );

            } else if( is_page_template( array( 'template/template-search.php' )) ) {
                
                $sort_by = houzez_option('search_default_order');
                
            } else if ( is_tax() ) {
                $sort_by = houzez_option('taxonomy_default_order');
                
            } else if(is_singular('houzez_agent')) {
                $sort_by = houzez_option('agent_listings_order');

            } else if(is_singular('houzez_agency')) {
                $sort_by = houzez_option('agency_listings_order');

            }
        }

        if ( $sort_by == 'a_title' ) {
            $query_args['orderby'] = 'title';
            $query_args['order'] = 'ASC';
        } else if ( $sort_by == 'd_title' ) {
            $query_args['orderby'] = 'title';
            $query_args['order'] = 'DESC';
        } else if ( $sort_by == 'a_price' ) {
            $query_args['orderby'] = 'meta_value_num';
            $query_args['meta_key'] = 'fave_property_price';
            $query_args['order'] = 'ASC';
        } else if ( $sort_by == 'd_price' ) {
            $query_args['orderby'] = 'meta_value_num';
            $query_args['meta_key'] = 'fave_property_price';
            $query_args['order'] = 'DESC';
        } else if ( $sort_by == 'featured' ) {
            $query_args['meta_key'] = 'fave_featured';
            $query_args['meta_value'] = '1';
            $query_args['orderby'] = 'meta_value date';
        } else if ( $sort_by == 'featured_random' ) {
            $query_args['meta_key'] = 'fave_featured';
            $query_args['meta_value'] = '1';
            $query_args['orderby'] = 'meta_value DESC rand';
        } else if ( $sort_by == 'a_date' ) {
            $query_args['orderby'] = 'date';
            $query_args['order'] = 'ASC';
        } else if ( $sort_by == 'd_date' ) {
            $query_args['orderby'] = 'date';
            $query_args['order'] = 'DESC';
        } else if ( $sort_by == 'featured_first' ) {
            $query_args['orderby'] = 'meta_value date';
            $query_args['meta_key'] = 'fave_featured';
        } else if ( $sort_by == 'featured_first_random' ) {
            $query_args['meta_key'] = 'fave_featured';
            $query_args['orderby'] = 'meta_value DESC rand'; 
        } else if ( $sort_by == 'featured_top' ) {
            $query_args['orderby'] = 'meta_value date';
            $query_args['meta_key'] = 'fave_featured';
        } else if ( $sort_by == 'random' ) {
            $query_args['orderby'] = 'rand';
            $query_args['order'] = 'DESC';
        }

        return apply_filters( 'houzez_sort_properties', $query_args );

    }
}


/*-----------------------------------------------------------------------------------*/
// Remove property attachments
/*-----------------------------------------------------------------------------------*/
add_action( 'wp_ajax_houzez_remove_property_thumbnail', 'houzez_remove_property_thumbnail' );
add_action( 'wp_ajax_nopriv_houzez_remove_property_thumbnail', 'houzez_remove_property_thumbnail' );
if( !function_exists('houzez_remove_property_thumbnail') ) {
    function houzez_remove_property_thumbnail() {

        $nonce = $_POST['removeNonce'];
        $remove_attachment = false;
        if (!wp_verify_nonce($nonce, 'verify_gallery_nonce')) {

            echo json_encode(array(
                'remove_attachment' => false,
                'reason' => esc_html__('Invalid Nonce', 'houzez')
            ));
            wp_die();
        }

        if (isset($_POST['thumb_id']) && isset($_POST['prop_id'])) {
            $thumb_id = intval($_POST['thumb_id']);
            $prop_id = intval($_POST['prop_id']);

            $property_status = get_post_status ( $prop_id );

            if ( $thumb_id > 0 && $prop_id > 0 && $property_status != "draft" ) {
                delete_post_meta($prop_id, 'fave_property_images', $thumb_id);
                $remove_attachment = wp_delete_attachment($thumb_id);
            } elseif ( $thumb_id > 0 && $prop_id > 0 && $property_status == "draft" ) {
                delete_post_meta($prop_id, 'fave_property_images', $thumb_id);
                $remove_attachment = true;
            } elseif ($thumb_id > 0) {
                if( false == wp_delete_attachment( $thumb_id )) {
                    $remove_attachment = false;
                } else {
                    $remove_attachment = true;
                }
            }
        }

        echo json_encode(array(
            'remove_attachment' => $remove_attachment,
        ));
        wp_die();

    }
}

/*-----------------------------------------------------------------------------------*/
// Remove property attachments
/*-----------------------------------------------------------------------------------*/
add_action( 'wp_ajax_houzez_remove_property_documents', 'houzez_remove_property_documents' );
add_action( 'wp_ajax_nopriv_houzez_remove_property_documents', 'houzez_remove_property_documents' );
if( !function_exists('houzez_remove_property_documents') ) {
    function houzez_remove_property_documents() {

        $nonce = $_POST['removeNonce'];
        $remove_attachment = false;
        if (!wp_verify_nonce($nonce, 'verify_gallery_nonce')) {

            echo json_encode(array(
                'remove_attachment' => false,
                'reason' => esc_html__('Invalid Nonce', 'houzez')
            ));
            wp_die();
        }

        if (isset($_POST['thumb_id']) && isset($_POST['prop_id'])) {
            $thumb_id = intval($_POST['thumb_id']);
            $prop_id = intval($_POST['prop_id']);

            $property_status = get_post_status ( $prop_id );

            if ( $thumb_id > 0 && $prop_id > 0 && $property_status != "draft" ) {
                delete_post_meta($prop_id, 'fave_attachments', $thumb_id);
                $remove_attachment = wp_delete_attachment($thumb_id);
            } elseif ( $thumb_id > 0 && $prop_id > 0 && $property_status == "draft" ) {
                delete_post_meta($prop_id, 'fave_attachments', $thumb_id);
                $remove_attachment = true;
            } elseif ($thumb_id > 0) {
                if( false == wp_delete_attachment( $thumb_id )) {
                    $remove_attachment = false;
                } else {
                    $remove_attachment = true;
                }
            }
        }

        echo json_encode(array(
            'remove_attachment' => $remove_attachment,
        ));
        wp_die();

    }
}

/*-----------------------------------------------------------------------------------*/
/*   Upload property gallery images
/*-----------------------------------------------------------------------------------*/
add_action( 'wp_ajax_houzez_property_img_upload', 'houzez_property_img_upload' );    // only for logged in user
add_action( 'wp_ajax_nopriv_houzez_property_img_upload', 'houzez_property_img_upload' );
if( !function_exists( 'houzez_property_img_upload' ) ) {
    function houzez_property_img_upload( ) {

        // Check security Nonce
        $verify_nonce = $_REQUEST['verify_nonce'];
        if ( ! wp_verify_nonce( $verify_nonce, 'verify_gallery_nonce' ) ) {
            echo json_encode( array( 'success' => false , 'reason' => 'Invalid nonce!' ) );
            die;
        }

        $submitted_file = $_FILES['property_upload_file'];
        $uploaded_image = wp_handle_upload( $submitted_file, array( 'test_form' => false ) );

        if ( isset( $uploaded_image['file'] ) ) {
            $file_name          =   basename( $submitted_file['name'] );
            $file_type          =   wp_check_filetype( $uploaded_image['file'] );

            // Prepare an array of post data for the attachment.
            $attachment_details = array(
                'guid'           => $uploaded_image['url'],
                'post_mime_type' => $file_type['type'],
                'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $file_name ) ),
                'post_content'   => '',
                'post_status'    => 'inherit'
            );

            $attach_id      =   wp_insert_attachment( $attachment_details, $uploaded_image['file'] );
            $attach_data    =   wp_generate_attachment_metadata( $attach_id, $uploaded_image['file'] );
            wp_update_attachment_metadata( $attach_id, $attach_data );

            $user_id = get_current_user_id();
            $watermark_image_url = get_user_meta($user_id, 'fave_watermark_image', true);

            $thumbnail_url = wp_get_attachment_image_src( $attach_id, 'houzez-item-image-1' );

            $feat_image_url = wp_get_attachment_url( $attach_id );

            $ajax_response = array(
                'success'   => true,
                'url' => $thumbnail_url[0],
                'attachment_id'    => $attach_id,
                'full_image'    => $feat_image_url
            );

            echo json_encode( $ajax_response );
            die;

        } else {
            $ajax_response = array( 'success' => false, 'reason' => 'Image upload failed!' );
            echo json_encode( $ajax_response );
            die;
        }

    }
}

// Utility function to convert URL to local file path
function houzez_get_local_path($url) {
    $parsed_url = parse_url($url);
    $path = $_SERVER['DOCUMENT_ROOT'] . $parsed_url['path'];
    return $path;
}

function houzez_apply_watermark($image_path, $watermark_path, $position = 'bottom-right', $size_percentage = 10) {
    // Load the image and watermark
    list($image_width, $image_height) = getimagesize($image_path);
    $image = imagecreatefromstring(file_get_contents($image_path));
    $watermark = imagecreatefrompng($watermark_path);

    // Calculate watermark size based on user preference
    $watermark_width = imagesx($watermark);
    $watermark_height = imagesy($watermark);
    $scale = ($image_width * ($size_percentage / 100.0)) / $watermark_width;
    $new_watermark_width = $watermark_width * $scale;
    $new_watermark_height = $watermark_height * $scale;

    // Create a new watermark with the scaled size
    $scaled_watermark = imagecreatetruecolor($new_watermark_width, $new_watermark_height);
    imagealphablending($scaled_watermark, false);
    imagesavealpha($scaled_watermark, true);
    imagecopyresampled($scaled_watermark, $watermark, 0, 0, 0, 0, $new_watermark_width, $new_watermark_height, $watermark_width, $watermark_height);

    // Calculate the position of the watermark
    switch ($position) {
        case 'top-left':
            $dest_x = 10;
            $dest_y = 10;
            break;
        case 'top-right':
            $dest_x = $image_width - $new_watermark_width - 10;
            $dest_y = 10;
            break;
        case 'bottom-left':
            $dest_x = 10;
            $dest_y = $image_height - $new_watermark_height - 10;
            break;
        case 'bottom-right':
            $dest_x = $image_width - $new_watermark_width - 10;
            $dest_y = $image_height - $new_watermark_height - 10;
            break;
        default:
            // Default to bottom-right
            $dest_x = $image_width - $new_watermark_width - 10;
            $dest_y = $image_height - $new_watermark_height - 10;
            break;
    }

    // Combine the images
    imagealphablending($image, true);
    imagesavealpha($image, true);
    imagecopy($image, $scaled_watermark, $dest_x, $dest_y, 0, 0, $new_watermark_width, $new_watermark_height);

    // Get WordPress upload directory info
    $upload_dir = wp_upload_dir();
    $local_path = str_replace(home_url('/wp-content/uploads/'), $upload_dir['basedir'] . '/', $image_path);

    // Ensure the directory exists and is writable
    if (!file_exists(dirname($local_path))) {
        mkdir(dirname($local_path), 0777, true);
    }

    // Save the image back to the filesystem
    switch (strtolower(pathinfo($image_path, PATHINFO_EXTENSION))) {
        case 'jpeg':
        case 'jpg':
            //imagejpeg($image, $image_path);
            imagejpeg($image, $local_path); 
            break;
        case 'png':
            imagepng($image, $image_path);
            break;
        case 'gif':
            imagegif($image, $image_path);
            break;
        default:
            // Unsupported image type
            break;
    }

    // Clean up
    imagedestroy($image);
    imagedestroy($watermark);
    imagedestroy($scaled_watermark);
}



/*-----------------------------------------------------------------------------------*/
/*   Upload property gallery images
/*-----------------------------------------------------------------------------------*/
add_action( 'wp_ajax_houzez_property_attachment_upload', 'houzez_property_attachment_upload' );    // only for logged in user
add_action( 'wp_ajax_nopriv_houzez_property_attachment_upload', 'houzez_property_attachment_upload' );
if( !function_exists( 'houzez_property_attachment_upload' ) ) {
    function houzez_property_attachment_upload( ) {

        // Check security Nonce
        $verify_nonce = $_REQUEST['verify_nonce'];
        if ( ! wp_verify_nonce( $verify_nonce, 'verify_gallery_nonce' ) ) {
            echo json_encode( array( 'success' => false , 'reason' => 'Invalid nonce!' ) );
            die;
        }

        $submitted_file = $_FILES['property_attachment_file'];
        $uploaded_image = wp_handle_upload( $submitted_file, array( 'test_form' => false ) );

        if ( isset( $uploaded_image['file'] ) ) {
            $file_name          =   basename( $submitted_file['name'] );
            $file_type          =   wp_check_filetype( $uploaded_image['file'] );

            // Prepare an array of post data for the attachment.
            $attachment_details = array(
                'guid'           => $uploaded_image['url'],
                'post_mime_type' => $file_type['type'],
                'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $file_name ) ),
                'post_content'   => '',
                'post_status'    => 'inherit'
            );

            $attach_id      =   wp_insert_attachment( $attachment_details, $uploaded_image['file'] );
            $attach_data    =   wp_generate_attachment_metadata( $attach_id, $uploaded_image['file'] );
            wp_update_attachment_metadata( $attach_id, $attach_data );

            $attachment_title = get_the_title($attach_id);
            $attachment_url = wp_get_attachment_url( $attach_id );

            $ajax_response = array(
                'success'   => true,
                'url' => $attachment_url,
                'attachment_id'    => $attach_id,
                'attach_title'    => $attachment_title,
            );

            echo json_encode( $ajax_response );
            die;

        } else {
            $ajax_response = array( 'success' => false, 'reason' => 'File upload failed!' );
            echo json_encode( $ajax_response );
            die;
        }

    }
}

/*-----------------------------------------------------------------------------------*/
/*  Houzez get ajax single property
/*-----------------------------------------------------------------------------------*/
add_action( 'wp_ajax_nopriv_houzez_get_single_property', 'houzez_get_single_property' );
add_action( 'wp_ajax_houzez_get_single_property', 'houzez_get_single_property' );

if( !function_exists('houzez_get_single_property') ) {
    function houzez_get_single_property() {
        check_ajax_referer('houzez_map_ajax_nonce', 'security');

        $prop_id = isset($_POST['prop_id']) ? sanitize_text_field($_POST['prop_id']) : '';

        $args = array(
            'p' => $prop_id,
            'posts_per_page' => 1,
            'post_type' => 'property',
            'post_status' => 'publish'
        );


        $query = new WP_Query($args);
        $props = array();

        while( $query->have_posts() ) {
            $query->the_post();

            $post_id = get_the_ID();
            $property_location = get_post_meta( get_the_ID(),'fave_property_location',true);
            $lat_lng = explode(',', $property_location);
            $prop_images        = get_post_meta( get_the_ID(), 'fave_property_images', false );
            $prop_featured       = get_post_meta( get_the_ID(), 'fave_featured', true );
            $prop_type = wp_get_post_terms( get_the_ID(), 'property_type', array("fields" => "ids") );

            $prop = new stdClass();

            $prop->id = $post_id;
            $prop->title = get_the_title();
            $prop->lat = $lat_lng[0];
            $prop->lng = $lat_lng[1];
            $prop->bedrooms = get_post_meta( get_the_ID(), 'fave_property_bedrooms', true );
            $prop->bathrooms = get_post_meta( get_the_ID(), 'fave_property_bathrooms', true );
            $prop->address = get_post_meta( get_the_ID(), 'fave_property_map_address', true );
            $prop->thumbnail = get_the_post_thumbnail( $post_id, 'houzez-property-thumb-image' );
            $prop->url = get_permalink();
            $prop->prop_meta = houzez_listing_meta_v1();
            $prop->type = houzez_taxonomy_simple('property_type');
            $prop->images_count = count( $prop_images );
            $prop->price = houzez_listing_price_v1();
            $prop->pricePin = houzez_listing_price_map_pins();

            foreach( $prop_type as $term_id ) {
                $icon = get_tax_meta( $term_id, 'fave_prop_type_icon');
                $retinaIcon = get_tax_meta( $term_id, 'fave_prop_type_icon_retina');

                $prop->term_id = $term_id;

                if( !empty($icon['src']) ) {
                    $prop->icon = $icon['src'];
                } else {
                    $prop->icon = get_template_directory_uri() . '/images/map/pin-single-family.png';
                }
                if( !empty($retinaIcon['src']) ) {
                    $prop->retinaIcon = $retinaIcon['src'];
                } else {
                    $prop->retinaIcon = get_template_directory_uri() . '/images/map/pin-single-family.png';
                }
            }

            array_push($props, $prop);
        }

        wp_reset_postdata();

        if(count($props) > 0) {
            echo json_encode(array('getprops'=>true, 'props'=>$props));
            exit();
        } else {
            echo json_encode(array('getprops'=>false));
            exit();
        }

        die();

    }
}


/*-----------------------------------------------------------------------------------*/
/*  Houzez Print Property
/*-----------------------------------------------------------------------------------*/
add_action( 'wp_ajax_nopriv_houzez_create_print', 'houzez_create_print' );
add_action( 'wp_ajax_houzez_create_print', 'houzez_create_print' );

if( !function_exists('houzez_create_print')) {
    function houzez_create_print () {

        if(!isset($_POST['propid'])|| !is_numeric($_POST['propid'])){
            exit();
        }
        global $hide_fields;
        $hide_fields = houzez_option('hide_detail_prop_fields');
        $property_id = intval($_POST['propid']);
    
        print  '<html><head><link href="'.get_stylesheet_uri().'" rel="stylesheet" type="text/css" />';
        print  '<html><head><link href="'.HOUZEZ_CSS_DIR_URI.'bootstrap.min.css" rel="stylesheet" type="text/css" />';
        print  '<html><head><link href="'.HOUZEZ_CSS_DIR_URI.'main.css" rel="stylesheet" type="text/css" />';
        print  '<html><head><link href="'.HOUZEZ_CSS_DIR_URI.'icons.css" rel="stylesheet" type="text/css" />';
        print  '<html><head><link href="'.HOUZEZ_CSS_DIR_URI.'font-awesome.min.css" rel="stylesheet" type="text/css" />';

        if( is_rtl() ) {
            print '<link href="'.HOUZEZ_CSS_DIR_URI.'/rtl.css" rel="stylesheet" type="text/css" />';
            print '<link href="'.HOUZEZ_CSS_DIR_URI.'/bootstrap-rtl.min.css" rel="stylesheet" type="text/css" />';
        }
        print '</head>';
        print  '<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script><script>$(window).load(function(){ print(); });</script>';
        print  '<body class="print-page">';

        $print_logo = houzez_option( 'print_page_logo', false, 'url' );

        $print_agent = houzez_option('print_agent');
        $print_description = houzez_option('print_description');
        $print_details = houzez_option('print_details');
        $print_details_additional = houzez_option('print_details_additional');
        $print_features = houzez_option('print_features');
        $print_floorplans = houzez_option('print_floorplans');
        $print_gallery = houzez_option('print_gallery');
        $print_gr_code = houzez_option('print_gr_code');

        $args = array(
            'post_type' => 'property',
            'p' => $property_id,
        );

        $the_query = new WP_Query($args);

        if($the_query->have_posts()): 
            while($the_query->have_posts()): $the_query->the_post(); 
                global $property_features, $energy_class;
                $image_id     = get_post_thumbnail_id( get_the_ID() );
                $full_img     = wp_get_attachment_image_src($image_id, 'houzez-gallery');
                $full_img     = isset($full_img [0]) ? $full_img [0] : '';
                $property_features     = wp_get_post_terms( get_the_ID(), 'property_feature', array("fields" => "all"));
                $energy_class = houzez_get_listing_data('energy_class');
                $floor_plans  = get_post_meta( get_the_ID(), 'floor_plans', true );
                $prop_images  = get_post_meta( get_the_ID(), 'fave_property_images', false );
                $agent_array = houzez20_property_contact_form();

        ?>

            <div class="print-main-wrap">
                <div class="print-wrap">
                    <header class="print-header">
                        <div class="print-logo-wrap">
                            <div class="logo">
                                <a href="#">
                                    <img src="<?php echo esc_url($print_logo); ?>" alt="logo">
                                </a>
                            </div><!-- .logo -->
                            <div class="primary-text"><?php bloginfo( 'description' ); ?></div>
                        </div><!-- print-logo-wrap -->
                        
                        <div class="print-title-wrap">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <?php get_template_part('property-details/partials/title'); ?>      
                                    <?php get_template_part('property-details/partials/item-address'); ?>
                                </div>          
                                <?php get_template_part('property-details/partials/item-price'); ?>
                            </div><!-- d-flex -->
                        </div><!-- print-title-wrap -->
                        
                        <?php if( !empty($full_img) ) { ?>
                        <div class="print-banner-wrap">
                            <?php if($print_gr_code != 0) { ?>
                            <div class="qr-code">
                                <img class="img-fluid" src="https://qrcode.tec-it.com/API/QRCode?size=small&dpi=120&data=<?php echo esc_url( get_permalink($property_id) ); ?>" title="<?php echo esc_attr(get_the_title()); ?>" />
                            </div>
                            <?php } ?>
                            <img class="img-fluid" src="<?php echo esc_url( $full_img ); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                        </div><!-- print-banner-wrap -->
                        <?php } ?>
                        
                        <?php 
                        if( $print_agent != 0 && !empty($agent_array)) { ?>
                        <div class="print-agent-info-wrap">
                            
                            <h2 class="print-title"><?php echo houzez_option('sps_contact_info', 'Contact Information'); ?></h2>
                            
                            <?php 
                            if( isset( $agent_array['agent_info'] ) ) {
                                foreach( $agent_array['agent_info'] as $agent_info ) {  ?>
                                   
                                    <div class="agent-details">
                                        <div class="d-flex align-items-center">
                                            <?php if(!empty($agent_info['picture'])) { ?>
                                            <div class="agent-image">
                                                <img class="rounded" src="<?php echo esc_url($agent_info['picture']); ?>" alt="<?php echo esc_attr($agent_info['agent_name']); ?>" width="80" height="80">
                                            </div>
                                            <?php } ?>

                                            <ul class="list-unstyled m-0 ml-3 mr-3">
                                                <li class="agent-name">
                                                    <i class="houzez-icon icon-single-neutral mr-1"></i> <?php echo esc_attr($agent_info['agent_name']); ?>
                                                </li>
                                                <li class="agent-phone-wrap clearfix">
                                                    <?php if(!empty($agent_info['agent_phone'])) { ?>
                                                    <i class="houzez-icon icon-phone mr-1"></i> <strong><?php echo esc_attr($agent_info['agent_phone']); ?></strong>
                                                    <?php } ?>

                                                    <?php if(!empty($agent_info['agent_mobile'])) { ?>
                                                    <i class="houzez-icon icon-mobile-phone mr-1"></i> <strong><?php echo esc_attr($agent_info['agent_mobile']); ?></strong>
                                                    <?php } ?>
                                                </li>

                                                <?php if(!empty($agent_info['agent_email'])) { ?>
                                                <li><i class="houzez-icon icon-envelope mr-1"></i> <strong><?php echo esc_attr($agent_info['agent_email']); ?></strong></li>
                                                <?php } ?>
                                            </ul>
                                        </div><!-- d-flex -->
                                    </div><!-- agent-details -->
                                    <br/>
                                <?php
                                }
                            }
                            ?>
                        </div><!-- print-agent-info-wrap -->
                        <?php } ?>

                    </header>  

                    
                    <section class="print-content">
                        
                        <?php 
                        if( $print_description != 0 ) { ?>

                            <div class="print-section">
                                <h2 class="print-title"><?php echo houzez_option('sps_description', 'Description'); ?></h2>
                                <?php the_content(); ?>       
                            </div>

                        <?php } ?>

                        <?php 
                        if( $print_details != 0 ) { ?>

                            <div class="print-section">
                                <h2 class="print-title"><?php echo houzez_option('sps_details', 'Details'); ?></h2>
                                <div class="block-content-wrap">
                                    <?php get_template_part('property-details/partials/details'); ?> 
                                </div><!-- block-content-wrap -->
                            </div>

                        <?php } ?>

                        <?php 
                        if( $print_features != 0 && !empty($property_features)) { ?>

                            <div class="print-section">
                                <h2 class="print-title"><?php echo houzez_option('sps_features', 'Features'); ?></h2>
                                <div class="block-content-wrap">
                                    <?php get_template_part('property-details/partials/features'); ?>  
                                </div><!-- block-content-wrap -->
                            </div>

                        <?php } ?>

                        <?php
                        if( houzez_option('print_energy_class') != 0 && !empty($energy_class) ) { ?>
                            <div class="print-section">
                                <h2 class="print-title"><?php echo houzez_option('sps_energy_class', 'Energy Class'); ?></h2>
                                <div class="block-content-wrap">
                                    <?php get_template_part('property-details/partials/energy-class'); ?> 
                                </div><!-- block-content-wrap -->
                            </div><!-- print-section -->
                        <?php } ?>

                        <?php 
                        if( !empty( $floor_plans ) && $print_floorplans != 0 ) { ?>

                            <div class="print-section">
                                <h2 class="print-title"><?php echo houzez_option('sps_floor_plans', 'Floor Plans'); ?></h2>
                                
                                <?php 
                                foreach( $floor_plans as $plan ):
                                    $price_postfix = '';
                                    if( !empty( $plan['fave_plan_price_postfix'] ) ) {
                                        $price_postfix = ' / '.$plan['fave_plan_price_postfix'];
                                    }
                                    $filetype = wp_check_filetype($plan['fave_plan_image']);
                                ?>
                                <div class="floor-plan-wrap">
                                    <div class="floor-plan-top">
                                        <div class="d-flex align-items-center">
                                            <div class="accordion-title flex-grow-1">
                                                <?php echo esc_attr( $plan['fave_plan_title'] ); ?>
                                            </div><!-- accordion-title -->
                                            <ul class="floor-information list-unstyled list-inline m-0">
                                                <?php if( !empty( $plan['fave_plan_size'] ) ) { ?>
                                                    <li class="list-inline-item">
                                                        <?php esc_html_e( 'Size', 'houzez' ); ?>: 
                                                        <strong> <?php echo esc_attr( $plan['fave_plan_size'] ); ?></strong>
                                                    </li>
                                                <?php } ?>

                                                <?php if( !empty( $plan['fave_plan_rooms'] ) ) { ?>
                                                    <li class="list-inline-item">
                                                        <i class="houzez-icon icon-hotel-double-bed-1 mr-1"></i>
                                                        <strong><?php echo esc_attr( $plan['fave_plan_rooms'] ); ?></strong>
                                                    </li>
                                                <?php } ?>

                                                <?php if( !empty( $plan['fave_plan_bathrooms'] ) ) { ?>
                                                    <li class="list-inline-item">
                                                        <i class="houzez-icon icon-bathroom-shower-1 mr-1"></i>
                                                        <strong><?php echo esc_attr( $plan['fave_plan_bathrooms'] ); ?></strong>
                                                    </li>
                                                <?php } ?>

                                                <?php if( !empty( $plan['fave_plan_price'] ) ) { ?>
                                                    <li class="list-inline-item">
                                                        <?php esc_html_e( 'Price', 'houzez' ); ?>: 
                                                        <strong><?php echo houzez_get_property_price( $plan['fave_plan_price'] ).$price_postfix; ?></strong>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </div><!-- d-flex -->
                                    </div><!-- floor-plan-top -->
                                    
                                    <?php 
                                    if( !empty( $plan['fave_plan_image'] ) ) { ?>
                    
                                        <?php if($filetype['ext'] != 'pdf' ) {?>
                                        <a href="<?php echo esc_url( $plan['fave_plan_image'] ); ?>" target="_blank">
                                            <img class="img-fluid" src="<?php echo esc_url( $plan['fave_plan_image'] ); ?>" alt="image">
                                        </a>
                                        <?php } ?>
                                        
                                    <?php } ?>

                                    <?php
                                    if( !empty( $plan['fave_plan_description'] ) ) { ?>
                                    <div class="floor-plan-description mt-3">
                                        <p>
                                            <?php echo wp_kses_post( $plan['fave_plan_description'] ); ?>
                                        </p>
                                    </div><!-- floor-plan-description -->
                                    <?php } ?>

                                </div><!-- floor-plan-wrap -->
                                <?php endforeach; ?>

                            </div>
                        <?php } ?>


                        <?php 
                        if( !empty( $prop_images ) && $print_gallery != 0 ) { ?>
                        <div class="print-section">
                            <h2 class="print-title"><?php esc_html_e('Property images', 'houzez'); ?></h2>
                            <?php 
                            foreach( $prop_images as $img_id ): 
                                $image_url = houzez_get_image_by_id($img_id, 'houzez-gallery');
                                ?>
                                <div class="print-gallery-image"> 
                                <img src="<?php echo $image_url[0]; ?>" class="img-fluid mb-3">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php } ?>


                    </section>
                </div><!-- print-wrap -->
            </div><!-- print-main-wrap -->

        <?php
            endwhile;
        endif;

        ?>

<?php
        print '</body></html>';
        wp_die();
    }
}

/*-----------------------------------------------------------------------------------*/
// Get Current Area | @return mixed|string|void
/*-----------------------------------------------------------------------------------*/
if ( ! function_exists( 'houzez_get_current_area' ) ) {

    function houzez_get_current_area() {

        if ( isset( $_COOKIE[ "houzez_current_area" ] ) ) {
            $current_area = $_COOKIE[ "houzez_current_area" ];
        }

        if ( empty( $current_area ) ) {
            $current_area = houzez_option('houzez_base_area');
        }

        return $current_area;
    }
}

/*-----------------------------------------------------------------------------------*/
// Ajax Area Switch
/*-----------------------------------------------------------------------------------*/
if ( ! function_exists( 'houzez_switch_area' ) ) {

    function houzez_switch_area() {

        if ( isset( $_POST[ 'switch_to_area' ] ) ):

            $expiry_period = '';

            $switch_to_area = $_POST[ 'switch_to_area' ];

            // expiry time
            $expiry_period = intval( $expiry_period );
            if ( ! $expiry_period ) {
                $expiry_period = 60 * 60;   // one hour
            }
            $expiry = time() + $expiry_period;

            // save cookie
            if ( setcookie( 'houzez_current_area', $switch_to_area, $expiry, '/' ) ) {
                echo json_encode( array(
                    'success' => true
                ) );
            } else {
                echo json_encode( array(
                    'success' => false,
                    'message' => __( "Failed to updated cookie !", 'houzez' )
                ) );
            }

        else:
            echo json_encode( array(
                    'success' => false,
                    'message' => __( "Invalid Request !", 'houzez' )
                )
            );
        endif;

        die;

    }

    add_action( 'wp_ajax_nopriv_houzez_switch_area', 'houzez_switch_area' );
    add_action( 'wp_ajax_houzez_switch_area', 'houzez_switch_area' );
}


if( !function_exists('houzez_get_area_size') ) {
    function houzez_get_area_size( $areaSize ) {
        $prop_size = $areaSize;
        $prop_area_size = '';
        $houzez_base_area = houzez_option('houzez_base_area');

        if( !empty( $prop_size ) ) {
            // Convert string to float
            $prop_size = (float)$prop_size;

            if( isset( $_COOKIE[ "houzez_current_area" ] ) ) {
                if( $_COOKIE[ "houzez_current_area" ] == 'sq_meter' && $houzez_base_area != 'sq_meter'  ) {
                    $prop_size = $prop_size * 0.09290304; //m2 = ft2 x 0.09290304

                } elseif( $_COOKIE[ "houzez_current_area" ] == 'sqft' && $houzez_base_area != 'sqft' ) {
                    $prop_size = $prop_size / 0.09290304; //ft2 = m2 ÷ 0.09290304
                }
            }

            $prop_area_size = esc_attr( round( $prop_size, 0 ) );

        }
        return $prop_area_size;

    }
}

if( !function_exists('houzez_get_size_unit') ) {
    function houzez_get_size_unit( $areaUnit ) {
        $measurement_unit_global = houzez_option('measurement_unit_global');
        $area_switcher_enable = houzez_option('area_switcher_enable');

        if( $area_switcher_enable != 0 ) {
            $prop_size_prefix = houzez_option('houzez_base_area');

            if( isset( $_COOKIE[ "houzez_current_area" ] ) ) {
                $prop_size_prefix =$_COOKIE[ "houzez_current_area" ];
            }

            if( $prop_size_prefix == 'sqft' ) {
                $prop_size_prefix = houzez_option('measurement_unit_sqft_text');
            } elseif( $prop_size_prefix == 'sq_meter' ) {
                $prop_size_prefix = houzez_option('measurement_unit_square_meter_text');
            }

        } else {
            if ($measurement_unit_global == 1) {
                $prop_size_prefix = houzez_option('measurement_unit');

                if( $prop_size_prefix == 'sqft' ) {
                    $prop_size_prefix = houzez_option('measurement_unit_sqft_text');
                } elseif( $prop_size_prefix == 'sq_meter' ) {
                    $prop_size_prefix = houzez_option('measurement_unit_square_meter_text');
                }

            } else {
                $prop_size_prefix = $areaUnit;
            }
        }
        return $prop_size_prefix;
    }
}

if( !function_exists('houzez_autocomplete_search') ) {
    function houzez_autocomplete_search() {

        return;
    }
}

if( !function_exists('houzez_generate_invoice') ):
    function houzez_generate_invoice( $billingFor, $billionType, $packageID, $invoiceDate, $userID, $featured, $upgrade, $paypalTaxID, $paymentMethod, $is_package = 0 ) {

        $total_taxes = 0;
        $price_per_submission = houzez_option('price_listing_submission');
        $price_featured_submission = houzez_option('price_featured_listing_submission');

        $price_per_submission      = floatval( $price_per_submission );
        $price_featured_submission = floatval( $price_featured_submission );

        $args = array(
            'post_title'    => 'Invoice ',
            'post_status'   => 'publish',
            'post_type'     => 'houzez_invoice'
        );
        $inserted_post_id =  wp_insert_post( $args );

        if( $billionType != 'one_time' ) {
            $billionType = __( 'Recurring', 'houzez' );
        } else {
            $billionType = __( 'One Time', 'houzez' );
        }

        if( $is_package == 0 ) {
            if( $upgrade == 1 ) {
                $total_price = $price_featured_submission;

            } else {
                if( $featured == 1 ) {
                    $total_price = $price_per_submission+$price_featured_submission;
                } else {
                    $total_price = $price_per_submission;
                }
            }
        } else {
            $pack_price = get_post_meta( $packageID, 'fave_package_price', true);
            $pack_tax = get_post_meta( $packageID, 'fave_package_tax', true );

            if( !empty($pack_tax) && !empty($pack_price) ) {
                $total_taxes = intval($pack_tax)/100 * $pack_price;
                $total_taxes = round($total_taxes, 2);
            }
            
            $total_price = $pack_price + $total_taxes;
        }
        
        $fave_meta = array();

        $fave_meta['invoice_billion_for'] = $billingFor;
        $fave_meta['invoice_billing_type'] = $billionType;
        $fave_meta['invoice_item_id'] = $packageID;
        $fave_meta['invoice_item_price'] = $total_price;
        $fave_meta['invoice_tax'] = $total_taxes;
        $fave_meta['invoice_purchase_date'] = $invoiceDate;
        $fave_meta['invoice_buyer_id'] = $userID;
        $fave_meta['paypal_txn_id'] = $paypalTaxID;
        $fave_meta['invoice_payment_method'] = $paymentMethod;

        update_post_meta( $inserted_post_id, 'HOUZEZ_invoice_buyer', $userID );
        update_post_meta( $inserted_post_id, 'HOUZEZ_invoice_type', $billionType );
        update_post_meta( $inserted_post_id, 'HOUZEZ_invoice_for', $billingFor );
        update_post_meta( $inserted_post_id, 'HOUZEZ_invoice_item_id', $packageID );
        update_post_meta( $inserted_post_id, 'HOUZEZ_invoice_price', $total_price );
        update_post_meta( $inserted_post_id, 'HOUZEZ_invoice_tax', $total_taxes );
        update_post_meta( $inserted_post_id, 'HOUZEZ_invoice_date', $invoiceDate );
        update_post_meta( $inserted_post_id, 'HOUZEZ_paypal_txn_id', $paypalTaxID );
        update_post_meta( $inserted_post_id, 'HOUZEZ_invoice_payment_method', $paymentMethod );

        update_post_meta( $inserted_post_id, '_houzez_invoice_meta', $fave_meta );

        // Update post title
        $update_post = array(
            'ID'         => $inserted_post_id,
            'post_title' => 'Invoice '.$inserted_post_id,
        );
        wp_update_post( $update_post );
        return $inserted_post_id;
    }
endif;

/*-----------------------------------------------------------------------------------*/
/*  Houzez Invoice Filter
/*-----------------------------------------------------------------------------------*/
add_action( 'wp_ajax_nopriv_houzez_invoices_ajax_search', 'houzez_invoices_ajax_search' );
add_action( 'wp_ajax_houzez_invoices_ajax_search', 'houzez_invoices_ajax_search' );

if( !function_exists('houzez_invoices_ajax_search') ){
    function houzez_invoices_ajax_search() {
        global $current_user, $houzez_local;
        wp_get_current_user();
        $userID = $current_user->ID;

        $houzez_local = houzez_get_localization();

        $meta_query = array();
        $date_query = array();

        if( isset($_POST['invoice_status']) &&  $_POST['invoice_status'] !='' ){
            $meta_query[] = array(
                'key' => 'invoice_payment_status',
                'value' => esc_html( $_POST['invoice_status'] ),
                'type' => 'NUMERIC',
                'compare' => '=',
            );
        }

        if( isset($_POST['invoice_type']) &&  $_POST['invoice_type'] !='' ){
        
            $meta_query[] = array(
                'key' => 'HOUZEZ_invoice_for',
                'value' => esc_html( $_POST['invoice_type'] ),
                'type' => 'CHAR',
                'compare' => 'LIKE',
            );


        }

        if( isset($_POST['startDate']) &&  $_POST['startDate'] !='' ){
            $temp_array = array();
            $temp_array['after'] = esc_html( $_POST['startDate'] );
            $date_query[] = $temp_array;
        }

        if( isset($_POST['endDate']) &&  $_POST['endDate'] !='' ){
            $temp_array = array();
            $temp_array['before'] = esc_html( $_POST['endDate'] );
            $date_query[] = $temp_array;
        }

        if ( ! houzez_is_admin() && ! houzez_is_editor() || (isset($_GET['mine']) && $_GET['mine']) ) { 
            $meta_query[] = array(
                'key' => 'HOUZEZ_invoice_buyer',
                'value' => get_current_user_id(),
                'compare' => '='
            );
        }


        $invoices_args = array(
            'post_type' => 'houzez_invoice',
            'posts_per_page' => 10,
            'date_query' => $date_query,
        );

        $meta_count = count($meta_query);
        $meta_query['relation'] = 'AND';
        if ($meta_count > 0) {
            $invoices_args['meta_query'] = $meta_query;
        }

        $invoices = new WP_Query( $invoices_args );
        $total_price = 0;

        ob_start();
        while ( $invoices->have_posts()): $invoices->the_post();
            $fave_meta = houzez_get_invoice_meta( get_the_ID() );
            get_template_part('template-parts/dashboard/invoice/invoice-item');

            $total_price += $fave_meta['invoice_item_price'];
        endwhile;

        $result = ob_get_contents();
        ob_end_clean();

        echo json_encode( array( 'success' => true, 'result' => $result, 'total_price' => houzez_get_invoice_price( $total_price ) ) );
        wp_die();
    }
}

if( !function_exists('houzez_get_agent_info') ) {
    function houzez_get_agent_info( $args, $type ) {
        if( $type == 'for_grid_list' ) {
            return '<a href="'.$args[ 'link' ].'">'.$args[ 'agent_name' ].'</a> ';

        } elseif( $type == 'agent_form' ) {
            $output = '';

            $output .= '<div class="media agent-media">';
                $output .= '<div class="media-left">';
                    $output .= '<input type="checkbox">';
                    $output .= '<a href="'.$args[ 'link' ].'">';
                        $output .= '<img src="'.$args[ 'picture' ].'" alt="'.$args[ 'agent_name' ].'" width="75" height="75">';
                    $output .= '</a>';
                $output .= '</div>';

                $output .= '<div class="media-body">';
                    $output .= '<dl>';
                        if( !empty($args[ 'agent_name' ]) ) {
                            $output .= '<dd><i class="fa fa-user"></i> '.$args[ 'agent_name' ].'</dd>';
                        }
                        if( !empty( $args[ 'agent_mobile' ] ) ) {
                            $output .= '<dd><i class="fa fa-phone"></i><span class="clickToShow">'.esc_attr( $args[ 'agent_mobile' ] ).'</span></dd>';
                        }
                        $output .= '<dd><a href="'.$args[ 'link' ].'" class="view">'.esc_html__('View my listing', 'houzez' ).'</a></dd>';
                    $output .= '</dl>';
                $output .= '</div>';
            $output .= '</div>';

            return $output;
        }
    }
}

if( !function_exists('houzez_get_property_agent') ) {
    function houzez_get_property_agent($prop_id) {

        $agent_display_option = get_post_meta( $prop_id, 'fave_agent_display_option', true );
        $prop_agent_display = get_post_meta( $prop_id, 'fave_agents', true );
        $listing_agent = '';
        $prop_agent_num = $agent_num_call = $prop_agent = $prop_agent_link = $property_agent = '';
        if( $prop_agent_display != '-1' && $agent_display_option == 'agent_info' ) {

            $agent_ids = get_post_meta( $prop_id, 'fave_agents' );
            // remove invalid ids
            $agent_ids = array_filter( $agent_ids, function($hz){
                return ( $hz > 0 );
            });

            $agent_ids = array_unique( $agent_ids );

            if ( ! empty( $agent_ids ) ) {
                $agents_count = count( $agent_ids );
                $listing_agent = array();
                foreach ( $agent_ids as $agent ) {
                    if ( 0 < intval( $agent ) ) {

                        $agent_id = intval( $agent );
                        $agent_mobile = get_post_meta( $agent_id, 'fave_agent_mobile', true );
                        $agent_mobile_call = str_replace(array('(',')',' ','-'),'', $agent_mobile);
                        $agent_email = get_post_meta( $agent_id, 'fave_agent_email', true );
                        $agent_whatsapp = get_post_meta( $agent_id, 'fave_agent_whatsapp', true );
                        $agent_whatsapp_call = str_replace(array('(',')',' ','-'),'', $agent_whatsapp);
                        
                        $agent_args = array();
                        $agent_args[ 'agent_name' ] = get_the_title( $agent_id );
                        $agent_args[ 'agent_mobile' ] = $agent_mobile;
                        $agent_args[ 'agent_mobile_call' ] = $agent_mobile_call;

                        $agent_args[ 'agent_whatsapp' ] = $agent_whatsapp;
                        $agent_args[ 'agent_whatsapp_call' ] = $agent_whatsapp_call;

                        $agent_args[ 'agent_email' ] = $agent_email;
                        $agent_args[ 'link' ] = get_permalink($agent_id);
                        
                        $listing_agent[] = houzez_get_agent_info( $agent_args, 'for_grid_list' );
                    }
                }
            }

        } elseif( $agent_display_option == 'agency_info' ) {

            $prop_agency_ids = get_post_meta( $prop_id, 'fave_property_agency' ); 
            // remove invalid ids
            $prop_agency_ids = array_filter( $prop_agency_ids, function($hz){
                return ( $hz > 0 );
            });

            $prop_agency_ids = array_unique( $prop_agency_ids );

            if ( ! empty( $prop_agency_ids ) ) {
                $agency_count = count( $prop_agency_ids );
                $listing_agent = array();
                foreach ( $prop_agency_ids as $agency ) {
                    if ( 0 < intval( $agency ) ) {

                        $agency_id = intval( $agency );

                        $agency_mobile = get_post_meta( $agency_id, 'fave_agency_mobile', true );
                        $agency_mobile_call = str_replace(array('(',')',' ','-'),'', $agency_mobile);
                        $agency_whatsapp = get_post_meta( $agency_id, 'fave_agency_whatsapp', true );
                        $agency_whatsapp_call = str_replace(array('(',')',' ','-'),'', $agency_whatsapp);
                        $agency_email = get_post_meta( $agency_id, 'fave_agency_email', true );

                        $agency_args = array();
                        
                        $agency_args[ 'agent_name' ] = get_the_title( $agency_id );
                        $agency_args[ 'agent_mobile' ] = $agency_mobile;
                        $agency_args[ 'agent_mobile_call' ] = $agency_mobile_call;

                        $agency_args[ 'agent_whatsapp' ] = $agency_whatsapp;
                        $agency_args[ 'agent_whatsapp_call' ] = $agency_whatsapp_call;

                        $agency_args[ 'agent_email' ] = $agency_email;

                        $agency_args[ 'link' ] = get_permalink($agency_id);
                        $listing_agent[] = houzez_get_agent_info( $agency_args, 'for_grid_list' );
                    }
                }
            }

        } else {

            $listing_agent = array();
            $author_args = array();
            $author_args[ 'agent_name' ] = get_the_author();
            $author_args[ 'agent_mobile' ] = get_the_author_meta( 'fave_author_mobile' );
            $author_args[ 'agent_mobile_call' ] = str_replace(array('(',')',' ','-'),'', get_the_author_meta( 'fave_author_mobile' ));

            $author_args[ 'agent_whatsapp' ] = get_the_author_meta( 'fave_author_whatsapp' );
            $author_args[ 'agent_whatsapp_call' ] = str_replace(array('(',')',' ','-'),'', get_the_author_meta( 'fave_author_whatsapp' ));

            $author_args[ 'agent_email' ] = get_the_author_meta( 'email' );
            $author_args[ 'link' ] = get_author_posts_url( get_the_author_meta( 'ID' ) );

            $listing_agent[] = houzez_get_agent_info( $author_args, 'for_grid_list' );
        }
        return $listing_agent;
    }
}

if( !function_exists('houzez20_get_property_agent') ) {
    function houzez20_get_property_agent($prop_id, $type = null) {

        $agent_display_option = get_post_meta( $prop_id, 'fave_agent_display_option', true );
        $prop_agent_display = get_post_meta( $prop_id, 'fave_agents', true );
        $listing_agent = '';
        $prop_agent_num = $agent_num_call = $prop_agent = $prop_agent_link = $property_agent = '';
        if( $prop_agent_display != '-1' && $agent_display_option == 'agent_info' ) {

            $agent_ids = get_post_meta( $prop_id, 'fave_agents' );

            // remove invalid ids
            $agent_ids = array_filter( $agent_ids, function($hz){
                return ( $hz > 0 );
            });

            $reversed_agent_ids = array_reverse($agent_ids);
            $agent_id = array_pop($reversed_agent_ids);

            if( $agent_id != '' ) {

                $agent_id = intval( $agent_id );
                $agent_mobile = get_post_meta( $agent_id, 'fave_agent_mobile', true );
                $agent_mobile_call = str_replace(array('(',')',' ','-'),'', $agent_mobile);
                $agent_email = get_post_meta( $agent_id, 'fave_agent_email', true );
                $agent_whatsapp = get_post_meta( $agent_id, 'fave_agent_whatsapp', true );
                $agent_whatsapp_call = str_replace(array('(',')',' ','-'),'', $agent_whatsapp);
                
                $agent_args = array();
                $agent_args[ 'agent_name' ] = get_the_title( $agent_id );
                $agent_args[ 'agent_mobile' ] = $agent_mobile;
                $agent_args[ 'agent_mobile_call' ] = $agent_mobile_call;

                $agent_args[ 'agent_whatsapp' ] = $agent_whatsapp;
                $agent_args[ 'agent_whatsapp_call' ] = $agent_whatsapp_call;

                $agent_args[ 'agent_email' ] = $agent_email;
                $agent_args[ 'link' ] = get_permalink($agent_id);
                
                $listing_agent = $agent_args;
            }

        } elseif( $agent_display_option == 'agency_info' ) {

            $prop_agency_ids = get_post_meta( $prop_id, 'fave_property_agency' ); 
            // remove invalid ids
            $prop_agency_ids = array_filter( $prop_agency_ids, function($hz){
                return ( $hz > 0 );
            });

            $reversed_agency_ids = array_reverse($prop_agency_ids);
            $agency_id = array_pop($reversed_agency_ids);

            if ( ! empty( $agency_id ) ) {
                
                $agency_id = intval( $agency_id );
                $agency_mobile = get_post_meta( $agency_id, 'fave_agency_mobile', true );
                $agency_mobile_call = str_replace(array('(',')',' ','-'),'', $agency_mobile);
                $agency_whatsapp = get_post_meta( $agency_id, 'fave_agency_whatsapp', true );
                $agency_whatsapp_call = str_replace(array('(',')',' ','-'),'', $agency_whatsapp);
                $agency_email = get_post_meta( $agency_id, 'fave_agency_email', true );

                $agency_args = array();
                
                $agency_args[ 'agent_name' ] = get_the_title( $agency_id );
                $agency_args[ 'agent_mobile' ] = $agency_mobile;
                $agency_args[ 'agent_mobile_call' ] = $agency_mobile_call;

                $agency_args[ 'agent_whatsapp' ] = $agency_whatsapp;
                $agency_args[ 'agent_whatsapp_call' ] = $agency_whatsapp_call;

                $agency_args[ 'agent_email' ] = $agency_email;

                $agency_args[ 'link' ] = get_permalink($agency_id);
                $listing_agent = $agency_args;

            }

        } else {

            $listing_agent = array();
            $author_args = array();
            $author_args[ 'agent_name' ] = get_the_author();
            $author_args[ 'agent_mobile' ] = get_the_author_meta( 'fave_author_mobile' );
            $author_args[ 'agent_mobile_call' ] = str_replace(array('(',')',' ','-'),'', get_the_author_meta( 'fave_author_mobile' ));

            $author_args[ 'agent_whatsapp' ] = get_the_author_meta( 'fave_author_whatsapp' );
            $author_args[ 'agent_whatsapp_call' ] = str_replace(array('(',')',' ','-'),'', get_the_author_meta( 'fave_author_whatsapp' ));

            $author_args[ 'agent_email' ] = get_the_author_meta( 'email' );
            $author_args[ 'link' ] = get_author_posts_url( get_the_author_meta( 'ID' ) );

            $listing_agent = $author_args;
        }
        return $listing_agent;
    }
}

add_action( 'wp_ajax_nopriv_houzez_get_auto_complete_search', 'houzez_get_auto_complete_search' );
add_action( 'wp_ajax_houzez_get_auto_complete_search', 'houzez_get_auto_complete_search' );

if ( !function_exists( 'houzez_get_auto_complete_search' ) ) {

    function houzez_get_auto_complete_search() {
        $current_language = apply_filters( 'wpml_current_language', null );
        global $wpdb;
        $key = $_POST['key'];
        $key = $wpdb->esc_like($key);
        $keyword_field = houzez_option('keyword_field');
        $houzez_local = houzez_get_localization();
        $response = '';

        if( $keyword_field != 'prop_city_state_county' ) {

            if ( $keyword_field == "prop_title" ) {

                $table = $wpdb->posts;

                $data = $wpdb->get_results( 
                    $wpdb->prepare(
                        "SELECT DISTINCT * FROM {$table} WHERE post_type = %s AND post_status = %s AND (post_title LIKE %s OR post_content LIKE %s)",
                        'property',
                        'publish',
                        '%' . $key . '%',
                        '%' . $key . '%'
                    )
                );


                if ( sizeof( $data ) != 0 ) {

                    $search_url = add_query_arg( 'keyword', $key, houzez_get_search_template_link() );

                    echo '<div class="auto-complete-keyword">';
                    echo '<ul class="list-group">';

                    $new_data = array();

                    foreach ( $data as $post ) {

                        $propID = $post->ID;

                        $post_language = apply_filters( 'wpml_element_language_code', null, array('element_id' => $propID, 'element_type' => 'post'));

                        if ($post_language !== $current_language) {
                            continue;
                        }

                        $new_data [] = $post;
                        
                        // echo $prop_thumb = get_the_post_thumbnail( $propID );
                        $prop_beds = get_post_meta( $propID, 'fave_property_bedrooms', true );
                        $prop_baths = get_post_meta( $propID, 'fave_property_bathrooms', true );
                        $prop_size = houzez_get_listing_area_size( $propID );
                        $prop_type = houzez_taxonomy_simple('property_type');
                        $prop_img = get_the_post_thumbnail_url( $propID, array ( 40, 40 ) );

                        if ( empty( $prop_img ) ) {
                            $prop_img = houzez_get_image_placeholder_url('thumbnail');
                        }

                        ?>

                        <li class="list-group-item" data-text="<?php echo $post->post_title; ?>">
                            <div class="d-flex align-items-center">
                                <div class="auto-complete-image-wrap">
                                    <a href="<?php the_permalink( $propID ); ?>">
                                        <img class="img-fluid rounded" src="<?php echo $prop_img; ?>" width="40" height="40" alt="image">
                                    </a>    
                                </div><!-- auto-complete-image-wrap -->
                                <div class="auto-complete-content-wrap ml-3">
                                    <div class="auto-complete-title">
                                        <a href="<?php the_permalink( $propID ); ?>"><?php echo $post->post_title; ?></a>
                                    </div>
                                </div><!-- auto-complete-content-wrap -->
                            </div><!-- d-flex -->
                        </li><!-- list-group-item -->
                        <?php

                    }

                    echo '</ul>';

                    echo '<div class="auto-complete-footer">';
                        echo '<span class="auto-complete-count"><i class="houzez-icon icon-pin mr-1"></i> ' . sizeof( $new_data ) . ' '.$houzez_local['listins_found'].'</span>';
                        echo '<a target="_blank" href="' . $search_url . '" class="search-result-view">'.$houzez_local['view_all_results'].'</a>';
                    echo '</div>';


                    echo '</div>';

                } else {

               ?>
               <ul class="list-group">
                   <li class="list-group-item"> <?php echo $houzez_local['auto_result_not_found']; ?> </li>
               </ul>
               <?php

           }

       } else if ( $keyword_field == "prop_address" ) {

                $posts_table = $wpdb->posts;
                $postmeta_table = $wpdb->postmeta;

                $data = $wpdb->get_results( 
                    $wpdb->prepare(
                            "SELECT DISTINCT post.ID, meta.meta_value FROM {$postmeta_table} AS meta INNER JOIN $posts_table AS post ON meta.post_id=post.ID AND post.post_type='property' and post.post_status='publish' AND meta.meta_value LIKE %s AND ( meta.meta_key='fave_property_map_address' OR meta.meta_key='fave_property_zip' OR meta.meta_key='fave_property_address' OR meta.meta_key='fave_property_id' )",
                            '%' . $key . '%'
                        )
                );

                if ( sizeof( $data ) != 0 ) {

                    echo '<ul class="list-group">';

                    $new_data = array();

                    foreach ( $data as $title ) {

                        $post_language = apply_filters( 'wpml_element_language_code', null, array('element_id' => $title->ID, 'element_type' => 'post'));

                        if ($post_language !== $current_language) {
                            continue;
                        }

                        $new_data [] = $title;
                        ?>
                        
                        <li class="list-group-item" data-text="<?php echo $title->meta_value; ?>">
                            <div class="d-flex align-items-center">
                                <div class="auto-complete-content-wrap flex-fill">
                                    <i class="houzez-icon icon-pin mr-1"></i> <?php echo $title->meta_value; ?>
                                </div><!-- auto-complete-content-wrap -->
                            </div><!-- d-flex -->
                        </li>
                        <?php

                    }

                    echo '</ul>';

                } else {

               ?>
               <ul class="list-group">
                   <li class="list-group-item"> <?php echo $houzez_local['auto_result_not_found']; ?> </li>
               </ul>
               <?php

           }

            }

        } else {
            $terms_table = $wpdb->terms;
            $term_taxonomy = $wpdb->term_taxonomy;

            $data = $wpdb->get_results( 
                $wpdb->prepare(
                    "SELECT DISTINCT * FROM {$terms_table} as term INNER JOIN $term_taxonomy AS term_taxonomy ON term.term_id = term_taxonomy.term_id AND term.name LIKE %s AND ( term_taxonomy.taxonomy = %s OR term_taxonomy.taxonomy = %s OR term_taxonomy.taxonomy = %s )",
                    '%' . $key . '%',
                    'property_area',
                    'property_city',
                    'property_state'
                )
            );

            if ( sizeof( $data ) != 0 ) {

                echo '<ul class="list-group">';

                $new_data = array();

                foreach ( $data as $term ) {
        
                    $term_language = apply_filters( 'wpml_element_language_code', null, array('element_id' => $term->term_id, 'element_type' => 'category'));

                    if ($term_language !== $current_language) {
                        continue;
                    }

                    $new_data [] = $term;
                }

                // Sort the $new_data array based on the taxonomy
                usort($new_data, function($a, $b) {
                    $order = ['property_state' => 1, 'property_city' => 2, 'property_area' => 3];
                    return $order[$a->taxonomy] - $order[$b->taxonomy];
                });

                // Display the sorted terms
                foreach ($new_data as $term) {
                    

                    $taxonomy_img_id = get_term_meta( $term->term_id, 'fave_taxonomy_img', true );

                    $term_type = explode( 'property_', $term->taxonomy );
                    $term_type = $term_type[1];
                    $prop_count = $term->count;

                    if ( empty( $taxonomy_img_id ) ) {
                       $term_img = '<img src="http://placehold.it/40x40" width="40" height="40">';
                   } else {
                        $term_img = wp_get_attachment_image( $taxonomy_img_id, array( 40, 40 ), array( "class" => "img-fluid rounded" ) );
                   }

                   if( $term_type == 'state' ) {
                        $term_type = $houzez_local['auto_state'];
                   } else if( $term_type == 'city' ) {
                        $term_type = $houzez_local['auto_city'];
                   } else if( $term_type == 'area' ) {
                        $term_type = $houzez_local['auto_area'];
                   }

                    ?>
                    <li class="list-group-item" data-text="<?php echo $term->name; ?>">
                        <div class="d-flex align-items-center">
                            <div class="auto-complete-image-wrap">
                                <a href="<?php echo get_term_link( $term ); ?>">
                                    <?php echo $term_img; ?>
                                </a>    
                            </div><!-- auto-complete-image-wrap -->
                            <div class="auto-complete-content-wrap flex-fill ml-3">
                                <div class="auto-complete-title"><?php echo esc_attr($term->name); ?></div>
                                <ul class="item-amenities">
                                    <li><?php if ( !empty( $term_type ) ) { ?>
                                    <?php echo $term_type; ?>
                                <?php } ?>
                                <?php if ( !empty( $prop_count ) ) : ?>
                                     - <?php echo $prop_count . ' ' . $houzez_local['auto_listings']; ?>
                                <?php endif; ?></li>
                                </ul>
                            </div><!-- auto-complete-content-wrap -->
                            <div class="auto-complete-content-wrap ml-3">
                                <a target="_blank" href="<?php echo get_term_link( $term ); ?>" class="search-result-view"><?php echo $houzez_local['auto_view_lists']; ?></a>
                            </div><!-- auto-complete-content-wrap -->
                        </div><!-- d-flex -->
                    </li>
                    <?php

                }

                echo '</ul>';

            } else {

               ?>
               <ul class="list-group">
                   <li class="list-group-item"> <?php echo $houzez_local['auto_result_not_found']; ?> </li>
               </ul>
               <?php

           }

        }

        wp_die();

    }

}

/*-------------------------------------------------------------------------------
*
* Agency ajax pagination data
*-------------------------------------------------------------------------------*/
add_action('wp_ajax_houzez_ajax_agency_filter', 'houzez_ajax_agency_filter');
add_action('wp_ajax_nopriv_houzez_ajax_agency_filter', 'houzez_ajax_agency_filter');

if( !function_exists('houzez_ajax_agency_filter')) {
    function houzez_ajax_agency_filter() {

        $paged = isset($_POST['paged']) ? sanitize_text_field($_POST['paged']) : '';
        $agency_id = $_POST['agency_id'];

        $agency_agents = Houzez_Query::get_agency_agents_ids($agency_id);

        $loop_get_agent_properties_ids = Houzez_Query::loop_get_agent_properties_ids($agency_agents);
        $loop_agency_properties_ids = Houzez_Query::loop_agency_properties_ids($agency_id);
        $properties_ids = array_merge($loop_get_agent_properties_ids, $loop_agency_properties_ids);

        if(empty($properties_ids)) {
            
            $agency_listing_args = array(
                'post_type' => 'property',
                'posts_per_page' => 9,
                'paged' => $paged,
                'post_status' => 'publish',
                'meta_query' => array(
                    array(
                        'key' => 'fave_property_agency',
                        'value' => $agency_id,
                        'compare' => '='
                    )
                )
            );

            ?>
            <div class="grid-view grid-view-3-col">
                <div class="row">
            <?php
            $agency_qry = new WP_Query( $agency_listing_args );
            if( $agency_qry->have_posts() ):
                while( $agency_qry->have_posts() ): $agency_qry->the_post();
                    get_template_part('template-parts/property-for-listing');
                endwhile;
                wp_reset_postdata();
            else:
                get_template_part('template-parts/property', 'none');
            endif;
            echo '</div>';
            echo '</div>';
            echo '<hr>';
            houzez_halpmap_ajax_pagination( $agency_qry->max_num_pages, $paged, $range = 2 );

        } else {
            
            $agency_listing_args = array(
                'post_type' => 'property',
                'posts_per_page' => 9,
                'paged' => $paged,
                'post__in' => $properties_ids,
                'post_status' => 'publish'
            );

            ?>
            <div class="grid-view grid-view-3-col">
                <div class="row">
            <?php
            $agency_qry = new WP_Query( $agency_listing_args );
            if( $agency_qry->have_posts() ):
                while( $agency_qry->have_posts() ): $agency_qry->the_post();
                    get_template_part('template-parts/property-for-listing');
                endwhile;
                wp_reset_postdata();
            else:
                get_template_part('template-parts/property', 'none');
            endif;
            echo '</div>';
            echo '</div>';
            echo '<hr>';
            houzez_halpmap_ajax_pagination( $agency_qry->max_num_pages, $paged, $range = 2 );

        }

        wp_die();
    }
}


if ( !function_exists( 'houzez_get_agent_info_bottom' ) ) {
    function houzez_get_agent_info_bottom( $args, $type, $is_single = true ) {

        $view_listing = houzez_option('agent_view_listing');
        $agent_phone_num = houzez_option('agent_phone_num');
        if( empty($args['agent_name']) ) {
            return '';
        }
        if( $type == 'for_grid_list' ) {
            return '<a href="'.$args[ 'link' ].'">'.$args[ 'agent_name' ].'</a> ';

        } elseif( $type == 'agent_form' ) {
            $output = '';

            $output .= '<div class="agent-details">';
                $output .= '<div class="d-flex align-items-center">';
                    
                    $output .= '<div class="agent-image">';
                        if ( $is_single == false ) :
                            $output .= '<input type="checkbox" checked="checked" class="houzez-hidden multiple-agent-check" name="target_email[]" value="' . $args['agent_email'] . '" >';
                        endif;
                        
                        $output .= '<a href="'.$args[ 'link' ].'">';
                            $output .= '<img class="rounded" src="'.$args[ 'picture' ].'" alt="'.$args[ 'agent_name' ].'" width="80" height="80">';
                        $output .= '</a>';
                    $output .= '</div>';

                    $output .= '<ul class="agent-information list-unstyled">';
                        
                        if ( !empty( $args[ 'agent_name' ] ) ) :
                        $output .= '<li class="agent-name">';
                            $output .= '<i class="houzez-icon icon-single-neutral mr-1"></i> '.$args[ 'agent_name' ];
                        $output .= '</li>';
                        endif;


                        $output .= '<li class="agent-phone-wrap clearfix">';

                            if ( !empty( $args[ 'agent_phone' ] ) && houzez_option('agent_phone_num', 1) ) :
                            $output .= '<i class="houzez-icon icon-phone mr-1"></i>';
                            $output .= '<span class="agent-phone '.houzez_get_show_phone().'">';
                                 $output .= '<a href="tel:'.esc_attr( $args[ 'agent_phone_call' ] ).'">'.esc_attr($args[ 'agent_phone' ]).'</a>';
                            $output .= '</span>';
                            endif;

                            if ( !empty( $args[ 'agent_mobile' ] ) && houzez_option('agent_mobile_num', 1) ) :
                            $output .= '<i class="houzez-icon icon-mobile-phone mr-1"></i>';
                            $output .= '<span class="agent-phone '.houzez_get_show_phone().'">';
                                 $output .= '<a href="tel:'.esc_attr( $args[ 'agent_mobile_call' ] ).'">'.esc_attr($args[ 'agent_mobile' ]).'</a>';
                            $output .= '</span>';
                            endif;

                            if ( !empty( $args[ 'agent_skype' ] ) && $args[ 'agent_skype' ] != "#" && houzez_option('agent_skype_con', 1) ) :
                            $output .= '<i class="houzez-icon icon-video-meeting-skype mr-1"></i>';
                            $output .= '<span>';
                                 $output .= '<a href="skype:'.esc_attr( $args[ 'agent_skype' ] ).'?call">'.esc_attr( $args[ 'agent_skype' ] ).'</a>';
                            $output .= '</span>';
                            endif;

                            if ( !empty( $args[ 'agent_whatsapp' ] ) && houzez_option('agent_whatsapp_num', 1) ) :
                            $output .= '<i class="houzez-icon icon-messaging-whatsapp mr-1"></i>';
                            $output .= '<span>';
                                 $output .= '<a target="_blank" href="https://api.whatsapp.com/send?phone='.esc_attr( $args[ 'agent_whatsapp_call' ] ).'&text='.houzez_option('spl_con_interested', "Hello, I am interested in").' ['.get_the_title().'] '.get_permalink().'">'.esc_html__('WhatsApp', 'houzez').'</a>';
                            $output .= '</span>';
                            endif;

                        $output .= '</li>';


                        if( houzez_option('agent_con_social', 1) ) {
                            $output .= '<li class="agent-social-media">';
                                
                                if( !empty( $args[ 'facebook' ] ) ) :
                                $output .= '<span>';
                                    $output .= '<a class="btn-facebook" target="_blank" href="'.esc_url($args['facebook']).'">';
                                        $output .= '<i class="houzez-icon icon-social-media-facebook mr-2"></i>';
                                    $output .= '</a>';
                                $output .= '</span>';
                                endif;
                                
                                if( !empty( $args[ 'instagram' ] ) ) :
                                $output .= '<span>';
                                    $output .= '<a class="btn-instagram" target="_blank" href="'.esc_url($args['instagram']).'">';
                                        $output .= '<i class="houzez-icon icon-social-instagram mr-2"></i>';
                                    $output .= '</a>';
                                $output .= '</span>';
                                endif;

                                if( !empty( $args[ 'twitter' ] ) ) :
                                $output .= '<span>';
                                    $output .= '<a class="btn-twitter" target="_blank" href="'.esc_url($args['twitter']).'">';
                                        $output .= '<i class="houzez-icon icon-x-logo-twitter-logo-2 mr-2"></i>';
                                    $output .= '</a>';
                                $output .= '</span>';
                                endif;

                                if( !empty( $args[ 'linkedin' ] ) ) :
                                $output .= '<span>';
                                    $output .= '<a class="btn-linkedin" target="_blank" href="'.esc_url($args['linkedin']).'">';
                                        $output .= '<i class="houzez-icon icon-professional-network-linkedin mr-2"></i>';
                                    $output .= '</a>';
                                $output .= '</span>';
                                endif;

                                if( !empty( $args[ 'googleplus' ] ) ) :
                                $output .= '<span>';
                                    $output .= '<a class="btn-google-plus" target="_blank" href="'.esc_url($args['googleplus']).'">';
                                        $output .= '<i class="houzez-icon icon-social-media-google-plus-1 mr-2"></i>';
                                    $output .= '</a>';
                                $output .= '</span>';
                                endif;

                                if( !empty( $args[ 'youtube' ] ) ) :
                                $output .= '<span>';
                                    $output .= '<a class="btn-youtube" target="_blank" href="'.esc_url($args['youtube']).'">';
                                       $output .= '<i class="houzez-icon icon-social-video-youtube-clip mr-2"></i>';
                                    $output .= '</a>';
                                $output .= '</span>';
                                endif;

                            $output .= '</li>';
                        }
                    $output .= '</ul>';
                $output .= '</div>';
            $output .= '</div>';


            return $output;

        }

    }
}

if ( !function_exists( 'houzez_get_agent_info_bottom_v2' ) ) {
    function houzez_get_agent_info_bottom_v2( $args, $type, $is_single = true ) {

        if( empty($args['agent_name']) ) {
            return '';
        }
        ob_start();
        ?>
        <div class="agent-details">
    
            <div class="agent-image">
                <img class="rounded" src="<?php echo esc_url( $args[ 'picture' ] ); ?>" alt="<?php echo esc_attr( $args['agent_name'] ); ?>" width="80" height="80">
                <?php if ( $is_single == false ) { ?>
                <input type="checkbox" class="houzez-hidden multiple-agent-check" checked="checked" name="target_email[]" value="<?php echo $args['agent_email']; ?>" >
                <?php } ?>
            </div>

            <ul class="agent-information list-unstyled">
                <li class="agent-name">
                    <?php if( !empty( $args['agent_name'] ) ) { ?>
                        <i class="houzez-icon icon-single-neutral mr-1"></i> <?php echo esc_attr( $args['agent_name'] ); ?>
                    <?php } ?>
                </li>

                <li class="agent-phone-wrap clearfix">
        
                    <?php if( !empty( $args['agent_phone'] ) && houzez_option('agent_phone_num', 1) ) { ?>
                        <i class="houzez-icon icon-phone mr-1"></i>
                        <span class="agent-phone <?php houzez_show_phone(); ?>">
                            <?php echo esc_attr( $args['agent_phone'] );?>
                        </span>
                    <?php } ?>

                    <?php if( !empty( $args['agent_mobile'] ) && houzez_option('agent_mobile_num', 1) ) { ?>
                        <i class="houzez-icon icon-mobile-phone mr-1"></i>
                        <span class="agent-phone <?php houzez_show_phone(); ?>">
                            <?php echo esc_attr( $args['agent_mobile'] );?>
                        </span>
                    <?php } ?>

                    <?php if( !empty( $args['agent_skype'] ) && houzez_option('agent_skype_con', 1) ) { ?>
                        <i class="houzez-icon icon-video-meeting-skype mr-1"></i>
                        <span>
                            <a href="skype:<?php esc_attr( $args[ 'agent_skype' ] ); ?>?call"><?php echo esc_attr( $args[ 'agent_skype' ] ); ?></a>
                        </span>
                    <?php } ?>

                    <?php if( !empty( $args['agent_whatsapp'] ) && houzez_option('agent_whatsapp_num', 1) ) { ?>
                        <i class="houzez-icon icon-messaging-whatsapp mr-1"></i>
                        <span>
                            <a target="_blank" href="https://api.whatsapp.com/send?phone=<?php echo esc_attr( $args[ 'agent_whatsapp_call' ] ); ?>&text=<?php echo houzez_option('spl_con_interested', "Hello, I am interested in").' ['.get_the_title().'] '.get_permalink(); ?> "><?php echo esc_html__('WhatsApp', 'houzez'); ?></a>
                        </span>
                    <?php } ?>

                </li>

                <?php if( houzez_option('agent_con_social', 1) ) { ?>
                <li class="agent-social-media mb-3">
                    <?php if( !empty( $args['facebook'] ) ) { ?>
                        <span><a class="btn-facebook" target="_blank" href="<?php echo esc_url( $args['facebook'] ); ?>"><i class="houzez-icon icon-social-media-facebook mr-2"></i></a></span>
                    <?php } ?>

                    <?php if( !empty( $args['twitter'] ) ) { ?>
                        <span><a class="btn-twitter" target="_blank" href="<?php echo esc_url( $args['twitter'] ); ?>"><i class="houzez-icon icon-x-logo-twitter-logo-2 mr-2"></i></a></span>
                    <?php } ?>

                    <?php if( !empty( $args['linkedin'] ) ) { ?>
                        <span><a class="btn-linkedin" target="_blank" href="<?php echo esc_url( $args['linkedin'] ); ?>"><i class="houzez-icon icon-professional-network-linkedin mr-2"></i></a></span>
                    <?php } ?>

                    <?php if( !empty( $args['googleplus'] ) ) { ?>
                        <span><a class="btn-google-plus" target="_blank" href="<?php echo esc_url( $args['googleplus'] ); ?>"><i class="houzez-icon icon-social-media-google-plus-1 mr-2"></i></a></span>
                    <?php } ?>

                    <?php if( !empty( $args['youtube'] ) ) { ?>
                        <span><a class="btn-youtube" target="_blank" href="<?php echo esc_url( $args['youtube'] ); ?>"><i class="houzez-icon icon-social-video-youtube-clip mr-2"></i></a></span>
                    <?php } ?>

                    <?php if( !empty( $args['instagram'] ) ) { ?>
                        <span><a class="btn-instagram" target="_blank" href="<?php echo esc_url( $args['instagram'] ); ?>"><i class="houzez-icon icon-social-video-instagram-clip mr-2"></i></a></span>
                    <?php } ?>
                </li>
                <?php } ?>
            </ul>

            <?php if( houzez_option('agent_view_listing') != 0 ) { ?>
            <a class="btn btn-primary btn-slim" href="<?php echo esc_url($args[ 'link' ]); ?>" target="_blank"><?php echo houzez_option('spl_con_view_listings', 'View listings'); ?></a>
            <?php } ?>
        </div><!-- agent-details -->

        <?php
        $data = ob_get_contents();
        ob_clean();

        return $data;

    }
}

if ( !function_exists( 'houzez_get_agent_info_top' ) ) {
    function houzez_get_agent_info_top($args, $type, $is_single = true) {
        global $ele_settings;
        $view_listing_link = isset($ele_settings['view_listing']) ? $ele_settings['view_listing'] : 'yes';
        
        $view_listing = houzez_option('agent_view_listing');
        $agent_phone_num = houzez_option('agent_phone_num');

        if( empty($args['agent_name']) ) {
            return '';
        }

        if ($type == 'for_grid_list') {
            return '<a href="' . $args['link'] . '">' . $args['agent_name'] . '</a> ';

        } elseif ($type == 'agent_form') {
            $output = '';

            $output .= '<div class="agent-details">';
                $output .= '<div class="d-flex align-items-center">';
                    
                    $output .= '<div class="agent-image">';
                        
                        if ( $is_single == false ) {
                            $output .= '<input type="checkbox" class="houzez-hidden" checked="checked" class="multiple-agent-check" name="target_email[]" value="' . $args['agent_email'] . '" >';
                        }

                        $output .= '<img class="rounded" src="' . $args['picture'] . '" alt="' . $args['agent_name'] . '" width="70" height="70">';

                    $output .= '</div>';

                    $output .= '<ul class="agent-information list-unstyled">';

                        if (!empty($args['agent_name'])) {
                            $output .= '<li class="agent-name">';
                                $output .= '<i class="houzez-icon icon-single-neutral mr-1"></i> '.$args['agent_name'];
                            $output .= '</li>';
                        }
                        
                        if ( $is_single == false && !empty($args['agent_mobile'])) {
                            $output .= '<li class="agent-phone agent-phone-hidden">';
                                $output .= '<i class="houzez-icon icon-phone mr-1"></i> ' . esc_attr($args['agent_mobile']);
                            $output .= '</li>';
                        }

                        
                        if($view_listing != 0 && $view_listing_link == 'yes') {
                            $output .= '<li class="agent-link">';
                                $output .= '<a href="' . $args['link'] . '">' . houzez_option('spl_con_view_listings', 'View listings') . '</a>';
                            $output .= '</li>';
                        }


                    $output .= '</ul>';
                $output .= '</div>';
            $output .= '</div>';

            return $output;
        }
    }
}

if(!function_exists('houzez20_property_contact_form')) {
    function houzez20_property_contact_form($is_top = true, $luxury = false) {
        global $post;

        $allowed_html_array = array(
            'a' => array(
                'href' => array(),
                'title' => array()
            )
        );
        $listing_agent = $prop_agent = $prop_agent_phone = $prop_agent_mobile = $picture = $agent_id = '';
        $prop_agent_num = $agent_num_call = $prop_agent_email = $prop_agent_permalink = $linkedin = $prop_agent_whatsapp = $agent_telegram = $agent_lineapp = '';
        $return_array = array();
        $listing_agent_info = array();

        $agent_display = houzez_get_listing_data('agent_display_option');

        $is_single_agent = true;

        if( $agent_display != 'none' ) {
            if( $agent_display == 'agent_info' ) {

                $agents_ids = houzez_get_listing_data('agents', false);
                $prop_agent_photo_url = '';

                $agents_ids = array_filter( $agents_ids, function($hz){
                    return ( $hz > 0 );
                });

                $agents_ids = array_unique( $agents_ids );

                if ( ! empty( $agents_ids ) ) {
                    $agents_count = count( $agents_ids );
                    if ( $agents_count > 1 ) :
                        $is_single_agent = false;
                    endif;
                    $listing_agent = '';
                    foreach ( $agents_ids as $agent ) {
                        if ( 0 < intval( $agent ) ) {

                            $agent_id = intval( $agent );
                            $prop_agent_phone = get_post_meta( $agent_id, 'fave_agent_office_num', true );
                            $prop_agent_mobile = get_post_meta( $agent_id, 'fave_agent_mobile', true );
                            $prop_agent_whatsapp = get_post_meta( $agent_id, 'fave_agent_whatsapp', true );
                            $agent_telegram = get_post_meta( $agent_id, 'fave_agent_telegram', true );
                            $agent_lineapp = get_post_meta( $agent_id, 'fave_agent_line_id', true );
                            $prop_agent_email = get_post_meta( $agent_id, 'fave_agent_email', true );
                            $linkedin = get_post_meta( $agent_id, 'fave_agent_linkedin', true );
                            $agent_num_call = str_replace(array('(',')',' ','-'),'', $prop_agent_mobile);
                            $prop_agent = get_the_title( $agent_id );
                            $thumb_id = get_post_thumbnail_id( $agent_id );
                            if($thumb_id) {
                                $thumb_url_array = wp_get_attachment_image_src( $thumb_id, array(150,150), true );
                                $prop_agent_photo_url = $thumb_url_array[0];
                            }
                            
                            $prop_agent_permalink = get_post_permalink( $agent_id );

                            $agent_args = array();
                            $agent_args[ 'agent_id' ] = $agent_id;
                            $agent_args[ 'agent_type' ] = $agent_display;
                            $agent_args[ 'agent_skype' ] = get_post_meta( $agent_id, 'fave_agent_skype', true );
                            $agent_args[ 'agent_name' ] = $prop_agent;
                            $agent_args[ 'agent_mobile' ] = $prop_agent_mobile;
                            $agent_args[ 'agent_mobile_call' ] = str_replace(array('(',')',' ','-'),'', $prop_agent_mobile);
                            $agent_args[ 'agent_whatsapp' ] = $prop_agent_whatsapp;
                            $agent_args[ 'agent_whatsapp_call' ] = str_replace(array('(',')',' ','-'),'', $prop_agent_whatsapp);
                            $agent_args[ 'agent_phone' ] = $prop_agent_phone;
                            $agent_args[ 'agent_phone_call' ] = str_replace(array('(',')',' ','-'),'', $prop_agent_phone);
                            $agent_args[ 'agent_email' ] = $prop_agent_email;
                            $agent_args[ 'link' ] = $prop_agent_permalink;
                            $agent_args[ 'agent_position' ] = get_post_meta( $agent_id, 'fave_agent_position', true );
                            $agent_args[ 'agent_company' ] = get_post_meta( $agent_id, 'fave_agent_company', true );
                            $agent_args[ 'agent_service_area' ] = get_post_meta( $agent_id, 'fave_agent_service_area', true );
                            $agent_args[ 'agent_specialties' ] = get_post_meta( $agent_id, 'fave_agent_specialties', true );
                            $agent_args[ 'agent_tax_no' ] = get_post_meta( $agent_id, 'fave_agent_tax_no', true );
                            $agent_args[ 'facebook' ] = get_post_meta( $agent_id, 'fave_agent_facebook', true );
                            $agent_args[ 'twitter' ] = get_post_meta( $agent_id, 'fave_agent_twitter', true );
                            $agent_args[ 'linkedin' ] = get_post_meta( $agent_id, 'fave_agent_linkedin', true );
                            $agent_args[ 'googleplus' ] = get_post_meta( $agent_id, 'fave_agent_googleplus', true );
                            $agent_args[ 'youtube' ] = get_post_meta( $agent_id, 'fave_agent_youtube', true );
                            $agent_args[ 'instagram' ] = get_post_meta( $agent_id, 'fave_agent_instagram', true );
                            $agent_args[ 'telegram' ] = $agent_telegram;
                            $agent_args[ 'lineapp' ] = $agent_lineapp;

                            if( empty( $prop_agent_photo_url )) {

                                $agent_placeholder_url = houzez_option( 'houzez_agent_placeholder', false, 'url' );
                                if( ! empty($agent_placeholder_url) ) {
                                    $agent_args[ 'picture' ] = $agent_placeholder_url;
                                    $picture = $agent_placeholder_url;
                                } else {
                                    $agent_args[ 'picture' ] = HOUZEZ_IMAGE. 'profile-avatar.png';
                                    $picture = HOUZEZ_IMAGE. 'profile-avatar.png';
                                }

                            } else {
                                $agent_args[ 'picture' ] = $prop_agent_photo_url;
                                $picture = $prop_agent_photo_url;
                            }

                            $listing_agent_info[] = $agent_args;
                
                            if($is_top) {
                                $listing_agent .= houzez_get_agent_info_top( $agent_args, 'agent_form', $is_single_agent );
                            } else {

                                if($luxury) {
                                    $listing_agent .= houzez_get_agent_info_bottom_v2( $agent_args, 'agent_form', $is_single_agent );
                                } else {
                                    $listing_agent .= houzez_get_agent_info_bottom( $agent_args, 'agent_form', $is_single_agent );
                                }
                                
                            }

                        }
                    }
                }

            } elseif( $agent_display == 'agency_info' ) {

                $prop_agent_photo_url = '';
                $agent_id = get_post_meta( get_the_ID(), 'fave_property_agency', true );

                $prop_agent_phone = get_post_meta( $agent_id, 'fave_agency_phone', true );
                $prop_agent_mobile = get_post_meta( $agent_id, 'fave_agency_mobile', true );
                $prop_agent_whatsapp = get_post_meta( $agent_id, 'fave_agency_whatsapp', true );

                $agent_telegram = get_post_meta( $agent_id, 'fave_agency_telegram', true );
                $agent_lineapp = get_post_meta( $agent_id, 'fave_agency_line_id', true );

                $prop_agent_email = get_post_meta( $agent_id, 'fave_agency_email', true );
                $linkedin = get_post_meta( $agent_id, 'fave_agency_linkedin', true );
                $agent_num_call = str_replace(array('(',')',' ','-'),'', $prop_agent_mobile);
                $prop_agent = get_the_title( $agent_id );
                $thumb_id = get_post_thumbnail_id( $agent_id );

                if($thumb_id) {
                    $thumb_url_array = wp_get_attachment_image_src( $thumb_id, array(150,150), true );
                    $prop_agent_photo_url = $thumb_url_array[0];
                }
                $prop_agent_permalink = get_post_permalink( $agent_id );

                $agent_args = array();
                $agent_args[ 'agent_id' ] = $agent_id;
                $agent_args[ 'agent_type' ] = $agent_display;
                $agent_args[ 'agent_skype' ] = get_post_meta( $agent_id, 'fave_agency_skype', true );
                $agent_args[ 'agent_name' ] = $prop_agent;
                $agent_args[ 'agent_mobile' ] = $prop_agent_mobile;
                $agent_args[ 'agent_mobile_call' ] = str_replace(array('(',')',' ','-'),'', $prop_agent_mobile);
                $agent_args[ 'agent_whatsapp' ] = $prop_agent_whatsapp;
                $agent_args[ 'agent_whatsapp_call' ] = str_replace(array('(',')',' ','-'),'', $prop_agent_whatsapp);
                $agent_args[ 'agent_phone' ] = $prop_agent_phone;
                $agent_args[ 'agent_phone_call' ] = str_replace(array('(',')',' ','-'),'', $prop_agent_phone);
                $agent_args[ 'agent_email' ] = $prop_agent_email;
                $agent_args[ 'link' ] = $prop_agent_permalink;

                $agent_args[ 'agent_position' ] = '';
                $agent_args[ 'agent_company' ] = '';
                $agent_args[ 'agent_service_area' ] = get_post_meta( $agent_id, 'fave_agency_service_area', true );
                $agent_args[ 'agent_specialties' ] = get_post_meta( $agent_id, 'fave_agency_specialties', true );
                $agent_args[ 'agent_tax_no' ] = '';

                $agent_args[ 'facebook' ] = get_post_meta( $agent_id, 'fave_agency_facebook', true );
                $agent_args[ 'twitter' ] = get_post_meta( $agent_id, 'fave_agency_twitter', true );
                $agent_args[ 'linkedin' ] = get_post_meta( $agent_id, 'fave_agency_linkedin', true );
                $agent_args[ 'googleplus' ] = get_post_meta( $agent_id, 'fave_agency_googleplus', true );
                $agent_args[ 'youtube' ] = get_post_meta( $agent_id, 'fave_agency_youtube', true );
                $agent_args[ 'instagram' ] = get_post_meta( $agent_id, 'fave_agency_instagram', true );
                $agent_args[ 'telegram' ] = $agent_telegram;
                $agent_args[ 'lineapp' ] = $agent_lineapp;

                if( empty( $prop_agent_photo_url )) {
                    
                    $agency_placeholder_url = houzez_option( 'houzez_agency_placeholder', false, 'url' );
                    if( ! empty($agency_placeholder_url) ) {
                        $agent_args[ 'picture' ] = $agency_placeholder_url;
                        $picture = $agency_placeholder_url;
                    } else {
                        $agent_args[ 'picture' ] = HOUZEZ_IMAGE. 'profile-avatar.png';
                        $picture = HOUZEZ_IMAGE. 'profile-avatar.png';
                    }

                } else {
                    $agent_args[ 'picture' ] = $prop_agent_photo_url;
                    $picture = $prop_agent_photo_url;
                }

                $listing_agent_info[] = $agent_args;
                if($is_top) {
                    $listing_agent .= houzez_get_agent_info_top( $agent_args, 'agent_form' );
                } else {
                    if($luxury) {
                        $listing_agent .= houzez_get_agent_info_bottom_v2( $agent_args, 'agent_form' );
                    } else {
                        $listing_agent .= houzez_get_agent_info_bottom( $agent_args, 'agent_form' );
                    }
                }
            

            } else {

                $author_id = $post->post_author;
                $prop_agent = get_the_author_meta( 'display_name', $author_id );
                $prop_agent_permalink = get_author_posts_url( $author_id );
                $prop_agent_phone = get_the_author_meta( 'fave_author_phone', $author_id );
                $prop_agent_mobile = get_the_author_meta( 'fave_author_mobile', $author_id );
                $prop_agent_whatsapp = get_the_author_meta( 'fave_author_whatsapp', $author_id );
                $agent_num_call = str_replace(array('(',')',' ','-'),'', $prop_agent_num);
                $prop_agent_photo_url = get_the_author_meta( 'fave_author_custom_picture', $author_id );

                $agent_telegram = get_the_author_meta( 'fave_author_telegram', $author_id );
                $agent_lineapp = get_the_author_meta( 'fave_author_line_id', $author_id );

                $prop_agent_email = get_the_author_meta( 'email', $author_id );
                $linkedin = get_the_author_meta( 'fave_author_linkedin', $author_id );

                $agent_args = array();
                $agent_id   = $author_id;
                $agent_args[ 'agent_id' ] = $author_id;
                $agent_args[ 'agent_type' ] = $agent_display;
                $agent_args[ 'agent_skype' ] = get_the_author_meta( 'fave_author_skype', $author_id );
                $agent_args[ 'agent_name' ] = $prop_agent;
                $agent_args[ 'agent_mobile' ] = $prop_agent_mobile;
                $agent_args[ 'agent_mobile_call' ] = str_replace(array('(',')',' ','-'),'', $prop_agent_mobile);

                $agent_args[ 'agent_whatsapp' ] = $prop_agent_whatsapp;
                $agent_args[ 'agent_whatsapp_call' ] = str_replace(array('(',')',' ','-'),'', $prop_agent_whatsapp);
                $agent_args[ 'agent_phone' ] = $prop_agent_phone;
                $agent_args[ 'agent_phone_call' ] = str_replace(array('(',')',' ','-'),'', $prop_agent_phone);
                $agent_args[ 'agent_email' ] = $prop_agent_email;
                $agent_args[ 'link' ] = $prop_agent_permalink;

                $agent_args[ 'agent_position' ] = get_the_author_meta( 'fave_author_title', $author_id );
                $agent_args[ 'agent_company' ] = get_the_author_meta( 'fave_author_company', $author_id );
                $agent_args[ 'agent_service_area' ] = get_the_author_meta( 'fave_author_service_areas', $author_id );
                $agent_args[ 'agent_specialties' ] = get_the_author_meta( 'fave_author_specialties', $author_id );
                $agent_args[ 'agent_tax_no' ] = get_the_author_meta( 'fave_author_tax_no', $author_id );

                $agent_args[ 'facebook' ] = get_the_author_meta( 'fave_author_facebook', $author_id );
                $agent_args[ 'twitter' ] = get_the_author_meta( 'fave_author_twitter', $author_id );
                $agent_args[ 'linkedin' ] = get_the_author_meta( 'fave_author_linkedin', $author_id );
                $agent_args[ 'googleplus' ] = get_the_author_meta( 'fave_author_googleplus', $author_id );
                $agent_args[ 'youtube' ] = get_the_author_meta( 'fave_author_youtube', $author_id );
                $agent_args[ 'instagram' ] = get_the_author_meta( 'fave_author_instagram', $author_id );
                $agent_args[ 'telegram' ] = $agent_telegram;
                $agent_args[ 'lineapp' ] = $agent_lineapp;

                if( empty( $prop_agent_photo_url )) {
                    $agent_args[ 'picture' ] = HOUZEZ_IMAGE. 'profile-avatar.png';
                    $picture = HOUZEZ_IMAGE. 'profile-avatar.png';
                } else {
                    $agent_args[ 'picture' ] = $prop_agent_photo_url;
                    $picture = $prop_agent_photo_url;
                }
                $listing_agent_info[] = $agent_args;
                if($is_top) {
                    $listing_agent .= houzez_get_agent_info_top( $agent_args, 'agent_form' );
                } else {
                    if($luxury) {
                        $listing_agent .= houzez_get_agent_info_bottom_v2( $agent_args, 'agent_form' );
                    } else {
                        $listing_agent .= houzez_get_agent_info_bottom( $agent_args, 'agent_form' );
                    }
                }

            }

            $return_array['agent_info'] = $listing_agent_info;
            $return_array['agent_data'] = $listing_agent;
            $return_array['is_single_agent'] = $is_single_agent;
            $return_array['agent_email'] = $prop_agent_email;
            $return_array['agent_name'] = $prop_agent;
            $return_array['linkedin'] = $linkedin;
            $return_array['agent_phone'] = $prop_agent_phone;
            $return_array['agent_phone_call'] = str_replace(array('(',')',' ','-'),'', $prop_agent_phone);
            $return_array['agent_mobile'] = $prop_agent_mobile;
            $return_array['agent_mobile_call'] = str_replace(array('(',')',' ','-'),'', $prop_agent_mobile);

            $return_array['agent_whatsapp'] = $prop_agent_whatsapp;
            $return_array['agent_whatsapp_call'] = str_replace(array('(',')',' ','-'),'', $prop_agent_whatsapp);
            $return_array['agent_telegram'] = $agent_telegram;
            $return_array['agent_lineapp'] = $agent_lineapp;
            $return_array['picture'] = $picture;
            $return_array['link'] = $prop_agent_permalink;
            $return_array['agent_type'] = $agent_display;
            $return_array['agent_id'] = $agent_id;
        }

        return $return_array;
    } // End function
}

// houzez_property_clone
add_action( 'wp_ajax_houzez_property_clone', 'houzez_property_clone' );

if ( !function_exists( 'houzez_property_clone' ) ) {
    function houzez_property_clone() {

        // Check if propID is provided
        if ( ! isset( $_POST['propID'] ) ) {
            wp_send_json_error( esc_html__( 'No property ID provided!', 'houzez' ) );
            wp_die();
        }

        // Verify nonce for security
        if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'clone_property_nonce' ) ) {
            wp_send_json_error( esc_html__( 'Security check failed!', 'houzez' ) );
            wp_die();
        }

        global $wpdb;
        $userID = get_current_user_id();
        $new_post_author = $userID;
        $post_id = absint( $_POST['propID'] );

        // Validate post_id
        if ( ! $post_id || get_post_type( $post_id ) !== 'property' ) {
            wp_send_json_error( esc_html__( 'Invalid property ID!', 'houzez' ) );
            wp_die();
        }

        // Get the post author
        $post_author = get_post_field( 'post_author', $post_id );

        // Get agency agents associated with the current user
        $agencyAgentsArray = array();
        $agencyAgents = houzez_get_agency_agents( $userID );
        if ( $agencyAgents ) {
            $agencyAgentsArray = $agencyAgents;
        }

        // Check if the current user has permission to edit the property
        if (
            $post_author != $userID &&
            ! houzez_is_admin() &&
            ! houzez_is_editor() &&
            ! in_array( $post_author, $agencyAgentsArray )
        ) {
            wp_send_json_error( esc_html__( 'You do not have permission to edit this property!', 'houzez' ) );
            wp_die();
        }

        $post = get_post( $post_id );

        if (isset( $post ) && $post != null) {

            /*
             * new post data array
             */
            $args = array(
                'comment_status' => $post->comment_status,
                'ping_status'    => $post->ping_status,
                'post_author'    => $new_post_author,
                'post_content'   => $post->post_content,
                'post_excerpt'   => $post->post_excerpt,
                'post_name'      => $post->post_name,
                'post_parent'    => $post->post_parent,
                'post_password'  => $post->post_password,
                'post_status'    => 'draft',
                'post_title'     => $post->post_title,
                'post_type'      => $post->post_type,
                'to_ping'        => $post->to_ping,
                'menu_order'     => $post->menu_order
            );

            /*
             * insert the post by wp_insert_post() function
             */
            $new_post_id = wp_insert_post( $args );

            /*
             * get all current post terms ad set them to the new post draft
             */
            $taxonomies = get_object_taxonomies($post->post_type); // returns array of taxonomy names for post type, ex array("category", "post_tag");
            foreach ($taxonomies as $taxonomy) {
                $post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
                wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
            }

            /*
             * duplicate all post meta just in two SQL queries
             */
            $post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id");
            if (count($post_meta_infos)!=0) {
                $sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
                foreach ($post_meta_infos as $meta_info) {
                    $meta_key = $meta_info->meta_key;
                    $meta_value = addslashes($meta_info->meta_value);
                    $sql_query_sel[]= "SELECT $new_post_id, '$meta_key', '$meta_value'";
                }
                $sql_query.= implode(" UNION ALL ", $sql_query_sel);
                $wpdb->query($sql_query);
            }

            update_post_meta( $new_post_id, 'fave_featured', 0 );
            update_post_meta( $new_post_id, 'houzez_featured_listing_date', '' );
            update_post_meta( $new_post_id, 'fave_payment_status', 'not_paid' );

            if( houzez_option('auto_property_id', 0) != 0 ) {
                $pattern = houzez_option( 'property_id_pattern' );
                $new_id   = preg_replace( '/{ID}/', $new_post_id, $pattern );
                update_post_meta($new_post_id, 'fave_property_id', $new_id);
            }

            $dashboard_listings = houzez_get_template_link_2('template/user_dashboard_properties.php');
            $dashboard_listings = add_query_arg( 'cloned', 1, $dashboard_listings );

            wp_send_json_success( array(
                'redirect' => esc_url( $dashboard_listings ),
                'message'  => esc_html__( 'Successfully cloned', 'houzez' )
            ) );
            wp_die();

        } else {
            wp_send_json_error( esc_html__( 'Post creation failed, could not find original post:', 'houzez' ) );
            wp_die();
        }

    }
}

// houzez_property_on_hold
add_action( 'wp_ajax_houzez_property_on_hold', 'houzez_property_on_hold' );

if ( !function_exists( 'houzez_property_on_hold' ) ) {
    function houzez_property_on_hold() {

        // Check if propID is provided
        if ( ! isset( $_POST['propID'] ) ) {
            wp_send_json_error( esc_html__( 'No property ID provided!', 'houzez' ) );
            wp_die();
        }

        // Verify nonce for security
        if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'puthold_property_nonce' ) ) {
            wp_send_json_error( esc_html__( 'Security check failed!', 'houzez' ) );
            wp_die();
        }

        global $wpdb;
        $userID = get_current_user_id();
        $post_id = absint( $_POST['propID'] );

        // Validate post_id
        if ( ! $post_id || get_post_type( $post_id ) !== 'property' ) {
            wp_send_json_error( esc_html__( 'Invalid property ID!', 'houzez' ) );
            wp_die();
        }

        // Get the post author
        $post_author = get_post_field( 'post_author', $post_id );

        // Get agency agents associated with the current user
        $agencyAgentsArray = array();
        $agencyAgents = houzez_get_agency_agents( $userID );
        if ( $agencyAgents ) {
            $agencyAgentsArray = $agencyAgents;
        }

        // Check if the current user has permission to edit the property
        if (
            $post_author != $userID &&
            ! houzez_is_admin() &&
            ! houzez_is_editor() &&
            ! in_array( $post_author, $agencyAgentsArray )
        ) {
            wp_send_json_error( esc_html__( 'You do not have permission to edit this property!', 'houzez' ) );
            wp_die();
        }

        $post_status = get_post_status( $post_id );

        if($post_status == 'publish') { 
            $post = array(
                'ID'            => $post_id,
                'post_status'   => 'on_hold'
            );
        } elseif ($post_status == 'on_hold') {
            $post = array(
                'ID'            => $post_id,
                'post_status'   => 'publish'
            );
        } else {
            wp_send_json_error( esc_html__( 'Invalid post status!', 'houzez' ) );
            wp_die();
        }

        $updated_post_id = wp_update_post( $post );

        if ( is_wp_error( $updated_post_id ) ) {
            wp_send_json_error( esc_html__( 'Failed to update property status!', 'houzez' ) );
            wp_die();
        } else {
            wp_send_json_success( esc_html__( 'Property status updated successfully!', 'houzez' ) );
            wp_die();
        }

    }
}

// // houzez_property_mark_sold
// add_action( 'wp_ajax_houzez_property_mark_sold', 'houzez_property_mark_sold' );

// if ( ! function_exists( 'houzez_property_mark_sold' ) ) {
//     function houzez_property_mark_sold() {
//         // Check if propID is provided
//         if ( ! isset( $_POST['propID'] ) ) {
//             wp_send_json_error( esc_html__( 'No property ID provided!', 'houzez' ) );
//             wp_die();
//         }

//         // Verify nonce for security
//         if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'sold_property_nonce' ) ) {
//             wp_send_json_error( esc_html__( 'Security check failed!', 'houzez' ) );
//             wp_die();
//         }

//         global $wpdb;
//         $userID = get_current_user_id();
//         $post_id = absint( $_POST['propID'] );

//         // Validate post_id
//         if ( ! $post_id || get_post_type( $post_id ) !== 'property' ) {
//             wp_send_json_error( esc_html__( 'Invalid property ID!', 'houzez' ) );
//             wp_die();
//         }

//         // Get the post author
//         $post_author = get_post_field( 'post_author', $post_id );

//         // Get agency agents associated with the current user
//         $agencyAgentsArray = array();
//         $agencyAgents = houzez_get_agency_agents( $userID );
//         if ( $agencyAgents ) {
//             $agencyAgentsArray = $agencyAgents;
//         }

//         // Check if the current user has permission to edit the property
//         if (
//             $post_author != $userID &&
//             ! houzez_is_admin() &&
//             ! houzez_is_editor() &&
//             ! in_array( $post_author, $agencyAgentsArray )
//         ) {
//             wp_send_json_error( esc_html__( 'You do not have permission to edit this property!', 'houzez' ) );
//             wp_die();
//         }

//         $post_status = get_post_status( $post_id );

//         // Update post status based on current status
//         if ( $post_status == 'publish' ) {
//             $post = array(
//                 'ID'          => $post_id,
//                 'post_status' => 'houzez_sold',
//             );

//             $mark_sold_status = houzez_option( 'mark_sold_status' );

//             if ( $mark_sold_status !== '' ) {
//                 $mark_sold_status = intval( $mark_sold_status );
//                 wp_set_object_terms( $post_id, $mark_sold_status, 'property_status' );
//             }
//         } elseif ( $post_status == 'houzez_sold' ) {
//             $post = array(
//                 'ID'          => $post_id,
//                 'post_status' => 'publish',
//             );
//         } else {
//             wp_send_json_error( esc_html__( 'Invalid post status!', 'houzez' ) );
//             wp_die();
//         }

//         $updated_post_id = wp_update_post( $post );

//         if ( is_wp_error( $updated_post_id ) ) {
//             wp_send_json_error( esc_html__( 'Failed to update property status!', 'houzez' ) );
//             wp_die();
//         } else {
//             wp_send_json_success( esc_html__( 'Property status updated successfully!', 'houzez' ) );
//             wp_die();
//         }
//     }
// }

// AJAX action registration.
add_action( 'wp_ajax_houzez_property_mark_sold', 'houzez_property_mark_sold' );

if ( ! function_exists( 'houzez_property_mark_sold' ) ) {
    function houzez_property_mark_sold() {
        // Check if the property ID is provided.
        if ( ! isset( $_POST['propID'] ) ) {
            wp_send_json_error( esc_html__( 'No property ID provided!', 'houzez' ) );
        }

        // Verify nonce for security.
        if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'sold_property_nonce' ) ) {
            wp_send_json_error( esc_html__( 'Security check failed!', 'houzez' ) );
        }

        $user_id = get_current_user_id();
        $prop_id = absint( $_POST['propID'] );

        // Process the "mark sold" action.
        $result = houzez_process_property_mark_sold( $prop_id, $user_id );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'msg' => $result->get_error_message() ) );
        } else {
            wp_send_json_success( $result );
        }
    }
}

/**
 * Process marking a property as sold or unsold.
 *
 * @param int $prop_id Property ID.
 * @param int $user_id Current user ID.
 * @return array|WP_Error Result array on success, WP_Error on failure.
 */
if( ! function_exists('houzez_process_property_mark_sold') ) {
    function houzez_process_property_mark_sold( $prop_id, $user_id ) {
        // Sanitize and validate the property ID.
        $prop_id = absint( $prop_id );
        if ( ! $prop_id || 'property' !== get_post_type( $prop_id ) ) {
            return new WP_Error( 'invalid_property', esc_html__( 'Invalid property ID!', 'houzez' ) );
        }

        // Get the property author.
        $post_author = get_post_field( 'post_author', $prop_id );

        // Retrieve the agency agents associated with the current user.
        $agency_agents = houzez_get_agency_agents( $user_id );
        if ( ! is_array( $agency_agents ) ) {
            $agency_agents = array();
        }

        // Check if the current user has permission to edit this property.
        if ( $post_author != $user_id && ! houzez_is_admin() && ! houzez_is_editor() && ! in_array( $post_author, $agency_agents, true ) ) {
            return new WP_Error( 'permission_denied', esc_html__( 'You do not have permission to edit this property!', 'houzez' ) );
        }

        // Get current post status.
        $post_status = get_post_status( $prop_id );
        $post_update = array( 'ID' => $prop_id );

        if ( 'publish' === $post_status ) {
            // Mark property as sold.
            $post_update['post_status'] = 'houzez_sold';

            // Optionally update the property status taxonomy.
            $mark_sold_status = houzez_option( 'mark_sold_status' );
            if ( '' !== $mark_sold_status ) {
                $mark_sold_status = intval( $mark_sold_status );
                wp_set_object_terms( $prop_id, $mark_sold_status, 'property_status' );
            }
        } elseif ( 'houzez_sold' === $post_status ) {
            // Revert sold property back to published.
            $post_update['post_status'] = 'publish';
        } else {
            return new WP_Error( 'invalid_status', esc_html__( 'Invalid post status!', 'houzez' ) );
        }

        $updated_post_id = wp_update_post( $post_update );
        if ( is_wp_error( $updated_post_id ) ) {
            return new WP_Error( 'update_failed', esc_html__( 'Failed to update property status!', 'houzez' ) );
        }

        return array(
            'success' => true,
            'message'     => esc_html__( 'Property status updated successfully!', 'houzez' ),
        );
    }
}


/*-----------------------------------------------------------------------------------*/
/*  Houzez Invoice Print Property
/*-----------------------------------------------------------------------------------*/
add_action( 'wp_ajax_nopriv_houzez_create_invoice_print', 'houzez_create_invoice_print' );
add_action( 'wp_ajax_houzez_create_invoice_print', 'houzez_create_invoice_print' );

if ( !function_exists( 'houzez_create_invoice_print' ) ) {
    function houzez_create_invoice_print() {

        if(!isset($_POST['invoice_id'])|| !is_numeric($_POST['invoice_id'])){
            exit();
        }

        $houzez_local = houzez_get_localization();
        $invoice_id = intval($_POST['invoice_id']);
        $the_post= get_post( $invoice_id );

        if( $the_post->post_type != 'houzez_invoice' || $the_post->post_status != 'publish' ) {
            exit();
        }

        print  '<html><head><link href="'.get_stylesheet_uri().'" rel="stylesheet" type="text/css" />';
        print  '<html><head><link href="'.get_template_directory_uri().'/css/bootstrap.min.css" rel="stylesheet" type="text/css" />';
        print  '<html><head><link href="'.get_template_directory_uri().'/css/main.css" rel="stylesheet" type="text/css" />';

        if( is_rtl() ) {
            print '<link href="'.get_template_directory_uri().'/css/rtl.css" rel="stylesheet" type="text/css" />';
            print '<link href="'.get_template_directory_uri().'/css/bootstrap-rtl.min.css" rel="stylesheet" type="text/css" />';
        }
        print '</head>';
        print  '<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script><script>$(window).load(function(){ print(); });</script>';
        print  '<body class="print-page">';

        // Get user ID from the invoice post meta or similar
        $user_id_from_invoice = get_post_meta($invoice_id, 'HOUZEZ_invoice_buyer', true);

        // Get user info by user ID
        $user_info = get_userdata($user_id_from_invoice);

        // Check if user exists
        if ($user_info) {
            $userID = $user_info->ID;
            $user_login = $user_info->user_login;
            $user_email = $user_info->user_email;
            $first_name = $user_info->first_name;
            $last_name = $user_info->last_name;
        } 

        $user_address = get_user_meta( $userID, 'fave_author_address', true);
        if( !empty($first_name) && !empty($last_name) ) {
            $fullname = $first_name.' '.$last_name;
        } else {
            $fullname = $current_user->display_name;
        }
        $invoice_id = $_REQUEST['invoice_id'];
        $post = get_post( $invoice_id );
        $invoice_data = houzez_get_invoice_meta( $invoice_id );

        $publish_date = $post->post_date;
        $publish_date = date_i18n( get_option('date_format'), strtotime( $publish_date ) );
        $invoice_logo = houzez_option( 'invoice_logo', false, 'url' );
        $invoice_company_name = houzez_option( 'invoice_company_name' );
        $invoice_address = houzez_option( 'invoice_address' );
        $invoice_phone = houzez_option( 'invoice_phone' );
        $invoice_additional_info = houzez_option( 'invoice_additional_info' );
        $invoice_thankyou = houzez_option( 'invoice_thankyou' );
        ?>
        <div class="print-main-wrap">
            <div class="print-wrap">
                <div class="invoice-wrap">
                    <div class="row">
                        <div class="col-md-9 col-sm-12">
                            <div class="invoice-logo mb-3">
                                <div class="logo">
                                    <?php if( !empty($invoice_logo) ) { ?>
                                        <img src="<?php echo esc_url($invoice_logo); ?>" alt="logo">
                                    <?php } ?>
                                </div>
                            </div>
                        </div><!-- col-md-9 col-sm-12 -->
                        <div class="col-md-3 col-sm-12">
                            <div class="invoice-date mb-3">
                                <ul class="list-unstyled">
                                    <li>
                                        <strong><?php esc_html_e('Invoice', 'houzez'); ?>:</strong> 
                                        <?php echo esc_attr($invoice_id); ?>
                                    </li>
                                    <li>
                                        <strong><?php esc_html_e('Date', 'houzez'); ?>:</strong> 
                                        <?php echo $publish_date; ?>
                                    </li>
                                </ul>
                            </div>
                        </div><!-- col-md-3 col-sm-12 -->
                    </div><!-- row -->

                    <div class="invoice-spacer mb-5"></div>
                    
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <ul class="list-unstyled">
                                <li><strong><?php esc_html_e('To:', 'houzez'); ?>:</strong></li>
                                <li><?php echo esc_attr($fullname); ?></li>
                                <?php if( !empty($user_address)) { ?>
                                <li><?php echo esc_attr($user_address); ?></li>
                                <?php } ?>

                                <li><?php esc_html_e('Email:', 'houzez'); ?> <?php echo esc_attr($user_email);?></li>
                            </ul>
                        </div><!-- col-md-6 col-sm-12 -->
                        <div class="col-md-6 col-sm-12">
                            <ul class="list-unstyled">
                                
                                <?php if( !empty($invoice_company_name) ) { ?>
                                <li> 
                                    <strong> <?php echo esc_attr($invoice_company_name); ?>:</strong>
                                </li>
                                <?php } ?>

                                <?php if( !empty($invoice_address) ) { ?>
                                <li><?php echo ($invoice_address); ?></li>
                                <?php } ?>

                                <?php if( !empty($invoice_phone) ) { ?>
                                <li><?php esc_html_e('Phone', 'houzez'); ?>: <?php echo esc_attr($invoice_phone); ?></li>
                                <?php } ?>
                            </ul>

                        </div><!-- col-md-6 col-sm-12 -->
                    </div><!-- row -->

                    <div class="invoice-spacer mb-5"></div>

                    <div class="invoce-content">
                        <ul class="list-unstyled">
                            <li>
                                <strong><?php echo $houzez_local['billing_for']; ?></strong> 
                                <span>
                                    <?php
                                    if( $invoice_data['invoice_billion_for'] != 'package' && $invoice_data['invoice_billion_for'] != 'Package' ) {
                                        echo esc_html($invoice_data['invoice_billion_for']);
                                    } else {
                                        echo esc_html__('Membership Plan', 'houzez').' '. get_the_title( get_post_meta( $invoice_id, 'HOUZEZ_invoice_item_id', true) );
                                    }
                                    ?>
                                </span>
                            </li>

                            <li>
                                <strong><?php echo $houzez_local['billing_type']; ?></strong> 
                                <span><?php echo esc_html( $invoice_data['invoice_billing_type'] ); ?></span>
                            </li>

                            <li>
                                <strong><?php echo $houzez_local['payment_method']; ?></strong> 
                                <span>
                                    <?php if( $invoice_data['invoice_payment_method'] == 'Direct Bank Transfer' ) {
                                        echo $houzez_local['bank_transfer'];
                                    } else {
                                        echo $invoice_data['invoice_payment_method'];
                                    } ?>
                                </span>
                            </li>

                            <li>
                                <strong><?php echo $houzez_local['invoice_price']; ?>:</strong> 
                                <span><?php echo houzez_get_invoice_price( $invoice_data['invoice_item_price'] )?></span>
                            </li>
                        </ul>
                    </div><!-- invoce-content -->

                    <div class="invoice-spacer mb-5"></div>
                    
                    <?php if( !empty($invoice_additional_info) || !empty($invoice_thankyou) ) { ?>
        
                        <?php if( !empty($invoice_additional_info)) { ?>
                        <div class="invoce-information">
                            <p><strong><?php echo esc_html__('Additional Information:', 'houzez'); ?>:</strong></p>
                            <p><?php echo $invoice_additional_info; ?> </p>
                        </div><!-- invoce-information -->
                        <?php } ?>
                    
                    <div class="invoice-spacer mb-5"></div>

                    <p><strong><?php echo $invoice_thankyou; ?></strong></p>
                    <?php } ?>

                </div><!-- invoice-wrap -->


            </div><!-- print-wrap -->
        </div><!-- print-main-wrap -->
        
        <?php

        print '</body></html>';
        wp_die();
    }
}


/* --------------------------------------------------------------------------
* Property delete ajax
* --------------------------------------------------------------------------- */
// Add action for AJAX delete property
add_action( 'wp_ajax_houzez_delete_property', 'houzez_delete_property' );

if ( ! function_exists( 'houzez_delete_property' ) ) {

    function houzez_delete_property() {
        
        // Verify nonce for security
        if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'delete_my_property_nonce' ) ) {
            wp_send_json_error( esc_html__( 'Security check failed!', 'houzez' ) );
            wp_die();
        }

        // Check if prop_id is provided
        if ( ! isset( $_POST['prop_id'] ) ) {
            wp_send_json_error( esc_html__( 'No Property ID found', 'houzez' ) );
            wp_die();
        }

        $propID = absint( $_POST['prop_id'] );

        if ( ! $propID ) {
            wp_send_json_error( esc_html__( 'Invalid Property ID', 'houzez' ) );
            wp_die();
        }

        // Check if the post exists and is of the correct post type
        $post = get_post( $propID );
        if ( ! $post || $post->post_type !== 'property' ) {
            wp_send_json_error( esc_html__( 'Property not found', 'houzez' ) );
            wp_die();
        }

        $userID = get_current_user_id();

        // Get the package user ID
        $packageUserId = $userID;
        $agent_agency_id = houzez_get_agent_agency_id( $userID );
        if ( $agent_agency_id ) {
            $packageUserId = $agent_agency_id;
        }

        // Get agency agents associated with the user
        $agencyAgentsArray = array();
        $agencyAgents = houzez_get_agency_agents( $userID );
        if ( $agencyAgents ) {
            $agencyAgentsArray = $agencyAgents;
        }

        // Get the post author
        $post_author = $post->post_author;

        // Check if the user has permission to delete the property
        if ( $post_author == $userID || houzez_is_admin() || houzez_is_editor() || in_array( $post_author, $agencyAgentsArray ) ) {

            // Delete property attachments if post status is not 'draft'
            if ( get_post_status( $propID ) != 'draft' ) {
                houzez_delete_property_attachments_frontend( $propID );
            }

            // Delete the post
            $deleted = wp_delete_post( $propID, true ); // true to force delete, bypass trash

            if ( ! $deleted ) {
                wp_send_json_error( esc_html__( 'Failed to delete property', 'houzez' ) );
                wp_die();
            }

            // Increment package listings count
            houzez_plusone_package_listings( $packageUserId );

            // Build dashboard listings URL
            $dashboard_listings = houzez_get_template_link_2( 'template/user_dashboard_properties.php' );
            $dashboard_listings = add_query_arg( 'deleted', 1, $dashboard_listings );

            wp_send_json_success( array(
                'redirect' => esc_url( $dashboard_listings ),
                'message'  => esc_html__( 'Property Deleted', 'houzez' )
            ) );
            wp_die();

        } else {
            wp_send_json_error( esc_html__( 'Permission denied', 'houzez' ) );
            wp_die();
        }

    }

}



/* --------------------------------------------------------------------------
* Property load more
* --------------------------------------------------------------------------- */
add_action( 'wp_ajax_nopriv_houzez_loadmore_properties', 'houzez_loadmore_properties' );
add_action( 'wp_ajax_houzez_loadmore_properties', 'houzez_loadmore_properties' );

if ( !function_exists( 'houzez_loadmore_properties' ) ) {
    function houzez_loadmore_properties() {
        global $houzez_local;

        $houzez_local = houzez_get_localization();
        $fake_loop_offset = 0; 

        $tax_query = array();
        $meta_query = array();

        $card_version = sanitize_text_field($_POST['card_version']);
        $property_type = houzez_traverse_comma_string($_POST['type']);
        $property_status = houzez_traverse_comma_string($_POST['status']);
        $property_state = houzez_traverse_comma_string($_POST['state']);
        $property_city = houzez_traverse_comma_string($_POST['city']);
        $property_country = houzez_traverse_comma_string($_POST['country']);
        $property_area = houzez_traverse_comma_string($_POST['area']);
        $property_label = houzez_traverse_comma_string($_POST['label']);
        $houzez_user_role = $_POST['user_role'];
        $featured_prop = $_POST['featured_prop'];
        $posts_limit = $_POST['prop_limit'];
        $sort_by = $_POST['sort_by'];
        $offset = $_POST['offset'];
        $paged = $_POST['paged'];

        $property_ids = $_POST['property_ids'];
        $min_price = $_POST['min_price'];
        $max_price = $_POST['max_price'];
        $min_beds = $_POST['min_beds'];
        $max_beds = $_POST['max_beds'];
        $min_baths = $_POST['min_baths'];
        $max_baths = $_POST['max_baths'];
        $properties_by_agents = houzez_traverse_comma_string($_POST['agents']);
        $properties_by_agencies = houzez_traverse_comma_string($_POST['agencies']);
        $post_status = $_POST['post_status'];

        $wp_query_args = array(
            'ignore_sticky_posts' => 1
        );

        if( !empty($houzez_user_role) ) {
            $role_ids = houzez_author_ids_by_role( $houzez_user_role );
            if (!empty($role_ids)) {
                $wp_query_args['author__in'] = $role_ids;
            }
        }

        if (!empty($property_type)) {
            $tax_query[] = array(
                'taxonomy' => 'property_type',
                'field' => 'slug',
                'terms' => $property_type
            );
        }

        if (!empty($property_status)) {
            $tax_query[] = array(
                'taxonomy' => 'property_status',
                'field' => 'slug',
                'terms' => $property_status
            );
        }
        if (!empty($property_country)) {
            $tax_query[] = array(
                'taxonomy' => 'property_country',
                'field' => 'slug',
                'terms' => $property_country
            );
        }
        if (!empty($property_state)) {
            $tax_query[] = array(
                'taxonomy' => 'property_state',
                'field' => 'slug',
                'terms' => $property_state
            );
        }
        if (!empty($property_city)) {
            $tax_query[] = array(
                'taxonomy' => 'property_city',
                'field' => 'slug',
                'terms' => $property_city
            );
        }
        if (!empty($property_area)) {
            $tax_query[] = array(
                'taxonomy' => 'property_area',
                'field' => 'slug',
                'terms' => $property_area
            );
        }
        if (!empty($property_label)) {
            $tax_query[] = array(
                'taxonomy' => 'property_label',
                'field' => 'slug',
                'terms' => $property_label
            );
        }

        if ( $sort_by == 'a_title' ) {
            $wp_query_args['orderby'] = 'title';
            $wp_query_args['order'] = 'ASC';
        } else if ( $sort_by == 'd_title' ) {
            $wp_query_args['orderby'] = 'title';
            $wp_query_args['order'] = 'DESC';
        } else if ( $sort_by == 'a_price' ) {
            $wp_query_args['orderby'] = 'meta_value_num';
            $wp_query_args['meta_key'] = 'fave_property_price';
            $wp_query_args['order'] = 'ASC';
        } else if ( $sort_by == 'd_price' ) {
            $wp_query_args['orderby'] = 'meta_value_num';
            $wp_query_args['meta_key'] = 'fave_property_price';
            $wp_query_args['order'] = 'DESC';
        } else if ( $sort_by == 'a_date' ) {
            $wp_query_args['orderby'] = 'date';
            $wp_query_args['order'] = 'ASC';
        } else if ( $sort_by == 'd_date' ) {
            $wp_query_args['orderby'] = 'date';
            $wp_query_args['order'] = 'DESC';
        } else if ( $sort_by == 'featured_top' ) {
            $wp_query_args['orderby'] = 'meta_value';
            $wp_query_args['meta_key'] = 'fave_featured';
            $wp_query_args['order'] = 'DESC';
        } else if ( $sort_by == 'featured_first_random' ) {
            $wp_query_args['meta_key'] = 'fave_featured';
            $wp_query_args['orderby'] = 'meta_value DESC rand'; 
        } else if ( $sort_by == 'featured_random' ) {
            $wp_query_args['meta_key'] = 'fave_featured';
            $wp_query_args['meta_value'] = '1';
            $wp_query_args['orderby'] = 'meta_value DESC rand';
        } else if ( $sort_by == 'random' ) {
            $wp_query_args['orderby'] = 'rand';
            $wp_query_args['order'] = 'DESC';
        }

        if (!empty($min_price) && !empty($max_price)) {
            $min_price = doubleval(houzez_clean($min_price));
            $max_price = doubleval(houzez_clean($max_price));

            if ($min_price >= 0 && $max_price > $min_price) {
                $meta_query[] = array(
                    'key' => 'fave_property_price',
                    'value' => array($min_price, $max_price),
                    'type' => 'NUMERIC',
                    'compare' => 'BETWEEN',
                );
            }
        } else if (!empty($min_price)) {
            $min_price = doubleval(houzez_clean($min_price));
            if ($min_price >= 0) {
                $meta_query[] = array(
                    'key' => 'fave_property_price',
                    'value' => $min_price,
                    'type' => 'NUMERIC',
                    'compare' => '>=',
                );
            }
        } else if (!empty($max_price)) {
            $max_price = doubleval(houzez_clean($max_price));
            if ($max_price >= 0) {
                $meta_query[] = array(
                    'key' => 'fave_property_price',
                    'value' => $max_price,
                    'type' => 'NUMERIC',
                    'compare' => '<=',
                );
            }
        }

        if ( !empty($min_beds) && !empty($max_beds) ) {
            $min_beds = intval($min_beds);
            $max_beds = intval($max_beds);

            if ($min_beds > 0 && $max_beds >= $min_beds) {
                $meta_query[] = array(
                    'key' => 'fave_property_bedrooms',
                    'value' => array($min_beds, $max_beds),
                    'type' => 'NUMERIC',
                    'compare' => 'BETWEEN',
                );
            }
        } else if ( !empty($min_beds) ) {
            $min_beds = intval($min_beds);
            if ($min_beds > 0) {
                $meta_query[] = array(
                    'key' => 'fave_property_bedrooms',
                    'value' => $min_beds,
                    'type' => 'NUMERIC',
                    'compare' => '>=',
                );
            }
        } else if ( ! empty($max_beds) ) {
            $max_beds = intval($max_beds);
            if ($max_beds > 0) {
                $meta_query[] = array(
                    'key' => 'fave_property_bedrooms',
                    'value' => $max_beds,
                    'type' => 'NUMERIC',
                    'compare' => '<=',
                );
            }
        }

        if ( !empty($min_baths) && !empty($max_baths) ) {
            $min_baths = intval($min_baths);
            $max_baths = intval($max_baths);

            if ($min_baths > 0 && $max_baths >= $min_baths) {
                $meta_query[] = array(
                    'key' => 'fave_property_bathrooms',
                    'value' => array($min_baths, $max_baths),
                    'type' => 'NUMERIC',
                    'compare' => 'BETWEEN',
                );
            }
        } else if ( !empty($min_baths) ) {
            $min_baths = intval($min_baths);
            if ($min_baths > 0) {
                $meta_query[] = array(
                    'key' => 'fave_property_bathrooms',
                    'value' => $min_baths,
                    'type' => 'NUMERIC',
                    'compare' => '>=',
                );
            }
        } else if ( !empty($max_baths) ) {
            $max_baths = intval($max_baths);
            if ($max_baths > 0) {
                $meta_query[] = array(
                    'key' => 'fave_property_bathrooms',
                    'value' => $max_baths,
                    'type' => 'NUMERIC',
                    'compare' => '<=',
                );
            }
        }

        if ( !empty($properties_by_agents) ) {
            $meta_query[] = array(
                'key'     => 'fave_agents',
                'value'   => $properties_by_agents,
                'compare' => 'IN',
            );
            $meta_query[] = array(
                'key'     => 'fave_agent_display_option',
                'value'   => 'agent_info',
                'compare' => '=',
            );
        }

        if ( !empty($properties_by_agencies) ) {
            $meta_query[] = array(
                'key'     => 'fave_property_agency',
                'value'   => $properties_by_agencies,
                'compare' => 'IN',
            );

            $meta_query[] = array(
                'key'     => 'fave_agent_display_option',
                'value'   => 'agency_info',
                'compare' => '=',
            );
        }

        if (!empty($featured_prop)) {
            if ($featured_prop == "yes") {
                $meta_query[] = [
                    'key' => 'fave_featured',
                    'value' => '1',
                    'compare' => '='
                ];
            } else {
                $meta_query[] = [
                    'relation' => 'OR',
                    [
                        'key' => 'fave_featured',
                        'value' => '0',
                        'compare' => '='
                    ],
                    [
                        'key' => 'fave_featured',
                        'compare' => 'NOT EXISTS'
                    ]
                ];
            }
        }

        $property_ids_array = explode(',', $property_ids);

        if (!empty($property_ids)) {
            $wp_query_args['post__in'] = $property_ids_array;
        }

        $tax_count = count( $tax_query );
    
        if( $tax_count > 1 ) {
            $tax_query['relation'] = 'AND';
        }
        if( $tax_count > 0 ){
            $wp_query_args['tax_query'] = $tax_query;
        }

        $meta_count = count($meta_query);
        if( $meta_count > 1 ) {
            $meta_query['relation'] = 'AND';
        }
        if ($meta_count > 0) {
            $wp_query_args['meta_query'] = $meta_query;
        }

        if( $post_status == 'publish' ) {
            $wp_query_args['post_status'] = 'publish';
        } else if( $post_status == 'houzez_sold' ) {
            $wp_query_args['post_status'] = 'houzez_sold';
        } else {
            $wp_query_args['post_status'] = array('publish', 'houzez_sold');
        }

        if (empty($posts_limit)) {
            $posts_limit = get_option('posts_per_page');
        }
        $wp_query_args['posts_per_page'] = $posts_limit;

        if (!empty($paged)) {
            $wp_query_args['paged'] = $paged;
        } else {
            $wp_query_args['paged'] = 1;
        }

        if (!empty($offset) and $paged > 1) {
            $wp_query_args['offset'] = $offset + ( ($paged - 1) * $posts_limit) ;
        } else {
            $wp_query_args['offset'] = $offset ;
        }

        $fake_loop_offset = $offset;

        $wp_query_args['post_type'] = 'property';
        
        $the_query = new WP_Query($wp_query_args);
            $response = array(
            'html' => '',
            'has_more_posts' => false
        );

        if ($the_query->have_posts()) {
            ob_start();
            while ($the_query->have_posts()) : $the_query->the_post();
                global $post;
                setup_postdata($post);
                get_template_part('template-parts/listing/'.$card_version);
            endwhile;
            wp_reset_postdata();
            $response['html'] = ob_get_clean();

            // Check if there are more posts
            $response['has_more_posts'] = ($the_query->found_posts > ($the_query->query_vars['paged'] * $the_query->query_vars['posts_per_page']));
        } else {
            $response['html'] = 'no_result';
        }

        wp_send_json($response);
        wp_die();
    }
}


if(!function_exists('houzez_get_custom_add_listing_field')) {
    function houzez_get_custom_add_listing_field($key) {

        if(class_exists('Houzez_Fields_Builder')) {

            $field_array = Houzez_Fields_Builder::get_field_by_slug($key);
            $field_title = houzez_wpml_translate_single_string($field_array['label']);
            $placeholder = houzez_wpml_translate_single_string($field_array['placeholder']);

            $field_name = $field_array['field_id'];
            $field_type = $field_array['type'];
            $field_options = $field_array['fvalues'];

            $selected = '';
            if (!houzez_edit_property()) {
                $selected = 'selected=selected';
            }

            $data_value = '';
            if (houzez_edit_property()) {
                global $prop_meta_data;
                $data_value = isset( $prop_meta_data[ 'fave_'.$key ] ) ? ( ( 'checkbox_list' == $field_type || 'radio' == $field_type ) || 'multiselect' == $field_type ? $prop_meta_data[ 'fave_'.$key ] : $prop_meta_data[ 'fave_'.$key ][0] ) : '';
            }


            if($field_type == 'select' ) { ?>

                <div class="form-group">
                    <label for="<?php echo esc_attr($field_name); ?>">
                        <?php echo $field_title.houzez_required_field($field_name); ?>
                    </label>

                    <select name="<?php echo esc_attr($field_name);?>" data-size="5" class="selectpicker <?php houzez_required_field_2($field_name); ?> form-control bs-select-hidden" title="<?php echo esc_attr($placeholder); ?>" data-live-search="false">
                        
                        <option <?php echo esc_attr($selected); ?> value=""><?php echo esc_attr($placeholder); ?> </option>
                        <?php
                        $options = unserialize($field_options);
                        
                        foreach ($options as $key => $val) {
                            $val = houzez_wpml_translate_single_string($val);
                            
                            $selected_val = houzez_get_field_meta($field_name);
                            $key = trim($key);

                            echo '<option '.selected($selected_val, $key, false).' value="'.esc_attr($key).'">'.esc_attr($val).'</option>';
                        }
                        ?>

                    </select><!-- selectpicker -->
                </div>

            <?php
            } else if($field_type == 'multiselect' ) { ?>

                <div class="form-group">
                    <label for="<?php echo esc_attr($field_name); ?>">
                        <?php echo $field_title.houzez_required_field($field_name); ?>
                    </label>

                    <select name="<?php echo esc_attr($field_name).'[]'; ?>" data-size="5" data-actions-box="true" class="selectpicker <?php houzez_required_field_2($field_name); ?> form-control bs-select-hidden" title="<?php echo esc_attr($placeholder); ?>" data-live-search="false" data-select-all-text="<?php echo houzez_option('cl_select_all', 'Select All'); ?>" data-deselect-all-text="<?php echo houzez_option('cl_deselect_all', 'Deselect All'); ?>" data-count-selected-text="{0}" multiple>
                        
                        <?php
                        $options = unserialize($field_options);
                        
                        foreach ($options as $key => $val) {
                            $val = houzez_wpml_translate_single_string($val);
                            $key = trim($key);
                            $selected = ( houzez_edit_property() && ! empty( $data_value ) && in_array( $key, $data_value ) ) ? 'selected' : '';

                            echo '<option '.esc_attr($selected).' value="'.esc_attr($key).'">'.esc_attr($val).'</option>';
                        }
                        ?>

                    </select><!-- selectpicker -->
                </div>

            <?php
            } else if( $field_type == 'checkbox_list' ) { ?>

                <div class="form-group">
                    <label for="<?php echo esc_attr($field_name); ?>">
                        <?php echo $field_title.houzez_required_field($field_name); ?>
                    </label>
                    <div class="features-list houzez-custom-field">
                        <?php
                        $options    = unserialize( $field_options );
                        $options    = explode( ',', $options );
                        $options    = array_filter( array_map( 'trim', $options ) );
                        $checkboxes = array_combine( $options, $options );

                        foreach ($checkboxes as $checkbox) {

                            $checked = ( houzez_edit_property() && ! empty( $data_value ) && in_array( $checkbox, $data_value ) ) ? 'checked' : '';
                            $checkbox_title = houzez_wpml_translate_single_string($checkbox);
                            echo '<label class="control control--checkbox">';
                                echo '<input type="checkbox" '.esc_attr($checked).' name="'.esc_attr($field_name).'[]" value="'.esc_attr($checkbox).'">'.esc_attr($checkbox_title);
                                echo '<span class="control__indicator"></span>';
                            echo '</label>';

                        }
                        ?>
                    </div><!-- features-list -->
                </div>

            <?php
            } else if( $field_type == 'radio' ) { ?>

                <div class="form-group">
                    <label for="<?php echo esc_attr($field_name); ?>">
                        <?php echo $field_title.houzez_required_field($field_name); ?>
                    </label>
                    <div class="features-list houzez-custom-field">
                        <?php
                        $options    = unserialize( $field_options );
                        $options    = explode( ',', $options );
                        $options    = array_filter( array_map( 'trim', $options ) );
                        $radios     = array_combine( $options, $options );

                        echo '<label class="control control--radio">';
                            echo '<input type="radio" name="'.esc_attr($field_name).'" value="">'.esc_html__('None', 'houzez');
                            echo '<span class="control__indicator"></span>';
                        echo '</label>';

                        foreach ($radios as $radio) {

                            $radio_checked = ( houzez_edit_property() && ! empty( $data_value ) && in_array( $radio, $data_value ) ) ? 'checked' : '';

                            $radio_title = houzez_wpml_translate_single_string($radio);
                            echo '<label class="control control--radio">';
                                echo '<input type="radio" '.esc_attr($radio_checked).' name="'.esc_attr($field_name).'" value="'.esc_attr($radio).'">'.esc_attr($radio_title);
                                echo '<span class="control__indicator"></span>';
                            echo '</label>';

                        }
                        ?>
                    </div><!-- features-list -->
                </div>

            <?php
            } else if( $field_type == 'number' ) { ?>

                <div class="form-group">
                    <label for="<?php echo esc_attr($field_name); ?>">
                        <?php echo $field_title.houzez_required_field($field_name); ?>
                    </label>
                    <input name="<?php echo esc_attr($field_name);?>" <?php houzez_required_field_2($field_name); ?> type="number" class="form-control" value="<?php
                    if (houzez_edit_property()) {
                        houzez_field_meta($field_name);
                    } ?>" placeholder="<?php echo esc_attr($placeholder);?>">
                </div>

            <?php
            } else if( $field_type == 'textarea' ) { ?>

                <div class="form-group">
                    <label for="<?php echo esc_attr($field_name); ?>">
                        <?php echo $field_title.houzez_required_field($field_name); ?>
                    </label>
                    <textarea class="form-control" name="<?php echo esc_attr($field_name);?>" placeholder="<?php echo esc_attr($placeholder);?>" <?php houzez_required_field_2($field_name); ?>><?php
                    if (houzez_edit_property()) {
                        houzez_field_meta($field_name);
                    } ?></textarea>
                </div>

            <?php
            } else if( $field_type == 'url' ) { ?>

                <div class="form-group">
                    <label for="<?php echo esc_attr($field_name); ?>">
                        <?php echo $field_title.houzez_required_field($field_name); ?>
                    </label>

                    <input name="<?php echo esc_attr($field_name);?>" <?php houzez_required_field_2($field_name); ?> type="url" class="form-control" value="<?php
                    if (houzez_edit_property()) {
                        houzez_field_meta($field_name);
                    } ?>" placeholder="<?php echo esc_attr($placeholder);?>">
                </div>

            <?php
            } else { ?>

                <div class="form-group">
                    <label for="<?php echo esc_attr($field_name); ?>">
                        <?php echo $field_title.houzez_required_field($field_name); ?>
                    </label>

                    <input name="<?php echo esc_attr($field_name);?>" <?php houzez_required_field_2($field_name); ?> type="text" class="form-control" value="<?php
                    if (houzez_edit_property()) {
                        houzez_field_meta($field_name);
                    } ?>" placeholder="<?php echo esc_attr($placeholder);?>">
                </div>

            <?php
            } 

        }
    }
}

add_action('wp_ajax_load_lightbox_content', 'houzez_listing_model');
add_action('wp_ajax_nopriv_load_lightbox_content', 'houzez_listing_model');

if( !function_exists('houzez_listing_model')) {
    function houzez_listing_model() {
        $listing_id = isset($_POST['listing_id']) ? $_POST['listing_id'] : '';

        if(empty($listing_id)) {
            echo esc_html__('Nothing found', 'houzez');
            return;
        }
        

        $lightbox_logo = houzez_option( 'lightbox_logo', false, 'url' );

        $userID      =   get_current_user_id();
        $fav_option = 'houzez_favorites-'.$userID;
        $fav_option = get_option( $fav_option );
        $icon = $key = '';
        if( !empty($fav_option) ) {
            $key = array_search($listing_id, $fav_option);
        }
        if( $key != false || $key != '' ) {
            $icon = 'text-danger';
        }
    
        $prop_id = houzez_get_listing_data_by_id('property_id', $listing_id);
        $prop_size = houzez_get_listing_data_by_id('property_size', $listing_id);
        $land_area = houzez_get_listing_data_by_id('property_land', $listing_id);
        $bedrooms = houzez_get_listing_data_by_id('property_bedrooms', $listing_id);
        $rooms = houzez_get_listing_data_by_id('property_rooms', $listing_id);
        $bathrooms = houzez_get_listing_data_by_id('property_bathrooms', $listing_id);
        $year_built = houzez_get_listing_data_by_id('property_year', $listing_id);
        $garage = houzez_get_listing_data_by_id('property_garage', $listing_id);
        $property_type = houzez_taxonomy_simple_2('property_type', $listing_id);
        $garage_size = houzez_get_listing_data_by_id('property_garage_size', $listing_id);

        $term_status = wp_get_post_terms( $listing_id, 'property_status', array("fields" => "all"));
        $term_label = wp_get_post_terms( $listing_id, 'property_label', array("fields" => "all"));

        $size = 'houzez-gallery';
        $properties_images = rwmb_meta( 'fave_property_images', 'type=plupload_image&size='.$size, $listing_id );

        $token = wp_generate_password(5, false, false);

    ?>
    <div class="modal-header">
        <div class="d-flex align-items-center">
            <div class="lightbox-logo flex-grow-1">
                <?php if(!empty($lightbox_logo)) { ?>
                <img class="img-fluid" src="<?php echo esc_url($lightbox_logo); ?>" alt="logo">
                <?php } ?>
            </div><!-- lightbox-logo -->
            <div class="lightbox-tools">
                <ul class="list-inline">
                    <?php if( houzez_option('disable_favorite') != 0 ) { ?>
                    <li class="list-inline-item btn-favorite">
                        <a class="add-favorite-js" data-listid="<?php echo intval($listing_id)?>" href="#"><i class="houzez-icon icon-love-it mr-2 <?php echo esc_attr($icon); ?>"></i> <span class="display-none"><?php echo houzez_option('cl_favorite'); ?></span></a>
                    </li>
                    <?php } ?>
                </ul>
            </div><!-- lightbox-tools -->
        </div><!-- d-flex -->
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div><!-- modal-header -->

    <div class="modal-body clearfix">

        <div class="lightbox-gallery-wrap">
            <a class="btn-expand">
                <i class="houzez-icon icon-expand-3"></i>
            </a>
            
            <?php  if( !empty($properties_images) && count($properties_images)) { ?>
            <div class="lightbox-gallery">
                <div id="preview-js-<?php echo esc_attr($token); ?>" class="lightbox-slider">
                    
                    <?php
                    $lightbox_caption = houzez_option('lightbox_caption', 0); 
                    foreach( $properties_images as $prop_image_id => $prop_image_meta ) {
                        $output = '';
                        $output .= '<div>';
                            $output .= '<img class="img-fluid" src="'.esc_url( $prop_image_meta['full_url'] ).'" alt="'.esc_attr($prop_image_meta['alt']).'" title="'.esc_attr($prop_image_meta['title']).'">';

                            if( !empty($prop_image_meta['caption']) && $lightbox_caption != 0 ) {
                                $output .= '<span class="hz-image-caption">'.esc_attr($prop_image_meta['caption']).'</span>';
                            }

                        $output .= '</div>';

                        echo $output;
                    }
                    ?>
                    
                </div>
            </div><!-- lightbox-gallery -->
            <?php 
            } else { 
                $featured_image_url = houzez_get_image_url('full', $listing_id);
                echo '<div>
                    <img class="img-fluid" src="'.esc_url($featured_image_url[0]).'">
                </div>';
            } ?>

        </div><!-- lightbox-gallery-wrap -->


        <div class="lightbox-content-wrap lightbox-form-wrap">
        
            <div class="labels-wrap labels-right"> 
                <?php 
                if( !empty($term_status) ) {
                    foreach( $term_status as $status ) {
                        $status_id = $status->term_id;
                        $status_name = $status->name;
                        echo '<a href="'.get_term_link($status_id).'" class="label-status label status-color-'.intval($status_id).'">
                                '.esc_attr($status_name).'
                            </a>';
                    }
                }

                if( !empty($term_label) ) {
                    foreach( $term_label as $label ) {
                        $label_id = $label->term_id;
                        $label_name = $label->name;
                        echo '<a href="'.get_term_link($label_id).'" class="label label-color-'.intval($label_id).'">
                                '.esc_attr($label_name).'
                            </a>';
                    }
                }
                ?>       
            </div>
            
            <h3 class="item-title">
                <a href="<?php echo esc_url(get_permalink($listing_id)); ?>"><?php echo get_the_title($listing_id); ?></a>
            </h3><!-- item-title -->

            <?php 
            $address_composer = houzez_option('listing_address_composer');
            $enabled_data = isset($address_composer['enabled']) ? $address_composer['enabled'] : [];
            $temp_array = array();

            if ($enabled_data) {
                unset($enabled_data['placebo']);
                foreach ($enabled_data as $key=>$value) {

                    if( $key == 'address' ) {
                        $map_address = houzez_get_listing_data_by_id('property_map_address', $listing_id);

                        if( $map_address != '' ) {
                            $temp_array[] = $map_address;
                        }

                    } else if( $key == 'streat-address' ) {
                        $property_address = houzez_get_listing_data_by_id('property_address', $listing_id);

                        if( $property_address != '' ) {
                            $temp_array[] = $property_address;
                        }

                    } else if( $key == 'country' ) {
                        $country = houzez_taxonomy_simple_2('property_country', $listing_id);

                        if( $country != '' ) {
                            $temp_array[] = $country;
                        }

                    } else if( $key == 'state' ) {
                        $state = houzez_taxonomy_simple_2('property_state', $listing_id);

                        if( $state != '' ) {
                            $temp_array[] = $state;
                        }

                    } else if( $key == 'city' ) {
                        $city = houzez_taxonomy_simple_2('property_city', $listing_id);

                        if( $city != '' ) {
                            $temp_array[] = $city;
                        }

                    } else if( $key == 'area' ) {
                        $area = houzez_taxonomy_simple_2('property_area', $listing_id);

                        if( $area != '' ) {
                            $temp_array[] = $area;
                        }

                    }
                }

                $address = join( ", ", $temp_array );

                if( ! empty( $address ) ) {
                    echo '<address class="item-address">';
                        echo $address;
                    echo '</address>';
                }
            }
            ?>
            
            <ul class="item-price-wrap hide-on-list">
                <?php echo houzez_listing_price_for_print($listing_id); ?>
            </ul>

            <p><?php echo houzez_get_excerpt(23, $listing_id); ?></p>

            <div class="property-overview-data">
                <?php
                $listing_data_composer = houzez_option('preview_data_composer');
                $data_composer = $listing_data_composer['enabled'];

                $meta_type = houzez_option('preview_meta_type');

                $bd_output = $b_output = $id_output = $garage_output = $area_size_output = $land_output = $year_output = $icon = $icon_bt = $icon_prop_id = $icon_garage = $icon_areasize = $icon_land = $icon_year = $cus_output = $cus_icon = '';
                $i = 0;
                if ($data_composer) {
                    unset($data_composer['placebo']);
                    foreach ($data_composer as $key=>$value) { $i ++;

                        $listing_area_size = houzez_get_listing_area_size( $listing_id );
                        $listing_size_unit = houzez_get_listing_size_unit( $listing_id );

                        $listing_land_size = houzez_get_land_area_size( $listing_id );
                        $listing_land_unit = houzez_get_land_size_unit( $listing_id );

                        if( $key == 'bed' && $bedrooms != '' ) {

                            $bd_output .= '<ul class="list-unstyled flex-fill">';
                                $bd_output .= '<li class="property-overview-item">';
                                    
                                    if(houzez_option('icons_type') == 'font-awesome') {
                                        $icon .= '<i class="'.houzez_option('fa_bed').' mr-1"></i>';

                                    } elseif (houzez_option('icons_type') == 'custom') {
                                        $cus_icon = houzez_option('bed');
                                        if(!empty($cus_icon['url'])) {
                                            $icon .= '<img class="img-fluid mr-1" src="'.esc_url($cus_icon['url']).'" width="16" height="16" alt="'.esc_attr($cus_icon['title']).'">';
                                        }
                                    } else {
                                        $icon .= '<i class="houzez-icon icon-hotel-double-bed-1 mr-1"></i>';
                                    }

                                    if( $meta_type != 'text' ) {
                                        $bd_output .= $icon;
                                    }
                                    
                                    $bd_output .= '<strong>'.esc_attr($bedrooms).'</strong>';
                                    
                                $bd_output .= '</li>';

                                if( $meta_type != 'icons' ) {
                                    $prop_bed_label = ($bedrooms > 1 ) ? houzez_option('glc_bedrooms', 'Bedrooms') : houzez_option('glc_bedroom', 'Bedroom');
                                    $bd_output .= '<li class="h-beds">'.esc_attr($prop_bed_label).'</li>';
                                }

                            $bd_output .= '</ul>';

                            if(!empty($bd_output)) {
                                echo $bd_output;
                            }

                        } else if( $key == 'room' && $rooms != '' ) {

                            $rooms_output .= '<ul class="list-unstyled flex-fill">';
                                $rooms_output .= '<li class="property-overview-item">';
                                    
                                    if(houzez_option('icons_type') == 'font-awesome') {
                                        $room_icon .= '<i class="'.houzez_option('fa_room').' mr-1"></i>';

                                    } elseif (houzez_option('icons_type') == 'custom') {
                                        $cus_icon = houzez_option('room');
                                        if(!empty($cus_icon['url'])) {
                                            $room_icon .= '<img class="img-fluid mr-1" src="'.esc_url($cus_icon['url']).'" width="16" height="16" alt="'.esc_attr($cus_icon['title']).'">';
                                        }
                                    } else {
                                        $room_icon .= '<i class="houzez-icon icon-hotel-double-bed-1 mr-1"></i>';
                                    }

                                    if( $meta_type != 'text' ) {
                                        $rooms_output .= $room_icon;
                                    }
                                    
                                    $rooms_output .= '<strong>'.esc_attr($rooms).'</strong>';
                                    
                                $rooms_output .= '</li>';

                                if( $meta_type != 'icons' ) {
                                    $prop_room_label = ($rooms > 1 ) ? houzez_option('glc_rooms', 'Rooms') : houzez_option('glc_room', 'Room');
                                    $rooms_output .= '<li class="h-beds">'.esc_attr($prop_room_label).'</li>';
                                }

                            $rooms_output .= '</ul>';

                            if(!empty($rooms_output)) {
                                echo $rooms_output;
                            }

                        } elseif( $key == 'bath' && $bathrooms != "" ) {

                            $b_output .= '<ul class="list-unstyled flex-fill">';
                                $b_output .= '<li class="property-overview-item">';
                                    
                                    if(houzez_option('icons_type') == 'font-awesome') {
                                        $icon_bt .= '<i class="'.houzez_option('fa_bath').' mr-1"></i>';

                                    } elseif (houzez_option('icons_type') == 'custom') {
                                        $cus_icon = houzez_option('bath');
                                        if(!empty($cus_icon['url'])) {
                                            $icon_bt .= '<img class="img-fluid mr-1" src="'.esc_url($cus_icon['url']).'" width="16" height="16" alt="'.esc_attr($cus_icon['title']).'">';
                                        }
                                    } else {
                                        $icon_bt .= '<i class="houzez-icon icon-bathroom-shower-1 mr-1"></i>';
                                    }

                                    if( $meta_type != 'text' ) {
                                        $b_output .= $icon_bt;
                                    }
                                    
                                    $b_output .= '<strong>'.esc_attr($bathrooms).'</strong>';
                                    
                                $b_output .= '</li>';

                                if( $meta_type != 'icons' ) {
                                    $prop_bath_label = ($bathrooms > 1 ) ? houzez_option('glc_bathrooms', 'Bathrooms') : houzez_option('glc_bathroom', 'Bathroom');
                                    $b_output .= '<li class="h-baths">'.esc_attr($prop_bath_label).'</li>';
                                }

                            $b_output .= '</ul>';

                            if(!empty($b_output)) {
                                echo $b_output;
                            }

                        } elseif( $key == 'property-id' && $prop_id != "" ) {

                            $id_output .= '<ul class="list-unstyled flex-fill">';
                                $id_output .= '<li class="property-overview-item">';
                                    
                                    if(houzez_option('icons_type') == 'font-awesome') {
                                        $icon_prop_id .= '<i class="'.houzez_option('fa_property-id').' mr-1"></i>';

                                    } elseif (houzez_option('icons_type') == 'custom') {
                                        $cus_icon = houzez_option('property-id');
                                        if(!empty($cus_icon['url'])) {
                                            $icon_prop_id .= '<img class="img-fluid mr-1" src="'.esc_url($cus_icon['url']).'" width="16" height="16" alt="'.esc_attr($cus_icon['title']).'">';
                                        }
                                    } else {
                                        $icon_prop_id .= '<i class="houzez-icon icon-tags mr-1"></i>';
                                    }

                                    if( $meta_type != 'text' ) {
                                        $id_output .= $icon_prop_id;
                                    }
                                    
                                    $id_output .= '<strong>'.esc_attr($prop_id).'</strong>';
                                    
                                $id_output .= '</li>';

                                if( $meta_type != 'icons' ) {
                                    $prop_id_label = houzez_option('glc_listing_id', 'Listing ID');
                                    $id_output .= '<li class="h-property-id">'.esc_attr($prop_id_label).'</li>';
                                }

                            $id_output .= '</ul>';

                            if(!empty($id_output)) {
                                echo $id_output;
                            }

                        } elseif( $key == 'garage' && $garage != "" ) {

                            $garage_output .= '<ul class="list-unstyled flex-fill">';
                                $garage_output .= '<li class="property-overview-item">';
                                    
                                    if(houzez_option('icons_type') == 'font-awesome') {
                                        $icon_garage .= '<i class="'.houzez_option('fa_garage').' mr-1"></i>';

                                    } elseif (houzez_option('icons_type') == 'custom') {
                                        $cus_icon = houzez_option('garage');
                                        if(!empty($cus_icon['url'])) {
                                            $icon_garage .= '<img class="img-fluid mr-1" src="'.esc_url($cus_icon['url']).'" width="16" height="16" alt="'.esc_attr($cus_icon['title']).'">';
                                        }
                                    } else {
                                        $icon_garage .= '<i class="houzez-icon icon-car-1 mr-1"></i>';
                                    }

                                    if( $meta_type != 'text' ) {
                                        $garage_output .= $icon_garage;
                                    }
                                    
                                    $garage_output .= '<strong>'.esc_attr($garage).'</strong>';
                                    
                                $garage_output .= '</li>';

                                if( $meta_type != 'icons' ) {
                                    $prop_garage_label = ($garage > 1 ) ? houzez_option('glc_garages', 'Garages') : houzez_option('glc_garage', 'Garage');
                                    $garage_output .= '<li class="h-garage">'.esc_attr($prop_garage_label).'</li>';
                                }

                            $garage_output .= '</ul>';

                            if(!empty($garage_output)) {
                                echo $garage_output;
                            }

                        } elseif( $key == 'area-size' && $listing_area_size != "" ) {

                            $area_size_output .= '<ul class="list-unstyled flex-fill">';
                                $area_size_output .= '<li class="property-overview-item">';
                                    
                                    if(houzez_option('icons_type') == 'font-awesome') {
                                        $icon_areasize .= '<i class="'.houzez_option('fa_area-size').' mr-1"></i>';

                                    } elseif (houzez_option('icons_type') == 'custom') {
                                        $cus_icon = houzez_option('area-size');
                                        if(!empty($cus_icon['url'])) {
                                            $icon_areasize .= '<img class="img-fluid mr-1" src="'.esc_url($cus_icon['url']).'" width="16" height="16" alt="'.esc_attr($cus_icon['title']).'">';
                                        }
                                    } else {
                                        $icon_areasize .= '<i class="houzez-icon icon-ruler-triangle mr-1"></i>';
                                    }

                                    if( $meta_type != 'text' ) {
                                        $area_size_output .= $icon_areasize;
                                    }
                                    
                                    $area_size_output .= '<strong>'.esc_attr($listing_area_size).'</strong>';
                                    
                                $area_size_output .= '</li>';

                                if( $meta_type != 'icons' ) {
                                    $area_size_output .= '<li class="h-area">'.$listing_size_unit.'</li>';
                                }

                            $area_size_output .= '</ul>';

                            if(!empty($area_size_output)) {
                                echo $area_size_output;
                            }

                        } elseif( $key == 'land-area' && $listing_land_size != "" ) {

                            $land_output .= '<ul class="list-unstyled flex-fill">';
                                $land_output .= '<li class="property-overview-item">';
                                    
                                    if(houzez_option('icons_type') == 'font-awesome') {
                                        $icon_land .= '<i class="'.houzez_option('fa_land-area').' mr-1"></i>';

                                    } elseif (houzez_option('icons_type') == 'custom') {
                                        $cus_icon = houzez_option('land-area');
                                        if(!empty($cus_icon['url'])) {
                                            $icon_land .= '<img class="img-fluid mr-1" src="'.esc_url($cus_icon['url']).'" width="16" height="16" alt="'.esc_attr($cus_icon['title']).'">';
                                        }
                                    } else {
                                        $icon_land .= '<i class="houzez-icon icon-real-estate-dimensions-map mr-1"></i>';
                                    }

                                    if( $meta_type != 'text' ) {
                                        $land_output .= $icon_land;
                                    }
                                    
                                    $land_output .= '<strong>'.esc_attr($listing_land_size).'</strong>';
                                    
                                $land_output .= '</li>';

                                if( $meta_type != 'icons' ) {
                                    $land_output .= '<li class="h-land-area">'.$listing_land_unit.'</li>';
                                }

                            $land_output .= '</ul>';

                            if(!empty($listing_land_size)) {
                                echo $land_output;
                            }

                        }  elseif( $key == 'year-built' && $year_built != "" ) {

                            $year_output .= '<ul class="list-unstyled flex-fill">';
                                $year_output .= '<li class="property-overview-item">';
                                    
                                    if(houzez_option('icons_type') == 'font-awesome') {
                                        $icon_year .= '<i class="'.houzez_option('fa_year-built').' mr-1"></i>';

                                    } elseif (houzez_option('icons_type') == 'custom') {
                                        $cus_icon = houzez_option('year-built');
                                        if(!empty($cus_icon['url'])) {
                                            $icon_year .= '<img class="img-fluid mr-1" src="'.esc_url($cus_icon['url']).'" width="16" height="16" alt="'.esc_attr($cus_icon['title']).'">';
                                        }
                                    } else {
                                        $icon_year .= '<i class="houzez-icon icon-attachment mr-1"></i>';
                                    }

                                    if( $meta_type != 'text' ) {
                                        $year_output .= $icon_year;
                                    }
                                    
                                    $year_output .= '<strong>'.esc_attr($year_built).'</strong>';
                                    
                                $year_output .= '</li>';

                                if( $meta_type != 'icons' ) {
                                    $year_output .= '<li class="h-year-built">'.houzez_option('glc_year_built', 'Year Built').'</li>';
                                }

                            $year_output .= '</ul>';

                            if(!empty($year_built)) {
                                echo $year_output;
                            }

                        } else {
                            
                            $cus_output = '';
                            $custom_icon = '';
                            $cus_data = houzez_get_listing_data_by_id($key, $listing_id);

                            $cus_output .= '<ul class="list-unstyled flex-fill">';
                            $cus_output .= '<li class="property-overview-item">';
                                
                                if(houzez_option('icons_type') == 'font-awesome') {
                                    $custom_icon .= '<i class="'.houzez_option('fa_'.$key).' mr-1"></i>';

                                } elseif (houzez_option('icons_type') == 'custom') {
                                    $cus_icon = houzez_option($key);

                                    if(!empty($cus_icon['url'])) {
                                        $alt = isset($cus_icon['title']) ? $cus_icon['title'] : '';
                                        $custom_icon .= '<img class="img-fluid mr-1" src="'.esc_url($cus_icon['url']).'" width="16" height="16" alt="'.esc_attr($alt).'">';
                                    }
                                } 

                                if( $meta_type != 'text' ) {
                                    $cus_output .= $custom_icon;
                                }
                                
                                $cus_output .= '<strong>'.esc_attr($cus_data).'</strong>';
                                
                            $cus_output .= '</li>';

                            if( $meta_type != 'icons' ) {
                                $cus_output .= '<li class="h-'.$key.'">'.esc_attr($value).'</li>';
                            }

                        $cus_output .= '</ul>';

                        if(!empty($cus_data)) {
                            echo $cus_output;
                        }

                        } // end else
                    if($i == 6)
                        break;
                    }
                }

                ?>
                
            </div>
            
            <a class="btn btn-primary btn-item" href="<?php echo esc_url(get_permalink($listing_id)); ?>">
                <?php echo houzez_option('glc_detail_btn', 'Details'); ?>
            </a><!-- btn-item -->

        </div><!-- lightbox-content-wrap -->
    </div><!-- modal-body -->
    <div class="modal-footer">
        
    </div><!-- modal-footer -->

    <?php
    wp_die();
    }
}

if( !function_exists('houzez_property_images_for_photoswipe') ) {
    function houzez_property_images_for_photoswipe($with_featured = false) {
        global $post;
        $size = 'full';
        $properties_images = rwmb_meta('fave_property_images', 'type=plupload_image&size=' . $size, $post->ID);
        $featured_image = houzez_get_image_url('full');
        
        $items = array();

        if ($with_featured && !empty($featured_image)) {
            $items[$featured_image[0]] = [
                'src' => $featured_image[0],
                'w'   => $featured_image[1],
                'h'   => $featured_image[2]
            ];
        }

        if (!empty($properties_images)) {
            foreach ($properties_images as $image_meta) {
                if (!array_key_exists($image_meta['url'], $items)) { // Check for duplicate
                    $items[$image_meta['url']] = [
                        'src' => $image_meta['url'],
                        'w'   => $image_meta['width'],
                        'h'   => $image_meta['height']
                    ];
                }
            }
        }

        return array_values($items); // Return re-indexed array
    }
}

if ( ! function_exists( 'houzez_taxonomy_pagination' ) ) {
    /**
     * Update Taxonomy Pagination according to theme option
     *
     * @param $query
     */
    function houzez_taxonomy_pagination( $query ) {
        if ( is_tax( 'property_type' ) || is_tax( 'property_status' ) || is_tax( 'property_label' ) || is_tax( 'property_city' ) || is_tax( 'property_feature' ) || is_tax( 'property_country' ) || is_tax( 'property_state' ) || is_tax( 'property_area' ) || is_post_type_archive('property') ) {
            if ( $query->is_main_query() ) {
                $taxonomy_num_posts = houzez_option('taxonomy_num_posts');
                $number_of_prop = intval($taxonomy_num_posts);
                if(!$number_of_prop){
                    $number_of_prop = 9;
                }
                $query->set( 'posts_per_page', $number_of_prop );
            }
        }
    }

    add_action( 'pre_get_posts', 'houzez_taxonomy_pagination' );
}

/*================================================================================
*   Properties ajax tabs
*=================================================================================*/

if ( ! function_exists( 'houzez_properties_ajax_tab_content' ) ) {
    function houzez_properties_ajax_tab_content() {

        if ( ! empty( $_POST['settings'] ) ) {
            $settings = $_POST['settings']; //houzez_clean_v2( $_POST['settings'] );
            
            $html = houzez_listings_tab( $settings );

            echo json_encode( $html );
            die();
        }
    }

    add_action( 'wp_ajax_houzez_get_properties_tab_content', 'houzez_properties_ajax_tab_content' );
    add_action( 'wp_ajax_nopriv_houzez_get_properties_tab_content', 'houzez_properties_ajax_tab_content' );
}

if( ! function_exists('houzez_listings_tab') ) {

    function houzez_listings_tab( $settings ) {
        
        $grid_style = $settings['grid_style'];
        $check_is_ajax = houzez_check_is_ajax();

        if ( $check_is_ajax ) {
            ob_start();
        }
        ?>

        <?php if ( ! $check_is_ajax ) : ?>
        <div class="houzez-tab-content">
        <?php endif; ?>

            <?php 

            $property_type = $property_status = $property_label = $property_country = $property_state = $property_city = $property_area = $properties_by_agents = $properties_by_agencies = '';

                if(!empty($settings['property_type'])) {
                    $property_type = implode (",", $settings['property_type']);
                }

                if(!empty($settings['property_status'])) {
                    $property_status = implode (",", $settings['property_status']);
                }

                if(!empty($settings['property_label'])) {
                    $property_label = implode (",", $settings['property_label']);
                }

                if(!empty($settings['property_country'])) {
                    $property_country = implode (",", $settings['property_country']);
                }

                if(!empty($settings['property_state'])) {
                    $property_state = implode (",", $settings['property_state']);
                }

                if(!empty($settings['property_city'])) {
                    $property_city = implode (",", $settings['property_city']);
                }

                if(!empty($settings['property_area'])) {
                    $property_area = implode (",", $settings['property_area']);
                }

                if( !empty($settings['properties_by_agents']) ) {
                    $properties_by_agents = implode (",", $settings['properties_by_agents']);
                }

                if( !empty($settings['properties_by_agencies']) ) {
                    $properties_by_agencies = implode (",", $settings['properties_by_agencies']);
                }


                $args['module_type'] =  $settings['module_type'];
                $args['houzez_user_role'] =  $settings['houzez_user_role'];
                $args['featured_prop'] =  $settings['featured_prop'];
                $args['posts_limit'] =  $settings['posts_limit'];
                $args['sort_by'] =  $settings['sort_by'];
                $args['offset'] =  $settings['offset'];
                $args['post_status'] =  $settings['post_status'];
                $args['pagination_type'] =  $settings['pagination_type'];

                $args['property_type']   =  $property_type;
                $args['property_status']   =  $property_status;
                $args['property_label']   =  $property_label;
                $args['property_country']   =  $property_country;
                $args['property_state']   =  $property_state;
                $args['property_city']   =  $property_city;
                $args['property_area']   =  $property_area;
                $args['thumb_size'] = $settings['listing_thumb_size'];
                $args['properties_by_agents'] = $properties_by_agents;
                $args['properties_by_agencies'] = $properties_by_agencies;
                $args['min_price'] = $settings['min_price'];
                $args['max_price'] = $settings['max_price'];

                $args['min_beds'] = $settings['min_beds'] ?? '';
                $args['max_beds'] = $settings['max_beds'] ?? '';
                $args['min_baths'] = $settings['min_baths'] ?? '';
                $args['max_baths'] = $settings['max_baths'] ?? '';

                if( $grid_style == 'cards-v1' ) {
                    echo houzez_property_card_v1( $args );
                } elseif( $grid_style == 'cards-v2' ) {
                    echo houzez_property_card_v2( $args );
                } elseif( $grid_style == 'cards-v3' ) {
                    echo houzez_property_card_v3( $args );
                } elseif( $grid_style == 'cards-v5' ) {
                    echo houzez_property_card_v5( $args );
                } elseif( $grid_style == 'cards-v6' ) {
                    echo houzez_property_card_v6( $args );
                } elseif( $grid_style == 'cards-v7' ) {
                    echo houzez_property_card_v7( $args );
                } elseif( $grid_style == 'cards-v8' ) {
                    echo houzez_property_card_v8( $args );
                }
                ?>

        <?php if ( ! $check_is_ajax ) : ?>
        </div>
        <?php endif; ?>

        <?php
        if ( $check_is_ajax ) {
            return [
                'html' => ob_get_clean(),
            ];
        }
    }
}

if( !function_exists('houzez_build_features_array') ) {
    function houzez_build_features_array() {

        $all_features = array();
        $terms = get_terms( array(
            'taxonomy' => 'property_feature',
            'hide_empty' => false,
            'parent'=> 0

        ));

        foreach( $terms as $key => $term ) {

            $temp_array = array();

            $child_terms = get_terms( array(
                'taxonomy'   => 'property_feature',
                'hide_empty' => false,
                'parent'     => $term->term_id
            ));

            $children = array();
            $children_id = array();

            if( is_array($child_terms) ) {
                foreach( $child_terms as $child_key => $child_term ) {
                    $children[] = $child_term->name;
                    $children_id[] = $child_term->term_id;
                }
            }

            $temp_array['term_id']   = $term->term_id;
            $temp_array['name']      = $term->name;
            $temp_array['childs']    = $children;
            $temp_array['child_ids'] = $children_id;

            $all_features[] = $temp_array;
        }

        return $all_features;
    }
}

if( !function_exists('houzez_feature_output') ) {
    function houzez_feature_output( $term_name, $term_id, $property_features ) {

        $feature_icon = '';
        $return_html = '';

        $icon_type  = get_term_meta($term_id, 'fave_feature_icon_type', true);
        $icon_class = get_term_meta($term_id, 'fave_prop_features_icon', true);
        $img_icon   = get_term_meta($term_id, 'fave_feature_img_icon', true);
        $term_link = get_term_link($term_id, 'property_feature');
    
        if($icon_type == 'custom') {
            $icon_url = wp_get_attachment_url( $img_icon );
            if(!empty($icon_url)) {
                $feature_icon = '<img src="'.esc_url($icon_url).'" class="hz-fte-img mr-2">';
            }
        } else {
            if(!empty($icon_class))
            $feature_icon = '<i class="'.$icon_class.' mr-2"></i>';
        }

        // Check if the feature is selected by matching both term_id and name
        $feature_selected = false;
        if (is_array($property_features)) {
            foreach ($property_features as $feature) {
                if ($feature->term_id === $term_id && $feature->name === $term_name) {
                    $feature_selected = true;
                    break;
                }
            }
        }

        if ($feature_selected) {
            if( !empty($feature_icon) ) {
                $return_html = '<li>'.$feature_icon.'<a href="' . esc_url($term_link) . '">' . esc_attr($term_name) . '</a></li>';
            } else {
                $return_html = '<li><i class="houzez-icon icon-check-circle-1 mr-2"></i><a href="' . esc_url($term_link) . '">' . esc_attr($term_name) . '</a></li>';
            }
        }

        return $return_html;
    }
}

if( !function_exists('houzez_feature_submit_output') ) {
    function houzez_feature_submit_output( $term_name, $term_id, $features_terms_id ) {

        $return_html = '';

        $return_html .= '<div class="col-md-3 col-sm-6 col-6">';
        $return_html .= '<label class="control control--checkbox">';
        if ( in_array( $term_id, $features_terms_id ) ) {
            $return_html .= '<input type="checkbox" name="prop_features[]" value="' . esc_attr( $term_id ) . '" checked />';
            $return_html .= esc_attr( $term_name );
            $return_html .= '<span class="control__indicator"></span>';
        } else {
            $return_html .= '<input type="checkbox" name="prop_features[]" value="' . esc_attr( $term_id ) . '" />';
            $return_html .= esc_attr( $term_name );
            $return_html .= '<span class="control__indicator"></span>';
        }
        $return_html .= '</label>';
        $return_html .= '</div>';
        

        return $return_html;
    }
}

if( ! function_exists( 'houzez_get_leaf_terms' ) ) {
    function houzez_get_leaf_terms($taxonomy) {
        // Get all terms in the taxonomy
        $all_terms = get_terms([
            'taxonomy'   => $taxonomy,
            'hide_empty' => true,
        ]);

        // Initialize an array to store the "leaf" terms
        $leaf_terms = [];

        foreach ($all_terms as $term) {
            // Check if the term has children
            $children = get_terms([
                'taxonomy'   => $taxonomy,
                'parent'     => $term->term_id,
                'hide_empty' => true,
            ]);

            // If the term does not have children, add it to the leaf terms array
            if (empty($children)) {
                $leaf_terms[] = $term->name;
            }
        }

        return $leaf_terms;
    }

}

if( !function_exists('houzez_get_similar_properties') ) {
    function houzez_get_similar_properties($property_id = null, $criteria = array(), $count = 3, $sort_by = 'd_date') {
        if (!$property_id) {
            $property_id = get_the_ID();
        }

        $properties_args = array(
            'post_type'           => 'property',
            'posts_per_page'      => intval($count),
            'post__not_in'        => array($property_id),
            'post_parent__not_in' => array($property_id),
            'post_status'         => 'publish'
        );

        if (!empty($criteria) && is_array($criteria)) {
            $similar_taxonomies_count = count($criteria);
            $tax_query = array();

            for ($i = 0; $i < $similar_taxonomies_count; $i++) {
                $similar_terms = get_the_terms($property_id, $criteria[$i]);
                if (!empty($similar_terms) && is_array($similar_terms)) {
                    $terms_array = array();
                    foreach ($similar_terms as $property_term) {
                        $terms_array[] = $property_term->term_id;
                    }
                    $tax_query[] = array(
                        'taxonomy' => $criteria[$i],
                        'field'    => 'id',
                        'terms'    => $terms_array,
                    );
                }
            }

            $tax_count = count($tax_query);
            if ($tax_count > 1) {
                $tax_query['relation'] = 'AND';
            }
            if ($tax_count > 0) {
                $properties_args['tax_query'] = $tax_query;
            }
        }

        // Sort properties based on the provided sort parameter
        if ($sort_by == 'a_title') {
            $properties_args['orderby'] = 'title';
            $properties_args['order'] = 'ASC';
        } else if ($sort_by == 'd_title') {
            $properties_args['orderby'] = 'title';
            $properties_args['order'] = 'DESC';
        } else if ($sort_by == 'a_price') {
            $properties_args['orderby'] = 'meta_value_num';
            $properties_args['meta_key'] = 'fave_property_price';
            $properties_args['order'] = 'ASC';
        } else if ($sort_by == 'd_price') {
            $properties_args['orderby'] = 'meta_value_num';
            $properties_args['meta_key'] = 'fave_property_price';
            $properties_args['order'] = 'DESC';
        } else if ($sort_by == 'a_date') {
            $properties_args['orderby'] = 'date';
            $properties_args['order'] = 'ASC';
        } else if ($sort_by == 'd_date') {
            $properties_args['orderby'] = 'date';
            $properties_args['order'] = 'DESC';
        } else if ($sort_by == 'featured_first') {
            $properties_args['orderby'] = 'meta_value date';
            $properties_args['meta_key'] = 'fave_featured';
        } else if ($sort_by == 'featured_first_random') {
            $properties_args['orderby'] = 'meta_value DESC rand';
            $properties_args['meta_key'] = 'fave_featured';
        } else if ($sort_by == 'random') {
            $properties_args['orderby'] = 'rand date';
        }

        return new WP_Query($properties_args);
    }
}