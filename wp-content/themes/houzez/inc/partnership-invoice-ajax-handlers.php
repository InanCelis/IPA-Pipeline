<?php
/**
 * Partnership Invoice AJAX Handlers
 * File: inc/partnership-invoice-ajax-handlers.php
 */

// Save Partnership Invoice
add_action('wp_ajax_save_partnership_invoice', 'save_partnership_invoice_handler');
function save_partnership_invoice_handler() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'partnership_invoice_nonce')) {
        wp_send_json_error('Invalid nonce');
        return;
    }

    // Check user permissions (allow administrators)
    if (!current_user_can('administrator')) {
        wp_send_json_error('Access denied - Admin only');
        return;
    }

    global $wpdb;
    $table_invoices = $wpdb->prefix . 'partnership_invoices';
    $table_payment_items = $wpdb->prefix . 'partnership_invoice_payment_items';

    // Validate required fields
    if (empty($_POST['invoice_type']) || empty($_POST['partnership_id']) || empty($_POST['date_issued']) || empty($_POST['due_date'])) {
        wp_send_json_error('Missing required fields');
        return;
    }

    $invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
    $invoice_type = sanitize_text_field($_POST['invoice_type']);
    $partnership_id = intval($_POST['partnership_id']);
    $date_issued = sanitize_text_field($_POST['date_issued']);
    $due_date = sanitize_text_field($_POST['due_date']);
    $total_amount = isset($_POST['total_amount']) ? floatval($_POST['total_amount']) : 0;

    // Billed To
    $billed_to_name = isset($_POST['billed_to_name']) ? sanitize_text_field($_POST['billed_to_name']) : '';
    $billed_to_position = isset($_POST['billed_to_position']) ? sanitize_text_field($_POST['billed_to_position']) : '';
    $billed_to_company = isset($_POST['billed_to_company']) ? sanitize_text_field($_POST['billed_to_company']) : '';
    $billed_to_address = isset($_POST['billed_to_address']) ? sanitize_textarea_field($_POST['billed_to_address']) : '';
    $other_details = isset($_POST['other_details']) ? sanitize_textarea_field($_POST['other_details']) : '';

    // Service Description
    $service_project = isset($_POST['service_project']) ? sanitize_text_field($_POST['service_project']) : '';
    $service_package_tier = isset($_POST['service_package_tier']) ? sanitize_text_field($_POST['service_package_tier']) : '';
    $service_project_duration = isset($_POST['service_project_duration']) ? sanitize_text_field($_POST['service_project_duration']) : '';
    $service_telemarketers = isset($_POST['service_telemarketers']) ? sanitize_text_field($_POST['service_telemarketers']) : '';
    $service_monthly_hours = isset($_POST['service_monthly_hours']) ? sanitize_text_field($_POST['service_monthly_hours']) : '';
    $scope_of_work = isset($_POST['scope_of_work']) ? sanitize_textarea_field($_POST['scope_of_work']) : '';

    // Payment Status
    $payment_status = isset($_POST['payment_status']) ? sanitize_text_field($_POST['payment_status']) : 'Pending';

    // Currency
    $currency_code = isset($_POST['currency_code']) ? sanitize_text_field($_POST['currency_code']) : 'USD';

    // Calculate USD amount
    $amount_usd = $total_amount;
    if ($currency_code !== 'USD') {
        // Get exchange rate
        $table_exchange_rates = $wpdb->prefix . 'exchange_rates';
        $exchange_rate = $wpdb->get_var($wpdb->prepare(
            "SELECT exchange_rate FROM $table_exchange_rates WHERE currency_to = 'USD' AND currency_from = %s ORDER BY last_updated DESC LIMIT 1",
            $currency_code
        ));
        if ($exchange_rate) {
            $amount_usd = $total_amount * floatval($exchange_rate);
        }
    }

    // Payment Items
    $payment_items_json = isset($_POST['payment_items']) ? stripslashes($_POST['payment_items']) : '[]';
    $payment_items = json_decode($payment_items_json, true);

    $data = array(
        'invoice_type' => $invoice_type,
        'partnership_id' => $partnership_id,
        'date_issued' => $date_issued,
        'due_date' => $due_date,
        'billed_to_name' => $billed_to_name,
        'billed_to_position' => $billed_to_position,
        'billed_to_company' => $billed_to_company,
        'billed_to_address' => $billed_to_address,
        'other_details' => $other_details,
        'service_project' => $service_project,
        'service_package_tier' => $service_package_tier,
        'service_project_duration' => $service_project_duration,
        'service_telemarketers' => $service_telemarketers,
        'service_monthly_hours' => $service_monthly_hours,
        'scope_of_work' => $scope_of_work,
        'payment_status' => $payment_status,
        'total_amount' => $total_amount,
        'currency_code' => $currency_code,
        'amount_usd' => $amount_usd
    );

    if ($invoice_id > 0) {
        // Get existing invoice to check if type changed
        $existing_invoice = $wpdb->get_row($wpdb->prepare(
            "SELECT invoice_type, invoice_number, date_issued FROM $table_invoices WHERE id = %d",
            $invoice_id
        ));

        if ($existing_invoice) {
            // Check if invoice type changed
            if ($existing_invoice->invoice_type !== $invoice_type) {
                // Type changed - regenerate invoice number
                $invoice_number = generate_partnership_invoice_number($invoice_type, $date_issued);
                $data['invoice_number'] = $invoice_number;
            }
            // If type didn't change, keep existing invoice number (don't include in $data, will not be updated)
        }

        // Update existing invoice
        $result = $wpdb->update($table_invoices, $data, array('id' => $invoice_id));
        if ($result === false) {
            wp_send_json_error('Database error updating invoice: ' . $wpdb->last_error);
            return;
        }

        // Delete existing payment items
        $wpdb->delete($table_payment_items, array('invoice_id' => $invoice_id));
    } else {
        // Generate invoice number
        $invoice_number = generate_partnership_invoice_number($invoice_type, $date_issued);
        $data['invoice_number'] = $invoice_number;

        // Insert new invoice
        $result = $wpdb->insert($table_invoices, $data);
        if ($result === false) {
            wp_send_json_error('Database error creating invoice: ' . $wpdb->last_error);
            return;
        }
        $invoice_id = $wpdb->insert_id;

        if (!$invoice_id) {
            wp_send_json_error('Failed to get invoice ID');
            return;
        }
    }

    // Insert payment items
    if (!empty($payment_items) && is_array($payment_items)) {
        $sort_order = 0;
        foreach ($payment_items as $item) {
            $result = $wpdb->insert($table_payment_items, array(
                'invoice_id' => $invoice_id,
                'description' => isset($item['description']) ? sanitize_text_field($item['description']) : '',
                'payment_date' => isset($item['payment_date']) ? sanitize_text_field($item['payment_date']) : null,
                'amount_due' => isset($item['amount_due']) ? floatval($item['amount_due']) : 0,
                'sort_order' => $sort_order++
            ));

            if ($result === false) {
                error_log('Error inserting payment item: ' . $wpdb->last_error);
            }
        }
    }

    wp_send_json_success(array(
        'message' => 'Invoice saved successfully',
        'invoice_id' => $invoice_id
    ));
}

