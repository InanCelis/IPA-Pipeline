<?php
/**
 * Pipeline AJAX Handlers
 * Handles all AJAX requests for the Pipeline system
 */

// ========================================
// DIAGNOSTIC TEST HANDLER
// ========================================

// Test Sales Access - Diagnostic endpoint to verify user authentication
add_action('wp_ajax_test_sales_access', 'test_sales_access_handler');
function test_sales_access_handler() {
    error_log('TEST: Handler reached for user ID = ' . get_current_user_id());

    $current_user = wp_get_current_user();
    $user_data = array(
        'user_id' => get_current_user_id(),
        'username' => $current_user->user_login,
        'roles' => $current_user->roles,
        'capabilities' => array_keys($current_user->allcaps),
        'has_pipeline_access' => user_has_pipeline_access(),
        'is_logged_in' => is_user_logged_in()
    );

    error_log('TEST: User data = ' . print_r($user_data, true));

    wp_send_json_success($user_data);
}

// ========================================
// LEADS AJAX HANDLERS
// ========================================

// Save Pipeline Lead
add_action('wp_ajax_save_pipeline_lead', 'save_pipeline_lead_handler');
function save_pipeline_lead_handler() {
    // Log for debugging
    error_log('Save lead handler called by user: ' . get_current_user_id());

    // Verify nonce
    if (!check_ajax_referer('save_pipeline_lead', 'nonce', false)) {
        error_log('Nonce check failed');
        wp_send_json_error('Security check failed. Please refresh the page and try again.');
        return;
    }

    error_log('Nonce check passed');

    if (!user_has_pipeline_access()) {
        error_log('Access denied for user: ' . get_current_user_id());
        wp_send_json_error('Access denied. You do not have permission to access this feature.');
        return;
    }

    error_log('Access check passed');

    global $wpdb;
    $table_leads = $wpdb->prefix . 'pipeline_leads';
    $table_deals = $wpdb->prefix . 'pipeline_deals';

    $lead_id = isset($_POST['lead_id']) ? intval($_POST['lead_id']) : 0;
    $old_status = '';

    if ($lead_id > 0) {
        // Get old status before update
        $old_status = $wpdb->get_var($wpdb->prepare(
            "SELECT status FROM $table_leads WHERE id = %d",
            $lead_id
        ));
    }

    // Check if user is Sales role - restrict editable fields
    $current_user = wp_get_current_user();
    $is_sales_role = in_array('sales_role', $current_user->roles);

    if ($is_sales_role && $lead_id > 0) {
        // Sales role can only edit: contact info (name, email, phone) and status
        // For updates, only include editable fields
        $data = array(
            'fullname' => sanitize_text_field($_POST['fullname']),
            'firstname' => sanitize_text_field($_POST['firstname']),
            'lastname' => sanitize_text_field($_POST['lastname']),
            'email' => sanitize_email($_POST['email']),
            'contact_number' => sanitize_text_field($_POST['contact_number']),
            'status' => sanitize_text_field($_POST['status']),
        );
    } else {
        // Admins and other roles can edit all fields
        $data = array(
            'fullname' => sanitize_text_field($_POST['fullname']),
            'firstname' => sanitize_text_field($_POST['firstname']),
            'lastname' => sanitize_text_field($_POST['lastname']),
            'email' => sanitize_email($_POST['email']),
            'contact_number' => sanitize_text_field($_POST['contact_number']),
            'property_url' => isset($_POST['property_url']) ? esc_url_raw($_POST['property_url']) : null,
            'lead_source' => sanitize_text_field($_POST['lead_source']),
            'status' => sanitize_text_field($_POST['status']),
            'assigned_to' => sanitize_text_field($_POST['assigned_to']),
            'partners' => isset($_POST['partners']) ? json_encode($_POST['partners']) : null,
            'tags' => isset($_POST['tags']) ? json_encode(array_map('sanitize_text_field', $_POST['tags'])) : null,
            'message' => isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : null,
        );
    }

    // Handle status changes
    $new_status = $data['status'];

    // If changing to Qualified, create deal record
    if ($new_status == 'Qualified' && $old_status != 'Qualified') {
        // Will create deal record after saving lead
        $create_deal = true;
    } else {
        $create_deal = false;
    }

    // If changing to Cold Lead
    if ($new_status == 'Cold Lead') {
        $data['is_cold_lead'] = 1;
        $data['is_active'] = 0; // Hide from active leads
    } else {
        $data['is_cold_lead'] = 0;
        $data['is_active'] = 1;
    }

    if ($lead_id > 0) {
        // Update existing lead
        $result = $wpdb->update($table_leads, $data, array('id' => $lead_id));

        if ($result === false) {
            $error_msg = 'Failed to update lead';
            if ($wpdb->last_error) {
                $error_msg .= ': ' . $wpdb->last_error;
            }
            wp_send_json_error($error_msg);
            return;
        }

        // Create deal if status changed to Qualified
        if ($create_deal) {
            $deal_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_deals WHERE lead_id = %d",
                $lead_id
            ));

            if (!$deal_exists) {
                $wpdb->insert($table_deals, array(
                    'lead_id' => $lead_id,
                    'deal_status' => 'N/A',
                    'is_active' => 1
                ));
            }
        }

        wp_send_json_success('Lead updated successfully');
    } else {
        // Insert new lead
        if (!isset($data['date_inquiry'])) {
            $data['date_inquiry'] = current_time('mysql');
        }
        $data['created_by'] = get_current_user_id();

        $result = $wpdb->insert($table_leads, $data);

        if ($result === false) {
            wp_send_json_error('Failed to create lead');
            return;
        }

        $new_lead_id = $wpdb->insert_id;

        // Create deal if status is Qualified
        if ($new_status == 'Qualified') {
            $wpdb->insert($table_deals, array(
                'lead_id' => $new_lead_id,
                'deal_status' => 'N/A',
                'is_active' => 1
            ));
        }

        wp_send_json_success('Lead created successfully');
    }
}

