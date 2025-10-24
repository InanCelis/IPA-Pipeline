<?php
/**
 * Pipeline - Reports
 */

global $wpdb;
$table_leads = $wpdb->prefix . 'pipeline_leads';
$table_deals = $wpdb->prefix . 'pipeline_deals';
$table_invoices = $wpdb->prefix . 'pipeline_invoices';

// Date range filter
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : date('Y-m-d');
$report_type = isset($_GET['report_type']) ? sanitize_text_field($_GET['report_type']) : 'sales';

// Sales Report Data
$sales_data = array(
    'fully_paid' => $wpdb->get_var($wpdb->prepare("
        SELECT SUM(referral_fee_amount) FROM $table_invoices
        WHERE payment_status = 'Fully Paid'
        AND date_issued BETWEEN %s AND %s
        AND is_active = 1
    ", $date_from, $date_to)),
    'partial' => $wpdb->get_var($wpdb->prepare("
        SELECT SUM(referral_fee_amount) FROM $table_invoices
        WHERE payment_status = 'Partial'
        AND date_issued BETWEEN %s AND %s
        AND is_active = 1
    ", $date_from, $date_to)),
    'pending' => $wpdb->get_var($wpdb->prepare("
        SELECT SUM(referral_fee_amount) FROM $table_invoices
        WHERE payment_status = 'Pending'
        AND date_issued BETWEEN %s AND %s
        AND is_active = 1
    ", $date_from, $date_to)),
    'overdue' => $wpdb->get_var($wpdb->prepare("
        SELECT SUM(referral_fee_amount) FROM $table_invoices
        WHERE payment_status = 'Overdue'
        AND date_issued BETWEEN %s AND %s
        AND is_active = 1
    ", $date_from, $date_to)),
);

// Leads Report Data
$leads_data = array(
    'new_lead' => $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM $table_leads
        WHERE status = 'New Lead'
        AND date_inquiry BETWEEN %s AND %s
        AND is_active = 1
    ", $date_from, $date_to)),
    'qualifying' => $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM $table_leads
        WHERE status = 'Qualifying'
        AND date_inquiry BETWEEN %s AND %s
        AND is_active = 1
    ", $date_from, $date_to)),
    'wrong_contact' => $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM $table_leads
        WHERE status = 'Wrong Contact'
        AND date_inquiry BETWEEN %s AND %s
        AND is_active = 1
    ", $date_from, $date_to)),
    'qualified' => $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM $table_leads
        WHERE status = 'Qualified'
        AND date_inquiry BETWEEN %s AND %s
        AND is_active = 1
    ", $date_from, $date_to)),
    'cold_lead' => $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM $table_leads
        WHERE is_cold_lead = 1
        AND date_inquiry BETWEEN %s AND %s
    ", $date_from, $date_to)),
);

// Deals Report Data
$deals_data = array(
    'na' => $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM $table_deals
        WHERE deal_status = 'N/A'
        AND updated_at BETWEEN %s AND %s
        AND is_active = 1
    ", $date_from, $date_to)),
    'preparing_options' => $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM $table_deals
        WHERE deal_status = 'Preparing Options'
        AND updated_at BETWEEN %s AND %s
        AND is_active = 1
    ", $date_from, $date_to)),
    'options_sent' => $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM $table_deals
        WHERE deal_status = 'Options Sent'
        AND updated_at BETWEEN %s AND %s
        AND is_active = 1
    ", $date_from, $date_to)),
    'site_visit' => $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM $table_deals
        WHERE deal_status = 'Site Visit'
        AND updated_at BETWEEN %s AND %s
        AND is_active = 1
    ", $date_from, $date_to)),
    'negotiation' => $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM $table_deals
        WHERE deal_status = 'Negotiation and Documentation'
        AND updated_at BETWEEN %s AND %s
        AND is_active = 1
    ", $date_from, $date_to)),
    'for_payment' => $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM $table_deals
        WHERE deal_status = 'For Payment'
        AND updated_at BETWEEN %s AND %s
        AND is_active = 1
    ", $date_from, $date_to)),
    'buyer_payment_completed' => $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM $table_deals
        WHERE deal_status = 'Buyer Payment Completed'
        AND updated_at BETWEEN %s AND %s
        AND is_active = 1
    ", $date_from, $date_to)),
);

// Performance by Person
$person_in_charge_option = get_option('partnership_field_person_in_charge', '');
$person_in_charge_list = !empty($person_in_charge_option) ? explode("\n", $person_in_charge_option) : array();
$person_in_charge_list = array_map('trim', $person_in_charge_list);
$person_in_charge_list = array_filter($person_in_charge_list);

$performance_data = array();
foreach ($person_in_charge_list as $person) {
    $performance_data[$person] = array(
        'total_leads' => $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table_leads
            WHERE assigned_to = %s
            AND date_inquiry BETWEEN %s AND %s
            AND is_active = 1
        ", $person, $date_from, $date_to)),
        'new_leads' => $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table_leads
            WHERE assigned_to = %s AND status = 'New Lead'
            AND date_inquiry BETWEEN %s AND %s
            AND is_active = 1
        ", $person, $date_from, $date_to)),
        'qualified' => $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table_leads
            WHERE assigned_to = %s AND status = 'Qualified'
            AND date_inquiry BETWEEN %s AND %s
            AND is_active = 1
        ", $person, $date_from, $date_to)),
        'invoiced' => $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT i.id) FROM $table_invoices i
            LEFT JOIN $table_leads l ON i.lead_id = l.id
            WHERE l.assigned_to = %s
            AND i.date_issued BETWEEN %s AND %s
            AND i.is_active = 1
        ", $person, $date_from, $date_to)),
        'total_sales' => $wpdb->get_var($wpdb->prepare("
            SELECT SUM(i.referral_fee_amount) FROM $table_invoices i
            LEFT JOIN $table_leads l ON i.lead_id = l.id
            WHERE l.assigned_to = %s
            AND i.payment_status = 'Fully Paid'
            AND i.date_issued BETWEEN %s AND %s
            AND i.is_active = 1
        ", $person, $date_from, $date_to)),
    );
}
?>

<div class="pipeline-header">
    <h2 class="pipeline-title">Reports & Analytics</h2>
</div>

<!-- Date Range Filter -->
<div class="filter-section">
    <form method="GET" action="">
        <input type="hidden" name="hpage" value="reports">
        <div class="filter-row">
            <div class="filter-group">
                <label>Report Type</label>
                <select name="report_type" class="filter-select">
                    <option value="sales" <?php selected($report_type, 'sales'); ?>>Sales Report</option>
                    <option value="leads" <?php selected($report_type, 'leads'); ?>>Leads Report</option>
                    <option value="deals" <?php selected($report_type, 'deals'); ?>>Deals Report</option>
                    <option value="performance" <?php selected($report_type, 'performance'); ?>>Performance Report</option>
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
                <button type="submit" class="btn btn-primary">Generate Report</button>
                <button type="button" class="btn btn-success" onclick="exportReport()">
                    <i class="houzez-icon icon-download-bottom"></i> Export
                </button>
            </div>
        </div>
    </form>
</div>

<?php if ($report_type == 'sales') : ?>
    <!-- Sales Report -->
    <div class="chart-container">
        <h3>Sales Report (<?php echo date('M d, Y', strtotime($date_from)); ?> - <?php echo date('M d, Y', strtotime($date_to)); ?>)</h3>

        <div class="stats-grid">
            <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <div class="stat-label">Fully Paid</div>
                <div class="stat-value">$<?php echo number_format($sales_data['fully_paid'] ?: 0, 2); ?></div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="stat-label">Partial</div>
                <div class="stat-value">$<?php echo number_format($sales_data['partial'] ?: 0, 2); ?></div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="stat-label">Pending</div>
                <div class="stat-value">$<?php echo number_format($sales_data['pending'] ?: 0, 2); ?></div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                <div class="stat-label">Overdue</div>
                <div class="stat-value">$<?php echo number_format($sales_data['overdue'] ?: 0, 2); ?></div>
            </div>
        </div>

        <canvas id="salesChart" width="400" height="200"></canvas>
    </div>

<?php elseif ($report_type == 'leads') : ?>
    <!-- Leads Report -->
    <div class="chart-container">
        <h3>Leads Report (<?php echo date('M d, Y', strtotime($date_from)); ?> - <?php echo date('M d, Y', strtotime($date_to)); ?>)</h3>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">New Leads</div>
                <div class="stat-value"><?php echo number_format($leads_data['new_lead']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Qualifying</div>
                <div class="stat-value"><?php echo number_format($leads_data['qualifying']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Qualified</div>
                <div class="stat-value"><?php echo number_format($leads_data['qualified']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Wrong Contact</div>
                <div class="stat-value"><?php echo number_format($leads_data['wrong_contact']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Cold Leads</div>
                <div class="stat-value"><?php echo number_format($leads_data['cold_lead']); ?></div>
            </div>
        </div>

        <canvas id="leadsChart" width="400" height="200"></canvas>
    </div>

<?php elseif ($report_type == 'deals') : ?>
    <!-- Deals Report -->
    <div class="chart-container">
        <h3>Deals Report (<?php echo date('M d, Y', strtotime($date_from)); ?> - <?php echo date('M d, Y', strtotime($date_to)); ?>)</h3>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">N/A</div>
                <div class="stat-value"><?php echo number_format($deals_data['na']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Preparing Options</div>
                <div class="stat-value"><?php echo number_format($deals_data['preparing_options']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Options Sent</div>
                <div class="stat-value"><?php echo number_format($deals_data['options_sent']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Site Visit</div>
                <div class="stat-value"><?php echo number_format($deals_data['site_visit']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Negotiation</div>
                <div class="stat-value"><?php echo number_format($deals_data['negotiation']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">For Payment</div>
                <div class="stat-value"><?php echo number_format($deals_data['for_payment']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Buyer Payment Completed</div>
                <div class="stat-value"><?php echo number_format($deals_data['buyer_payment_completed']); ?></div>
            </div>
        </div>

        <canvas id="dealsChart" width="400" height="200"></canvas>
    </div>

<?php else : ?>
    <!-- Performance Report -->
    <div class="chart-container">
        <h3>Performance by Person-in-Charge (<?php echo date('M d, Y', strtotime($date_from)); ?> - <?php echo date('M d, Y', strtotime($date_to)); ?>)</h3>

        <table class="pipeline-table">
            <thead>
                <tr>
                    <th>Person</th>
                    <th>Total Leads</th>
                    <th>New Leads</th>
                    <th>Qualified</th>
                    <th>Invoiced</th>
                    <th>Total Sales</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($performance_data as $person => $data) : ?>
                    <tr>
                        <td><strong><?php echo esc_html($person); ?></strong></td>
                        <td><?php echo number_format($data['total_leads']); ?></td>
                        <td><?php echo number_format($data['new_leads']); ?></td>
                        <td><?php echo number_format($data['qualified']); ?></td>
                        <td><?php echo number_format($data['invoiced']); ?></td>
                        <td><strong>$<?php echo number_format($data['total_sales'] ?: 0, 2); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Sales Chart
<?php if ($report_type == 'sales') : ?>
const salesCtx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(salesCtx, {
    type: 'bar',
    data: {
        labels: ['Fully Paid', 'Partial', 'Pending', 'Overdue'],
        datasets: [{
            label: 'Amount ($)',
            data: [
                <?php echo $sales_data['fully_paid'] ?: 0; ?>,
                <?php echo $sales_data['partial'] ?: 0; ?>,
                <?php echo $sales_data['pending'] ?: 0; ?>,
                <?php echo $sales_data['overdue'] ?: 0; ?>
            ],
            backgroundColor: [
                'rgba(67, 233, 123, 0.8)',
                'rgba(240, 147, 251, 0.8)',
                'rgba(79, 172, 254, 0.8)',
                'rgba(250, 112, 154, 0.8)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    }
                }
            }
        }
    }
});
<?php endif; ?>

// Leads Chart
<?php if ($report_type == 'leads') : ?>
const leadsCtx = document.getElementById('leadsChart').getContext('2d');
const leadsChart = new Chart(leadsCtx, {
    type: 'doughnut',
    data: {
        labels: ['New Leads', 'Qualifying', 'Qualified', 'Wrong Contact', 'Cold Leads'],
        datasets: [{
            data: [
                <?php echo $leads_data['new_lead']; ?>,
                <?php echo $leads_data['qualifying']; ?>,
                <?php echo $leads_data['qualified']; ?>,
                <?php echo $leads_data['wrong_contact']; ?>,
                <?php echo $leads_data['cold_lead']; ?>
            ],
            backgroundColor: [
                'rgba(102, 126, 234, 0.8)',
                'rgba(240, 147, 251, 0.8)',
                'rgba(79, 172, 254, 0.8)',
                'rgba(67, 233, 123, 0.8)',
                'rgba(226, 227, 229, 0.8)'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true
    }
});
<?php endif; ?>

// Deals Chart
<?php if ($report_type == 'deals') : ?>
const dealsCtx = document.getElementById('dealsChart').getContext('2d');
const dealsChart = new Chart(dealsCtx, {
    type: 'bar',
    data: {
        labels: ['N/A', 'Preparing Options', 'Options Sent', 'Site Visit', 'Negotiation', 'For Payment', 'Buyer Payment Completed'],
        datasets: [{
            label: 'Number of Deals',
            data: [
                <?php echo $deals_data['na']; ?>,
                <?php echo $deals_data['preparing_options']; ?>,
                <?php echo $deals_data['options_sent']; ?>,
                <?php echo $deals_data['site_visit']; ?>,
                <?php echo $deals_data['negotiation']; ?>,
                <?php echo $deals_data['for_payment']; ?>,
                <?php echo $deals_data['buyer_payment_completed']; ?>
            ],
            backgroundColor: [
                'rgba(226, 227, 229, 0.8)',
                'rgba(102, 126, 234, 0.8)',
                'rgba(79, 172, 254, 0.8)',
                'rgba(240, 147, 251, 0.8)',
                'rgba(250, 112, 154, 0.8)',
                'rgba(255, 193, 7, 0.8)',
                'rgba(67, 233, 123, 0.8)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
<?php endif; ?>

function exportReport() {
    alert('Export functionality would generate a CSV or Excel file with the current report data.');
    // Implementation would use AJAX to generate and download a CSV/Excel file
}
</script>
