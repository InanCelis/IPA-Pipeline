<?php
/**
 * Pipeline - Deals Management
 */

global $wpdb;
$table_deals = $wpdb->prefix . 'pipeline_deals';
$table_leads = $wpdb->prefix . 'pipeline_leads';
$table_partnerships = $wpdb->prefix . 'partnerships';

// Check if current user is sales_role
$current_user = wp_get_current_user();
$is_sales_user = in_array('sales_role', $current_user->roles);
$is_admin = current_user_can('administrator');

// Get filter parameters
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$status_filter = isset($_GET['deal_status']) ? sanitize_text_field($_GET['deal_status']) : '';
$assigned_filter = isset($_GET['assigned']) ? sanitize_text_field($_GET['assigned']) : '';

// Pagination
$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query - Join with leads to get lead info
// Only show deals where lead status is "Qualified"
$where = array('d.is_active = 1', 'l.status = "Qualified"');

// If sales user, only show their assigned deals
if ($is_sales_user && !$is_admin) {
    $where[] = $wpdb->prepare("l.assigned_to = %d", $current_user->ID);
}

if (!empty($search)) {
    $where[] = $wpdb->prepare(
        "(l.fullname LIKE %s OR l.email LIKE %s OR l.contact_number LIKE %s)",
        '%' . $wpdb->esc_like($search) . '%',
        '%' . $wpdb->esc_like($search) . '%',
        '%' . $wpdb->esc_like($search) . '%'
    );
}

if (!empty($status_filter)) {
    $where[] = $wpdb->prepare("d.deal_status = %s", $status_filter);
}

if (!empty($assigned_filter)) {
    $where[] = $wpdb->prepare("l.assigned_to = %s", $assigned_filter);
}

$where_clause = implode(' AND ', $where);