// Delete Pipeline Lead
add_action('wp_ajax_delete_pipeline_lead', 'delete_pipeline_lead_handler');
function delete_pipeline_lead_handler() {
    // Verify nonce
    if (!check_ajax_referer('delete_pipeline_lead', 'nonce', false)) {
        wp_send_json_error('Security check failed. Please refresh the page and try again.');
        return;
    }

    if (!user_has_pipeline_access()) {
        wp_send_json_error('Access denied. You do not have permission to access this feature.');
        return;
    }

    $lead_id = intval($_POST['lead_id']);

    global $wpdb;
    $table_leads = $wpdb->prefix . 'pipeline_leads';

    // Soft delete
    $result = $wpdb->update(
        $table_leads,
        array(
            'is_active' => 0,
            'deleted_at' => current_time('mysql')
        ),
        array('id' => $lead_id)
    );

    if ($result !== false) {
        wp_send_json_success('Lead deleted successfully');
    } else {
        wp_send_json_error('Failed to delete lead');
    }
}

// ========================================
// DEALS AJAX HANDLERS
// ========================================

// Save Pipeline Deal
add_action('wp_ajax_save_pipeline_deal', 'save_pipeline_deal_handler');
function save_pipeline_deal_handler() {
    // Log for debugging
    error_log('Save deal handler called by user: ' . get_current_user_id());

    // Verify nonce
    if (!check_ajax_referer('save_pipeline_deal', 'nonce', false)) {
        error_log('Nonce check failed for deal');
        wp_send_json_error('Security check failed. Please refresh the page and try again.');
        return;
    }

    error_log('Deal nonce check passed');

    if (!user_has_pipeline_access()) {
        error_log('Access denied for deal user: ' . get_current_user_id());
        wp_send_json_error('Access denied. You do not have permission to access this feature.');
        return;
    }

    error_log('Deal access check passed');

    global $wpdb;
    $table_deals = $wpdb->prefix . 'pipeline_deals';
    $table_invoices = $wpdb->prefix . 'pipeline_invoices';
    $table_leads = $wpdb->prefix . 'pipeline_leads';

    $deal_id = isset($_POST['deal_id']) ? intval($_POST['deal_id']) : 0;
    $lead_id = isset($_POST['lead_id']) ? intval($_POST['lead_id']) : 0;

    $old_status = '';
    if ($deal_id > 0) {
        $old_status = $wpdb->get_var($wpdb->prepare(
            "SELECT deal_status FROM $table_deals WHERE id = %d",
            $deal_id
        ));
    }

    // Sales users can edit all deal fields (no restrictions)
    $data = array(
        'lead_id' => $lead_id,
        'property_type' => isset($_POST['property_type']) ? json_encode($_POST['property_type']) : null,
        'country' => isset($_POST['country']) ? json_encode($_POST['country']) : null,
        'city' => isset($_POST['city']) ? json_encode($_POST['city']) : null,
        'num_rooms' => isset($_POST['num_rooms']) ? intval($_POST['num_rooms']) : null,
        'area_size' => sanitize_text_field($_POST['area_size']),
        'num_bathrooms' => isset($_POST['num_bathrooms']) ? intval($_POST['num_bathrooms']) : null,
        'budget_amount' => isset($_POST['budget_amount']) ? floatval($_POST['budget_amount']) : null,
        'budget_payment_method' => sanitize_text_field($_POST['budget_payment_method']),
        'purpose_of_purchase' => isset($_POST['purpose_of_purchase']) ? json_encode($_POST['purpose_of_purchase']) : null,
        'timeline_urgency' => sanitize_text_field($_POST['timeline_urgency']),
        'move_in_target' => sanitize_text_field($_POST['move_in_target']),
        'stage_buying_process' => sanitize_text_field($_POST['stage_buying_process']),
        'deal_status' => sanitize_text_field($_POST['deal_status']),
    );

    $new_status = $data['deal_status'];

    // If status changed to "For Payment", move to invoices
    if ($new_status == 'For Payment' && $old_status != 'For Payment') {
        $create_invoice = true;
    } else {
        $create_invoice = false;
    }

    if ($deal_id > 0) {
        // Update existing deal
        $result = $wpdb->update($table_deals, $data, array('id' => $deal_id));

        if ($result === false) {
            wp_send_json_error('Failed to update deal');
            return;
        }

        // Create invoice if status changed to For Payment
        if ($create_invoice) {
            // Check if invoice already exists
            $invoice_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_invoices WHERE deal_id = %d",
                $deal_id
            ));

            if (!$invoice_exists) {
                // Get lead info
                $lead = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table_leads WHERE id = %d",
                    $lead_id
                ));

                if ($lead) {
                    $wpdb->insert($table_invoices, array(
                        'lead_id' => $lead_id,
                        'deal_id' => $deal_id,
                        'invoice_number' => generate_invoice_number(),
                        'date_issued' => current_time('mysql', false),
                        'due_date' => date('Y-m-d', strtotime('+30 days')),
                        'payment_status' => 'Pending',
                        'is_active' => 1
                    ));
                }
            }
        }

        wp_send_json_success('Deal updated successfully');
    } else {
        // Insert new deal
        $result = $wpdb->insert($table_deals, $data);

        if ($result === false) {
            wp_send_json_error('Failed to create deal');
            return;
        }

        wp_send_json_success('Deal created successfully');
    }
}

