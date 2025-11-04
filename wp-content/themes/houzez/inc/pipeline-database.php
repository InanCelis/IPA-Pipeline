<?php
/**
 * Pipeline Database Schema
 * Creates all necessary tables for the sales pipeline system
 */

function create_pipeline_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // 1. LEADS TABLE
    $table_leads = $wpdb->prefix . 'pipeline_leads';
    $sql_leads = "CREATE TABLE IF NOT EXISTS $table_leads (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        fullname varchar(255) NOT NULL,
        firstname varchar(255) DEFAULT NULL,
        lastname varchar(255) DEFAULT NULL,
        email varchar(255) DEFAULT NULL,
        contact_number varchar(50) DEFAULT NULL,
        property_url text DEFAULT NULL,
        date_inquiry datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        lead_source varchar(100) DEFAULT NULL,
        status varchar(50) NOT NULL DEFAULT 'New Lead',
        assigned_to varchar(255) DEFAULT NULL,
        partners text DEFAULT NULL COMMENT 'JSON array of partnership IDs',
        tags text DEFAULT NULL COMMENT 'JSON array of tags',
        message text DEFAULT NULL,
        last_update datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        is_active tinyint(1) DEFAULT 1 COMMENT 'For soft delete and cold leads',
        is_cold_lead tinyint(1) DEFAULT 0,
        created_by bigint(20) DEFAULT NULL,
        deleted_at datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY status (status),
        KEY assigned_to (assigned_to),
        KEY is_active (is_active),
        KEY is_cold_lead (is_cold_lead),
        KEY date_inquiry (date_inquiry)
    ) $charset_collate;";
    dbDelta($sql_leads);

    // Add missing columns if they don't exist (for existing installations)
    $columns_to_check = array(
        'property_url' => "ALTER TABLE $table_leads ADD COLUMN property_url text DEFAULT NULL AFTER contact_number",
        'tags' => "ALTER TABLE $table_leads ADD COLUMN tags text DEFAULT NULL COMMENT 'JSON array of tags' AFTER partners",
        'message' => "ALTER TABLE $table_leads ADD COLUMN message text DEFAULT NULL AFTER tags"
    );

    foreach ($columns_to_check as $column => $sql) {
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_leads LIKE '$column'");
        if (empty($column_exists)) {
            $wpdb->query($sql);
        }
    }

    // 2. DEALS TABLE
    $table_deals = $wpdb->prefix . 'pipeline_deals';
    $sql_deals = "CREATE TABLE IF NOT EXISTS $table_deals (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        lead_id bigint(20) NOT NULL,
        property_type text DEFAULT NULL COMMENT 'JSON array',
        country text DEFAULT NULL COMMENT 'JSON array of term IDs',
        city text DEFAULT NULL COMMENT 'JSON array of term IDs',
        num_rooms int DEFAULT NULL,
        area_size varchar(100) DEFAULT NULL,
        num_bathrooms int DEFAULT NULL,
        budget_amount decimal(15,2) DEFAULT NULL,
        budget_payment_method varchar(100) DEFAULT NULL,
        purpose_of_purchase text DEFAULT NULL COMMENT 'JSON array',
        timeline_urgency varchar(100) DEFAULT NULL,
        move_in_target varchar(255) DEFAULT NULL,
        stage_buying_process varchar(100) DEFAULT NULL,
        deal_status varchar(50) DEFAULT 'N/A',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        is_active tinyint(1) DEFAULT 1,
        deleted_at datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY lead_id (lead_id),
        KEY deal_status (deal_status),
        KEY is_active (is_active)
    ) $charset_collate;";
    dbDelta($sql_deals);

    // 3. INVOICES TABLE
    $table_invoices = $wpdb->prefix . 'pipeline_invoices';
    $sql_invoices = "CREATE TABLE IF NOT EXISTS $table_invoices (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        lead_id bigint(20) NOT NULL,
        deal_id bigint(20) DEFAULT NULL,
        partnership_id bigint(20) DEFAULT NULL,
        invoice_number varchar(50) NOT NULL UNIQUE,
        date_issued date NOT NULL,
        due_date date NOT NULL,
        billed_to_name varchar(255) DEFAULT NULL,
        billed_to_position varchar(255) DEFAULT NULL,
        billed_to_company varchar(255) DEFAULT NULL,
        transaction_details text DEFAULT NULL,
        description text DEFAULT NULL,
        sale_price decimal(15,2) DEFAULT 0,
        commission_rate decimal(5,2) DEFAULT 0,
        referral_fee_amount decimal(15,2) DEFAULT 0,
        property_url text DEFAULT NULL,
        payment_status varchar(50) DEFAULT 'Pending',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        is_active tinyint(1) DEFAULT 1,
        deleted_at datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY lead_id (lead_id),
        KEY partnership_id (partnership_id),
        KEY payment_status (payment_status),
        KEY invoice_number (invoice_number),
        KEY is_active (is_active)
    ) $charset_collate;";
    dbDelta($sql_invoices);

    // 4. COMMENTS TABLE (for tracking progress)
    $table_comments = $wpdb->prefix . 'pipeline_comments';
    $sql_comments = "CREATE TABLE IF NOT EXISTS $table_comments (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        entity_type varchar(20) NOT NULL COMMENT 'lead, deal, or invoice',
        entity_id bigint(20) NOT NULL,
        comment text NOT NULL,
        created_by bigint(20) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        is_active tinyint(1) DEFAULT 1,
        deleted_at datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY entity_type (entity_type),
        KEY entity_id (entity_id),
        KEY created_by (created_by)
    ) $charset_collate;";
    dbDelta($sql_comments);

    // 5. FIELD MANAGEMENT TABLE
    $table_fields = $wpdb->prefix . 'pipeline_field_options';
    $sql_fields = "CREATE TABLE IF NOT EXISTS $table_fields (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        field_name varchar(100) NOT NULL,
        field_label varchar(255) NOT NULL,
        options text NOT NULL COMMENT 'JSON array of options',
        is_active tinyint(1) DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY field_name (field_name)
    ) $charset_collate;";
    dbDelta($sql_fields);

    // 6. WHITELISTED USERS TABLE
    $table_whitelist = $wpdb->prefix . 'pipeline_whitelist';
    $sql_whitelist = "CREATE TABLE IF NOT EXISTS $table_whitelist (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        email varchar(255) NOT NULL,
        permissions text DEFAULT NULL COMMENT 'JSON array of permissions',
        is_active tinyint(1) DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY user_id (user_id),
        KEY email (email)
    ) $charset_collate;";
    dbDelta($sql_whitelist);

    // Insert default field options
    insert_default_field_options();
}

