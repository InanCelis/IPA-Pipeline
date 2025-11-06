<?php
/**
 * Partnership - Invoices Management
 * File: template-parts/partnership/invoices.php
 */

global $wpdb;
$table_invoices = $wpdb->prefix . 'partnership_invoices';
$table_payment_items = $wpdb->prefix . 'partnership_invoice_payment_items';
$table_partnerships = $wpdb->prefix . 'partnerships';

// Get filter parameters
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$status_filter = isset($_GET['payment_status']) ? sanitize_text_field($_GET['payment_status']) : '';
$type_filter = isset($_GET['invoice_type']) ? sanitize_text_field($_GET['invoice_type']) : '';
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';

// Pagination
$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where = array('i.is_active = 1');

if (!empty($search)) {
    $where[] = $wpdb->prepare(
        "(i.invoice_number LIKE %s OR i.billed_to_company LIKE %s OR i.billed_to_name LIKE %s OR p.company_name LIKE %s)",
        '%' . $wpdb->esc_like($search) . '%',
        '%' . $wpdb->esc_like($search) . '%',
        '%' . $wpdb->esc_like($search) . '%',
        '%' . $wpdb->esc_like($search) . '%'
    );
}

if (!empty($status_filter)) {
    $where[] = $wpdb->prepare("i.payment_status = %s", $status_filter);
}

if (!empty($type_filter)) {
    $where[] = $wpdb->prepare("i.invoice_type = %s", $type_filter);
}

if (!empty($date_from)) {
    $where[] = $wpdb->prepare("i.date_issued >= %s", $date_from);
}

if (!empty($date_to)) {
    $where[] = $wpdb->prepare("i.date_issued <= %s", $date_to);
}

$where_clause = implode(' AND ', $where);

// Get total count
$total_invoices = $wpdb->get_var("
    SELECT COUNT(*)
    FROM $table_invoices i
    LEFT JOIN $table_partnerships p ON i.partnership_id = p.id
    WHERE $where_clause
");
$total_pages = ceil($total_invoices / $per_page);

// Get invoices with partnership info
$invoices = $wpdb->get_results("
    SELECT i.*, p.company_name as partnership_company
    FROM $table_invoices i
    LEFT JOIN $table_partnerships p ON i.partnership_id = p.id
    WHERE $where_clause
    ORDER BY i.date_issued DESC, i.id DESC
    LIMIT $offset, $per_page
");

// Get statistics (using USD amounts for consistency in reports)
$stats = array(
    'total' => $wpdb->get_var("SELECT COUNT(*) FROM $table_invoices WHERE is_active = 1"),
    'pending' => $wpdb->get_var("SELECT COUNT(*) FROM $table_invoices WHERE payment_status = 'Pending' AND is_active = 1"),
    'sent' => $wpdb->get_var("SELECT COUNT(*) FROM $table_invoices WHERE payment_status = 'Sent' AND is_active = 1"),
    'partial' => $wpdb->get_var("SELECT COUNT(*) FROM $table_invoices WHERE payment_status = 'Partial' AND is_active = 1"),
    'fully_paid' => $wpdb->get_var("SELECT COUNT(*) FROM $table_invoices WHERE payment_status = 'Fully Paid' AND is_active = 1"),
    'overdue' => $wpdb->get_var("SELECT COUNT(*) FROM $table_invoices WHERE payment_status = 'Overdue' AND is_active = 1"),
    'total_amount' => $wpdb->get_var("SELECT SUM(COALESCE(amount_usd, total_amount, 0)) FROM $table_invoices WHERE is_active = 1"),
    'paid_amount' => $wpdb->get_var("SELECT SUM(COALESCE(amount_usd, total_amount, 0)) FROM $table_invoices WHERE payment_status = 'Fully Paid' AND is_active = 1"),
    'unpaid_amount' => $wpdb->get_var("SELECT SUM(COALESCE(amount_usd, total_amount, 0)) FROM $table_invoices WHERE payment_status != 'Fully Paid' AND is_active = 1"),
    'ipa_invoices' => $wpdb->get_var("SELECT COUNT(*) FROM $table_invoices WHERE invoice_type = 'International Property Alerts' AND is_active = 1"),
    'primetask_invoices' => $wpdb->get_var("SELECT COUNT(*) FROM $table_invoices WHERE invoice_type = 'Primetask VA Inc.' AND is_active = 1")
);

// Get partnerships for dropdown
$partnerships = $wpdb->get_results("SELECT id, company_name FROM $table_partnerships ORDER BY company_name");

// Get dropdown options
$project_options = array_filter(explode("\n", get_option('partnership_field_invoice_project', '')));
$package_tier_options = array_filter(explode("\n", get_option('partnership_field_invoice_package_tier', '')));
$project_duration_options = array_filter(explode("\n", get_option('partnership_field_invoice_project_duration', '')));

// Get currencies
$table_currencies = $wpdb->prefix . 'houzez_currencies';
$currencies = $wpdb->get_results("SELECT * FROM $table_currencies ORDER BY currency_name");

// Create a currency symbol lookup array for table display
$currency_symbols = array();
foreach ($wpdb->get_results("SELECT currency_code, currency_symbol FROM $table_currencies") as $curr) {
    $currency_symbols[$curr->currency_code] = $curr->currency_symbol;
}
?>

<style>
.dropdown-menu a:hover {
    background-color: #f5f5f5 !important;
}
.btn-light.dropdown-toggle:hover {
    background: #e2e6ea !important;
    border-color: #dae0e5 !important;
}
.btn-success:hover {
    background: #218838 !important;
    border-color: #1e7e34 !important;
}
</style>

<div class="partnership-header">
    <h2 class="partnership-title">Partnership Invoices</h2>
    <button class="btn btn-primary" onclick="openAddInvoiceModal()">
        <i class="houzez-icon icon-add-circle"></i> Create New Invoice
    </button>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Invoices</div>
        <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Pending</div>
        <div class="stat-value"><?php echo number_format($stats['pending']); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Sent</div>
        <div class="stat-value"><?php echo number_format($stats['sent']); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Partial Paid</div>
        <div class="stat-value"><?php echo number_format($stats['partial']); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Fully Paid</div>
        <div class="stat-value"><?php echo number_format($stats['fully_paid']); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Overdue</div>
        <div class="stat-value"><?php echo number_format($stats['overdue']); ?></div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 20px;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 25px; border-radius: 8px; color: white;">
        <div style="font-size: 14px; opacity: 0.9;">Total Invoice Amount</div>
        <div style="font-size: 32px; font-weight: bold;">$<?php echo number_format($stats['total_amount'] ?: 0, 2); ?></div>
    </div>
    <div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); padding: 25px; border-radius: 8px; color: white;">
        <div style="font-size: 14px; opacity: 0.9;">Total Paid Amount</div>
        <div style="font-size: 32px; font-weight: bold;">$<?php echo number_format($stats['paid_amount'] ?: 0, 2); ?></div>
    </div>
    <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 25px; border-radius: 8px; color: white;">
        <div style="font-size: 14px; opacity: 0.9;">Total Unpaid Amount</div>
        <div style="font-size: 32px; font-weight: bold;">$<?php echo number_format($stats['unpaid_amount'] ?: 0, 2); ?></div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px;">
    <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); padding: 25px; border-radius: 8px; color: white;">
        <div style="font-size: 14px; opacity: 0.9;">IPA Invoices</div>
        <div style="font-size: 32px; font-weight: bold;"><?php echo number_format($stats['ipa_invoices']); ?></div>
    </div>
    <div style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); padding: 25px; border-radius: 8px; color: white;">
        <div style="font-size: 14px; opacity: 0.9;">Primetask Invoices</div>
        <div style="font-size: 32px; font-weight: bold;"><?php echo number_format($stats['primetask_invoices']); ?></div>
    </div>