// Delete Pipeline Deal
add_action('wp_ajax_delete_pipeline_deal', 'delete_pipeline_deal_handler');
function delete_pipeline_deal_handler() {
    // Verify nonce
    if (!check_ajax_referer('delete_pipeline_deal', 'nonce', false)) {
        wp_send_json_error('Security check failed. Please refresh the page and try again.');
        return;
    }

    if (!user_has_pipeline_access()) {
        wp_send_json_error('Access denied. You do not have permission to access this feature.');
        return;
    }

    $deal_id = intval($_POST['deal_id']);

    global $wpdb;
    $table_deals = $wpdb->prefix . 'pipeline_deals';

    // Soft delete
    $result = $wpdb->update(
        $table_deals,
        array(
            'is_active' => 0,
            'deleted_at' => current_time('mysql')
        ),
        array('id' => $deal_id)
    );

    if ($result !== false) {
        wp_send_json_success('Deal deleted successfully');
    } else {
        wp_send_json_error('Failed to delete deal');
    }
}

// Move Deal Back to Leads
add_action('wp_ajax_move_deal_to_leads', 'move_deal_to_leads_handler');
function move_deal_to_leads_handler() {
    check_ajax_referer('move_deal_to_leads', 'nonce');

    if (!user_has_pipeline_access()) {
        wp_send_json_error('Access denied');
        return;
    }

    $lead_id = intval($_POST['lead_id']);

    global $wpdb;
    $table_leads = $wpdb->prefix . 'pipeline_leads';

    // Update lead status back to Qualifying
    $result = $wpdb->update(
        $table_leads,
        array('status' => 'Qualifying'),
        array('id' => $lead_id)
    );

    if ($result !== false) {
        wp_send_json_success('Deal moved back to Leads');
    } else {
        wp_send_json_error('Failed to move deal to leads');
    }
}

// Sync Qualified Leads to Deals
add_action('wp_ajax_sync_qualified_leads_to_deals', 'sync_qualified_leads_to_deals_handler');
function sync_qualified_leads_to_deals_handler() {
    check_ajax_referer('sync_qualified_leads_to_deals', 'nonce');

    if (!user_has_pipeline_access()) {
        wp_send_json_error('Access denied');
        return;
    }

    global $wpdb;
    $table_leads = $wpdb->prefix . 'pipeline_leads';
    $table_deals = $wpdb->prefix . 'pipeline_deals';

    // Get all qualified leads
    $qualified_leads = $wpdb->get_results("
        SELECT id FROM $table_leads
        WHERE status = 'Qualified' AND is_active = 1
    ");

    $created_count = 0;

    foreach ($qualified_leads as $lead) {
        // Check if deal already exists
        $deal_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_deals WHERE lead_id = %d",
            $lead->id
        ));

        if (!$deal_exists) {
            $wpdb->insert($table_deals, array(
                'lead_id' => $lead->id,
                'deal_status' => 'N/A',
                'is_active' => 1
            ));
            $created_count++;
        }
    }

    wp_send_json_success("Synced successfully! Created $created_count new deals.");
}

// ========================================
// INVOICE AJAX HANDLERS
// ========================================