// // Generate Partnership Invoice Number
// function generate_partnership_invoice_number($invoice_type, $date_issued) {
//     global $wpdb;
//     $table_invoices = $wpdb->prefix . 'partnership_invoices';

//     $date = new DateTime($date_issued);
//     $year = $date->format('Y');
//     $month = $date->format('m');

//     // Determine company code
//     $company_code = ($invoice_type === 'International Property Alerts') ? 'IPAP' : 'PVIP';

//     // Get count of invoices for this month and type
//     $prefix = "INV-{$year}-{$month}-{$company_code}-";
//     $count = $wpdb->get_var($wpdb->prepare(
//         "SELECT COUNT(*) FROM $table_invoices WHERE invoice_number LIKE %s",
//         $wpdb->esc_like($prefix) . '%'
//     ));

//     $number = str_pad($count + 1, 5, '0', STR_PAD_LEFT);
//     return $prefix . $number;
// }

// Generate Partnership Invoice Number
function generate_partnership_invoice_number($invoice_type, $date_issued) {
    global $wpdb;
    $table_invoices = $wpdb->prefix . 'partnership_invoices';

    $date = new DateTime($date_issued);
    $year = $date->format('Y');
    $month = $date->format('m');

    // Determine company code
    $company_code = ($invoice_type === 'International Property Alerts') ? 'IPAP' : 'PVIP';

    // Prefix format
    $prefix = "INV-{$year}-{$month}-{$company_code}-";

    // Get the latest invoice number with this prefix
    $last_invoice = $wpdb->get_var($wpdb->prepare(
        "SELECT invoice_number 
         FROM $table_invoices 
         WHERE invoice_number LIKE %s 
         ORDER BY invoice_number DESC 
         LIMIT 1",
        $wpdb->esc_like($prefix) . '%'
    ));

    // Extract the last 5 digits
    if ($last_invoice) {
        $last_number = intval(substr($last_invoice, -5));
    } else {
        $last_number = 0;
    }

    // Next invoice number
    $number = str_pad($last_number + 1, 5, '0', STR_PAD_LEFT);

    return $prefix . $number;
}