</div>

<!-- Filters -->
<div class="filter-section">
    <form method="GET" action="">
        <input type="hidden" name="hpage" value="invoices">
        <div class="filter-row">
            <div class="filter-group">
                <label>Search</label>
                <input type="text" name="search" class="filter-input" placeholder="Search by invoice #, partner, billed to" value="<?php echo esc_attr($search); ?>">
            </div>
            <div class="filter-group">
                <label>Invoice Type</label>
                <select name="invoice_type" class="filter-select">
                    <option value="">All Types</option>
                    <option value="International Property Alerts" <?php selected($type_filter, 'International Property Alerts'); ?>>International Property Alerts</option>
                    <option value="Primetask VA Inc." <?php selected($type_filter, 'Primetask VA Inc.'); ?>>Primetask VA Inc.</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Payment Status</label>
                <select name="payment_status" class="filter-select">
                    <option value="">All Statuses</option>
                    <option value="Pending" <?php selected($status_filter, 'Pending'); ?>>Pending</option>
                    <option value="Sent" <?php selected($status_filter, 'Sent'); ?>>Sent</option>
                    <option value="Partial" <?php selected($status_filter, 'Partial'); ?>>Partial</option>
                    <option value="Fully Paid" <?php selected($status_filter, 'Fully Paid'); ?>>Fully Paid</option>
                    <option value="Overdue" <?php selected($status_filter, 'Overdue'); ?>>Overdue</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Date From</label>
                <input type="date" name="date_from" class="filter-input" value="<?php echo esc_attr($date_from); ?>">
            </div>
            <div class="filter-group">
                <label>Date To</label>
                <input type="date" name="date_to" class="filter-input" value="<?php echo esc_attr($date_to); ?>">
            </div>
            <div class="filter-group">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="?hpage=invoices" class="btn btn-secondary">Reset</a>
            </div>
        </div>
    </form>
</div>