// Save Pipeline Invoice
add_action('wp_ajax_save_pipeline_invoice', 'save_pipeline_invoice_handler');
function save_pipeline_invoice_handler() {
    check_ajax_referer('save_pipeline_invoice', 'nonce');

    if (!user_has_pipeline_access()) {
        wp_send_json_error('Access denied');
        return;
    }

    global $wpdb;
    $table_invoices = $wpdb->prefix . 'pipeline_invoices';
    $table_deals = $wpdb->prefix . 'pipeline_deals';

    $invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
    $lead_id = intval($_POST['lead_id']);

    // Calculate referral fee
    $sale_price = floatval($_POST['sale_price']);
    $commission_rate = floatval($_POST['commission_rate']);
    $referral_fee = ($sale_price * $commission_rate) / 100;

    $data = array(
        'lead_id' => $lead_id,
        'partnership_id' => isset($_POST['partnership_id']) ? intval($_POST['partnership_id']) : null,
        'date_issued' => sanitize_text_field($_POST['date_issued']),
        'due_date' => sanitize_text_field($_POST['due_date']),
        'billed_to_name' => sanitize_text_field($_POST['billed_to_name']),
        'billed_to_position' => sanitize_text_field($_POST['billed_to_position']),
        'billed_to_company' => sanitize_text_field($_POST['billed_to_company']),
        'billed_to_address' => sanitize_text_field($_POST['billed_to_address']),
        'other_details' => isset($_POST['other_details']) ? sanitize_textarea_field($_POST['other_details']) : '',
        'transaction_details' => sanitize_textarea_field($_POST['transaction_details']),
        'description' => sanitize_textarea_field($_POST['description']),
        'sale_price' => $sale_price,
        'commission_rate' => $commission_rate,
        'referral_fee_amount' => $referral_fee,
        'property_url' => esc_url_raw($_POST['property_url']),
        'payment_status' => sanitize_text_field($_POST['payment_status']),
    );

    if ($invoice_id > 0) {
        // Update existing invoice
        $result = $wpdb->update($table_invoices, $data, array('id' => $invoice_id));

        if ($result === false) {
            wp_send_json_error('Failed to update invoice');
            return;
        }

        // Ensure the deal status remains "Invoice Created" when updating
        $wpdb->update(
            $table_deals,
            array('deal_status' => 'Invoice Created'),
            array('lead_id' => $lead_id, 'is_active' => 1)
        );

        wp_send_json_success('Invoice updated successfully');
    } else {
        // Generate invoice number
        $data['invoice_number'] = generate_invoice_number();

        // Insert new invoice
        $result = $wpdb->insert($table_invoices, $data);

        if ($result === false) {
            wp_send_json_error('Failed to create invoice');
            return;
        }

        // Update the deal status to "Invoice Created" for this lead
        $wpdb->update(
            $table_deals,
            array('deal_status' => 'Invoice Created'),
            array('lead_id' => $lead_id, 'is_active' => 1)
        );

        wp_send_json_success('Invoice created successfully');
    }
}

// Delete Pipeline Invoice
add_action('wp_ajax_delete_pipeline_invoice', 'delete_pipeline_invoice_handler');
function delete_pipeline_invoice_handler() {
    check_ajax_referer('delete_pipeline_invoice', 'nonce');

    if (!user_has_pipeline_access()) {
        wp_send_json_error('Access denied');
        return;
    }

    $invoice_id = intval($_POST['invoice_id']);

    global $wpdb;
    $table_invoices = $wpdb->prefix . 'pipeline_invoices';
    $table_deals = $wpdb->prefix . 'pipeline_deals';

    // Get the lead_id before deleting
    $invoice = $wpdb->get_row($wpdb->prepare(
        "SELECT lead_id FROM $table_invoices WHERE id = %d",
        $invoice_id
    ));

    if (!$invoice) {
        wp_send_json_error('Invoice not found');
        return;
    }

    $lead_id = $invoice->lead_id;

    // Soft delete
    $result = $wpdb->update(
        $table_invoices,
        array(
            'is_active' => 0,
            'deleted_at' => current_time('mysql')
        ),
        array('id' => $invoice_id)
    );

    if ($result !== false) {
        // Revert deal status back to "Buyer Payment Completed"
        $wpdb->update(
            $table_deals,
            array('deal_status' => 'Buyer Payment Completed'),
            array('lead_id' => $lead_id, 'is_active' => 1)
        );

        wp_send_json_success('Invoice deleted successfully');
    } else {
        wp_send_json_error('Failed to delete invoice');
    }
}