// Get Partnership Invoice
add_action('wp_ajax_get_partnership_invoice', 'get_partnership_invoice_handler');
function get_partnership_invoice_handler() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'partnership_invoice_nonce')) {
        wp_send_json_error('Invalid nonce');
        return;
    }

    if (!current_user_can('administrator')) {
        wp_send_json_error('Access denied');
        return;
    }

    global $wpdb;
    $table_invoices = $wpdb->prefix . 'partnership_invoices';
    $table_payment_items = $wpdb->prefix . 'partnership_invoice_payment_items';

    $invoice_id = intval($_POST['invoice_id']);

    $invoice = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_invoices WHERE id = %d AND is_active = 1",
        $invoice_id
    ));

    if (!$invoice) {
        wp_send_json_error('Invoice not found');
        return;
    }

    $payment_items = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_payment_items WHERE invoice_id = %d ORDER BY sort_order",
        $invoice_id
    ));

    wp_send_json_success(array(
        'invoice' => $invoice,
        'payment_items' => $payment_items
    ));
}

// Delete Partnership Invoice
add_action('wp_ajax_delete_partnership_invoice', 'delete_partnership_invoice_handler');
function delete_partnership_invoice_handler() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'partnership_invoice_nonce')) {
        wp_send_json_error('Invalid nonce');
        return;
    }

    if (!current_user_can('administrator')) {
        wp_send_json_error('Access denied');
        return;
    }

    global $wpdb;
    $table_invoices = $wpdb->prefix . 'partnership_invoices';

    $invoice_id = intval($_POST['invoice_id']);

    // Soft delete
    $wpdb->update(
        $table_invoices,
        array(
            'is_active' => 0,
            'deleted_at' => current_time('mysql')
        ),
        array('id' => $invoice_id)
    );

    wp_send_json_success('Invoice deleted successfully');
}

// Preview Partnership Invoice PDF
add_action('wp_ajax_preview_partnership_invoice_pdf', 'preview_partnership_invoice_pdf_handler');
function preview_partnership_invoice_pdf_handler() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'partnership_invoice_nonce')) {
        wp_send_json_error('Invalid nonce');
        return;
    }

    if (!current_user_can('administrator')) {
        wp_send_json_error('Access denied');
        return;
    }

    global $wpdb;
    $table_invoices = $wpdb->prefix . 'partnership_invoices';
    $table_payment_items = $wpdb->prefix . 'partnership_invoice_payment_items';
    $table_partnerships = $wpdb->prefix . 'partnerships';

    $invoice_id = intval($_POST['invoice_id']);

    $invoice = $wpdb->get_row($wpdb->prepare(
        "SELECT i.*, p.company_name as partnership_company
        FROM $table_invoices i
        LEFT JOIN $table_partnerships p ON i.partnership_id = p.id
        WHERE i.id = %d AND i.is_active = 1",
        $invoice_id
    ));

    if (!$invoice) {
        wp_send_json_error('Invoice not found');
        return;
    }

    $payment_items = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_payment_items WHERE invoice_id = %d ORDER BY sort_order",
        $invoice_id
    ));

    $html = generate_partnership_invoice_html($invoice, $payment_items);
    wp_send_json_success($html);
}