<!-- Invoices Table -->
<table class="pipeline-table w-100">
    <thead>
        <tr>
            <th>Invoice #</th>
            <th>Type</th>
            <th>Partner</th>
            <th>Billed To</th>
            <th>Date Issued</th>
            <th>Due Date</th>
            <th>Amount</th>
            <th>USD Amount</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($invoices)) : ?>
            <tr>
                <td colspan="10" class="no-results">No invoices found</td>
            </tr>
        <?php else : ?>
            <?php foreach ($invoices as $invoice) :
                $status_class = 'status-' . strtolower(str_replace(' ', '-', $invoice->payment_status));
            ?>
                <tr>
                    <td data-label="Invoice #"><strong><?php echo esc_html($invoice->invoice_number); ?></strong></td>
                    <td data-label="Type">
                        <?php if ($invoice->invoice_type === 'International Property Alerts') : ?>
                            <span style="background: #2196f3; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;">IPA</span>
                        <?php else : ?>
                            <span style="background: #ff9800; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;">PRIMETASK</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Partner"><?php echo esc_html($invoice->partnership_company); ?></td>
                    <td data-label="Billed To"><?php echo esc_html($invoice->billed_to_name ?: $invoice->billed_to_company); ?></td>
                    <td data-label="Date Issued"><?php echo date('M d, Y', strtotime($invoice->date_issued)); ?></td>
                    <td data-label="Due Date"><?php echo date('M d, Y', strtotime($invoice->due_date)); ?></td>
                    <td data-label="Amount"><strong><?php
                        $curr_code = !empty($invoice->currency_code) ? $invoice->currency_code : 'USD';
                        $curr_symbol = isset($currency_symbols[$curr_code]) ? $currency_symbols[$curr_code] : '$';
                        echo esc_html($curr_symbol) . number_format($invoice->total_amount, 2);
                    ?></strong></td>
                    <td data-label="USD Amount"><strong>$<?php
                        $usd_amount = !empty($invoice->amount_usd) ? $invoice->amount_usd : $invoice->total_amount;
                        echo number_format($usd_amount, 2);
                    ?></strong></td>
                    <td data-label="Status"><span class="status-badge <?php echo $status_class; ?>"><?php echo esc_html($invoice->payment_status); ?></span></td>
                    <td data-label="Actions">
                        <?php
                        // Get comment count for this invoice
                        $comments_table = $wpdb->prefix . 'invoice_comments';
                        $comment_count = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$comments_table} WHERE invoice_id = %d AND invoice_type = 'partnership'",
                            $invoice->id
                        ));
                        ?>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <!-- Comments Button (Green) -->
                            <button class="btn btn-sm btn-success" onclick="openInvoiceCommentModal(<?php echo $invoice->id; ?>, 'partnership')" title="Comments" style="position: relative; padding: 5px 10px; border: 1px solid #28a745; background: #28a745; color: white; border-radius: 4px; cursor: pointer;">
                                <i class="houzez-icon icon-messages-bubble"></i>
                                <?php if ($comment_count > 0): ?>
                                    <span style="position: absolute;
                                        margin-top: -45px;
                                        margin-left: 8px;
                                        background: #dc3545;
                                        color: #fff;
                                        font-size: 10px;
                                        font-weight: 600;
                                        border-radius: 50%;
                                        padding: 3px 6px;
                                        min-width: 16px;
                                        height: 16px;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        line-height: 1;
                                        box-shadow: 0 0 2px rgba(0, 0, 0, 0.2);
                                        z-index: 99;">
                                        <?php echo intval($comment_count); ?>
                                    </span>
                                <?php endif; ?>
                            </button>

                            <!-- Actions Dropdown (Light Gray) -->
                            <div class="dropdown" style="position: relative; display: inline-block;">
                                <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="actionsDropdown<?php echo $invoice->id; ?>" onclick="toggleInvoiceDropdown(<?php echo $invoice->id; ?>)" style="padding: 5px 15px; border: 1px solid #ddd; background: #f8f9fa; color: #333; border-radius: 4px; cursor: pointer;">
                                    Actions
                                </button>
                                <div id="dropdownMenu<?php echo $invoice->id; ?>" class="dropdown-menu" style="display: none; position: absolute; right: 0; top: 100%; background: white; border: 1px solid #ddd; border-radius: 4px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); min-width: 160px; z-index: 1000; margin-top: 2px;">
                                    <a href="javascript:void(0)" onclick="editInvoice(<?php echo $invoice->id; ?>); toggleInvoiceDropdown(<?php echo $invoice->id; ?>)" style="display: block; padding: 8px 15px; color: #333; text-decoration: none; border-bottom: 1px solid #f0f0f0;">
                                        <i class="houzez-icon icon-pencil"></i> Edit
                                    </a>
                                    <a href="javascript:void(0)" onclick="previewInvoicePDF(<?php echo $invoice->id; ?>); toggleInvoiceDropdown(<?php echo $invoice->id; ?>)" style="display: block; padding: 8px 15px; color: #333; text-decoration: none; border-bottom: 1px solid #f0f0f0;">
                                        <i class="houzez-icon icon-print-text"></i> Preview
                                    </a>
                                    <a href="javascript:void(0)" onclick="downloadInvoicePDF(<?php echo $invoice->id; ?>); toggleInvoiceDropdown(<?php echo $invoice->id; ?>)" style="display: block; padding: 8px 15px; color: #333; text-decoration: none; border-bottom: 1px solid #f0f0f0;">
                                        <i class="houzez-icon icon-download-bottom"></i> Download
                                    </a>
                                    <a href="javascript:void(0)" onclick="deleteInvoice(<?php echo $invoice->id; ?>); toggleInvoiceDropdown(<?php echo $invoice->id; ?>)" style="display: block; padding: 8px 15px; color: #dc3545; text-decoration: none;">
                                        <i class="houzez-icon icon-remove-circle"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<!-- Pagination -->
