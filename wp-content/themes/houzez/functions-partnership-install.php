<?php
/**
 * Partnership Management - Database Installation
 * Place this file in your theme directory and run it once to create the database tables
 * File: functions-partnership-install.php
 * 
 * Add to your theme's functions.php:
 * require_once get_template_directory() . '/functions-partnership-install.php';
 */

// Run installation on theme activation
add_action('after_switch_theme', 'partnership_management_install');

function partnership_management_install() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Table for partnerships
    $table_partnerships = $wpdb->prefix . 'partnerships';
    $sql_partnerships = "CREATE TABLE IF NOT EXISTS $table_partnerships (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        company_name varchar(255) NOT NULL,
        commission_rate varchar(50) DEFAULT NULL,
        agreement_status varchar(50) DEFAULT NULL,
        date_signed date DEFAULT NULL,
        date_expiration date DEFAULT NULL,
        industry varchar(100) DEFAULT NULL,
        country varchar(100) DEFAULT NULL,
        website varchar(255) DEFAULT NULL,
        xml_links text DEFAULT NULL,
        manner_upload varchar(50) DEFAULT 'TBD',
        property_upload_status varchar(50) DEFAULT NULL,
        total_properties int(11) DEFAULT 0,
        added_by bigint(20) DEFAULT NULL,
        person_in_charge varchar(100) DEFAULT NULL,
        contact_person varchar(255) DEFAULT NULL,
        mobile varchar(50) DEFAULT NULL,
        email varchar(255) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_company_name (company_name),
        KEY idx_agreement_status (agreement_status),
        KEY idx_person_in_charge (person_in_charge),
        KEY idx_industry (industry),
        KEY idx_country (country)
    ) $charset_collate;";
    
    // Table for partnership comments
    $table_comments = $wpdb->prefix . 'partnership_comments';
    $sql_comments = "CREATE TABLE IF NOT EXISTS $table_comments (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        partnership_id bigint(20) NOT NULL,
        user_id bigint(20) NOT NULL,
        comment text NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_partnership_id (partnership_id),
        KEY idx_user_id (user_id),
        FOREIGN KEY (partnership_id) REFERENCES $table_partnerships(id) ON DELETE CASCADE
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_partnerships);
    dbDelta($sql_comments);
    
    // Insert sample data (optional - remove if not needed)
    partnership_insert_sample_data();
    
    // Create default dropdown options
    partnership_create_default_options();
    
    // Set version
    update_option('partnership_management_db_version', '1.0');
}

function partnership_insert_sample_data() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'partnerships';
    
    // Check if table is empty
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    if ($count > 0) {
        return; // Already has data
    }
    
    // Sample partnerships
    $sample_data = array(
        array(
            'company_name' => 'ABC Real Estate Development',
            'commission_rate' => '5%',
            'agreement_status' => 'Signed',
            'date_signed' => '2025-01-15',
            'date_expiration' => '2026-01-15',
            'industry' => 'Developer',
            'website' => 'https://example-abc.com',
            'manner_upload' => 'XML Feed',
            'property_upload_status' => 'Completed',
            'total_properties' => 45,
            'person_in_charge' => 'Aya Piad',
            'contact_person' => 'John Smith',
            'mobile' => '+1234567890',
            'email' => 'john@example-abc.com',
            'added_by' => 1,
            'created_at' => current_time('mysql')
        ),
        array(
            'company_name' => 'Premier Property Agency',
            'commission_rate' => '7%',
            'agreement_status' => 'Pending Signature',
            'date_signed' => null,
            'date_expiration' => null,
            'industry' => 'Real Estate Agency',
            'website' => 'https://example-premier.com',
            'manner_upload' => 'Manual',
            'property_upload_status' => 'Ongoing',
            'total_properties' => 12,
            'person_in_charge' => 'Elly Herriman',
            'contact_person' => 'Sarah Johnson',
            'mobile' => '+9876543210',
            'email' => 'sarah@example-premier.com',
            'added_by' => 1,
            'created_at' => current_time('mysql')
        ),
        array(
            'company_name' => 'Global Investments Corp',
            'commission_rate' => '10%',
            'agreement_status' => 'Preparing',
            'date_signed' => null,
            'date_expiration' => null,
            'industry' => 'Individual Owner',
            'website' => 'https://example-global.com',
            'manner_upload' => 'TBD',
            'property_upload_status' => null,
            'total_properties' => 0,
            'person_in_charge' => 'Philip Clarke',
            'contact_person' => 'Mike Brown',
            'mobile' => '+5551234567',
            'email' => 'mike@example-global.com',
            'added_by' => 1,
            'created_at' => current_time('mysql')
        )
    );
    
    foreach ($sample_data as $data) {
        $wpdb->insert($table_name, $data);
    }
}

function partnership_create_default_options() {
    // Store default options for dropdown fields
    $default_options = array(
        'partnership_field_agreement_status' => "None\nSigned\nPreparing\nPending\nPending Signature\nDeclined",
        'partnership_field_industry' => "None\nDeveloper\nReal Estate Agency\nIndividual Owner\nIndividual Broker\nIndividual Agent\nCurrency/Broker",
        'partnership_field_manner_upload' => "TBD\nManual\nXML Feed\nWeb Scrape",
        'partnership_field_property_upload_status' => "None\nOngoing\nCompleted",
        'partnership_field_person_in_charge' => "Aya Piad\nElly Herriman\nPhilip Clarke"
    );
    
    foreach ($default_options as $key => $value) {
        // Only add if not already exists (don't overwrite existing customizations)
        if (false === get_option($key)) {
            add_option($key, $value, '', 'no');
        }
    }
}

// Uninstall function (optional - only use if you want to completely remove everything)
function partnership_management_uninstall() {
    global $wpdb;
    
    // Drop tables
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}partnership_comments");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}partnerships");
    
    // Delete options
    delete_option('partnership_management_db_version');
    delete_option('partnership_field_agreement_status');
    delete_option('partnership_field_industry');
    delete_option('partnership_field_manner_upload');
    delete_option('partnership_field_property_upload_status');
    delete_option('partnership_field_person_in_charge');
}

// Check and update database if needed
add_action('admin_init', 'partnership_check_db_version');
function partnership_check_db_version() {
    $current_version = get_option('partnership_management_db_version', '0');
    if (version_compare($current_version, '1.0', '<')) {
        partnership_management_install();
    }
}
?>