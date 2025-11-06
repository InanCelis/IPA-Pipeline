<?php
/**
 * Template Name: User Dashboard Property Owner
 * Created by Inan Celis.
 * User: waqasriaz
 * Date: 08/14/25
 * Time: 4:58 PM
 */

global $houzez_local, $current_user, $wpdb;
wp_get_current_user();
$userID     = $current_user->ID;
$user_login = $current_user->user_login;

// Execute the custom SQL query for "Owned by" statistics
$owned_by_stats = $wpdb->get_results("
    SELECT 
    -- Normalize the extracted value by trimming whitespace and standardizing case
    TRIM(
        UPPER(
            SUBSTRING_INDEX(
                SUBSTRING_INDEX(
                    SUBSTRING(
                        pm.meta_value, 
                        LOCATE('\"fave_additional_feature_title\";s:8:\"Owned by\"', pm.meta_value) + 
                        LOCATE('\"fave_additional_feature_value\";s:', 
                            SUBSTRING(pm.meta_value, LOCATE('\"fave_additional_feature_title\";s:8:\"Owned by\"', pm.meta_value))
                        ) + 
                        LENGTH('\"fave_additional_feature_value\";s:') + 
                        2 + 
                        CAST(
                            SUBSTRING(
                                pm.meta_value,
                                LOCATE('\"fave_additional_feature_title\";s:8:\"Owned by\"', pm.meta_value) + 
                                LOCATE('\"fave_additional_feature_value\";s:', 
                                    SUBSTRING(pm.meta_value, LOCATE('\"fave_additional_feature_title\";s:8:\"Owned by\"', pm.meta_value))
                                ) + 
                                LENGTH('\"fave_additional_feature_value\";s:') + 1,
                                LOCATE(':\"', 
                                    SUBSTRING(pm.meta_value, 
                                        LOCATE('\"fave_additional_feature_title\";s:8:\"Owned by\"', pm.meta_value) + 
                                        LOCATE('\"fave_additional_feature_value\";s:', 
                                            SUBSTRING(pm.meta_value, LOCATE('\"fave_additional_feature_title\";s:8:\"Owned by\"', pm.meta_value))
                                        ) + 
                                        LENGTH('\"fave_additional_feature_value\";s:') + 1
                                    )
                                ) - 1
                            ) AS UNSIGNED
                        )
                    ), 
                    '\";', 1
                ), 
                '\"', -1
            )
        )
    ) AS owned_by_value,
    COUNT(*) as count
FROM {$wpdb->prefix}postmeta pm
    INNER JOIN {$wpdb->prefix}posts p ON pm.post_id = p.ID
WHERE pm.meta_key = 'additional_features'
    AND pm.meta_value LIKE '%\"fave_additional_feature_title\";s:8:\"Owned by\"%'
    AND p.post_type = 'property'
    AND p.post_status = 'publish'
GROUP BY owned_by_value
HAVING owned_by_value IS NOT NULL AND owned_by_value != ''
ORDER BY count DESC;
");

// Get count of properties WITHOUT "Owned by" field
$properties_without_owner = $wpdb->get_var("
    SELECT COUNT(DISTINCT p.ID)
    FROM {$wpdb->prefix}posts p
    LEFT JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id 
        AND pm.meta_key = 'additional_features' 
        AND pm.meta_value LIKE '%\"fave_additional_feature_title\";s:8:\"Owned by\"%'
    WHERE p.post_type = 'property'
        AND p.post_status = 'publish'
        AND pm.post_id IS NULL
");

// Add "Unknown" entry to the stats array if there are properties without owner
if ($properties_without_owner > 0) {
    $unknown_entry = new stdClass();
    $unknown_entry->owned_by_value = 'Unknown';
    $unknown_entry->count = $properties_without_owner;
    
    // Add to the beginning of the array or sort by count
    $owned_by_stats[] = $unknown_entry;
    
    // Re-sort by count descending
    usort($owned_by_stats, function($a, $b) {
        return $b->count - $a->count;
    });
}

get_header();
?>
<style>
    html {
        margin-top: 0 !important;
    }
    body {
        padding-top: 0 !important;
    }

    header.elementor.elementor-17380.elementor-location-header, 
    footer.elementor.elementor-17737.elementor-location-footer {
        display: none;
    }
    
    .stats-section {
        background: #fff;
        padding: 20px;
        margin-bottom: 30px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .stats-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }
    
    .stats-table th,
    .stats-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .stats-table th {
        background-color: #f8f9fa;
        font-weight: bold;
        color: #495057;
    }
    
    .stats-table tbody tr:hover {
        background-color: #f5f5f5;
    }
    
    .unknown-owner-row {
        background-color: #ffeaea !important;
        border-left: 4px solid #dc3545;
    }
    
    .unknown-owner-row:hover {
        background-color: #ffdddd !important;
    }
    
    .unknown-owner-text {
        color: #dc3545;
        font-weight: bold;
        font-style: italic;
    }
    
    .count-badge {
        background: #007bff;
        color: white;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: bold;
    }
    
    .view-listings-btn {
        background: var(--e-global-color-primary);
        color: white;
        padding: 6px 12px;
        border-radius: 4px;
        text-decoration: none;
        font-size: 12px;
        font-weight: bold;
        display: inline-block;
        transition: background 0.3s ease;
    }
    
    .view-listings-btn:hover {
        background: #0056b3;
        color: white;
        text-decoration: none;
    }
    
    .section-title {
        margin: 0 0 15px 0;
        color: #333;
        font-size: 20px;
    }

    .access-denied {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 40px 20px;
        text-align: center;
        margin-bottom: 30px;
    }
    
    .access-denied h3 {
        color: #dc3545;
        margin-bottom: 15px;
    }
    
    .access-denied p {
        color: #6c757d;
        margin-bottom: 0;
    }
</style>
<?php get_template_part('template-parts/dashboard/mobile-header'); ?>
<header class="header-main-wrap dashboard-header-main-wrap">
    <div class="dashboard-header-wrap">
        <div class="d-flex align-items-center">
            <div class="dashboard-header-left flex-grow-1">
                <h1><?php echo houzez_option('dsh_owner', 'Property Owner Dashboard'); ?></h1>         
            </div><!-- dashboard-header-left -->
            <div class="dashboard-header-right">
                <!-- Logout button removed -->
            </div><!-- dashboard-header-right -->
        </div><!-- d-flex -->
    </div><!-- dashboard-header-wrap -->
</header><!-- .header-main-wrap -->

<section class="dashboard-content-wrap">
    <div class="dashboard-content-inner-wrap">
    <?php if (current_user_can('administrator')) : ?>  
        <!-- Property Ownership Statistics Section -->
        <?php if (!empty($owned_by_stats)) { ?>
        <div class="stats-section">
            <h2 class="section-title">Property Ownership Statistics</h2>
            <p>Properties grouped by "Owned by" field from additional features:</p>
            
            <table class="stats-table">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Owner Name', 'houzez'); ?></th>
                        <th><?php echo esc_html__('Number of Properties', 'houzez'); ?></th>
                        <th><?php echo esc_html__('Percentage', 'houzez'); ?></th>
                        <th><?php echo esc_html__('View Listings', 'houzez'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_properties = array_sum(wp_list_pluck($owned_by_stats, 'count'));
                    foreach ($owned_by_stats as $stat) : 
                        $percentage = ($stat->count / $total_properties) * 100;
                    ?>
                    <tr<?php echo ($stat->owned_by_value == 'Unknown') ? ' class="unknown-owner-row"' : ''; ?>>
                        <td>
                            <?php if ($stat->owned_by_value == 'Unknown') { ?>
                                <span class="unknown-owner-text"><?php echo esc_html($stat->owned_by_value); ?></span>
                            <?php } else { ?>
                                <strong><?php echo esc_html($stat->owned_by_value ? $stat->owned_by_value : 'Not specified'); ?></strong>
                            <?php } ?>
                        </td>
                        <td>
                            <span class="count-badge"><?php echo number_format($stat->count); ?></span>
                        </td>
                        <td>
                            <?php echo number_format($percentage, 1); ?>%
                        </td>
                        <td>
                            <?php 
                            // Create the URL for viewing listings by this owner on separate page
                            $owner_name = $stat->owned_by_value ? $stat->owned_by_value : 'not-specified';
                            
                            // Special handling for "Unknown" properties (without owner field)
                            if ($stat->owned_by_value == 'Unknown') {
                                $view_url = home_url('/property-owner/result/?owner=unknown');
                            } else {
                                $view_url = home_url('/property-owner/result/?owner=' . urlencode($stat->owned_by_value));
                            }
                            ?>
                            <a href="<?php echo esc_url($view_url); ?>" class="view-listings-btn">
                                View All (<?php echo $stat->count; ?>)
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="border-top: 2px solid #333; font-weight: bold;">
                        <td>Total Properties</td>
                        <td><span class="count-badge" style="background: var(--e-global-color-primary);"><?php echo number_format($total_properties); ?></span></td>
                        <td>100%</td>
                        <td>
                            <!-- Removed "View All Properties" button -->
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php } ?>
    <?php else : ?>
        <!-- Access Denied Message for Non-Admin Users -->
        <div class="access-denied">
            <h3>Access Restricted</h3>
            <p>This section is only available to administrators. Please contact your site administrator if you need access to this information.</p>
        </div>
    <?php endif; ?>
    </div><!-- dashboard-content-inner-wrap -->
</section><!-- dashboard-content-wrap -->

<section class="dashboard-side-wrap">
    <?php get_template_part('template-parts/dashboard/side-wrap'); ?>
</section>

<?php get_footer(); ?>