<?php if ($total_pages > 1) :
    // Build query string to preserve filters
    $filter_params = array();
    if (!empty($search)) $filter_params[] = 'search=' . urlencode($search);
    if (!empty($type_filter)) $filter_params[] = 'invoice_type=' . urlencode($type_filter);
    if (!empty($status_filter)) $filter_params[] = 'payment_status=' . urlencode($status_filter);
    if (!empty($date_from)) $filter_params[] = 'date_from=' . urlencode($date_from);
    if (!empty($date_to)) $filter_params[] = 'date_to=' . urlencode($date_to);
    $filter_query = !empty($filter_params) ? '&' . implode('&', $filter_params) : '';
?>
    <div class="pagination">
        <button onclick="window.location.href='?hpage=invoices&paged=1<?php echo $filter_query; ?>'" <?php echo $page <= 1 ? 'disabled' : ''; ?>>First</button>
        <button onclick="window.location.href='?hpage=invoices&paged=<?php echo max(1, $page - 1) . $filter_query; ?>'" <?php echo $page <= 1 ? 'disabled' : ''; ?>>Previous</button>
        <span class="page-info">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
        <button onclick="window.location.href='?hpage=invoices&paged=<?php echo min($total_pages, $page + 1) . $filter_query; ?>'" <?php echo $page >= $total_pages ? 'disabled' : ''; ?>>Next</button>
        <button onclick="window.location.href='?hpage=invoices&paged=<?php echo $total_pages . $filter_query; ?>'" <?php echo $page >= $total_pages ? 'disabled' : ''; ?>>Last</button>
    </div>
<?php endif; ?>