// Download Partnership Invoice PDF
add_action('wp_ajax_download_partnership_invoice_pdf', 'download_partnership_invoice_pdf_handler');
function download_partnership_invoice_pdf_handler() {
    if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'partnership_invoice_pdf_download')) {
        wp_die('Invalid nonce');
    }

    if (!current_user_can('administrator')) {
        wp_die('Access denied');
    }

    global $wpdb;
    $table_invoices = $wpdb->prefix . 'partnership_invoices';
    $table_payment_items = $wpdb->prefix . 'partnership_invoice_payment_items';
    $table_partnerships = $wpdb->prefix . 'partnerships';

    $invoice_id = intval($_GET['invoice_id']);

    $invoice = $wpdb->get_row($wpdb->prepare(
        "SELECT i.*, p.company_name as partnership_company
        FROM $table_invoices i
        LEFT JOIN $table_partnerships p ON i.partnership_id = p.id
        WHERE i.id = %d AND i.is_active = 1",
        $invoice_id
    ));

    if (!$invoice) {
        wp_die('Invoice not found');
    }

    $payment_items = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_payment_items WHERE invoice_id = %d ORDER BY sort_order",
        $invoice_id
    ));

    $html = generate_partnership_invoice_html($invoice, $payment_items, true);

    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Invoice <?php echo esc_html($invoice->invoice_number); ?></title>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    </head>
    <body>
        <div id="invoice-content">
            <?php echo $html; ?>
        </div>
        <script>
            window.onload = function() {
                const element = document.getElementById('invoice-content');
                const opt = {
                    margin: 0,
                    filename: '<?php echo esc_js($invoice->invoice_number); ?>.pdf',
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 2, useCORS: true },
                    jsPDF: { unit: 'mm', format: 'letter', orientation: 'portrait' }
                };

                html2pdf().set(opt).from(element).save().then(function() {
                    setTimeout(function() {
                        window.close();
                    }, 500);
                });
            };
        </script>
    </body>
    </html>
    <?php
    exit;
}

