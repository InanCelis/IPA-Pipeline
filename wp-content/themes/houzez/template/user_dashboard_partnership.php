<?php
/**
 * Template Name: User Dashboard Partnership Agreement
 * Created for partnership management system
 */

global $houzez_local, $current_user, $wpdb;
wp_get_current_user();
$userID = $current_user->ID;

// Check if user is admin or has permission
if (!current_user_can('administrator')) {
    wp_redirect(home_url());
    exit;
}

// Get current page
$hpage = isset($_GET['hpage']) ? sanitize_text_field($_GET['hpage']) : 'partners';

get_header();
?>

<style>
    header.elementor.elementor-17380.elementor-location-header, 
    footer.elementor.elementor-17737.elementor-location-footer {
        display: none;
    }
    
    .partnership-container {
        background: #fff;
        border-radius: 8px;
        padding: 0;
        margin-bottom: 30px;
    }
    
    .partnership-tabs {
        display: flex;
        border-bottom: 2px solid #e0e0e0;
        padding: 0;
        margin: 0;
        list-style: none;
        background: #f8f9fa;
    }
    
    .partnership-tabs li {
        margin: 0;
    }
    
    .partnership-tabs a {
        display: block;
        padding: 15px 30px;
        text-decoration: none;
        color: #666;
        font-weight: 600;
        border-bottom: 3px solid transparent;
        transition: all 0.3s ease;
    }
    
    .partnership-tabs a:hover {
        background: #fff;
        color: var(--e-global-color-primary);
    }
    
    .partnership-tabs a.active {
        color: var(--e-global-color-primary);
        border-bottom-color: var(--e-global-color-primary);
        background: #fff;
    }
    
    .partnership-content {
        padding: 30px;
    }
    
    .partnership-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .partnership-title {
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
    
    .partnership-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    
    .partnership-table th {
        background: #f8f9fa;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        color: #333;
        border-bottom: 2px solid #dee2e6;
        font-size: 14px;
    }
    
    .partnership-table td {
        padding: 12px;
        border-bottom: 1px solid #eee;
        vertical-align: middle;
        font-size: 14px;
    }
    
    .partnership-table tbody tr:hover {
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
    
    .status-signed {
        background: #d4edda;
        color: #155724;
    }
    
    .status-pending, .status-pending-signature {
        background: #fff3cd;
        color: #856404;
    }
    
    .status-preparing {
        background: #d1ecf1;
        color: #0c5460;
    }
    
    .status-declined, .status-none, .status-partner-declined {
        background: #f8d7da;
        color: #721c24;
    }


    .status-draft-contract-sent, .status-draft {
        background: #f1f1f1;
        color: #242424ff;
    }

    .status-upload-completed {
        background: #e7ffec;
    }
    
    .action-buttons {
        display: flex;
        gap: 8px;
    }
    
    .btn-sm {
        padding: 0px 10px;
        font-size: 10px;
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
        max-width: 800px;
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
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
    
    .stat-label {
        font-size: 14px;
        opacity: 0.9;
        margin-bottom: 10px;
    }
    
    .stat-value {
        font-size: 32px;
        font-weight: bold;
    }
    
    .field-item {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        padding: 15px;
        margin-bottom: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .field-info h4 {
        margin: 0 0 5px 0;
        color: #333;
        font-size: 16px;
    }
    
    .field-info p {
        margin: 0;
        color: #666;
        font-size: 13px;
    }
    
    .field-options {
        background: #f8f9fa;
        padding: 10px;
        border-radius: 4px;
        margin-top: 10px;
    }
    
    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
        
        .filter-row {
            flex-direction: column;
        }
        
        .partnership-table {
            font-size: 12px;
        }
        
        .partnership-table th,
        .partnership-table td {
            padding: 8px;
        }
    }
</style>

<header class="header-main-wrap dashboard-header-main-wrap">
    <div class="dashboard-header-wrap">
        <div class="d-flex align-items-center">
            <div class="dashboard-header-left flex-grow-1">
                <h1>Partnership Agreement Management</h1>
            </div>
        </div>
    </div>
</header>

<section class="dashboard-content-wrap">
    <div class="dashboard-content-inner-wrap">
        <div class="partnership-container">
            <ul class="partnership-tabs">
                <li>
                    <a href="?hpage=partners" class="<?php echo $hpage == 'partners' ? 'active' : ''; ?>">
                        Partners List
                    </a>
                </li>
                <li>
                    <a href="?hpage=reports" class="<?php echo $hpage == 'reports' ? 'active' : ''; ?>">
                        Tally Reports
                    </a>
                </li>
                <li>
                    <a href="?hpage=fields" class="<?php echo $hpage == 'fields' ? 'active' : ''; ?>">
                        Field Management
                    </a>
                </li>
            </ul>
            
            <div class="partnership-content">
                <?php
                // Include the appropriate sub-page
                switch($hpage) {
                    case 'reports':
                        include(locate_template('template-parts/partnership/tally-reports.php'));
                        break;
                    case 'fields':
                        include(locate_template('template-parts/partnership/field-management.php'));
                        break;
                    case 'partners':
                    default:
                        include(locate_template('template-parts/partnership/partners-list.php'));
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