<!-- Add/Edit Invoice Modal -->
<div id="invoiceModal" class="modal">
    <div class="modal-content" style="max-width: 1200px; max-height: 90vh; overflow-y: auto;">
        <div class="modal-header">
            <h3 class="modal-title" id="invoiceModalTitle">Create Partnership Invoice</h3>
            <button class="close" onclick="closeInvoiceModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="invoiceForm">
                <input type="hidden" id="invoice_id" name="invoice_id">

                <!-- Basic Information -->
                <h4 style="margin: 0 0 20px 0; color: #333; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">
                    <i class="houzez-icon icon-file-text-1"></i> Invoice Information
                </h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="required">Type of Invoice</label>
                        <select class="form-control" id="invoice_type" name="invoice_type" required>
                            <option value="">Select Type</option>
                            <option value="International Property Alerts">International Property Alerts</option>
                            <option value="Primetask VA Inc.">Primetask VA Inc.</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="required">Partner</label>
                        <select class="form-control" id="partnership_id" name="partnership_id" required>
                            <option value="">Select Partner</option>
                            <?php foreach ($partnerships as $partnership) : ?>
                                <option value="<?php echo $partnership->id; ?>"><?php echo esc_html($partnership->company_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="required">Date Issued</label>
                        <input type="date" class="form-control" id="date_issued" name="date_issued" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label class="required">Due Date</label>
                        <input type="date" class="form-control" id="due_date" name="due_date" required value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                    </div>
                </div>

                <div class="form-group" style="margin-top: 15px;">
                    <label>Invoice Number</label>
                    <input type="text" class="form-control" id="invoice_number_display" name="invoice_number" readonly style="background-color: #f0f0f0; font-weight: bold; color: #333;" placeholder="Auto-generated">
                    <small style="color: #666;">Format: INV-YYYY-MM-IPAP-00001 or INV-YYYY-MM-PVIP-00001 (auto-generated)</small>
                </div>

                <!-- Billed To -->
                <h4 style="margin: 30px 0 20px 0; color: #333; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">
                    <i class="houzez-icon icon-single-neutral"></i> Billed To
                </h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" class="form-control" id="billed_to_name" name="billed_to_name">
                    </div>
                    <div class="form-group">
                        <label>Position</label>
                        <input type="text" class="form-control" id="billed_to_position" name="billed_to_position">
                    </div>
                    <div class="form-group">
                        <label>Company Name</label>
                        <input type="text" class="form-control" id="billed_to_company" name="billed_to_company">
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" class="form-control" id="billed_to_address" name="billed_to_address">
                    </div>
                </div>
                <div class="form-group" style="margin-top: 15px;">
                    <label>Other Details</label>
                    <textarea class="form-control" id="other_details" name="other_details" rows="3" placeholder="Enter any additional details..."></textarea>
                </div>

                <!-- Service Description -->
                <h4 style="margin: 30px 0 20px 0; color: #333; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">
                    <i class="houzez-icon icon-cog-1"></i> Service Description
                </h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Project</label>
                        <select class="form-control" id="service_project" name="service_project">
                            <option value="">Select Project</option>
                            <?php foreach ($project_options as $option) : ?>
                                <option value="<?php echo esc_attr(trim($option)); ?>"><?php echo esc_html(trim($option)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Package Tier</label>
                        <select class="form-control" id="service_package_tier" name="service_package_tier">
                            <option value="">Select Package Tier</option>
                            <?php foreach ($package_tier_options as $option) : ?>
                                <option value="<?php echo esc_attr(trim($option)); ?>"><?php echo esc_html(trim($option)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Project Duration</label>
                        <select class="form-control" id="service_project_duration" name="service_project_duration">
                            <option value="">Select Duration</option>
                            <?php foreach ($project_duration_options as $option) : ?>
                                <option value="<?php echo esc_attr(trim($option)); ?>"><?php echo esc_html(trim($option)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Telemarketers</label>
                        <input type="text" class="form-control" id="service_telemarketers" name="service_telemarketers">
                    </div>
                    <div class="form-group">
                        <label>Monthly Hours</label>
                        <input type="text" class="form-control" id="service_monthly_hours" name="service_monthly_hours">
                    </div>
                </div>

                <div class="form-group" style="margin-top: 15px;">
                    <label>Scope of Work</label>
                    <textarea class="form-control" id="scope_of_work" name="scope_of_work" rows="4"></textarea>
                </div>

                <!-- Schedule of Payment -->
                <h4 style="margin: 30px 0 20px 0; color: #333; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
                    <span><i class="houzez-icon icon-coin-dollar"></i> Schedule of Payment</span>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addPaymentItem()">
                        <i class="houzez-icon icon-add-circle"></i> Add Item
                    </button>
                </h4>

                <div id="payment_items_container">
                    <!-- Payment items will be dynamically added here -->
                </div>

                <!-- Currency Selection -->
                <div class="form-group" style="margin-top: 20px;">
                    <label for="currency_code">Currency <span style="color: red;">*</span></label>
                    <select class="form-control" id="currency_code" name="currency_code" required onchange="updateCurrencyDisplay()">
                        <?php if (!empty($currencies)): ?>
                            <?php foreach ($currencies as $currency): ?>
                                <option value="<?php echo esc_attr($currency->currency_code); ?>"
                                        data-symbol="<?php echo esc_attr($currency->currency_symbol); ?>"
                                        <?php echo $currency->currency_code === 'USD' ? 'selected' : ''; ?>>
                                    <?php echo esc_html($currency->currency_name . ' (' . $currency->currency_code . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="USD" data-symbol="$" selected>United States Dollar (USD)</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 20px; text-align: right;">
                    <div style="font-size: 18px; color: #333; margin-bottom: 10px;">
                        <strong>Total Amount:</strong>
                    </div>
                    <div style="font-size: 32px; font-weight: bold; color: var(--e-global-color-primary);" id="total_amount_display">
                        $0.00
                    </div>
                    <input type="hidden" id="total_amount" name="total_amount" value="0">
                </div>

                <!-- Payment Status -->
                <h4 style="margin: 30px 0 20px 0; color: #333; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">
                    <i class="houzez-icon icon-task-list-text-1"></i> Payment Status
                </h4>
                <div class="form-group">
                    <label class="required">Payment Status</label>
                    <select class="form-control" id="payment_status" name="payment_status" required>
                        <option value="Pending">Pending</option>
                        <option value="Sent">Sent</option>
                        <option value="Partial">Partial</option>
                        <option value="Fully Paid">Fully Paid</option>
                        <option value="Overdue">Overdue</option>
                    </select>
                    <small style="color: #666;">Note: Status will automatically change to "Overdue" when due date passes</small>
                </div>
            </form>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeInvoiceModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="saveInvoice()">
                <i class="houzez-icon icon-check-circle"></i> Save Invoice
            </button>
        </div>
    </div>
</div>

<!-- PDF Preview Modal -->
<div id="pdfPreviewModal" class="modal">
    <div class="modal-content" style="max-width: 900px; max-height: 90vh;">
        <div class="modal-header">
            <h3 class="modal-title">Invoice Preview</h3>
            <button class="close" onclick="closePdfPreviewModal()">&times;</button>
        </div>
        <div class="modal-body" id="pdfPreviewContent" style="padding: 40px; background: white;">
            <!-- PDF content will be loaded here -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closePdfPreviewModal()">Close</button>
            <button type="button" class="btn btn-primary" onclick="downloadCurrentInvoice()">
                <i class="houzez-icon icon-download-bottom"></i> Download PDF
            </button>
        </div>
    </div>
</div>

<!-- Invoice Comments Modal -->
<div id="invoiceCommentModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="houzez-icon icon-messages-bubble"></i> Invoice Comments
            </h3>
            <button class="close" onclick="closeInvoiceCommentModal()">&times;</button>
        </div>
        <form id="invoiceCommentForm">
            <input type="hidden" name="invoice_id" id="commentInvoiceId">
            <input type="hidden" name="invoice_type" id="commentInvoiceType">

            <div class="modal-body">
                <!-- Comments Container -->
                <div id="invoiceCommentsContainer" style="max-height: 400px; overflow-y: auto; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                    <div style="text-align: center; padding: 20px;">
                        <i class="houzez-icon icon-spinner-1 rotate-icon"></i> Loading comments...
                    </div>
                </div>

                <!-- Add Comment Form -->
                <div style="padding: 0;">
                    <label for="commentText" style="display: block; margin-bottom: 8px; font-weight: 600;">Add a comment:</label>
                    <textarea
                        name="comment"
                        id="commentText"
                        class="form-control"
                        rows="3"
                        required
                        placeholder="Type your comment here..."
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; resize: vertical;"
                    ></textarea>
                </div>
            </div>

            <div class="modal-footer" style="display: flex; justify-content: space-between; padding: 15px 20px; border-top: 1px solid #ddd;">
                <button type="button" class="btn btn-secondary" onclick="closeInvoiceCommentModal()">Close</button>
                <button type="submit" class="btn btn-primary">
                    <i class="houzez-icon icon-messages-bubble"></i> Send Comment
                </button>
            </div>
        </form>
    </div>
</div>

<script>
var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
let paymentItemCount = 0;
let currentInvoiceId = null;

function openAddInvoiceModal() {
    document.getElementById('invoiceModalTitle').textContent = 'Create Partnership Invoice';
    document.getElementById('invoiceForm').reset();
    document.getElementById('invoice_id').value = '';
    document.getElementById('payment_items_container').innerHTML = '';
    paymentItemCount = 0;
    addPaymentItem(); // Add one default item
    document.getElementById('invoiceModal').style.display = 'block';
}

function closeInvoiceModal() {
    document.getElementById('invoiceModal').style.display = 'none';
}

function closePdfPreviewModal() {
    document.getElementById('pdfPreviewModal').style.display = 'none';
}

function addPaymentItem() {
    paymentItemCount++;
    const container = document.getElementById('payment_items_container');
    const itemHtml = `
        <div class="payment-item" id="payment_item_${paymentItemCount}" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 15px; border: 2px solid #e0e0e0;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h5 style="margin: 0; color: #333;">
                    <i class="houzez-icon icon-coin-dollar"></i> Payment Item #${paymentItemCount}
                </h5>
                <button type="button" class="btn btn-sm btn-danger" onclick="removePaymentItem(${paymentItemCount})">
                    <i class="houzez-icon icon-remove-circle"></i> Remove
                </button>
            </div>
            <div class="form-grid">
                <div class="form-group" style="grid-column: span 2;">
                    <label>Description</label>
                    <input type="text" class="form-control payment-description" name="payment_items[${paymentItemCount}][description]" placeholder="Enter payment description">
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" class="form-control payment-date" name="payment_items[${paymentItemCount}][payment_date]">
                </div>
                <div class="form-group">
                    <label>Amount Due</label>
                    <input type="number" step="0.01" class="form-control payment-amount" name="payment_items[${paymentItemCount}][amount_due]" placeholder="0.00" oninput="calculateTotal()">
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', itemHtml);
}

function removePaymentItem(itemId) {
    const item = document.getElementById('payment_item_' + itemId);
    if (item) {
        item.remove();
        calculateTotal();
    }
}

function calculateTotal() {
    const amounts = document.querySelectorAll('.payment-amount');
    let total = 0;
    amounts.forEach(input => {
        const value = parseFloat(input.value) || 0;
        total += value;
    });
    const currencySelect = document.getElementById('currency_code');
    const selectedOption = currencySelect.options[currencySelect.selectedIndex];
    const currencySymbol = selectedOption.getAttribute('data-symbol') || '$';
    document.getElementById('total_amount_display').textContent = currencySymbol + total.toFixed(2);
    document.getElementById('total_amount').value = total.toFixed(2);
}

function updateCurrencyDisplay() {
    calculateTotal();
}

function saveInvoice() {
    const form = document.getElementById('invoiceForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    formData.append('action', 'save_partnership_invoice');
    formData.append('nonce', '<?php echo wp_create_nonce("partnership_invoice_nonce"); ?>');

    // Collect payment items
    const paymentItems = [];
    document.querySelectorAll('.payment-item').forEach(item => {
        const description = item.querySelector('.payment-description').value;
        const date = item.querySelector('.payment-date').value;
        const amount = item.querySelector('.payment-amount').value;
        if (description || date || amount) {
            paymentItems.push({
                description: description,
                payment_date: date,
                amount_due: amount
            });
        }
    });
    formData.append('payment_items', JSON.stringify(paymentItems));

    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            console.log('Save response:', response);
            if (response.success) {
                alert('Invoice saved successfully!');
                closeInvoiceModal();
                location.reload();
            } else {
                alert('Error: ' + (response.data || 'Unknown error occurred'));
                console.error('Save error:', response);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', xhr, status, error);
            alert('An error occurred while saving the invoice. Check console for details.');
        }
    });
}

function editInvoice(invoiceId) {
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'get_partnership_invoice',
            invoice_id: invoiceId,
            nonce: '<?php echo wp_create_nonce("partnership_invoice_nonce"); ?>'
        },
        success: function(response) {
            if (response.success) {
                const invoice = response.data.invoice;
                const items = response.data.payment_items;

                document.getElementById('invoiceModalTitle').textContent = 'Edit Partnership Invoice';
                document.getElementById('invoice_id').value = invoice.id;
                document.getElementById('invoice_type').value = invoice.invoice_type;
                document.getElementById('partnership_id').value = invoice.partnership_id;
                document.getElementById('date_issued').value = invoice.date_issued;
                document.getElementById('due_date').value = invoice.due_date;
                document.getElementById('invoice_number_display').value = invoice.invoice_number;
                document.getElementById('billed_to_name').value = invoice.billed_to_name || '';
                document.getElementById('billed_to_position').value = invoice.billed_to_position || '';
                document.getElementById('billed_to_company').value = invoice.billed_to_company || '';
                document.getElementById('billed_to_address').value = invoice.billed_to_address || '';
                document.getElementById('other_details').value = invoice.other_details || '';
                document.getElementById('service_project').value = invoice.service_project || '';
                document.getElementById('service_package_tier').value = invoice.service_package_tier || '';
                document.getElementById('service_project_duration').value = invoice.service_project_duration || '';
                document.getElementById('service_telemarketers').value = invoice.service_telemarketers || '';
                document.getElementById('service_monthly_hours').value = invoice.service_monthly_hours || '';
                document.getElementById('scope_of_work').value = invoice.scope_of_work || '';
                document.getElementById('payment_status').value = invoice.payment_status;
                document.getElementById('currency_code').value = invoice.currency_code || 'USD';

                // Load payment items
                document.getElementById('payment_items_container').innerHTML = '';
                paymentItemCount = 0;
                if (items && items.length > 0) {
                    items.forEach(item => {
                        addPaymentItem();
                        const currentItem = document.querySelector(`#payment_item_${paymentItemCount}`);
                        currentItem.querySelector('.payment-description').value = item.description || '';
                        currentItem.querySelector('.payment-date').value = item.payment_date || '';
                        currentItem.querySelector('.payment-amount').value = item.amount_due || '';
                    });
                } else {
                    addPaymentItem();
                }

                calculateTotal();
                document.getElementById('invoiceModal').style.display = 'block';
            } else {
                alert('Error loading invoice: ' + (response.data || 'Unknown error'));
            }
        },
        error: function() {
            alert('An error occurred while loading the invoice.');
        }
    });
}

