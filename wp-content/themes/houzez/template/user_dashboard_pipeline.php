<?php
/**
 * Template Name: User Dashboard Pipeline
 * Created for real estate sales pipeline management system
 */

global $houzez_local, $current_user, $wpdb;
wp_get_current_user();
$userID = $current_user->ID;

// Check if user has pipeline access
if (!user_has_pipeline_access($userID)) {
    wp_redirect(home_url());
    exit;
}

// Get current page
$hpage = isset($_GET['hpage']) ? sanitize_text_field($_GET['hpage']) : 'leads';

get_header();
?>

<style>
    header.elementor.elementor-17380.elementor-location-header,
    footer.elementor.elementor-17737.elementor-location-footer {
        display: none;
    }

    .pipeline-container {
        background: #fff;
        border-radius: 8px;
        padding: 0;
        margin-bottom: 30px;
    }

    .pipeline-tabs {
        display: flex;
        border-bottom: 2px solid #e0e0e0;
        padding: 0;
        margin: 0;
        list-style: none;
        background: #f8f9fa;
        flex-wrap: wrap;
    }

    .pipeline-tabs li {
        margin: 0;
    }

    .pipeline-tabs a {
        display: block;
        padding: 15px 25px;
        text-decoration: none;
        color: #666;
        font-weight: 600;
        border-bottom: 3px solid transparent;
        transition: all 0.3s ease;
    }

    .pipeline-tabs a:hover {
        background: #fff;
        color: var(--e-global-color-primary);
    }

    .pipeline-tabs a.active {
        color: var(--e-global-color-primary);
        border-bottom-color: var(--e-global-color-primary);
        background: #fff;
    }

    .pipeline-content {
        padding: 30px;
    }

    .pipeline-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }

    .pipeline-title {
        font-size: 24px;
        font-weight: bold;
        color: #333;
        margin: 0;
    }

    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
    }

    .btn-primary {
        background: var(--e-global-color-primary);
        color: white;
    }

    .btn-primary:hover {
        background: #0056b3;
        color: white;
    }

    .btn-success {
        background: #28a745;
        color: white;
    }

    .btn-success:hover {
        background: #218838;
    }

    .btn-danger {
        background: #dc3545;
        color: white;
    }

    .btn-danger:hover {
        background: #c82333;
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background: #5a6268;
    }

    .btn-info {
        background: #17a2b8;
        color: white;
    }

    .btn-info:hover {
        background: #138496;
    }

    .filter-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 6px;
        margin-bottom: 25px;
    }

    .filter-row {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        align-items: flex-end;
    }

    .filter-group {
        flex: 1;
        min-width: 200px;
    }

    .filter-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #333;
        font-size: 14px;
    }

    .filter-select,
    .filter-input {
        width: 100%;
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        background-color: white;
    }

    .pipeline-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .pipeline-table th {
        background: #f8f9fa;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        color: #333;
        border-bottom: 2px solid #dee2e6;
        font-size: 14px;
    }

    .pipeline-table td {
        padding: 12px;
        border-bottom: 1px solid #eee;
        vertical-align: middle;
        font-size: 14px;
    }

    .pipeline-table tbody tr:hover {
        background-color: #f5f5f5;
    }

    .status-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        display: inline-block;
    }

    .status-new-lead {
        background: #d1ecf1;
        color: #0c5460;
    }

    .status-qualifying {
        background: #fff3cd;
        color: #856404;
    }

    .status-wrong-contact {
        background: #f8d7da;
        color: #721c24;
    }

    .status-qualified {
        background: #d4edda;
        color: #155724;
    }

    .status-cold-lead {
        background: #e2e3e5;
        color: #383d41;
    }

    .status-na {
        background: #f1f1f1;
        color: #333;
    }

    .status-options-sent {
        background: #cce5ff;
        color: #004085;
    }

    .status-site-visit {
        background: #d6eaff;
        color: #003d7a;
    }

    .status-preparing-options {
        background: #fff4cc;
        color: #856404;
    }

    .status-negotiation-and-documentation {
        background: #ffe5b4;
        color: #856404;
    }

    .status-for-payment {
        background: #d4edda;
        color: #155724;
    }

    .status-sent {
        background: #cce5ff;
        color: #004085;
    }

    .status-fully-paid {
        background: #d4edda;
        color: #155724;
    }

    .status-partial {
        background: #fff3cd;
        color: #856404;
    }

    .status-overdue {
        background: #f8d7da;
        color: #721c24;
    }

    .status-pending {
        background: #e2e3e5;
        color: #383d41;
    }

    .action-buttons {
        display: flex;
        gap: 8px;
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        overflow-y: auto;
    }

    .modal-content {
        background-color: #fff;
        margin: 50px auto;
        padding: 0;
        border-radius: 8px;
        width: 90%;
        max-width: 900px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }

    .modal-header {
        padding: 20px 30px;
        border-bottom: 1px solid #dee2e6;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-title {
        font-size: 20px;
        font-weight: bold;
        margin: 0;
    }

    .close {
        font-size: 28px;
        font-weight: bold;
        color: #aaa;
        cursor: pointer;
        background: none;
        border: none;
        padding: 0;
        line-height: 1;
    }

    .close:hover {
        color: #000;
    }

    .modal-body {
        padding: 30px;
        max-height: 70vh;
        overflow-y: auto;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group.full-width {
        grid-column: 1 / -1;
    }

    .form-group label {
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
        font-size: 14px;
    }

    .form-group label.required::after {
        content: ' *';
        color: #dc3545;
    }

    .form-control {
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--e-global-color-primary);
        box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
    }

    textarea.form-control {
        resize: vertical;
        min-height: 100px;
    }

    .modal-footer {
        padding: 20px 30px;
        border-top: 1px solid #dee2e6;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 25px;
        border-radius: 8px;
        color: white;
    }

    .stat-card:nth-child(2) {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .stat-card:nth-child(3) {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .stat-card:nth-child(4) {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    }

    .stat-card:nth-child(5) {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    }

    .stat-card:nth-child(6) {
        background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
    }

    .stat-label {
        font-size: 14px;
        opacity: 0.9;
        margin-bottom: 10px;
    }

    .stat-value {
        font-size: 32px;
        font-weight: bold;
    }

    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        margin-top: 20px;
        padding: 20px 0;
    }

    .pagination button {
        padding: 8px 15px;
        border: 1px solid #ddd;
        background: white;
        cursor: pointer;
        border-radius: 4px;
    }

    .pagination button:hover {
        background: var(--e-global-color-primary);
        color: white;
        border-color: var(--e-global-color-primary);
    }

    .pagination button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .pagination .page-info {
        padding: 8px 15px;
        color: #666;
    }

    .comments-section {
        margin-top: 20px;
        border-top: 1px solid #eee;
        padding-top: 20px;
    }

    .comment-item {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 10px;
    }

    .comment-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }

    .comment-author {
        font-weight: 600;
        color: #333;
    }

    .comment-date {
        color: #999;
        font-size: 12px;
    }

    .comment-text {
        color: #666;
    }

    .comment-form {
        margin-top: 15px;
    }

    .select2-container {
        width: 100% !important;
    }

    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }

        .filter-row {
            flex-direction: column;
        }

        .pipeline-table {
            font-size: 12px;
        }

        .pipeline-table th,
        .pipeline-table td {
            padding: 8px;
        }

        .pipeline-tabs {
            overflow-x: auto;
        }
    }

    .alert {
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .alert-info {
        background: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }

    .loading {
        opacity: 0.6;
        pointer-events: none;
    }

    .spinner {
        border: 3px solid #f3f3f3;
        border-top: 3px solid var(--e-global-color-primary);
        border-radius: 50%;
        width: 30px;
        height: 30px;
        animation: spin 1s linear infinite;
        display: inline-block;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .no-results {
        text-align: center;
        padding: 40px;
        color: #999;
    }

    .field-item {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        padding: 15px;
        margin-bottom: 15px;
    }

    .field-info h4 {
        margin: 0 0 10px 0;
        color: #333;
        font-size: 16px;
    }

    .field-options {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 4px;
        margin-top: 10px;
    }

    .option-list {
        list-style: none;
        padding: 0;
        margin: 10px 0;
    }

    .option-list li {
        padding: 8px;
        background: white;
        margin-bottom: 5px;
        border-radius: 3px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .chart-container {
        background: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
</style>

<header class="header-main-wrap dashboard-header-main-wrap">
    <div class="dashboard-header-wrap">
        <div class="d-flex align-items-center">
            <div class="dashboard-header-left flex-grow-1">
                <h1>Real Estate Sales Pipeline</h1>
            </div>
        </div>
    </div>
</header>

<section class="dashboard-content-wrap">
    <div class="dashboard-content-inner-wrap">
        <div class="pipeline-container">
            <ul class="pipeline-tabs">
                <li>
                    <a href="?hpage=leads" class="<?php echo $hpage == 'leads' ? 'active' : ''; ?>">
                        <i class="houzez-icon icon-single-neutral mr-1"></i> Leads
                    </a>
                </li>
                <li>
                    <a href="?hpage=deals" class="<?php echo $hpage == 'deals' ? 'active' : ''; ?>">
                        <i class="houzez-icon icon-task-list-text-1 mr-1"></i> Deals
                    </a>
                </li>
                <li>
                    <a href="?hpage=invoices" class="<?php echo $hpage == 'invoices' ? 'active' : ''; ?>">
                        <i class="houzez-icon icon-accounting-document mr-1"></i> Invoice & Documentation
                    </a>
                </li>
                <li>
                    <a href="?hpage=reports" class="<?php echo $hpage == 'reports' ? 'active' : ''; ?>">
                        <i class="houzez-icon icon-analytics-bars mr-1"></i> Reports
                    </a>
                </li>
                <li>
                    <a href="?hpage=fields" class="<?php echo $hpage == 'fields' ? 'active' : ''; ?>">
                        <i class="houzez-icon icon-cog-1 mr-1"></i> Field Management
                    </a>
                </li>
                <li>
                    <a href="?hpage=whitelist" class="<?php echo $hpage == 'whitelist' ? 'active' : ''; ?>">
                        <i class="houzez-icon icon-lock-5 mr-1"></i> Whitelisted Users
                    </a>
                </li>
            </ul>

            <div class="pipeline-content">
                <?php
                // Include the appropriate sub-page
                switch($hpage) {
                    case 'deals':
                        include(locate_template('template-parts/pipeline/deals.php'));
                        break;
                    case 'invoices':
                        include(locate_template('template-parts/pipeline/invoices.php'));
                        break;
                    case 'reports':
                        include(locate_template('template-parts/pipeline/reports.php'));
                        break;
                    case 'fields':
                        include(locate_template('template-parts/pipeline/field-management.php'));
                        break;
                    case 'whitelist':
                        include(locate_template('template-parts/pipeline/whitelist.php'));
                        break;
                    case 'leads':
                    default:
                        include(locate_template('template-parts/pipeline/leads.php'));
                        break;
                }
                ?>
            </div>
        </div>
    </div>
</section>

<section class="dashboard-side-wrap">
    <?php get_template_part('template-parts/dashboard/side-wrap'); ?>
</section>

<?php get_footer(); ?>