function insert_default_field_options() {
    global $wpdb;
    $table = $wpdb->prefix . 'pipeline_field_options';

    $default_fields = array(
        array(
            'field_name' => 'lead_source',
            'field_label' => 'Lead Source',
            'options' => json_encode(array('Website', 'Portal', 'Referral', 'Campaign', 'Social Media', 'Walk-in'))
        ),
        array(
            'field_name' => 'property_type',
            'field_label' => 'Property Type',
            'options' => json_encode(array('Apartment/Condominium', 'Villa', 'Townhouse', 'Plot', 'Hotel', 'Resort'))
        ),
        array(
            'field_name' => 'budget_payment_method',
            'field_label' => 'Budget Payment Method',
            'options' => json_encode(array('Cash', 'Mortgage', 'Installment', 'Mixed'))
        ),
        array(
            'field_name' => 'purpose_of_purchase',
            'field_label' => 'Purpose of Purchase',
            'options' => json_encode(array('Holiday', 'Investment', 'End-use / Personal Use'))
        ),
        array(
            'field_name' => 'timeline_urgency',
            'field_label' => 'Timeline & Urgency',
            'options' => json_encode(array('Immediately / ASAP', 'in 3 months', 'in 6 months', 'in 12 months', 'Not decided'))
        ),
        array(
            'field_name' => 'stage_buying_process',
            'field_label' => 'Stage in Buying Process',
            'options' => json_encode(array('Exploring / Canvassing', 'Shortlisted Options', 'Ready to Decide'))
        )
    );

    foreach ($default_fields as $field) {
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE field_name = %s",
            $field['field_name']
        ));

        if (!$exists) {
            $wpdb->insert($table, $field);
        }
    }
}

// Hook to create tables on theme activation
add_action('after_setup_theme', 'create_pipeline_tables');

// Function to generate invoice number
function generate_invoice_number() {
    global $wpdb;
    $table = $wpdb->prefix . 'pipeline_invoices';

    $year = date('Y');
    $month = date('m');

    $prefix = "INV-{$year}-{$month}-IPA-";

    // Get last invoice number for this month
    $last_invoice = $wpdb->get_var($wpdb->prepare(
        "SELECT invoice_number FROM $table
        WHERE invoice_number LIKE %s
        ORDER BY id DESC LIMIT 1",
        $prefix . '%'
    ));

    if ($last_invoice) {
        $last_number = intval(str_replace($prefix, '', $last_invoice));
        $new_number = $last_number + 1;
    } else {
        $new_number = 1;
    }

    return $prefix . str_pad($new_number, 5, '0', STR_PAD_LEFT);
}

// Function to check if user has pipeline access
function user_has_pipeline_access($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    // Admin always has access
    if (user_can($user_id, 'administrator')) {
        return true;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'pipeline_whitelist';

    $has_access = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table WHERE user_id = %d AND is_active = 1",
        $user_id
    ));

    return (bool) $has_access;
}

// Function to automatically update invoice status to overdue
function update_overdue_invoices() {
    global $wpdb;
    $table = $wpdb->prefix . 'pipeline_invoices';

    $wpdb->query("
        UPDATE $table
        SET payment_status = 'Overdue'
        WHERE due_date < CURDATE()
        AND payment_status NOT IN ('Fully Paid', 'Overdue')
        AND is_active = 1
    ");
}
add_action('init', 'update_overdue_invoices');
