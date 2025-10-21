<?php
$dashboard_crm = houzez_get_template_link_2('template/user_dashboard_crm.php');
$import_link = add_query_arg( 'hpage', 'import-leads', $dashboard_crm );
$hpage = isset($_GET['hpage']) ? sanitize_text_field($_GET['hpage']) : '';
$keyword = isset($_GET['keyword']) ? sanitize_text_field(trim($_GET['keyword'])) : '';
$leads = Houzez_leads::get_leads();
?>
<header class="header-main-wrap dashboard-header-main-wrap">
    <div class="dashboard-header-wrap">
        <div class="d-flex align-items-center">
            <div class="dashboard-header-left flex-grow-1">
                <h1><?php echo houzez_option('dsh_leads', 'Leads'); ?></h1>         
            </div><!-- dashboard-header-left -->
            <div class="dashboard-header-right">
                <a class="btn btn-primary open-close-slide-panel" href="#"><?php esc_html_e('Add New Lead', 'houzez'); ?></a>
            </div><!-- dashboard-header-right -->
        </div><!-- d-flex -->
    </div><!-- dashboard-header-wrap -->
</header><!-- .header-main-wrap -->
<section class="dashboard-content-wrap leads-main-wrap">
    <div class="dashboard-content-inner-wrap">
        
        <?php get_template_part('template-parts/dashboard/statistics/statistic-leads'); ?>

        <div class="dashboard-content-block-wrap">

            <div class="dashboard-tool-block">
                <div class="dashboard-tool-buttons-block">
                    <div class="dashboard-tool-button">
                        <button onclick="window.location.href='<?php echo esc_url($import_link);?>';" class="btn btn-primary-outlined"><?php echo esc_html__( 'Import', 'houzez' ); ?></button>
                    </div>
                    <div class="dashboard-tool-button">
                        <button id="export-leads" class="btn btn-primary-outlined"><span class="btn-loader houzez-loader-js"></span><?php esc_html_e( 'Export', 'houzez' ); ?>
                        </button>
                    </div>
                    <div class="dashboard-tool-button">
                        <button id="bulk-delete-leads" class="btn btn-grey-outlined"><?php echo esc_html__( 'Delete', 'houzez' ); ?></button>
                    </div>
                    <div class="dashboard-tool-button">
                        <div class="btn"><i class="houzez-icon icon-single-neutral-circle mr-2 grey"></i>
                            <?php echo esc_attr($leads['data']['total_records']); ?> <?php esc_html_e('Results Found', 'houzez'); ?>
                        </div>
                    </div>
                </div><!-- dashboard-tool-buttons-block -->
                
                <div class="dashboard-tool-search-block">
                    <div class="dashboard-crm-search-wrap">
                        <div class="d-flex">
                            <div class="flex-grow-1">
                                <div class="dashboard-crm-search">
                                    <form name="search-leads" method="get" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
                                        <input type="hidden" name="hpage" value="<?php echo esc_attr($hpage); ?>">
                                    <div class="d-flex">
                                        <div class="form-group">
                                            <div class="search-icon">
                                                <input name="keyword" type="text" value="<?php echo esc_attr($keyword); ?>" class="form-control" placeholder="<?php echo esc_html__('Search', 'houzez'); ?>">
                                            </div><!-- search-icon -->
                                        </div><!-- form-group -->
                                        <button type="submit" class="btn btn-search btn-secondary"><?php echo esc_html__( 'Search', 'houzez' ); ?></button>
                                    </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div><!-- dashboard-crm-search-wrap -->
                </div><!-- dashboard-tool-search-block -->
                
            </div><!-- dashboard-tool-block -->

            <?php
            $dashboard_crm = houzez_get_template_link_2('template/user_dashboard_crm.php');
        
            if(!empty($leads['data']['results'])) { ?>

                <table class="dashboard-table table-lined table-hover responsive-table">
                    <thead>
                        <tr>
                            <th>
                                <label class="control control--checkbox">
                                    <input type="checkbox" class="checkbox-delete" id="leads_select_all" name="leads_multicheck">
                                    <span class="control__indicator"></span>
                                </label>
                            </th>
                            <th><?php esc_html_e('Name', 'houzez'); ?></th>
                            <th><?php esc_html_e('Email', 'houzez'); ?></th>
                            <th><?php esc_html_e('Phone', 'houzez'); ?></th>
                            <th><?php esc_html_e('Type', 'houzez'); ?></th>
                            <th><?php esc_html_e('Date', 'houzez'); ?></th>
                            <th class="action-col"><?php esc_html_e('Actions', 'houzez'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        foreach ($leads['data']['results'] as $result) { 
                            $detail_link = add_query_arg(
                                array(
                                    'hpage' => 'lead-detail',
                                    'lead-id' => $result->lead_id,
                                    'tab' => 'enquires',
                                ), $dashboard_crm
                            );

                            $datetime = $result->time;

                            $datetime_unix = strtotime($datetime);
                            $get_date = houzez_return_formatted_date($datetime_unix);
                            $get_time = houzez_get_formatted_time($datetime_unix);
                        ?>

                            <tr>
                                <td>
                                    <label class="control control--checkbox">
                                        <input type="checkbox" class="checkbox-delete lead-bulk-delete" name="lead-bulk-delete[]" value="<?php echo intval($result->lead_id); ?>">
                                        <span class="control__indicator"></span>
                                    </label>
                                </td>
                                <td class="table-nowrap" data-label="<?php esc_html_e('Name', 'houzez'); ?>">
                                    <?php echo esc_attr($result->display_name); ?>
                                </td>
                                <td data-label="<?php esc_html_e('Email', 'houzez'); ?>">
                                    <a href="mailto:<?php echo esc_attr($result->email); ?>">
                                        <strong><?php echo esc_attr($result->email); ?></strong>
                                    </a>
                                </td>
                                <td data-label="<?php esc_html_e('Phone', 'houzez'); ?>">
                                    <?php echo esc_attr($result->mobile); ?>
                                </td>
                                <td data-label="<?php esc_html_e('Type', 'houzez'); ?>">
                                    <?php 
                                    if( $result->type ) {
                                        $type = stripslashes($result->type);
                                        $type = htmlentities($type);
                                        echo esc_attr($type); 
                                    }?>
                                </td>
                                <td class="table-nowrap" data-label="<?php esc_html_e('Date', 'houzez'); ?>">
                                    <?php echo esc_attr($get_date); ?><br>
                                    <?php echo esc_html__('at', 'houzez'); ?> <?php echo esc_attr($get_time); ?>
                                </td>
                                <td>
                                    <div class="dropdown property-action-menu">
                                        <button class="btn btn-primary-outlined dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <?php esc_html_e('Actions', 'houzez'); ?>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
                                            <a class="dropdown-item" href="<?php echo esc_url($detail_link); ?>"><?php esc_html_e('Details', 'houzez'); ?></a>

                                            <a class="edit-lead dropdown-item open-close-slide-panel" data-id="<?php echo intval($result->lead_id)?>" href="#"><?php esc_html_e('Edit', 'houzez'); ?></a>

                                            <a href="" class="delete-lead dropdown-item" data-id="<?php echo intval($result->lead_id); ?>" data-nonce="<?php echo wp_create_nonce('delete_lead_nonce') ?>"><?php esc_html_e('Delete', 'houzez'); ?></a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php
                        } ?>
                    </tbody>
                </table>
            <?php
            } else { ?>
                <div class="dashboard-content-block">

                    <?php if( $keyword ) { 
                        esc_html_e("No Result Found", 'houzez');
                    } else { ?>
                    <?php esc_html_e("You don't have any contact at this moment.", 'houzez'); ?> <a class="open-close-slide-panel" href="#"><strong><?php esc_html_e('Add New Lead', 'houzez'); ?></strong></a>
                    <?php } ?>
                </div><!-- dashboard-content-block -->
            <?php } ?>
        

        </div><!-- dashboard-content-block-wrap -->
    </div><!-- dashboard-content-inner-wrap -->

    <div class="leads-pagination-wrap">
        <div class="leads-pagination-item-count">
            <?php get_template_part('template-parts/dashboard/board/records-html'); ?>
        </div>

        <?php
        $total_pages = ceil($leads['data']['total_records'] / $leads['data']['items_per_page']);
        $current_page = $leads['data']['page'];
        houzez_crm_pagination($total_pages, $current_page);
        ?>

    </div> <!-- leads-pagination-wrap -->

</section><!-- dashboard-content-wrap -->
<section class="dashboard-side-wrap">
    <?php get_template_part('template-parts/dashboard/side-wrap'); ?>
</section>