// Get total count
$total_deals = $wpdb->get_var("
    SELECT COUNT(*)
    FROM $table_deals d
    LEFT JOIN $table_leads l ON d.lead_id = l.id
    WHERE $where_clause
");
$total_pages = ceil($total_deals / $per_page);

// Get deals with lead info and assigned user name
$deals = $wpdb->get_results("
    SELECT d.*, l.fullname, l.email, l.contact_number, l.assigned_to, l.partners, u.display_name as assigned_to_name
    FROM $table_deals d
    LEFT JOIN $table_leads l ON d.lead_id = l.id
    LEFT JOIN {$wpdb->users} u ON l.assigned_to = u.ID
    WHERE $where_clause
    ORDER BY d.updated_at DESC
    LIMIT $offset, $per_page
");

// Get statistics - Only count deals with Qualified leads (filtered for sales users)
$stats_where = "d.is_active = 1 AND l.status = 'Qualified'";
if ($is_sales_user && !$is_admin) {
    $stats_where .= $wpdb->prepare(" AND l.assigned_to = %d", $current_user->ID);
}

$stats = array(
    'total' => $wpdb->get_var("
        SELECT COUNT(*) FROM $table_deals d
        LEFT JOIN $table_leads l ON d.lead_id = l.id
        WHERE $stats_where
    "),
    'na' => $wpdb->get_var("
        SELECT COUNT(*) FROM $table_deals d
        LEFT JOIN $table_leads l ON d.lead_id = l.id
        WHERE d.deal_status = 'N/A' AND $stats_where
    "),
    'options_sent' => $wpdb->get_var("
        SELECT COUNT(*) FROM $table_deals d
        LEFT JOIN $table_leads l ON d.lead_id = l.id
        WHERE d.deal_status = 'Options Sent' AND $stats_where
    "),
    'site_visit' => $wpdb->get_var("
        SELECT COUNT(*) FROM $table_deals d
        LEFT JOIN $table_leads l ON d.lead_id = l.id
        WHERE d.deal_status = 'Site Visit' AND $stats_where
    "),
    'negotiation' => $wpdb->get_var("
        SELECT COUNT(*) FROM $table_deals d
        LEFT JOIN $table_leads l ON d.lead_id = l.id
        WHERE d.deal_status = 'Negotiation and Documentation' AND $stats_where
    "),
    'for_payment' => $wpdb->get_var("
        SELECT COUNT(*) FROM $table_deals d
        LEFT JOIN $table_leads l ON d.lead_id = l.id
        WHERE d.deal_status = 'For Payment' AND $stats_where
    "),
    'buyer_payment_completed' => $wpdb->get_var("
        SELECT COUNT(*) FROM $table_deals d
        LEFT JOIN $table_leads l ON d.lead_id = l.id
        WHERE d.deal_status = 'Buyer Payment Completed' AND $stats_where
    ")
);

// Get Sales users
$sales_users = get_users(array(
    'role' => 'sales_role',
    'orderby' => 'display_name',
    'order' => 'ASC'
));
$sales_user_list = $sales_users;

// Get field options
$field_options_table = $wpdb->prefix . 'pipeline_field_options';
$property_types = $wpdb->get_var("SELECT options FROM $field_options_table WHERE field_name = 'property_type'");
$property_types = $property_types ? json_decode($property_types, true) : array();

$payment_methods = $wpdb->get_var("SELECT options FROM $field_options_table WHERE field_name = 'budget_payment_method'");
$payment_methods = $payment_methods ? json_decode($payment_methods, true) : array();

$purposes = $wpdb->get_var("SELECT options FROM $field_options_table WHERE field_name = 'purpose_of_purchase'");
$purposes = $purposes ? json_decode($purposes, true) : array();

$timelines = $wpdb->get_var("SELECT options FROM $field_options_table WHERE field_name = 'timeline_urgency'");
$timelines = $timelines ? json_decode($timelines, true) : array();

$stages = $wpdb->get_var("SELECT options FROM $field_options_table WHERE field_name = 'stage_buying_process'");
$stages = $stages ? json_decode($stages, true) : array();

// Get countries and cities from WordPress taxonomies
$countries = get_terms(array('taxonomy' => 'property_country', 'hide_empty' => false));
$cities = get_terms(array('taxonomy' => 'property_city', 'hide_empty' => false));
?>

<div class="pipeline-header">
    <h2 class="pipeline-title">Deals Management</h2>
    <button class="btn btn-info" onclick="refreshDealsFromQualifiedLeads()">
        <i class="houzez-icon icon-refresh-arrows-1"></i> Sync Qualified Leads
    </button>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Deals</div>
        <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">N/A</div>
        <div class="stat-value"><?php echo number_format($stats['na']); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Options Sent</div>
        <div class="stat-value"><?php echo number_format($stats['options_sent']); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Site Visit</div>
        <div class="stat-value"><?php echo number_format($stats['site_visit']); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Negotiation</div>
        <div class="stat-value"><?php echo number_format($stats['negotiation']); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">For Payment</div>
        <div class="stat-value"><?php echo number_format($stats['for_payment']); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Buyer Payment Completed</div>
        <div class="stat-value"><?php echo number_format($stats['buyer_payment_completed']); ?></div>
    </div>
</div>

<!-- Filters -->
<div class="filter-section">
    <form method="GET" action="">
        <input type="hidden" name="hpage" value="deals">
        <div class="filter-row">
            <div class="filter-group">
                <label>Search</label>
                <input type="text" name="search" class="filter-input" placeholder="Search by client name, email, or phone" value="<?php echo esc_attr($search); ?>">
            </div>
            <div class="filter-group">
                <label>Deal Status</label>
                <select name="deal_status" class="filter-select">
                    <option value="">All Statuses</option>
                    <option value="N/A" <?php selected($status_filter, 'N/A'); ?>>N/A</option>
                    <option value="Preparing Options" <?php selected($status_filter, 'Preparing Options'); ?>>Preparing Options</option>
                    <option value="Options Sent" <?php selected($status_filter, 'Options Sent'); ?>>Options Sent</option>
                    <option value="Site Visit" <?php selected($status_filter, 'Site Visit'); ?>>Site Visit</option>
                    <option value="Negotiation and Documentation" <?php selected($status_filter, 'Negotiation and Documentation'); ?>>Negotiation and Documentation</option>
                    <option value="For Payment" <?php selected($status_filter, 'For Payment'); ?>>For Payment</option>
                    <option value="Buyer Payment Completed" <?php selected($status_filter, 'Buyer Payment Completed'); ?>>Buyer Payment Completed</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Assigned To</label>
                <select name="assigned" class="filter-select">
                    <option value="">All Assignees</option>
                    <?php foreach ($sales_user_list as $sales_user) : ?>
                        <option value="<?php echo esc_attr($sales_user->ID); ?>" <?php selected($assigned_filter, $sales_user->ID); ?>>
                            <?php echo esc_html($sales_user->display_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="?hpage=deals" class="btn btn-secondary">Reset</a>
            </div>
        </div>
    </form>
</div>

<!-- Deals Table -->
<table class="pipeline-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Client Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Property Type</th>
            <th>Budget</th>
            <th>Deal Status</th>
            <th>Assigned To</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($deals)) : ?>
            <tr>
                <td colspan="9" class="no-results">No deals found. Qualified leads will appear here automatically.</td>
            </tr>
        <?php else : ?>
            <?php foreach ($deals as $deal) :
                $status_class = 'status-' . strtolower(str_replace(' ', '-', $deal->deal_status));
                $property_types_array = $deal->property_type ? json_decode($deal->property_type, true) : array();
                $property_types_display = is_array($property_types_array) ? implode(', ', $property_types_array) : 'N/A';
            ?>
                <tr>
                    <td>#<?php echo $deal->id; ?></td>
                    <td><?php echo esc_html($deal->fullname); ?></td>
                    <td><?php echo esc_html($deal->email); ?></td>
                    <td><?php echo esc_html($deal->contact_number); ?></td>
                    <td><?php echo esc_html($property_types_display); ?></td>
                    <td><?php echo $deal->budget_amount ? '$' . number_format($deal->budget_amount, 2) : 'N/A'; ?></td>
                    <td><span class="status-badge <?php echo $status_class; ?>"><?php echo esc_html($deal->deal_status); ?></span></td>
                    <td><?php echo esc_html($deal->assigned_to_name); ?></td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-primary" onclick='viewDeal(<?php echo json_encode($deal); ?>)'>
                                <i class="houzez-icon icon-messages-bubble"></i> View
                            </button>
                            <button class="btn btn-sm btn-info" onclick='editDeal(<?php echo json_encode($deal); ?>)'>
                                <i class="houzez-icon icon-edit-1"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-secondary" onclick="moveToLeads(<?php echo $deal->lead_id; ?>)">
                                <i class="houzez-icon icon-undo-1"></i> Back to Leads
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteDeal(<?php echo $deal->id; ?>)">
                                <i class="houzez-icon icon-remove-circle"></i> Delete
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<!-- Pagination -->
<?php if ($total_pages > 1) : ?>
    <div class="pagination">
        <button onclick="window.location.href='?hpage=deals&paged=1'" <?php echo $page <= 1 ? 'disabled' : ''; ?>>First</button>
        <button onclick="window.location.href='?hpage=deals&paged=<?php echo max(1, $page - 1); ?>'" <?php echo $page <= 1 ? 'disabled' : ''; ?>>Previous</button>
        <span class="page-info">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
        <button onclick="window.location.href='?hpage=deals&paged=<?php echo min($total_pages, $page + 1); ?>'" <?php echo $page >= $total_pages ? 'disabled' : ''; ?>>Next</button>
        <button onclick="window.location.href='?hpage=deals&paged=<?php echo $total_pages; ?>'" <?php echo $page >= $total_pages ? 'disabled' : ''; ?>>Last</button>
    </div>
<?php endif; ?>

<!-- Edit Deal Modal -->
<div id="dealModal" class="modal">
    <div class="modal-content" style="max-width: 1100px;">
        <div class="modal-header">
            <h3 class="modal-title" id="dealModalTitle">Edit Buyer Requirements</h3>
            <button class="close" onclick="closeDealModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="dealForm">
                <input type="hidden" id="deal_id" name="deal_id">
                <input type="hidden" id="lead_id" name="lead_id">

                <h4 style="margin-bottom: 20px; color: #333; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">
                    <i class="houzez-icon icon-single-neutral"></i> Client Information
                </h4>
                <div id="clientInfo" style="margin-bottom: 30px; padding: 15px; background: #f8f9fa; border-radius: 6px;"></div>

                <h4 style="margin-bottom: 20px; color: #333; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">
                    <i class="houzez-icon icon-maps-pin-1"></i> Buyer Requirements
                </h4>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Property Type (Multiple Selection)</label>
                        <select class="form-control select2-property-type" id="property_type" name="property_type[]" multiple>
                            <?php foreach ($property_types as $type) : ?>
                                <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($type); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Country (Multiple Selection)</label>
                        <select class="form-control select2-country" id="country" name="country[]" multiple>
                            <?php foreach ($countries as $country) : ?>
                                <option value="<?php echo $country->term_id; ?>"><?php echo esc_html($country->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>City (Multiple Selection)</label>
                        <select class="form-control select2-city" id="city" name="city[]" multiple>
                            <?php foreach ($cities as $city) : ?>
                                <option value="<?php echo $city->term_id; ?>"><?php echo esc_html($city->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Number of Rooms</label>
                        <input type="number" class="form-control" id="num_rooms" name="num_rooms" min="0">
                    </div>
                    <div class="form-group">
                        <label>Area Size</label>
                        <input type="text" class="form-control" id="area_size" name="area_size" placeholder="e.g., 100sqm / 1000sqft">
                    </div>
                    <div class="form-group">
                        <label>Number of Bathrooms</label>
                        <input type="number" class="form-control" id="num_bathrooms" name="num_bathrooms" min="0">
                    </div>
                    <div class="form-group">
                        <label>Budget Amount</label>
                        <input type="number" class="form-control" id="budget_amount" name="budget_amount" step="0.01" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select class="form-control" id="budget_payment_method" name="budget_payment_method">
                            <option value="">Select Method</option>
                            <?php foreach ($payment_methods as $method) : ?>
                                <option value="<?php echo esc_attr($method); ?>"><?php echo esc_html($method); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label>Purpose of Purchase (Multiple Selection)</label>
                        <select class="form-control select2-purpose" id="purpose_of_purchase" name="purpose_of_purchase[]" multiple>
                            <?php foreach ($purposes as $purpose) : ?>
                                <option value="<?php echo esc_attr($purpose); ?>"><?php echo esc_html($purpose); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Timeline & Urgency</label>
                        <select class="form-control" id="timeline_urgency" name="timeline_urgency">
                            <option value="">Select Timeline</option>
                            <?php foreach ($timelines as $timeline) : ?>
                                <option value="<?php echo esc_attr($timeline); ?>"><?php echo esc_html($timeline); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Move-in Target</label>
                        <input type="text" class="form-control" id="move_in_target" name="move_in_target" placeholder="e.g., Q2 2025">
                    </div>
                    <div class="form-group">
                        <label>Stage in Buying Process</label>
                        <select class="form-control" id="stage_buying_process" name="stage_buying_process">
                            <option value="">Select Stage</option>
                            <?php foreach ($stages as $stage) : ?>
                                <option value="<?php echo esc_attr($stage); ?>"><?php echo esc_html($stage); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label class="required">Deal Status</label>
                        <select class="form-control" id="deal_status" name="deal_status" required>
                            <option value="N/A">N/A</option>
                            <option value="Options Sent">Options Sent</option>
                            <option value="Site Visit">Site Visit</option>
                            <option value="Preparing Options">Preparing Options</option>
                            <option value="Negotiation and Documentation">Negotiation and Documentation</option>
                            <option value="For Payment">For Payment</option>
                            <option value="Buyer Payment Completed">Buyer Payment Completed</option>
                        </select>
                        <small style="color: #666;">Note: Changing to "For Payment" will automatically create an invoice.</small>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeDealModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveDeal()">Save Deal</button>
        </div>
    </div>
</div>

<!-- View Deal Modal -->
<div id="viewDealModal" class="modal">
    <div class="modal-content" style="max-width: 1100px;">
        <div class="modal-header">
            <h3 class="modal-title">Deal Details</h3>
            <button class="close" onclick="closeViewDealModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="viewDealContent"></div>

            <div class="comments-section">
                <h4>Comments</h4>
                <div id="dealCommentsContainer"></div>

                <div class="comment-form">
                    <textarea class="form-control" id="newDealComment" placeholder="Add a comment..." rows="3"></textarea>
                    <button class="btn btn-primary" style="margin-top: 10px;" onclick="addDealComment()">Add Comment</button>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeViewDealModal()">Close</button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize Select2 for multiple selection
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2-property-type, .select2-country, .select2-city, .select2-purpose').select2({
            placeholder: 'Select options',
            allowClear: true,
            width: '100%'
        });
    }
});

let currentViewDealId = null;

function closeDealModal() {
    document.getElementById('dealModal').style.display = 'none';
}

function editDeal(deal) {
    document.getElementById('deal_id').value = deal.id;
    document.getElementById('lead_id').value = deal.lead_id;

    // Show client info
    let clientHtml = '<div class="form-grid">';
    clientHtml += '<div class="form-group"><strong>Name:</strong><p>' + (deal.fullname || 'N/A') + '</p></div>';
    clientHtml += '<div class="form-group"><strong>Email:</strong><p>' + (deal.email || 'N/A') + '</p></div>';
    clientHtml += '<div class="form-group"><strong>Phone:</strong><p>' + (deal.contact_number || 'N/A') + '</p></div>';
    clientHtml += '<div class="form-group"><strong>Assigned To:</strong><p>' + (deal.assigned_to_name || 'N/A') + '</p></div>';
    clientHtml += '</div>';
    document.getElementById('clientInfo').innerHTML = clientHtml;

    // Set form values
    setSelectValue('property_type', deal.property_type);
    setSelectValue('country', deal.country);
    setSelectValue('city', deal.city);
    setSelectValue('purpose_of_purchase', deal.purpose_of_purchase);

    document.getElementById('num_rooms').value = deal.num_rooms || '';
    document.getElementById('area_size').value = deal.area_size || '';
    document.getElementById('num_bathrooms').value = deal.num_bathrooms || '';
    document.getElementById('budget_amount').value = deal.budget_amount || '';
    document.getElementById('budget_payment_method').value = deal.budget_payment_method || '';
    document.getElementById('timeline_urgency').value = deal.timeline_urgency || '';
    document.getElementById('move_in_target').value = deal.move_in_target || '';
    document.getElementById('stage_buying_process').value = deal.stage_buying_process || '';
    document.getElementById('deal_status').value = deal.deal_status || 'N/A';

    document.getElementById('dealModal').style.display = 'block';
}

function setSelectValue(elementId, jsonValue) {
    if (jsonValue) {
        try {
            const values = JSON.parse(jsonValue);
            jQuery('#' + elementId).val(values).trigger('change');
        } catch (e) {
            console.error('Error parsing JSON for ' + elementId + ':', e);
        }
    } else {
        jQuery('#' + elementId).val(null).trigger('change');
    }
}

function saveDeal() {
    const formData = new FormData(document.getElementById('dealForm'));
    formData.append('action', 'save_pipeline_deal');
    formData.append('nonce', '<?php echo wp_create_nonce("save_pipeline_deal"); ?>');

    jQuery.ajax({
        url: '<?php echo admin_url("admin-ajax.php"); ?>',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                alert('Deal saved successfully!');
                location.reload();
            } else {
                alert('Error: ' + (response.data || 'Unknown error'));
            }
        },
        error: function() {
            alert('An error occurred while saving the deal.');
        }
    });
}

function deleteDeal(dealId) {
    if (!confirm('Are you sure you want to delete this deal?')) {
        return;
    }

    jQuery.ajax({
        url: '<?php echo admin_url("admin-ajax.php"); ?>',
        type: 'POST',
        data: {
            action: 'delete_pipeline_deal',
            deal_id: dealId,
            nonce: '<?php echo wp_create_nonce("delete_pipeline_deal"); ?>'
        },
        success: function(response) {
            if (response.success) {
                alert('Deal deleted successfully!');
                location.reload();
            } else {
                alert('Error: ' + (response.data || 'Unknown error'));
            }
        },
        error: function() {
            alert('An error occurred while deleting the deal.');
        }
    });
}

function moveToLeads(leadId) {
    if (!confirm('Move this deal back to Leads? This will change the lead status to "Qualifying".')) {
        return;
    }

    jQuery.ajax({
        url: '<?php echo admin_url("admin-ajax.php"); ?>',
        type: 'POST',
        data: {
            action: 'move_deal_to_leads',
            lead_id: leadId,
            nonce: '<?php echo wp_create_nonce("move_deal_to_leads"); ?>'
        },
        success: function(response) {
            if (response.success) {
                alert('Deal moved back to Leads successfully!');
                location.reload();
            } else {
                alert('Error: ' + (response.data || 'Unknown error'));
            }
        },
        error: function() {
            alert('An error occurred.');
        }
    });
}

function viewDeal(deal) {
    currentViewDealId = deal.id;

    let html = '<h4 style="border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 20px;">Client Information</h4>';
    html += '<div class="form-grid">';
    html += '<div class="form-group"><strong>Full Name:</strong><p>' + (deal.fullname || 'N/A') + '</p></div>';
    html += '<div class="form-group"><strong>Email:</strong><p>' + (deal.email || 'N/A') + '</p></div>';
    html += '<div class="form-group"><strong>Phone:</strong><p>' + (deal.contact_number || 'N/A') + '</p></div>';
    html += '<div class="form-group"><strong>Assigned To:</strong><p>' + (deal.assigned_to_name || 'N/A') + '</p></div>';
    html += '</div>';

    html += '<h4 style="border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; margin: 30px 0 20px 0;">Buyer Requirements</h4>';
    html += '<div class="form-grid">';

    const propertyTypes = deal.property_type ? JSON.parse(deal.property_type).join(', ') : 'N/A';
    html += '<div class="form-group"><strong>Property Type:</strong><p>' + propertyTypes + '</p></div>';
    html += '<div class="form-group"><strong>Budget:</strong><p>' + (deal.budget_amount ? '$' + parseFloat(deal.budget_amount).toLocaleString() : 'N/A') + '</p></div>';
    html += '<div class="form-group"><strong>Payment Method:</strong><p>' + (deal.budget_payment_method || 'N/A') + '</p></div>';
    html += '<div class="form-group"><strong>Rooms:</strong><p>' + (deal.num_rooms || 'N/A') + '</p></div>';
    html += '<div class="form-group"><strong>Bathrooms:</strong><p>' + (deal.num_bathrooms || 'N/A') + '</p></div>';
    html += '<div class="form-group"><strong>Area Size:</strong><p>' + (deal.area_size || 'N/A') + '</p></div>';

    const purposes = deal.purpose_of_purchase ? JSON.parse(deal.purpose_of_purchase).join(', ') : 'N/A';
    html += '<div class="form-group full-width"><strong>Purpose:</strong><p>' + purposes + '</p></div>';
    html += '<div class="form-group"><strong>Timeline:</strong><p>' + (deal.timeline_urgency || 'N/A') + '</p></div>';
    html += '<div class="form-group"><strong>Move-in Target:</strong><p>' + (deal.move_in_target || 'N/A') + '</p></div>';
    html += '<div class="form-group"><strong>Buying Stage:</strong><p>' + (deal.stage_buying_process || 'N/A') + '</p></div>';
    html += '<div class="form-group"><strong>Deal Status:</strong><p><span class="status-badge status-' + deal.deal_status.toLowerCase().replace(/ /g, '-') + '">' + deal.deal_status + '</span></p></div>';
    html += '</div>';

    document.getElementById('viewDealContent').innerHTML = html;
    loadDealComments(deal.id);
    document.getElementById('viewDealModal').style.display = 'block';
}

function closeViewDealModal() {
    document.getElementById('viewDealModal').style.display = 'none';
    currentViewDealId = null;
}

function loadDealComments(dealId) {
    // Load comments for both the deal AND the associated lead
    jQuery.ajax({
        url: '<?php echo admin_url("admin-ajax.php"); ?>',
        type: 'POST',
        data: {
            action: 'get_all_pipeline_comments',
            deal_id: dealId,
            nonce: '<?php echo wp_create_nonce("get_all_pipeline_comments"); ?>'
        },
        success: function(response) {
            if (response.success) {
                let html = '';
                if (response.data.length > 0) {
                    response.data.forEach(function(comment) {
                        let badge = '';
                        if (comment.entity_type === 'lead') {
                            badge = '<span style="background: #007bff; color: white; padding: 2px 8px; border-radius: 3px; font-size: 10px; margin-left: 5px;">LEAD</span>';
                        } else if (comment.entity_type === 'deal') {
                            badge = '<span style="background: #28a745; color: white; padding: 2px 8px; border-radius: 3px; font-size: 10px; margin-left: 5px;">DEAL</span>';
                        }

                        html += '<div class="comment-item">';
                        html += '<div class="comment-header">';
                        html += '<span class="comment-author">' + comment.author + badge + '</span>';
                        html += '<span class="comment-date">' + comment.date + '</span>';
                        html += '</div>';
                        html += '<div class="comment-text">' + comment.comment + '</div>';
                        html += '</div>';
                    });
                } else {
                    html = '<p style="color: #999;">No comments yet.</p>';
                }
                document.getElementById('dealCommentsContainer').innerHTML = html;
            }
        }
    });
}

function addDealComment() {
    const comment = document.getElementById('newDealComment').value.trim();
    if (!comment) {
        alert('Please enter a comment.');
        return;
    }

    jQuery.ajax({
        url: '<?php echo admin_url("admin-ajax.php"); ?>',
        type: 'POST',
        data: {
            action: 'add_pipeline_comment',
            entity_type: 'deal',
            entity_id: currentViewDealId,
            comment: comment,
            nonce: '<?php echo wp_create_nonce("add_pipeline_comment"); ?>'
        },
        success: function(response) {
            if (response.success) {
                document.getElementById('newDealComment').value = '';
                loadDealComments(currentViewDealId);
            } else {
                alert('Error adding comment: ' + (response.data || 'Unknown error'));
            }
        }
    });
}

function refreshDealsFromQualifiedLeads() {
    if (!confirm('This will sync all qualified leads and create deals for them. Continue?')) {
        return;
    }

    jQuery.ajax({
        url: '<?php echo admin_url("admin-ajax.php"); ?>',
        type: 'POST',
        data: {
            action: 'sync_qualified_leads_to_deals',
            nonce: '<?php echo wp_create_nonce("sync_qualified_leads_to_deals"); ?>'
        },
        success: function(response) {
            if (response.success) {
                alert(response.data);
                location.reload();
            } else {
                alert('Error: ' + (response.data || 'Unknown error'));
            }
        }
    });
}

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target.id === 'dealModal') {
        closeDealModal();
    }
    if (event.target.id === 'viewDealModal') {
        closeViewDealModal();
    }
}
</script>
