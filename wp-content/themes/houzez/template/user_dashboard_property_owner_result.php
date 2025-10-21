<?php
/**
 * Template Name: User Dashboard Property Owner Search Results
 * Advanced results page with filtering and search
 * Created by Inan Celis.
 * User: waqasriaz
 * Date: 08/14/25
 * Time: 4:58 PM
 */

global $houzez_local, $current_user, $wpdb;
wp_get_current_user();
$userID = $current_user->ID;

// Get URL parameters
$filter_owner = isset($_GET['owner']) ? sanitize_text_field($_GET['owner']) : '';
$search_query = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$property_type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
$property_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$sort_by = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'date_desc';

// Fixed pagination parameter handling
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
$current_page = max(1, $paged);
$properties_per_page = 15;
$offset = ($current_page - 1) * $properties_per_page;

// Build WHERE clause based on filters
$where_conditions = array();
$where_conditions[] = "p.post_type = 'property'";
$where_conditions[] = "p.post_status = 'publish'";

// Owner filter
if (!empty($filter_owner)) {
    if ($filter_owner == 'unknown') {
        // Properties without "Owned by" field
        $where_conditions[] = "pm_owner.post_id IS NULL";
    } else {
        // Properties with specific owner
        $where_conditions[] = $wpdb->prepare("pm_owner.meta_value LIKE %s", '%"fave_additional_feature_value"%' . $filter_owner . '%');
    }
}

// Search filter
$search_join = '';
if (!empty($search_query)) {
    $where_conditions[] = $wpdb->prepare("(p.post_title LIKE %s OR p.post_content LIKE %s)", '%' . $search_query . '%', '%' . $search_query . '%');
}

// Property type filter
$type_join = '';
if (!empty($property_type)) {
    $type_join = "LEFT JOIN {$wpdb->prefix}postmeta pm_type ON p.ID = pm_type.post_id AND pm_type.meta_key = 'fave_property_type'";
    $where_conditions[] = $wpdb->prepare("pm_type.meta_value = %s", $property_type);
}

// Property status filter
$status_join = '';
if (!empty($property_status)) {
    $status_join = "LEFT JOIN {$wpdb->prefix}postmeta pm_status ON p.ID = pm_status.post_id AND pm_status.meta_key = 'fave_property_status'";
    $where_conditions[] = $wpdb->prepare("pm_status.meta_value = %s", $property_status);
}

// Build ORDER BY clause
$order_by = 'p.post_date DESC';
switch ($sort_by) {
    case 'title_asc':
        $order_by = 'p.post_title ASC';
        break;
    case 'title_desc':
        $order_by = 'p.post_title DESC';
        break;
    case 'date_asc':
        $order_by = 'p.post_date ASC';
        break;
    case 'price_asc':
        $order_by = 'CAST(pm_price.meta_value AS UNSIGNED) ASC';
        break;
    case 'price_desc':
        $order_by = 'CAST(pm_price.meta_value AS UNSIGNED) DESC';
        break;
    default:
        $order_by = 'p.post_date DESC';
}