function deleteInvoice(invoiceId) {
    if (!confirm('Are you sure you want to delete this invoice?')) {
        return;
    }

    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'delete_partnership_invoice',
            invoice_id: invoiceId,
            nonce: '<?php echo wp_create_nonce("partnership_invoice_nonce"); ?>'
        },
        success: function(response) {
            if (response.success) {
                alert('Invoice deleted successfully!');
                location.reload();
            } else {
                alert('Error: ' + (response.data || 'Unknown error occurred'));
            }
        },
        error: function() {
            alert('An error occurred while deleting the invoice.');
        }
    });
}

function previewInvoicePDF(invoiceId) {
    currentInvoiceId = invoiceId;
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'preview_partnership_invoice_pdf',
            invoice_id: invoiceId,
            nonce: '<?php echo wp_create_nonce("partnership_invoice_nonce"); ?>'
        },
        success: function(response) {
            if (response.success) {
                document.getElementById('pdfPreviewContent').innerHTML = response.data;
                document.getElementById('pdfPreviewModal').style.display = 'block';
            } else {
                alert('Error: ' + (response.data || 'Unknown error occurred'));
            }
        },
        error: function() {
            alert('An error occurred while generating the preview.');
        }
    });
}

function downloadInvoicePDF(invoiceId) {
    const nonce = '<?php echo wp_create_nonce("partnership_invoice_pdf_download"); ?>';
    window.open(ajaxurl + '?action=download_partnership_invoice_pdf&invoice_id=' + invoiceId + '&nonce=' + nonce, '_blank');
}