// Generate Partnership Invoice HTML
function generate_partnership_invoice_html($invoice, $payment_items, $for_download = false) {
    global $wpdb;

    // Get currency symbol
    $currency_code = !empty($invoice->currency_code) ? $invoice->currency_code : 'USD';
    $currency_symbol = '$'; // Default to USD
    if ($currency_code !== 'USD') {
        $table_currencies = $wpdb->prefix . 'houzez_currencies';
        $currency = $wpdb->get_row($wpdb->prepare(
            "SELECT currency_symbol FROM $table_currencies WHERE currency_code = %s",
            $currency_code
        ));
        if ($currency) {
            $currency_symbol = $currency->currency_symbol;
        }
    }

    // Determine logo and company details based on invoice type
    if ($invoice->invoice_type === 'International Property Alerts') {
        $logo_url = 'https://internationalpropertyalerts.com/wp-content/uploads/2025/10/PDF-logo.png';
        $company_address = '20 Wenlock Road, London, England, N1 7GU';
        $company_website = 'https://internationalpropertyalerts.com/';
        $company_name_footer = 'INTERNATIONAL PROPERTY ALERTS LTD.';
        $company_number = 'COMPANY NO. 16469075';
    } else {
        $logo_url = 'https://internationalpropertyalerts.com/wp-content/uploads/2025/10/primetask-logo-v2.png';
        $company_address = '2130 Chino Roces Ave, Legazpi Village, Makati City, 1230 Metro Manila';
        $company_website = 'https://primetaskph.com/';
        $company_name_footer = 'Primetask VA INC.';
        $company_number = 'TIN #: 674-925-299-00000';
        $company_reg = 'COMPANY REG. NO.: 202503019292002';
    }

    $html = '<div style="font-family: Arial, sans-serif; font-size: 11px; min-height: 100vh; display: flex; flex-direction: column; margin: 0; padding: 0;">';

    // Header
    $html .= '<div style="background-color: #f5f5f5; padding: 15px 20px; margin: 0 0 10px 0;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 50%;">
                    <img src="' . $logo_url . '" alt="' . esc_attr($invoice->invoice_type) . '" style="max-width: 200px;">
                </td>
                <td style="width: 50%; text-align: right; vertical-align: center;">
                    <div style="font-size: 10px; line-height: 1.4;">
                        <strong>' . $company_address . '</strong><br>
                        <a href="' . $company_website . '" style="color: #007bff; text-decoration: none;">' . $company_website . '</a>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div style="flex: 1; padding: 0 20px;">';

    // Title
    $html .= '<h1 style="text-align: center; font-size: 22px; margin: 10px 0; color: #333;">INVOICE</h1>';

    // Invoice Details Box
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

    // Service Description
    $html .= '<div style="margin-bottom: 25px;">
        <h3 style="margin-bottom: 8px; color: #333; font-size: 12px;">Service Description:</h3>
        <table style="width: 100%; border-collapse: collapse; font-size: 10px;">
            <tr>
                <td style="padding: 4px 0; width: 30%;"><strong>Project:</strong></td>
                <td style="padding: 4px 0;">' . esc_html($invoice->service_project ?: 'N/A') . '</td>
            </tr>
            <tr>
                <td style="padding: 4px 0;"><strong>Package Tier:</strong></td>
                <td style="padding: 4px 0;">' . esc_html($invoice->service_package_tier ?: 'N/A') . '</td>
            </tr>
            <tr>
                <td style="padding: 4px 0;"><strong>Project Duration:</strong></td>
                <td style="padding: 4px 0;">' . esc_html($invoice->service_project_duration ?: 'N/A') . '</td>
            </tr>
            <tr>
                <td style="padding: 4px 0;"><strong>Telemarketers:</strong></td>
                <td style="padding: 4px 0;">' . esc_html($invoice->service_telemarketers ?: 'N/A') . '</td>
            </tr>
            <tr>
                <td style="padding: 4px 0;"><strong>Monthly Hours:</strong></td>
                <td style="padding: 4px 0;">' . esc_html($invoice->service_monthly_hours ?: 'N/A') . '</td>
            </tr>
        </table>
    </div>';

    // Scope of Work
    if ($invoice->scope_of_work) {
        // Process scope of work to add spacing between bullet points
        $scope_lines = explode("\n", $invoice->scope_of_work);
        $processed_scope = '';
        foreach ($scope_lines as $line) {
            $trimmed = trim($line);
            // Add margin-bottom to lines that start with bullet points
            if (preg_match('/^[•\-\*]/', $trimmed)) {
                $processed_scope .= '<div style="margin-bottom: 8px; line-height: 1.2;">' . esc_html($line) . '</div>';
            } else if (!empty($trimmed)) {
                $processed_scope .= '<div style="line-height: 1.2;">' . esc_html($line) . '</div>';
            }
        }

        $html .= '<div style="margin-bottom: 25px;">
            <h3 style="margin-bottom: 4px; color: #333; font-size: 12px;">Scope of Work:</h3>
            <div style="font-size: 10px;">' . $processed_scope . '</div>
        </div>';
    }

    // Schedule of Payment Table
    $html .= '<h3 style="margin-bottom: 8px; color: #333; font-size: 12px;">Schedule of Payment:</h3>';
    $html .= '<table class="invoice-table" style="width: 100%; border-collapse: collapse; margin: 6px 0;">
        <thead>
            <tr style="background: #f8f9fa;">
                <th style="border: 1px solid #ddd; padding: 6px; text-align: left; font-size: 10px;">Description</th>
                <th style="border: 1px solid #ddd; padding: 6px; text-align: center; font-size: 10px;">Date</th>
                <th style="border: 1px solid #ddd; padding: 6px; text-align: right; font-size: 10px;">Amount Due</th>
            </tr>
        </thead>
        <tbody>';

    if (!empty($payment_items)) {
        foreach ($payment_items as $item) {
            $html .= '<tr>
                <td style="border: 1px solid #ddd; padding: 6px; font-size: 10px;">' . esc_html($item->description) . '</td>
                <td style="border: 1px solid #ddd; padding: 6px; text-align: center; font-size: 10px;">' . date('m/d/Y', strtotime($item->payment_date)) . '</td>
                <td style="border: 1px solid #ddd; padding: 6px; text-align: right; font-size: 10px;">' . esc_html($currency_symbol) . number_format($item->amount_due, 2) . '</td>
            </tr>';
        }
    }

    $html .= '<tr style="background: #f8f9fa;">
                <td colspan="2" style="border: 1px solid #ddd; padding: 8px; text-align: right; font-size: 10px;"><strong>Total:</strong></td>
                <td style="border: 1px solid #ddd; padding: 8px; text-align: right; font-size: 10px;"><strong>' . esc_html($currency_symbol) . number_format($invoice->total_amount, 2) . '</strong></td>
            </tr>';

    $html .= '</tbody>
    </table>';

    $html .= '</div>'; // Close flex content div

    // Payment Instructions
    $html .= '<div style="margin: 0; margin-top: auto;">
        <div style="padding: 10px 20px; background: #f8f9fa;">
            <h3 style="margin-top: 0; margin-bottom: 8px; color: #333; font-size: 12px;">Please pay to:</h3>';

    if ($invoice->invoice_type === 'International Property Alerts') {
        $html .= '<table style="width: 100%; border-collapse: collapse;">
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
            </table>';
    } else {
        $html .= '<div style="line-height: 1.6; font-size: 9px;">
                <strong>Union Bank</strong> 002020041516<br>
                <strong>Account Name:</strong> PRIMETASK VA INC.<br>
                <strong>Swift Code:</strong> UBPHPHMM
            </div>';
    }

    $html .= '</div>

        <!-- Footer -->
        <div style="padding: 0; border-top: 1px solid #ddd; text-align: center; margin: 0;">
            <div style="background: #6c757d; color: white; padding: 8px; font-size: 10px;">';

    if ($invoice->invoice_type === 'International Property Alerts') {
        $html .= '<strong>' . $company_name_footer . '</strong>
                <div style="margin-top: 2px; font-size: 9px;">' . $company_number . '</div>';
    } else {
        $html .= '<table style="width: 100%; color: white;">
                    <tr>
                        <td style="width: 50%; text-align: left; padding: 0 10px;">
                            <strong>' . $company_name_footer . ' ' . $company_number . '</strong>
                        </td>
                        <td style="width: 50%; text-align: right; padding: 0 10px;">
                            <strong>' . $company_reg . '</strong>
                        </td>
                    </tr>
                </table>';
    }

    $html .= '</div>
        </div>
    </div>';

    $html .= '</div>'; // Close main container

    return $html;
}

// Automatic Overdue Status Update (Run daily via cron)
add_action('partnership_check_overdue_invoices', 'partnership_check_overdue_invoices_cron');
function partnership_check_overdue_invoices_cron() {
    global $wpdb;
    $table_invoices = $wpdb->prefix . 'partnership_invoices';

    $today = current_time('Y-m-d');

    // Update invoices that are past due date and not fully paid
    $wpdb->query($wpdb->prepare(
        "UPDATE $table_invoices
        SET payment_status = 'Overdue'
        WHERE due_date < %s
        AND payment_status NOT IN ('Fully Paid', 'Overdue')
        AND is_active = 1",
        $today
    ));
}

// Schedule the cron job if not already scheduled
if (!wp_next_scheduled('partnership_check_overdue_invoices')) {
    wp_schedule_event(time(), 'daily', 'partnership_check_overdue_invoices');
}