// Price join for sorting (using fave_property_price)
$price_join = '';
if (strpos($sort_by, 'price') !== false) {
    $price_join = "LEFT JOIN {$wpdb->prefix}postmeta pm_price ON p.ID = pm_price.post_id AND pm_price.meta_key = 'fave_property_price'";
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_query = "
    SELECT COUNT(DISTINCT p.ID)
    FROM {$wpdb->prefix}posts p
    LEFT JOIN {$wpdb->prefix}postmeta pm_owner ON p.ID = pm_owner.post_id AND pm_owner.meta_key = 'additional_features' AND (
        pm_owner.meta_value LIKE '%\"fave_additional_feature_title\";s:8:\"Owned by\"%'
        OR pm_owner.meta_value LIKE '%\"fave_additional_feature_title\";s:7:\"Owned By\"%'
    )
    {$type_join}
    {$status_join}
    {$price_join}
    WHERE {$where_clause}
";

$total_properties = $wpdb->get_var($count_query);

// Get properties
$main_query = "
    SELECT DISTINCT p.*, 
           pm_owner.meta_value as additional_features,
           pm_type.meta_value as property_type,
           pm_status.meta_value as property_status,
           pm_price.meta_value as property_price,
           pm_currency.meta_value as property_currency
    FROM {$wpdb->prefix}posts p
    LEFT JOIN {$wpdb->prefix}postmeta pm_owner ON p.ID = pm_owner.post_id AND pm_owner.meta_key = 'additional_features'
    LEFT JOIN {$wpdb->prefix}postmeta pm_type ON p.ID = pm_type.post_id AND pm_type.meta_key = 'fave_property_type'
    LEFT JOIN {$wpdb->prefix}postmeta pm_status ON p.ID = pm_status.post_id AND pm_status.meta_key = 'fave_property_status'
    LEFT JOIN {$wpdb->prefix}postmeta pm_price ON p.ID = pm_price.post_id AND pm_price.meta_key = 'fave_property_price'
    LEFT JOIN {$wpdb->prefix}postmeta pm_currency ON p.ID = pm_currency.post_id AND pm_currency.meta_key = 'fave_currency'
    WHERE {$where_clause}
    ORDER BY {$order_by}
    LIMIT {$properties_per_page} OFFSET {$offset}
";

$properties = $wpdb->get_results($main_query);

// Get available property types and statuses for filters
$property_types = $wpdb->get_col("
    SELECT DISTINCT meta_value 
    FROM {$wpdb->prefix}postmeta 
    WHERE meta_key = 'fave_property_type' 
    AND meta_value != '' 
    ORDER BY meta_value ASC
");

$property_statuses = $wpdb->get_col("
    SELECT DISTINCT meta_value 
    FROM {$wpdb->prefix}postmeta 
    WHERE meta_key = 'fave_property_status' 
    AND meta_value != '' 
    ORDER BY meta_value ASC
");

get_header();
?>
<style>
    header.elementor.elementor-17380.elementor-location-header, 
    footer.elementor.elementor-17737.elementor-location-footer {
        display: none;
    }
    td img {
        width: 100px;
        height: 100px;
    }
    .results-container {
        max-width: none;
        margin: 0;
        padding: 0;
        background: transparent;
        min-height: auto;
    }
    
    .results-header {
        background: #fff;
        padding: 10px 20px;
        border-radius: 0;
        box-shadow: none;
        margin-bottom: 20px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .results-title {
        margin: 0;
        color: #333;
        font-size: 24px;
        font-weight: bold;
    }
    
    .filter-select {
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        background-color: white;
        cursor: pointer;
        min-width: 200px;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 10px center;
        background-repeat: no-repeat;
        background-size: 16px;
        padding-right: 40px;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
    }
    
    .filter-select:focus {
        outline: none;
        border-color: var(--e-global-color-primary);
        box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
    }
    
    .sort-container {
        display: flex;
        align-items: center;
        gap: 10px;
        justify-content: flex-end;
    }
    
    .sort-label {
        font-weight: 500;
        color: #666;
        font-size: 14px;
        white-space: nowrap;
    }
    
    .btn {
        /* padding: 10px 20px; */
        border: none;
        border-radius: 4px;
        text-decoration: none;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-block;
    }
    
    .btn-primary {
        background: var(--e-global-color-primary);
        color: white;
    }
    
    .btn-primary:hover {
        background: #0056b3;
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .btn-secondary:hover {
        background: #545b62;
    }
    
    .btn-clear {
        background: #dc3545;
        color: white;
    }
    
    .btn-clear:hover {
        background: #c82333;
    }
    
    .results-section {
        background: #fff;
        border-radius: 0;
        box-shadow: none;
        overflow: hidden;
    }
    
    .results-meta {
        padding: 10px 0;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8f9fa;
    }
    
    .results-count {
        font-weight: bold;
        color: #333;
    }
    
    .sort-controls {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .results-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .results-table th {
        background: #f8f9fa;
        padding: 12px 10px;
        text-align: left;
        font-weight: bold;
        color: #495057;
        border-bottom: 2px solid #dee2e6;
        font-size: 14px;
    }
    
    .results-table td {
        padding: 12px 10px;
        border-bottom: 1px solid #eee;
        vertical-align: top;
        font-size: 14px;
    }
    
    .results-table tbody tr:hover {
        background-color: #f5f5f5;
    }
    
    .property-thumbnail {
        width: 60px;
        height: 45px;
        object-fit: cover;
        border-radius: 4px;
    }
    
    .no-thumb {
        width: 60px;
        height: 45px;
        background: #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        color: #999;
        font-size: 10px;
    }
    
    .property-title {
        font-weight: bold;
        color: var(--e-global-color-primary);
        text-decoration: none;
        font-size: 16px;
    }
    
    .property-title:hover {
        text-decoration: underline;
    }
    
    .property-meta {
        color: #666;
        font-size: 12px;
        margin-top: 5px;
    }
    
    .status-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: bold;
        text-transform: uppercase;
    }
    
    .status-publish {
        background: #d4edda;
        color: #155724;
    }
    
    .status-pending {
        background: #fff3cd;
        color: #856404;
    }
    
    .status-draft {
        background: #f8d7da;
        color: #721c24;
    }
    
    .price-display {
        font-weight: bold;
        color: #28a745;
        font-size: 16px;
    }
    
    .view-property-btn {
        background: var(--e-global-color-primary);
        color: white;
        padding: 8px 16px;
        border-radius: 4px;
        text-decoration: none;
        font-size: 13px;
        font-weight: bold;
    }

    .edit-property-btn {
        background: #002B4B;
        color: white;
        padding: 8px 16px;
        border-radius: 4px;
        text-decoration: none;
        font-size: 13px;
        font-weight: bold;
        margin-left: 5px;
    }
    
    .view-property-btn:hover, .edit-property-btn:hover {
        background: #0056b3;
        color: white;
        text-decoration: none;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px 0;
        gap: 10px;
    }
    
    .pagination a,
    .pagination span {
        padding: 10px 15px;
        border: 1px solid #ddd;
        text-decoration: none;
        color: #333;
        border-radius: 4px;
        transition: all 0.3s ease;
        font-weight: 500;
    }
    
    .pagination a:hover {
        background: var(--e-global-color-primary);
        color: white;
        border-color: var(--e-global-color-primary);
    }
    
    .pagination .current {
        background: var(--e-global-color-primary);
        color: white;
        border-color: var(--e-global-color-primary);
    }
    
    .pagination .disabled {
        color: #999;
        cursor: not-allowed;
        background: #f8f9fa;
    }
    
    .no-results {
        padding: 40px 0;
        text-align: center;
        color: #666;
    }
    
    .no-results h3 {
        margin-bottom: 20px;
        color: #333;
    }
    
    @media (max-width: 768px) {
        .results-header {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
        }
        
        .results-meta {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
        }
        
        .results-table {
            font-size: 14px;
        }
        
        .results-table th,
        .results-table td {
            padding: 10px 8px;
        }
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

<header class="header-main-wrap dashboard-header-main-wrap">
    <div class="dashboard-header-wrap">
        <div class="d-flex align-items-center">
            <div class="dashboard-header-left flex-grow-1">
                <h1><?php echo houzez_option('dsh_owner', 'Property Owner Search Results'); ?></h1>         
            </div><!-- dashboard-header-left -->
            <div class="dashboard-header-right">
                <!-- No logout button -->
            </div><!-- dashboard-header-right -->
        </div><!-- d-flex -->
    </div><!-- dashboard-header-wrap -->
</header><!-- .header-main-wrap -->

<section class="dashboard-content-wrap">
    <div class="dashboard-content-inner-wrap">
<?php if (current_user_can('administrator')) : ?>  
<div class="results-container">
    <!-- Header -->
    <div class="results-header">
        <a href="<?php echo home_url('/property-owner/'); ?>" class="btn btn-secondary">
            ← Back to Property Owner
        </a>

        <form method="GET" action="" id="sortForm">
            <!-- Preserve existing parameters -->
            <?php if ($filter_owner) { ?>
                <input type="hidden" name="owner" value="<?php echo esc_attr($filter_owner); ?>">
            <?php } ?>
            <?php if ($search_query) { ?>
                <input type="hidden" name="search" value="<?php echo esc_attr($search_query); ?>">
            <?php } ?>
            <?php if ($property_type) { ?>
                <input type="hidden" name="type" value="<?php echo esc_attr($property_type); ?>">
            <?php } ?>
            <?php if ($property_status) { ?>
                <input type="hidden" name="status" value="<?php echo esc_attr($property_status); ?>">
            <?php } ?>
            
            <div class="sort-container">
                <span class="sort-label">Sort by:</span>
                <select name="sort" class="filter-select" onchange="document.getElementById('sortForm').submit();">
                    <option value="date_desc" <?php selected($sort_by, 'date_desc'); ?>>Random Order</option>
                    <option value="price_asc" <?php selected($sort_by, 'price_asc'); ?>>Price - Low to High</option>
                    <option value="price_desc" <?php selected($sort_by, 'price_desc'); ?>>Price - High to Low</option>
                    <option value="featured" <?php selected($sort_by, 'featured'); ?>>Featured Listings First</option>
                    <option value="date_asc" <?php selected($sort_by, 'date_asc'); ?>>Date - Old to New</option>
                    <option value="date_desc" <?php selected($sort_by, 'date_desc'); ?>>Date - New to Old</option>
                </select>
            </div>
        </form>
    </div>
    
    <!-- Results -->
    <div class="results-section">
        <div class="results-meta">
            <div class="results-count">
                <?php echo number_format($total_properties); ?> Properties Found
                 <?php if ($filter_owner) { ?>
                    "<span style="color: red;"><?php echo esc_html($filter_owner == 'unknown' ? 'Unknown' : $filter_owner); ?></span>"
                <?php } ?>
                <?php if ($current_page > 1) { ?>
                    (Page <?php echo $current_page; ?> of <?php echo ceil($total_properties / $properties_per_page); ?>)
                <?php } ?>
            </div>
        </div>
        
        <?php if (!empty($properties)) { ?>
        <table class="results-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Image</th>
                    <th>Property Details</th>
                    <th>Price</th>
                    <th>Owner</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $count = $offset;
                foreach ($properties as $property) : 
                    $thumbnail = get_the_post_thumbnail($property->ID, 'thumbnail');
                    
                    // Get property meta data directly
                    $property_type_meta = get_post_meta($property->ID, 'fave_property_type', true);
                    $property_status_meta = get_post_meta($property->ID, 'fave_property_status', true);
                    $property_price_meta = get_post_meta($property->ID, 'fave_property_price', true);
                    $property_currency_meta = get_post_meta($property->ID, 'fave_currency', true);
                    
                    // Extract owner name from additional features
                    $owner_name = 'Unknown';
                    if ($property->additional_features) {
                        $features = maybe_unserialize($property->additional_features);
                        if (is_array($features)) {
                            foreach ($features as $feature) {
                                if ((isset($feature['fave_additional_feature_title']) && 
                                    $feature['fave_additional_feature_title'] == 'Owned by') || (isset($feature['fave_additional_feature_title']) && 
                                    $feature['fave_additional_feature_title'] == 'Owned By')) {
                                    $owner_name = $feature['fave_additional_feature_value'];
                                    break;
                                }
                            }
                        }
                    }
                    ?>
                    <tr>
                        <td><?php echo $count++; ?></td>
                        <td>
                            <?php if ($thumbnail) { ?>
                                <?php echo $thumbnail; ?>
                            <?php } else { ?>
                                <div class="no-thumb">No Image</div>
                            <?php } ?>
                        </td>
                        <td>
                            <a href="<?php echo get_permalink($property->ID); ?>" target="_blank" class="property-title">
                                <?php echo get_the_title($property->ID); ?>
                            </a>
                            <div class="property-meta">
                                Listing ID: <?php echo get_post_meta($property->ID, 'fave_property_id', true); ?> | 
                                Added: <?php echo get_the_date('M j, Y', $property->ID); ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($property_price_meta) { ?>
                                <div class="price-display">
                                    <?php 
                                    // Format the price with currency code
                                    $currency = $property_currency_meta ?: 'USD';
                                    // Convert to integer first, then format with commas
                                    $price_int = (int) str_replace(',', '', $property_price_meta);
                                    $formatted_price = number_format($price_int);
                                    echo '<span class="sub-price-in-usd"> '.get_currency_symbol($currency). number_format($price_int). ' '.$currency.'</span>';
                                    // echo $currency . ' ' . $formatted_price;
                                    ?>
                                </div>
                            <?php } else { ?>
                                <span style="color: #999;">N/A</span>
                            <?php } ?>
                        </td>
                        <td>
                            <strong><?php echo esc_html($owner_name); ?></strong>
                        </td>
                        <td>
                            <a href="<?php echo get_permalink($property->ID); ?>" target="_blank" class="view-property-btn">
                                <i class="houzez-icon icon-attachment"></i> 
                            </a>
                            <a href="/create-listing/?edit_property=<?php echo $property->ID; ?>" target="_blank" class="edit-property-btn">
                                <i class="houzez-icon icon-pencil"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php 
        // Fixed Pagination Section
        if ($total_properties > $properties_per_page) {
            $total_pages = ceil($total_properties / $properties_per_page);
            
            // Build base URL with current filters (remove paged parameter)
            $base_url = remove_query_arg('paged');
            
            echo '<div class="pagination">';
            
            // Previous link
            if ($current_page > 1) {
                $prev_url = add_query_arg('paged', $current_page - 1, $base_url);
                echo '<a href="' . esc_url($prev_url) . '">&laquo; Previous</a>';
            } else {
                echo '<span class="disabled">&laquo; Previous</span>';
            }
            
            // Page numbers
            $start_page = max(1, $current_page - 3);
            $end_page = min($total_pages, $current_page + 3);
            
            // First page + dots
            if ($start_page > 1) {
                $first_url = add_query_arg('paged', 1, $base_url);
                echo '<a href="' . esc_url($first_url) . '">1</a>';
                if ($start_page > 2) {
                    echo '<span>...</span>';
                }
            }
            
            // Page range
            for ($i = $start_page; $i <= $end_page; $i++) {
                if ($i == $current_page) {
                    echo '<span class="current">' . $i . '</span>';
                } else {
                    $page_url = add_query_arg('paged', $i, $base_url);
                    echo '<a href="' . esc_url($page_url) . '">' . $i . '</a>';
                }
            }
            
            // Last page + dots
            if ($end_page < $total_pages) {
                if ($end_page < $total_pages - 1) {
                    echo '<span>...</span>';
                }
                $last_url = add_query_arg('paged', $total_pages, $base_url);
                echo '<a href="' . esc_url($last_url) . '">' . $total_pages . '</a>';
            }
            
            // Next link
            if ($current_page < $total_pages) {
                $next_url = add_query_arg('paged', $current_page + 1, $base_url);
                echo '<a href="' . esc_url($next_url) . '">Next &raquo;</a>';
            } else {
                echo '<span class="disabled">Next &raquo;</span>';
            }
            
            echo '</div>';
        }
        ?>
        
        <?php } else { ?>
        <div class="no-results">
            <h3>No Properties Found</h3>
            <p>Try adjusting your search criteria or clearing the filters.</p>
            <?php if (!empty($filter_owner) || !empty($search_query) || !empty($property_type) || !empty($property_status)) { ?>
                <a href="<?php echo strtok($_SERVER["REQUEST_URI"], '?'); ?>" class="btn btn-primary">Clear All Filters</a>
            <?php } ?>
        </div>
        <?php } ?>
    </div>
</div>
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