function downloadCurrentInvoice() {
    if (currentInvoiceId) {
        downloadInvoicePDF(currentInvoiceId);
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const invoiceModal = document.getElementById('invoiceModal');
    const pdfModal = document.getElementById('pdfPreviewModal');
    const commentModal = document.getElementById('invoiceCommentModal');
    if (event.target === invoiceModal) {
        closeInvoiceModal();
    }
    if (event.target === pdfModal) {
        closePdfPreviewModal();
    }
    if (event.target === commentModal) {
        closeInvoiceCommentModal();
    }
}

// ============================================
// INVOICE COMMENTS FUNCTIONALITY
// ============================================

let currentCommentInvoiceId = null;
let currentCommentInvoiceType = null;

function openInvoiceCommentModal(invoiceId, invoiceType) {
    currentCommentInvoiceId = invoiceId;
    currentCommentInvoiceType = invoiceType;
    document.getElementById('commentInvoiceId').value = invoiceId;
    document.getElementById('commentInvoiceType').value = invoiceType;
    loadInvoiceComments(invoiceId, invoiceType);
    document.getElementById('invoiceCommentModal').style.display = 'block';
}

function closeInvoiceCommentModal() {
    document.getElementById('invoiceCommentModal').style.display = 'none';
    document.getElementById('invoiceCommentForm').reset();
    currentCommentInvoiceId = null;
    currentCommentInvoiceType = null;
}

function loadInvoiceComments(invoiceId, invoiceType) {
    const container = document.getElementById('invoiceCommentsContainer');
    const currentUserId = <?php echo get_current_user_id(); ?>;

    container.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="houzez-icon icon-spinner-1 rotate-icon"></i> Loading comments...</div>';

    jQuery.ajax({
        url: ajaxurl + '?action=get_invoice_comments&invoice_id=' + invoiceId + '&invoice_type=' + invoiceType,
        type: 'GET',
        success: function(response) {
            if (response.success) {
                const comments = response.data.comments;

                if (comments.length === 0) {
                    container.innerHTML = '<div style="text-align: center; padding: 30px; color: #999;">No comments yet. Be the first to comment!</div>';
                    return;
                }

                // Reverse to show oldest first
                const reversedComments = comments.reverse();
                let html = '';

                reversedComments.forEach(comment => {
                    const isCurrentUser = String(comment.user_id).trim() === String(currentUserId).trim();

                    html += `
                        <div style="display: flex; justify-content: ${isCurrentUser ? 'flex-end' : 'flex-start'}; margin-bottom: 15px;">
                            <div style="max-width: 70%; background: ${isCurrentUser ? '#0084ff' : '#e4e6eb'}; color: ${isCurrentUser ? '#fff' : '#000'}; padding: 10px 15px; border-radius: 18px; position: relative;">
                                ${!isCurrentUser ? `<div style="font-weight: bold; margin-bottom: 5px; font-size: 12px;">${comment.user_name}</div>` : ''}
                                <div style="word-wrap: break-word; white-space: pre-wrap;">${comment.comment}</div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 5px; gap: 10px;">
                                    <small style="opacity: 0.8; font-size: 11px;">${comment.created_at}</small>
                                    ${isCurrentUser ? `
                                        <button onclick="deleteInvoiceComment(${comment.id}, ${invoiceId}, '${invoiceType}')"
                                                style="background: none; border: none; color: ${isCurrentUser ? '#fff' : '#dc3545'}; cursor: pointer; padding: 2px 5px; font-size: 11px; opacity: 0.8;"
                                                title="Delete comment">
                                            <i class="houzez-icon icon-remove-circle"></i> Delete
                                        </button>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                });

                container.innerHTML = html;
                // Scroll to bottom
                container.scrollTop = container.scrollHeight;
            } else {
                container.innerHTML = '<div style="text-align: center; padding: 20px; color: #dc3545;">Error loading comments</div>';
            }
        },
        error: function() {
            container.innerHTML = '<div style="text-align: center; padding: 20px; color: #dc3545;">Error loading comments</div>';
        }
    });
}

function deleteInvoiceComment(commentId, invoiceId, invoiceType) {
    if (!confirm('Are you sure you want to delete this comment?')) {
        return;
    }

    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'delete_invoice_comment',
            comment_id: commentId
        },
        success: function(response) {
            if (response.success) {
                loadInvoiceComments(invoiceId, invoiceType);
            } else {
                alert('Error: ' + (response.data.message || 'Failed to delete comment'));
            }
        },
        error: function() {
            alert('An error occurred while deleting the comment');
        }
    });
}

// Handle comment form submission
jQuery(document).ready(function($) {
    $('#invoiceCommentForm').on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        formData.append('action', 'add_invoice_comment');

        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).text('Sending...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: Object.fromEntries(formData),
            success: function(response) {
                if (response.success) {
                    $('#invoiceCommentForm textarea[name="comment"]').val('');
                    loadInvoiceComments(currentCommentInvoiceId, currentCommentInvoiceType);

                    // Update comment count badge in table
                    location.reload();
                } else {
                    alert('Error: ' + (response.data.message || 'Failed to add comment'));
                }
            },
            error: function() {
                alert('An error occurred while adding the comment');
            },
            complete: function() {
                submitBtn.prop('disabled', false).text('Send Comment');
            }
        });
    });

    // Close dropdowns when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.dropdown').length) {
            $('.dropdown-menu').hide();
        }
    });
});

// Toggle dropdown menu
function toggleInvoiceDropdown(invoiceId) {
    const dropdown = document.getElementById('dropdownMenu' + invoiceId);
    const isVisible = dropdown.style.display === 'block';

    // Close all dropdowns first
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        menu.style.display = 'none';
    });

    // Toggle current dropdown
    dropdown.style.display = isVisible ? 'none' : 'block';
}
</script>