// Get Lead Info for Invoice
add_action('wp_ajax_get_lead_for_invoice', 'get_lead_for_invoice_handler');
function get_lead_for_invoice_handler() {
    check_ajax_referer('get_lead_for_invoice', 'nonce');

    if (!user_has_pipeline_access()) {
        wp_send_json_error('Access denied');
        return;
    }

    $lead_id = intval($_POST['lead_id']);

    global $wpdb;
    $table_leads = $wpdb->prefix . 'pipeline_leads';

    $lead = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_leads WHERE id = %d",
        $lead_id
    ));

    if ($lead) {
        wp_send_json_success($lead);
    } else {
        wp_send_json_error('Lead not found');
    }
}

// Preview Invoice PDF
add_action('wp_ajax_preview_invoice_pdf', 'preview_invoice_pdf_handler');
function preview_invoice_pdf_handler() {
    check_ajax_referer('preview_invoice_pdf', 'nonce');

    if (!user_has_pipeline_access()) {
        wp_send_json_error('Access denied');
        return;
    }

    $invoice_id = intval($_POST['invoice_id']);

    global $wpdb;
    $table_invoices = $wpdb->prefix . 'pipeline_invoices';
    $table_leads = $wpdb->prefix . 'pipeline_leads';
    $table_partnerships = $wpdb->prefix . 'partnerships';

    $invoice = $wpdb->get_row($wpdb->prepare("
        SELECT i.*, l.fullname, l.email, l.contact_number, p.company_name as partnership_company
        FROM $table_invoices i
        LEFT JOIN $table_leads l ON i.lead_id = l.id
        LEFT JOIN $table_partnerships p ON i.partnership_id = p.id
        WHERE i.id = %d
    ", $invoice_id));

    if (!$invoice) {
        wp_send_json_error('Invoice not found');
        return;
    }

    $html = generate_invoice_html($invoice);
    wp_send_json_success($html);
}

// Download Invoice PDF
add_action('wp_ajax_download_invoice_pdf', 'download_invoice_pdf_handler');
add_action('wp_ajax_nopriv_download_invoice_pdf', 'download_invoice_pdf_handler');
function download_invoice_pdf_handler() {
    if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'download_invoice_pdf')) {
        wp_die('Invalid request');
    }

    if (!user_has_pipeline_access()) {
        wp_die('Access denied');
    }

    $invoice_id = intval($_GET['invoice_id']);

    global $wpdb;
    $table_invoices = $wpdb->prefix . 'pipeline_invoices';
    $table_leads = $wpdb->prefix . 'pipeline_leads';
    $table_partnerships = $wpdb->prefix . 'partnerships';

    $invoice = $wpdb->get_row($wpdb->prepare("
        SELECT i.*, l.fullname, l.email, l.contact_number, p.company_name as partnership_company
        FROM $table_invoices i
        LEFT JOIN $table_leads l ON i.lead_id = l.id
        LEFT JOIN $table_partnerships p ON i.partnership_id = p.id
        WHERE i.id = %d
    ", $invoice_id));

    if (!$invoice) {
        wp_die('Invoice not found');
    }

    // Generate HTML for PDF
    $html = generate_invoice_html($invoice, true);
    $filename = $invoice->invoice_number . '.pdf';

    // Output HTML page that will auto-generate and download PDF
    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo esc_html($invoice->invoice_number); ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
        }
        body {
            font-family: Arial, sans-serif;
        }
        #loading {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            font-size: 18px;
            color: #333;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        #invoice-content {
            max-width: 100%;
            margin: 0;
            padding: 0;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div id="loading">
        <div class="spinner"></div>
        <p>Generating PDF: <strong><?php echo esc_html($invoice->invoice_number); ?>.pdf</strong></p>
        <p style="font-size: 14px; color: #666;">Please wait...</p>
    </div>

    <div id="invoice-content">
        <?php echo $html; ?>
    </div>

    <script>
        window.onload = function() {
            // Hide loading, show invoice
            document.getElementById('loading').style.display = 'none';

            const element = document.getElementById('invoice-content');
            const filename = '<?php echo esc_js($invoice->invoice_number); ?>.pdf';

            const opt = {
                margin: 0,
                filename: filename,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: {
                    scale: 2,
                    useCORS: true,
                    letterRendering: true
                },
                jsPDF: {
                    unit: 'in',
                    format: 'letter',
                    orientation: 'portrait'
                },
                pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
            };

            // Small delay to ensure fonts and images are loaded
            setTimeout(function() {
                // Generate PDF and trigger download
                html2pdf().set(opt).from(element).save().then(function() {
                    // Close window after download
                    setTimeout(function() {
                        window.close();
                    }, 1000);
                });
            }, 500);
        };
    </script>
</body>
</html>
<?php
    exit;
}

// Generate Invoice HTML
function generate_invoice_html($invoice, $for_download = false) {
    $logo_url = 'https://internationalpropertyalerts.com/wp-content/uploads/2025/10/PDF-logo.png';

    $html = '<div style="font-family: Arial, sans-serif; font-size: 11px; min-height: 100vh; display: flex; flex-direction: column; margin: 0; padding: 0;">';

    // Header with light gray background touching top and sides
    $html .= '<div style="background-color: #f5f5f5; padding: 15px 20px; margin: 0 0 10px 0;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 50%;">
                    <img src="' . $logo_url . '" alt="International Property Alerts" style="max-width: 200px;">
                </td>
                <td style="width: 50%; text-align: right; vertical-align: center;">
                    <div style="font-size: 10px; line-height: 1.4;">
                        <strong>20 Wenlock Road, London, England, N1 7GU</strong><br>
                        <a href="https://internationalpropertyalerts.com/" style="color: #007bff; text-decoration: none;">https://internationalpropertyalerts.com/</a>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div style="flex: 1; padding: 0 20px;">';

    // Title
    $html .= '<h1 style="text-align: center; font-size: 22px; margin: 10px 0; color: #333;">REFERRAL FEE<br>INVOICE</h1>';

    // Invoice Details Box with visible border
    $html .= '<table style="width: 100%; margin-bottom: 8px; border-collapse: collapse;">
        <tr>
            <td style="width: 65%; vertical-align: top;"></td>
            <td style="width: 35%; border: 2px solid #333; padding: 8px; font-size: 10px; box-sizing: border-box;">
                <div style="margin-bottom: 4px;"><strong>Issued:</strong> ' . date('m/d/Y', strtotime($invoice->date_issued)) . '</div>
                <div style="margin-bottom: 4px;"><strong>Due Date:</strong> ' . date('m/d/Y', strtotime($invoice->due_date)) . '</div>
                <div><strong>Invoice Number:</strong> ' . esc_html($invoice->invoice_number) . '</div>
            </td>
        </tr>
    </table>';

    // Billed To
    $html .= '<div style="margin-bottom: 25px;">
        <h3 style="margin-bottom: 4px; color: #333; font-size: 12px;">Billed To:</h3>
        <div style="line-height: 1.3; font-size: 10px;">
            ' . esc_html($invoice->billed_to_name ?: 'N/A') . '<br>
            ' . esc_html($invoice->billed_to_position ?: 'N/A') . '<br>
            ' . esc_html($invoice->billed_to_company ?: $invoice->partnership_company) . '<br>
            ' . esc_html($invoice->billed_to_address ?: 'N/A') . '
        </div>';

    // Add Other Details if present
    if (!empty($invoice->other_details)) {
        $html .= '<div style="margin-top: 10px;">
            <h4 style="margin-bottom: 4px; color: #666; font-size: 11px;">Other Details:</h4>
            <div style="line-height: 1.3; font-size: 10px; color: #555;">' . nl2br(esc_html($invoice->other_details)) . '</div>
        </div>';
    }

    $html .= '</div>';

    // Transaction Details with line-height 1
    if ($invoice->transaction_details) {
        $html .= '<div style="margin-bottom: 25px;">
            <h3 style="margin-bottom: 4px; color: #333; font-size: 12px;">Transaction Details</h3>
            <div style="white-space: pre-line; line-height: 0.7; font-size: 10px;">' . nl2br(esc_html($invoice->transaction_details)) . '</div>
        </div>';
    }

    // Fee Table
    $html .= '<table class="invoice-table" style="width: 100%; border-collapse: collapse; margin: 6px 0;">
        <thead>
            <tr style="background: #f8f9fa;">
                <th style="border: 1px solid #ddd; padding: 6px; text-align: left; font-size: 10px;">Description</th>
                <th style="border: 1px solid #ddd; padding: 6px; text-align: left; font-size: 10px;">Sale Price</th>
                <th style="border: 1px solid #ddd; padding: 6px; text-align: left; font-size: 10px;">Commission Rate</th>
                <th style="border: 1px solid #ddd; padding: 6px; text-align: left; font-size: 10px;">Referral Fee Amount</th>
                <th style="border: 1px solid #ddd; padding: 6px; text-align: left; font-size: 10px;">Due Date</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="border: 1px solid #ddd; padding: 6px; font-size: 10px;">' . esc_html($invoice->description ?: 'Referral Fee') . '</td>
                <td style="border: 1px solid #ddd; padding: 6px; font-size: 10px;">$' . number_format($invoice->sale_price, 2) . '</td>
                <td style="border: 1px solid #ddd; padding: 6px; font-size: 10px;">' . number_format($invoice->commission_rate, 2) . '%</td>
                <td style="border: 1px solid #ddd; padding: 6px; font-size: 10px;"><strong>$' . number_format($invoice->referral_fee_amount, 2) . '</strong></td>
                <td style="border: 1px solid #ddd; padding: 6px; font-size: 10px;">' . date('m/d/Y', strtotime($invoice->due_date)) . '</td>
            </tr>
        </tbody>
    </table>';

    $html .= '</div>'; // Close flex content div

    // Payment Instructions - Touch bottom of page
    $html .= '<div style="margin: 0; margin-top: auto;">
        <div style="padding: 10px 20px; background: #f8f9fa;">
            <h3 style="margin-top: 0; margin-bottom: 8px; color: #333; font-size: 12px;">Please pay to:</h3>

            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                        <h4 style="margin-bottom: 4px; color: #555; font-size: 10px;">Within UK, Please make payment to:</h4>
                        <div style="line-height: 1.4; font-size: 9px;">
                            <strong>Bank:</strong> Revolut<br>
                            <strong>Account Number:</strong> 41997379<br>
                            <strong>Account Name:</strong> White International Group Ltd.<br>
                            <strong>Sort Code:</strong> 04-29-09
                        </div>
                    </td>
                    <td style="width: 50%; vertical-align: top; padding-left: 10px; border-left: 1px solid #ddd;">
                        <h4 style="margin-bottom: 4px; color: #555; font-size: 10px;">Outside UK, Please make payment to:</h4>
                        <div style="line-height: 1.4; font-size: 9px;">
                            <strong>Bank:</strong> Revolut<br>
                            <strong>Account Name:</strong> White International Group Ltd.<br>
                            <strong>IBAN:</strong> GB77 REVO 0099 6945 0700 15<br>
                            <strong>BIC:</strong> REVOGB21<br>
                            <strong>Intermediary BIC:</strong> CHASGB2L
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Footer - Touch bottom -->
        <div style="padding: 0; border-top: 1px solid #ddd; text-align: center; margin: 0;">
            <div style="background: #6c757d; color: white; padding: 8px; font-size: 10px;">
                <strong>INTERNATIONAL PROPERTY ALERTS LTD.</strong>
                <div style="margin-top: 2px; font-size: 9px;">COMPANY NO. 16469075</div>
            </div>
        </div>
    </div>';

    $html .= '</div>'; // Close main container

    return $html;
}

// ========================================
// COMMENTS AJAX HANDLERS
// ========================================

// Get Pipeline Comments
add_action('wp_ajax_get_pipeline_comments', 'get_pipeline_comments_handler');
function get_pipeline_comments_handler() {
    check_ajax_referer('get_pipeline_comments', 'nonce');

    if (!user_has_pipeline_access()) {
        wp_send_json_error('Access denied');
        return;
    }

    global $wpdb;
    $table_comments = $wpdb->prefix . 'pipeline_comments';

    $entity_type = sanitize_text_field($_POST['entity_type']);
    $entity_id = intval($_POST['entity_id']);

    $comments = $wpdb->get_results($wpdb->prepare(
        "SELECT c.*, u.display_name as author
        FROM $table_comments c
        LEFT JOIN {$wpdb->users} u ON c.created_by = u.ID
        WHERE c.entity_type = %s AND c.entity_id = %d AND c.is_active = 1
        ORDER BY c.created_at DESC",
        $entity_type,
        $entity_id
    ));

    $formatted = array();
    foreach ($comments as $comment) {
        $formatted[] = array(
            'id' => $comment->id,
            'comment' => $comment->comment,
            'author' => $comment->author ?: 'Unknown',
            'date' => date('M d, Y @ h:i A', strtotime($comment->created_at))
        );
    }

    wp_send_json_success($formatted);
}

// Get All Pipeline Comments (for deals - includes lead comments)
add_action('wp_ajax_get_all_pipeline_comments', 'get_all_pipeline_comments_handler');
function get_all_pipeline_comments_handler() {
    check_ajax_referer('get_all_pipeline_comments', 'nonce');

    if (!user_has_pipeline_access()) {
        wp_send_json_error('Access denied');
        return;
    }

    global $wpdb;
    $table_comments = $wpdb->prefix . 'pipeline_comments';
    $table_deals = $wpdb->prefix . 'pipeline_deals';
    $table_invoices = $wpdb->prefix . 'pipeline_invoices';

    $deal_id = isset($_POST['deal_id']) ? intval($_POST['deal_id']) : 0;
    $invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;

    $lead_id = null;

    // Get lead_id from deal or invoice
    if ($deal_id > 0) {
        $lead_id = $wpdb->get_var($wpdb->prepare(
            "SELECT lead_id FROM $table_deals WHERE id = %d",
            $deal_id
        ));
    } elseif ($invoice_id > 0) {
        $invoice_data = $wpdb->get_row($wpdb->prepare(
            "SELECT lead_id, deal_id FROM $table_invoices WHERE id = %d",
            $invoice_id
        ));
        if ($invoice_data) {
            $lead_id = $invoice_data->lead_id;
            $deal_id = $invoice_data->deal_id;
        }
    }

    if (!$lead_id) {
        wp_send_json_success(array());
        return;
    }

    // Build query to get comments from lead, deal, and invoice
    $query_parts = array("(c.entity_type = 'lead' AND c.entity_id = " . intval($lead_id) . ")");

    if ($deal_id > 0) {
        $query_parts[] = "(c.entity_type = 'deal' AND c.entity_id = " . intval($deal_id) . ")";
    }

    if ($invoice_id > 0) {
        $query_parts[] = "(c.entity_type = 'invoice' AND c.entity_id = " . intval($invoice_id) . ")";
    }

    $where_clause = implode(' OR ', $query_parts);

    // Get comments for lead, deal, and invoice
    $comments = $wpdb->get_results(
        "SELECT c.*, u.display_name as author, c.entity_type
        FROM $table_comments c
        LEFT JOIN {$wpdb->users} u ON c.created_by = u.ID
        WHERE ($where_clause)
        AND c.is_active = 1
        ORDER BY c.created_at DESC"
    );

    $formatted = array();
    foreach ($comments as $comment) {
        $formatted[] = array(
            'id' => $comment->id,
            'comment' => $comment->comment,
            'author' => $comment->author ?: 'Unknown',
            'date' => date('M d, Y @ h:i A', strtotime($comment->created_at)),
            'entity_type' => $comment->entity_type
        );
    }

    wp_send_json_success($formatted);
}

// Add Pipeline Comment
add_action('wp_ajax_add_pipeline_comment', 'add_pipeline_comment_handler');
function add_pipeline_comment_handler() {
    check_ajax_referer('add_pipeline_comment', 'nonce');

    if (!user_has_pipeline_access()) {
        wp_send_json_error('Access denied');
        return;
    }

    global $wpdb;
    $table_comments = $wpdb->prefix . 'pipeline_comments';

    $data = array(
        'entity_type' => sanitize_text_field($_POST['entity_type']),
        'entity_id' => intval($_POST['entity_id']),
        'comment' => sanitize_textarea_field($_POST['comment']),
        'created_by' => get_current_user_id(),
    );

    $result = $wpdb->insert($table_comments, $data);

    if ($result) {
        wp_send_json_success('Comment added successfully');
    } else {
        wp_send_json_error('Failed to add comment');
    }
}

// ========================================
// FIELD MANAGEMENT AJAX HANDLERS
// ========================================

// Save Field Options
add_action('wp_ajax_save_field_options', 'save_field_options_handler');
function save_field_options_handler() {
    check_ajax_referer('save_field_options', 'nonce');

    if (!current_user_can('administrator')) {
        wp_send_json_error('Access denied');
        return;
    }

    global $wpdb;
    $table_fields = $wpdb->prefix . 'pipeline_field_options';

    $field_name = sanitize_text_field($_POST['field_name']);
    $options = isset($_POST['options']) ? $_POST['options'] : array();

    // Sanitize options
    $options = array_map('sanitize_text_field', $options);

    $result = $wpdb->update(
        $table_fields,
        array('options' => json_encode($options)),
        array('field_name' => $field_name)
    );

    if ($result !== false) {
        wp_send_json_success('Field options updated successfully');
    } else {
        wp_send_json_error('Failed to update field options');
    }
}

// ========================================
// WHITELIST AJAX HANDLERS
// ========================================

// Add User to Whitelist
add_action('wp_ajax_add_whitelist_user', 'add_whitelist_user_handler');
function add_whitelist_user_handler() {
    check_ajax_referer('add_whitelist_user', 'nonce');

    if (!current_user_can('administrator')) {
        wp_send_json_error('Access denied');
        return;
    }

    global $wpdb;
    $table_whitelist = $wpdb->prefix . 'pipeline_whitelist';

    $user_id = intval($_POST['user_id']);
    $user = get_user_by('id', $user_id);

    if (!$user) {
        wp_send_json_error('User not found');
        return;
    }

    $data = array(
        'user_id' => $user_id,
        'email' => $user->user_email,
        'permissions' => json_encode(array('view', 'edit', 'delete')),
        'is_active' => 1
    );

    $result = $wpdb->insert($table_whitelist, $data);

    if ($result) {
        wp_send_json_success('User added to whitelist');
    } else {
        wp_send_json_error('Failed to add user to whitelist');
    }
}

// Remove User from Whitelist
add_action('wp_ajax_remove_whitelist_user', 'remove_whitelist_user_handler');
function remove_whitelist_user_handler() {
    check_ajax_referer('remove_whitelist_user', 'nonce');

    if (!current_user_can('administrator')) {
        wp_send_json_error('Access denied');
        return;
    }

    global $wpdb;
    $table_whitelist = $wpdb->prefix . 'pipeline_whitelist';

    $whitelist_id = intval($_POST['whitelist_id']);

    $result = $wpdb->update(
        $table_whitelist,
        array('is_active' => 0),
        array('id' => $whitelist_id)
    );

    if ($result !== false) {
        wp_send_json_success('User removed from whitelist');
    } else {
        wp_send_json_error('Failed to remove user from whitelist');
    }
}
