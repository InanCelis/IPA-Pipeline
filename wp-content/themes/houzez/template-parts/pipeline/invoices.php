<?php
/**
 * Pipeline - Invoices & Documentation
 */

global $wpdb;
$table_invoices = $wpdb->prefix . 'pipeline_invoices';
$table_leads = $wpdb->prefix . 'pipeline_leads';
$table_deals = $wpdb->prefix . 'pipeline_deals';
$table_partnerships = $wpdb->prefix . 'partnerships';

// Get filter parameters
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$status_filter = isset($_GET['payment_status']) ? sanitize_text_field($_GET['payment_status']) : '';
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
        "(l.fullname LIKE %s OR i.invoice_number LIKE %s OR i.billed_to_company LIKE %s)",
        '%' . $wpdb->esc_like($search) . '%',
        '%' . $wpdb->esc_like($search) . '%',
        '%' . $wpdb->esc_like($search) . '%'
    );
}

if (!empty($status_filter)) {
    $where[] = $wpdb->prepare("i.payment_status = %s", $status_filter);
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
    LEFT JOIN $table_leads l ON i.lead_id = l.id
    WHERE $where_clause
");
$total_pages = ceil($total_invoices / $per_page);

// Get invoices with lead info
$invoices = $wpdb->get_results("
    SELECT i.*, l.fullname, l.email, l.contact_number, l.assigned_to,
           p.company_name as partnership_company
    FROM $table_invoices i
    LEFT JOIN $table_leads l ON i.lead_id = l.id
    LEFT JOIN $table_partnerships p ON i.partnership_id = p.id
    WHERE $where_clause
    ORDER BY i.date_issued DESC, i.id DESC
    LIMIT $offset, $per_page
");

// Get statistics
$stats = array(
    'total' => $wpdb->get_var("SELECT COUNT(*) FROM $table_invoices WHERE is_active = 1"),
    'pending' => $wpdb->get_var("SELECT COUNT(*) FROM $table_invoices WHERE payment_status = 'Pending' AND is_active = 1"),
    'sent' => $wpdb->get_var("SELECT COUNT(*) FROM $table_invoices WHERE payment_status = 'Sent' AND is_active = 1"),
    'partial' => $wpdb->get_var("SELECT COUNT(*) FROM $table_invoices WHERE payment_status = 'Partial' AND is_active = 1"),
    'fully_paid' => $wpdb->get_var("SELECT COUNT(*) FROM $table_invoices WHERE payment_status = 'Fully Paid' AND is_active = 1"),
    'overdue' => $wpdb->get_var("SELECT COUNT(*) FROM $table_invoices WHERE payment_status = 'Overdue' AND is_active = 1"),
    'total_amount' => $wpdb->get_var("SELECT SUM(referral_fee_amount) FROM $table_invoices WHERE is_active = 1"),
    'paid_amount' => $wpdb->get_var("SELECT SUM(referral_fee_amount) FROM $table_invoices WHERE payment_status = 'Fully Paid' AND is_active = 1"),
    'unpaid_amount' => $wpdb->get_var("SELECT SUM(referral_fee_amount) FROM $table_invoices WHERE payment_status != 'Fully Paid' AND is_active = 1")
);

// Get partnerships for dropdown
$partnerships = $wpdb->get_results("SELECT id, company_name FROM $table_partnerships WHERE agreement_status = 'Signed' ORDER BY company_name");

// Get leads that are in "Buyer Payment Completed" status only
$for_payment_deals = $wpdb->get_results("
    SELECT d.lead_id, l.fullname, d.deal_status
    FROM $table_deals d
    LEFT JOIN $table_leads l ON d.lead_id = l.id
    WHERE d.deal_status = 'Buyer Payment Completed' AND d.is_active = 1
    ORDER BY l.fullname
");
?>

<div class="pipeline-header">
    <h2 class="pipeline-title">Invoice & Documentation</h2>
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

<!-- Filters -->
<div class="filter-section">
    <form method="GET" action="">
        <input type="hidden" name="hpage" value="invoices">
        <div class="filter-row">
            <div class="filter-group">
                <label>Search</label>
                <input type="text" name="search" class="filter-input" placeholder="Search by client, invoice #, company" value="<?php echo esc_attr($search); ?>">
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
<table class="pipeline-table">
    <thead>
        <tr>
            <th>Invoice #</th>
            <th>Client Name</th>
            <th>Partner Company</th>
            <th>Date Issued</th>
            <th>Due Date</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($invoices)) : ?>
            <tr>
                <td colspan="8" class="no-results">No invoices found</td>
            </tr>
        <?php else : ?>
            <?php foreach ($invoices as $invoice) :
                $status_class = 'status-' . strtolower(str_replace(' ', '-', $invoice->payment_status));
            ?>
                <tr>
                    <td><strong><?php echo esc_html($invoice->invoice_number); ?></strong></td>
                    <td><?php echo esc_html($invoice->fullname); ?></td>
                    <td><?php echo esc_html($invoice->partnership_company ?: $invoice->billed_to_company); ?></td>
                    <td><?php echo date('M d, Y', strtotime($invoice->date_issued)); ?></td>
                    <td><?php echo date('M d, Y', strtotime($invoice->due_date)); ?></td>
                    <td><strong>$<?php echo number_format($invoice->referral_fee_amount, 2); ?></strong></td>
                    <td><span class="status-badge <?php echo $status_class; ?>"><?php echo esc_html($invoice->payment_status); ?></span></td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-success" onclick="previewInvoicePDF(<?php echo $invoice->id; ?>)">
                                <i class="houzez-icon icon-print-text"></i> Preview
                            </button>
                            <button class="btn btn-sm btn-info" onclick='editInvoice(<?php echo json_encode($invoice); ?>)'>
                                <i class="houzez-icon icon-edit-1"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-primary" onclick="downloadInvoicePDF(<?php echo $invoice->id; ?>)">
                                <i class="houzez-icon icon-download-bottom"></i> Download
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteInvoice(<?php echo $invoice->id; ?>)">
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
        <button onclick="window.location.href='?hpage=invoices&paged=1'" <?php echo $page <= 1 ? 'disabled' : ''; ?>>First</button>
        <button onclick="window.location.href='?hpage=invoices&paged=<?php echo max(1, $page - 1); ?>'" <?php echo $page <= 1 ? 'disabled' : ''; ?>>Previous</button>
        <span class="page-info">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
        <button onclick="window.location.href='?hpage=invoices&paged=<?php echo min($total_pages, $page + 1); ?>'" <?php echo $page >= $total_pages ? 'disabled' : ''; ?>>Next</button>
        <button onclick="window.location.href='?hpage=invoices&paged=<?php echo $total_pages; ?>'" <?php echo $page >= $total_pages ? 'disabled' : ''; ?>>Last</button>
    </div>
<?php endif; ?>

<!-- Add/Edit Invoice Modal -->
<div id="invoiceModal" class="modal">
    <div class="modal-content" style="max-width: 1100px;">
        <div class="modal-header">
            <h3 class="modal-title" id="invoiceModalTitle">Create Invoice</h3>
            <button class="close" onclick="closeInvoiceModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="invoiceForm">
                <input type="hidden" id="invoice_id" name="invoice_id">
                <input type="hidden" id="lead_id_hidden" name="lead_id">

                <div class="form-grid">
                    <div class="form-group">
                        <label class="required">Select Client (From Deals)</label>
                        <select class="form-control" id="lead_id" required onchange="loadLeadInfo(this.value)">
                            <option value="">Select Client</option>
                            <?php foreach ($for_payment_deals as $deal) : ?>
                                <option value="<?php echo $deal->lead_id; ?>"><?php echo esc_html($deal->fullname); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Partner Company</label>
                        <select class="form-control" id="partnership_id" name="partnership_id" readonly disabled style="background-color: #f0f0f0; cursor: not-allowed;">
                            <option value="">Select Partner</option>
                            <?php foreach ($partnerships as $partnership) : ?>
                                <option value="<?php echo $partnership->id; ?>"><?php echo esc_html($partnership->company_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" id="partnership_id_hidden" name="partnership_id">
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

                <h4 style="margin: 30px 0 20px 0; color: #333; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">Billed To</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" class="form-control" id="billed_to_name" name="billed_to_name">
                    </div>
                    <div class="form-group">
                        <label>Position</label>
                        <input type="text" class="form-control" id="billed_to_position" name="billed_to_position">
                    </div>
                    <div class="form-group full-width">
                        <label>Company Name</label>
                        <input type="text" class="form-control" id="billed_to_company" name="billed_to_company">
                    </div>
                    <div class="form-group full-width">
                        <label>Address</label>
                        <input type="text" class="form-control" id="billed_to_address" name="billed_to_address">
                    </div>
                </div>

                <h4 style="margin: 30px 0 20px 0; color: #333; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">Transaction Details</h4>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Transaction Details</label>
                        <textarea class="form-control" id="transaction_details" name="transaction_details" rows="6">Project Name:
Unit Details:
Buyer Name:
Date of Sale:</textarea>
                    </div>
                    <div class="form-group full-width">
                        <label>Description</label>
                        <input type="text" class="form-control" id="description" name="description" value="Referral Fee">
                    </div>
                    <div class="form-group">
                        <label class="required">Sale Price</label>
                        <input type="number" class="form-control" id="sale_price" name="sale_price" step="0.01" required onchange="calculateReferralFee()">
                    </div>
                    <div class="form-group">
                        <label class="required">Commission Rate (%)</label>
                        <input type="number" class="form-control" id="commission_rate" name="commission_rate" step="0.01" required onchange="calculateReferralFee()">
                    </div>
                    <div class="form-group">
                        <label>Referral Fee Amount</label>
                        <input type="number" class="form-control" id="referral_fee_display" step="0.01" readonly style="background: #f0f0f0; font-weight: bold;">
                    </div>
                    <div class="form-group">
                        <label class="required">Payment Status</label>
                        <select class="form-control" id="payment_status" name="payment_status" required>
                            <option value="Pending">Pending</option>
                            <option value="Sent">Sent</option>
                            <option value="Partial">Partial</option>
                            <option value="Fully Paid">Fully Paid</option>
                            <option value="Overdue">Overdue</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label>Property URL (Not visible in PDF)</label>
                        <input type="url" class="form-control" id="property_url" name="property_url" placeholder="https://...">
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeInvoiceModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveInvoice()">Save Invoice</button>
        </div>
    </div>
</div>

<!-- PDF Preview Modal -->
<div id="pdfPreviewModal" class="modal">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h3 class="modal-title">Invoice Preview</h3>
            <button class="close" onclick="closePDFPreviewModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="pdfPreviewContent" style="background: white; padding: 40px;"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closePDFPreviewModal()">Close</button>
            <button class="btn btn-primary" onclick="printInvoice()">
                <i class="houzez-icon icon-print-text"></i> Print
            </button>
        </div>
    </div>
</div>

<script>
function openAddInvoiceModal() {
    document.getElementById('invoiceModalTitle').textContent = 'Create New Invoice';
    document.getElementById('invoiceForm').reset();
    document.getElementById('invoice_id').value = '';
    document.getElementById('date_issued').value = '<?php echo date('Y-m-d'); ?>';
    document.getElementById('due_date').value = '<?php echo date('Y-m-d', strtotime('+30 days')); ?>';
    document.getElementById('description').value = 'Referral Fee';
    document.getElementById('payment_status').value = 'Pending';

    // Set pre-filled transaction details
    document.getElementById('transaction_details').value = 'Project Name:\nUnit Details:\nBuyer Name:\nDate of Sale:';

    // Reset Select Client to editable state
    document.getElementById('lead_id').removeAttribute('disabled');
    document.getElementById('lead_id').style.backgroundColor = '';
    document.getElementById('lead_id').style.cursor = '';

    document.getElementById('invoiceModal').style.display = 'block';
}

function closeInvoiceModal() {
    document.getElementById('invoiceModal').style.display = 'none';
}

function loadLeadInfo(leadId) {
    if (!leadId) return;

    // Update hidden field for lead_id
    document.getElementById('lead_id_hidden').value = leadId;

    jQuery.ajax({
        url: '<?php echo admin_url("admin-ajax.php"); ?>',
        type: 'POST',
        data: {
            action: 'get_lead_for_invoice',
            lead_id: leadId,
            nonce: '<?php echo wp_create_nonce("get_lead_for_invoice"); ?>'
        },
        success: function(response) {
            if (response.success && response.data) {
                const lead = response.data;
                // Auto-fill partner if available
                if (lead.partners) {
                    try {
                        const partners = JSON.parse(lead.partners);
                        if (partners.length > 0) {
                            jQuery('#partnership_id').val(partners[0]);
                            jQuery('#partnership_id_hidden').val(partners[0]);
                        }
                    } catch (e) {}
                }
            }
        }
    });
}

function calculateReferralFee() {
    const salePrice = parseFloat(document.getElementById('sale_price').value) || 0;
    const commissionRate = parseFloat(document.getElementById('commission_rate').value) || 0;
    const referralFee = (salePrice * commissionRate) / 100;
    document.getElementById('referral_fee_display').value = referralFee.toFixed(2);
}

function editInvoice(invoice) {
    try {
        document.getElementById('invoiceModalTitle').textContent = 'Edit Invoice';
        document.getElementById('invoice_id').value = invoice.id || '';
        document.getElementById('lead_id').value = invoice.lead_id || '';
        document.getElementById('lead_id_hidden').value = invoice.lead_id || '';
        document.getElementById('partnership_id').value = invoice.partnership_id || '';
        document.getElementById('partnership_id_hidden').value = invoice.partnership_id || '';
        document.getElementById('date_issued').value = invoice.date_issued || '';
        document.getElementById('due_date').value = invoice.due_date || '';
        document.getElementById('billed_to_name').value = invoice.billed_to_name || '';
        document.getElementById('billed_to_position').value = invoice.billed_to_position || '';
        document.getElementById('billed_to_company').value = invoice.billed_to_company || '';

        // Check if address field exists before setting
        var addressField = document.getElementById('billed_to_address');
        if (addressField) {
            addressField.value = invoice.billed_to_address || '';
        }

        document.getElementById('transaction_details').value = invoice.transaction_details || '';
        document.getElementById('description').value = invoice.description || 'Referral Fee';
        document.getElementById('sale_price').value = invoice.sale_price || '';
        document.getElementById('commission_rate').value = invoice.commission_rate || '';
        document.getElementById('referral_fee_display').value = invoice.referral_fee_amount || '';
        document.getElementById('property_url').value = invoice.property_url || '';
        document.getElementById('payment_status').value = invoice.payment_status || 'Pending';

        // Make Select Client and Partner Company read-only when editing
        document.getElementById('lead_id').setAttribute('disabled', 'disabled');
        document.getElementById('lead_id').style.backgroundColor = '#f0f0f0';
        document.getElementById('lead_id').style.cursor = 'not-allowed';

        document.getElementById('invoiceModal').style.display = 'block';
    } catch (error) {
        console.error('Error in editInvoice:', error);
        alert('Error opening edit form. Please check the browser console for details.');
    }
}

function saveInvoice() {
    const formData = new FormData(document.getElementById('invoiceForm'));
    formData.append('action', 'save_pipeline_invoice');
    formData.append('nonce', '<?php echo wp_create_nonce("save_pipeline_invoice"); ?>');

    jQuery.ajax({
        url: '<?php echo admin_url("admin-ajax.php"); ?>',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                alert('Invoice saved successfully!');
                location.reload();
            } else {
                alert('Error: ' + (response.data || 'Unknown error'));
            }
        },
        error: function() {
            alert('An error occurred while saving the invoice.');
        }
    });
}

function deleteInvoice(invoiceId) {
    if (!confirm('Are you sure you want to delete this invoice?')) {
        return;
    }

    jQuery.ajax({
        url: '<?php echo admin_url("admin-ajax.php"); ?>',
        type: 'POST',
        data: {
            action: 'delete_pipeline_invoice',
            invoice_id: invoiceId,
            nonce: '<?php echo wp_create_nonce("delete_pipeline_invoice"); ?>'
        },
        success: function(response) {
            if (response.success) {
                alert('Invoice deleted successfully!');
                location.reload();
            } else {
                alert('Error: ' + (response.data || 'Unknown error'));
            }
        },
        error: function() {
            alert('An error occurred while deleting the invoice.');
        }
    });
}

function previewInvoicePDF(invoiceId) {
    jQuery.ajax({
        url: '<?php echo admin_url("admin-ajax.php"); ?>',
        type: 'POST',
        data: {
            action: 'preview_invoice_pdf',
            invoice_id: invoiceId,
            nonce: '<?php echo wp_create_nonce("preview_invoice_pdf"); ?>'
        },
        success: function(response) {
            if (response.success) {
                document.getElementById('pdfPreviewContent').innerHTML = response.data;
                document.getElementById('pdfPreviewModal').style.display = 'block';
            } else {
                alert('Error: ' + (response.data || 'Unknown error'));
            }
        },
        error: function() {
            alert('An error occurred while previewing the invoice.');
        }
    });
}

function closePDFPreviewModal() {
    document.getElementById('pdfPreviewModal').style.display = 'none';
}

function printInvoice() {
    const printContent = document.getElementById('pdfPreviewContent').innerHTML;
    const printWindow = window.open('', '', 'height=800,width=800');
    printWindow.document.write('<html><head><title>Invoice</title>');
    printWindow.document.write('<style>body{font-family:Arial,sans-serif;margin:20px;}.invoice-table{width:100%;border-collapse:collapse;}.invoice-table th,.invoice-table td{border:1px solid #ddd;padding:10px;text-align:left;}.text-right{text-align:right;}</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(printContent);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}

function downloadInvoicePDF(invoiceId) {
    window.open('<?php echo admin_url("admin-ajax.php"); ?>?action=download_invoice_pdf&invoice_id=' + invoiceId + '&nonce=<?php echo wp_create_nonce("download_invoice_pdf"); ?>', '_blank');
}

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target.id === 'invoiceModal') {
        closeInvoiceModal();
    }
    if (event.target.id === 'pdfPreviewModal') {
        closePDFPreviewModal();
    }
}
</script>
