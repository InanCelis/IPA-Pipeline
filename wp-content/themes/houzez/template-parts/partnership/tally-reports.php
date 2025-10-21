<?php
/**
 * Tally Reports
 * File: template-parts/partnership/tally-reports.php
 */

global $wpdb;

$table_name = $wpdb->prefix . 'partnerships';

// Get statistics by person in charge
$reports = $wpdb->get_results("
    SELECT 
        person_in_charge,
        COUNT(*) as total_partners,
        SUM(CASE WHEN agreement_status = 'Signed' THEN 1 ELSE 0 END) as signed_partners,
        SUM(CASE WHEN agreement_status = 'Preparing' THEN 1 ELSE 0 END) as preparing_partners,
        SUM(CASE WHEN agreement_status = 'Pending Signature' THEN 1 ELSE 0 END) as pending_signature,
        SUM(CASE WHEN agreement_status = 'Draft Contract Sent' THEN 1 ELSE 0 END) as draft_contract,
        SUM(CASE WHEN agreement_status = 'Partner Declined' THEN 1 ELSE 0 END) as declined_partners,
        SUM(total_properties) as total_properties,
        SUM(CASE WHEN property_upload_status = 'Completed' THEN 1 ELSE 0 END) as completed_uploads,
        SUM(CASE WHEN property_upload_status = 'Ongoing' THEN 1 ELSE 0 END) as ongoing_uploads
    FROM {$table_name}
    WHERE person_in_charge != '' AND person_in_charge IS NOT NULL
    GROUP BY person_in_charge
    ORDER BY total_partners DESC
");

// Get overall statistics
$overall_stats = $wpdb->get_row("
    SELECT 
        COUNT(*) as total_partners,
        SUM(CASE WHEN agreement_status = 'Signed' THEN 1 ELSE 0 END) as signed_partners,
        SUM(total_properties) as total_properties,
        COUNT(DISTINCT person_in_charge) as total_persons
    FROM {$table_name}
");

// Get statistics by industry
$industry_stats = $wpdb->get_results("
    SELECT 
        industry,
        COUNT(*) as total_partners,
        SUM(total_properties) as total_properties
    FROM {$table_name}
    WHERE industry != '' AND industry IS NOT NULL
    GROUP BY industry
    ORDER BY total_partners DESC
");
?>

<div class="partnership-header">
    <h2 class="partnership-title">Tally Reports</h2>
    <button class="btn btn-primary" onclick="window.print()">
        <i class="houzez-icon icon-print-text"></i> Print Report
    </button>
</div>

<!-- Overall Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Partners</div>
        <div class="stat-value"><?php echo number_format($overall_stats->total_partners); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Signed Agreements</div>
        <div class="stat-value"><?php echo number_format($overall_stats->signed_partners); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Properties</div>
        <div class="stat-value"><?php echo number_format($overall_stats->total_properties); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Active Team Members</div>
        <div class="stat-value"><?php echo number_format($overall_stats->total_persons); ?></div>
    </div>
</div>
<!-- Performance Chart -->
<h3 style="margin: 40px 0 20px 0; color: #333; font-size: 20px;">Performance Overview</h3>
<div style="background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 30px;">
    <canvas id="performanceChart" style="max-height: 400px;"></canvas>
</div>

<!-- Reports by Person in Charge -->
<h3 style="margin: 30px 0 20px 0; color: #333; font-size: 20px;">Reports by Person in Charge</h3>
<table class="partnership-table">
    <thead>
        <tr>
            <th>Person in Charge</th>
            <th>Total Partners</th>
            <th>Signed</th>
            <th>Preparing</th>
            <th>Pending Signature</th>
            <th>Draft Contract Sent</th>
            <th>Declined</th>
            <th>Total Properties</th>
            <th>Completed Uploads</th>
            <th>Ongoing Uploads</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($reports)): ?>
            <tr>
                <td colspan="10" style="text-align: center; padding: 40px;">
                    No data available yet. Start by assigning partners to team members.
                </td>
            </tr>
        <?php else: ?>
            <?php foreach($reports as $report): ?>
                <tr>
                    <td><strong><?php echo esc_html($report->person_in_charge); ?></strong></td>
                    <td><strong><?php echo number_format($report->total_partners); ?></strong></td>
                    <td>
                        <span class="status-badge status-signed">
                            <?php echo number_format($report->signed_partners); ?>
                        </span>
                    </td>
                    <td>
                        <span class="status-badge status-preparing">
                            <?php echo number_format($report->preparing_partners); ?>
                        </span>
                    </td>
                    <td>
                        <span class="status-badge status-pending">
                            <?php echo number_format($report->pending_signature); ?>
                        </span>
                    </td>
                    <td>
                        <span class="status-badge status-draft">
                            <?php echo number_format($report->draft_contract); ?>
                        </span>
                    </td>
                    <td>
                        <span class="status-badge status-declined">
                            <?php echo number_format($report->declined_partners); ?>
                        </span>
                    </td>
                    <td><strong><?php echo number_format($report->total_properties); ?></strong></td>
                    <td><?php echo number_format($report->completed_uploads); ?></td>
                    <td><?php echo number_format($report->ongoing_uploads); ?></td>
                </tr>
            <?php endforeach; ?>
            
            <!-- Total Row -->
            <tr style="background: #f8f9fa; font-weight: bold;">
                <td>TOTAL</td>
                <td><?php echo number_format(array_sum(array_column($reports, 'total_partners'))); ?></td>
                <td><?php echo number_format(array_sum(array_column($reports, 'signed_partners'))); ?></td>
                <td><?php echo number_format(array_sum(array_column($reports, 'preparing_partners'))); ?></td>
                <td><?php echo number_format(array_sum(array_column($reports, 'pending_signature'))); ?></td>
                <td><?php echo number_format(array_sum(array_column($reports, 'draft_contract'))); ?></td>
                <td><?php echo number_format(array_sum(array_column($reports, 'declined_partners'))); ?></td>
                <td><?php echo number_format(array_sum(array_column($reports, 'total_properties'))); ?></td>
                <td><?php echo number_format(array_sum(array_column($reports, 'completed_uploads'))); ?></td>
                <td><?php echo number_format(array_sum(array_column($reports, 'ongoing_uploads'))); ?></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Industry Statistics -->
<?php if (!empty($industry_stats)): ?>
<h3 style="margin: 40px 0 20px 0; color: #333; font-size: 20px;">Distribution by Industry</h3>
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <?php foreach($industry_stats as $industry): ?>
        <div style="background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px;">
            <h4 style="margin: 0 0 15px 0; color: #333; font-size: 18px;">
                <?php echo esc_html($industry->industry); ?>
            </h4>
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                <span style="color: #666;">Partners:</span>
                <strong style="font-size: 18px; color: var(--e-global-color-primary);">
                    <?php echo number_format($industry->total_partners); ?>
                </strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: #666;">Properties:</span>
                <strong style="font-size: 18px; color: #28a745;">
                    <?php echo number_format($industry->total_properties); ?>
                </strong>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>



<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('performanceChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($reports, 'person_in_charge')); ?>,
                datasets: [
                    {
                        label: 'Total Partners',
                        data: <?php echo json_encode(array_column($reports, 'total_partners')); ?>,
                        backgroundColor: 'rgba(102, 126, 234, 0.8)',
                        borderColor: 'rgba(102, 126, 234, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Signed Agreements',
                        data: <?php echo json_encode(array_column($reports, 'signed_partners')); ?>,
                        backgroundColor: 'rgba(212, 237, 218, 1)',
                        borderColor: 'rgba(21, 87, 36, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Preparing',
                        data: <?php echo json_encode(array_column($reports, 'preparing_partners')); ?>,
                        backgroundColor: 'rgba(209, 236, 241, 1)',
                        borderColor: 'rgba(12, 84, 96, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Pending Signature',
                        data: <?php echo json_encode(array_column($reports, 'pending_signature')); ?>,
                        backgroundColor: 'rgba(255, 243, 205, 1)',
                        borderColor: 'rgba(133, 100, 4, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Draft Contract Sent',
                        data: <?php echo json_encode(array_column($reports, 'draft_contract')); ?>,
                        backgroundColor: 'rgba(241, 241, 241, 1)',
                        borderColor: 'rgba(110, 110, 110, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Declined',
                        data: <?php echo json_encode(array_column($reports, 'declined_partners')); ?>,
                        backgroundColor: 'rgba(248, 215, 218, 1)',
                        borderColor: 'rgba(114, 28, 36, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Partnership Status by Team Member',
                        font: {
                            size: 16,
                            weight: 'bold'
                        }
                    }
                }
            }
        });
    }
});
</script>

<style>
@media print {
    .partnership-tabs,
    .btn,
    .dashboard-side-wrap,
    header.header-main-wrap { 
        display: none !important;
    }
    
    .partnership-container {
        box-shadow: none !important;
    }
    
    table {
        font-size: 10px !important;
    }
    
    .stat-card {
        break-inside: avoid;
    }
}
</style>