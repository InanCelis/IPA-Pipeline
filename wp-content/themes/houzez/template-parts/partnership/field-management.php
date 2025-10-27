<?php
/**
 * Field Management - Manage Dropdown Options Only
 * File: template-parts/partnership/field-management.php
 */

global $wpdb;

// Handle form submissions for updating dropdown options
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['field_action'])) {
    if ($_POST['field_action'] === 'update_options') {
        $field_name = sanitize_text_field($_POST['field_name']);
        $options = sanitize_textarea_field($_POST['field_options']);
        
        // Save to WordPress options
        $option_name = 'partnership_field_' . $field_name;
        update_option($option_name, $options);
        
        echo '<div class="notice notice-success" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #28a745;">
            <strong>Success!</strong> Dropdown options updated successfully.
        </div>';
    }
}

// Get current options from database
$agreement_status_options = get_option('partnership_field_agreement_status', "None\nSigned\nPreparing\nPending\nPending Signature\nDeclined");
$industry_options = get_option('partnership_field_industry', "None\nDeveloper\nReal Estate Agency\nIndividual Owner\nIndividual Broker\nIndividual Agent\nCurrency/Broker");
$manner_upload_options = get_option('partnership_field_manner_upload', "TBD\nManual\nXML Feed\nWeb Scrape");
$property_upload_status_options = get_option('partnership_field_property_upload_status', "None\nOngoing\nCompleted");
$person_in_charge_options = get_option('partnership_field_person_in_charge', "Aya Piad\nElly Herriman\nPhilip Clarke");
$country_options = get_option('partnership_field_country', "Philippines\nSpain\nCyprus");
$invoice_project_options = get_option('partnership_field_invoice_project', "Project A\nProject B\nProject C");
$invoice_package_tier_options = get_option('partnership_field_invoice_package_tier', "Basic\nStandard\nPremium\nEnterprise");
$invoice_project_duration_options = get_option('partnership_field_invoice_project_duration', "1 Month\n3 Months\n6 Months\n12 Months");

// Fixed fields with their current options
$fixed_fields = array(
    array(
        'name' => 'agreement_status',
        'label' => 'Agreement Status',
        'description' => 'Status of the partnership agreement',
        'options' => $agreement_status_options,
        'icon' => 'icon-task-list-text-1'
    ),
    array(
        'name' => 'industry',
        'label' => 'Industry',
        'description' => 'Type of business or industry sector',
        'options' => $industry_options,
        'icon' => 'icon-building-cloudy'
    ),
    array(
        'name' => 'country',
        'label' => 'Country',
        'description' => 'Country where the partner is located',
        'options' => $country_options,
        'icon' => 'icon-earth-1'
    ),
    array(
        'name' => 'manner_upload',
        'label' => 'Manner of Upload',
        'description' => 'How properties are uploaded to the system',
        'options' => $manner_upload_options,
        'icon' => 'icon-upload-button'
    ),
    array(
        'name' => 'property_upload_status',
        'label' => 'Property Upload Status',
        'description' => 'Current status of property upload process',
        'options' => $property_upload_status_options,
        'icon' => 'icon-house-nature'
    ),
    array(
        'name' => 'person_in_charge',
        'label' => 'Person in Charge',
        'description' => 'Team member responsible for this partnership',
        'options' => $person_in_charge_options,
        'icon' => 'icon-single-neutral'
    ),
    array(
        'name' => 'invoice_project',
        'label' => 'Invoice Project',
        'description' => 'Project name for partnership invoices',
        'options' => $invoice_project_options,
        'icon' => 'icon-folder-2'
    ),
    array(
        'name' => 'invoice_package_tier',
        'label' => 'Invoice Package Tier',
        'description' => 'Service package tier for partnership invoices',
        'options' => $invoice_package_tier_options,
        'icon' => 'icon-crown'
    ),
    array(
        'name' => 'invoice_project_duration',
        'label' => 'Invoice Project Duration',
        'description' => 'Project duration options for partnership invoices',
        'options' => $invoice_project_duration_options,
        'icon' => 'icon-clock-2'
    )
);
?>

<div class="partnership-header">
    <h2 class="partnership-title">Field Management</h2>
    <button class="btn btn-primary" onclick="window.location.reload()">
        <i class="houzez-icon icon-refresh-1"></i> Refresh
    </button>
</div>

<div style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 20px; border-radius: 4px; margin-bottom: 30px;">
    <div style="display: flex; align-items: start; gap: 15px;">
        <i class="houzez-icon icon-information-circle" style="font-size: 24px; color: #1976d2;"></i>
        <div>
            <strong style="color: #1976d2; font-size: 16px;">About Field Management</strong>
            <p style="margin: 10px 0 0 0; color: #424242; line-height: 1.6;">
                This section allows you to manage the dropdown options for partnership fields. The fields themselves are fixed based on your requirements, 
                but you can customize the available options for each dropdown field. Simply click "Edit Options" on any field to modify its dropdown values.
            </p>
        </div>
    </div>
</div>

<!-- Statistics -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 8px; color: white;">
        <div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;">Total Fields</div>
        <div style="font-size: 32px; font-weight: bold;"><?php echo count($fixed_fields); ?></div>
    </div>
    <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 20px; border-radius: 8px; color: white;">
        <div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;">Dropdown Fields</div>
        <div style="font-size: 32px; font-weight: bold;"><?php echo count($fixed_fields); ?></div>
    </div>
    <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); padding: 20px; border-radius: 8px; color: white;">
        <div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;">Total Options</div>
        <div style="font-size: 32px; font-weight: bold;">
            <?php 
            $total_options = 0;
            foreach($fixed_fields as $field) {
                $total_options += count(array_filter(explode("\n", $field['options'])));
            }
            echo $total_options;
            ?>
        </div>
    </div>
    <div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); padding: 20px; border-radius: 8px; color: white;">
        <div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;">Last Updated</div>
        <div style="font-size: 18px; font-weight: bold;">
            <?php echo date('M d, Y'); ?>
        </div>
    </div>
</div>

<h3 style="margin: 30px 0 20px 0; color: #333; font-size: 20px; display: flex; align-items: center;">
    <i class="houzez-icon icon-cog-1" style="margin-right: 10px;"></i> Partnership Dropdown Fields
</h3>
<p style="color: #666; margin-bottom: 25px;">Manage the options available in dropdown fields throughout the partnership and invoice system.</p>

<!-- Field List -->
<div style="display: grid; gap: 20px;">
    <?php foreach($fixed_fields as $field): ?>
        <div class="field-item" style="border: 2px solid #e0e0e0;">
            <div class="field-info" style="flex: 1;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
                    <i class="houzez-icon <?php echo $field['icon']; ?>" style="font-size: 28px; color: var(--e-global-color-primary);"></i>
                    <div>
                        <h4 style="margin: 0; font-size: 18px;"><?php echo esc_html($field['label']); ?></h4>
                        <p style="margin: 5px 0 0 0; color: #666; font-size: 13px;"><?php echo esc_html($field['description']); ?></p>
                    </div>
                </div>
                
                <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-top: 15px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <strong style="color: #333; font-size: 14px;">Current Options:</strong>
                        <span style="background: var(--e-global-color-primary); color: white; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: bold;">
                            <?php echo count(array_filter(explode("\n", $field['options']))); ?> options
                        </span>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        <?php 
                        $options = array_filter(explode("\n", $field['options']));
                        foreach($options as $option): 
                            if(trim($option)): ?>
                                <span style="display: inline-block; background: white; padding: 6px 14px; border-radius: 4px; font-size: 13px; border: 1px solid #ddd; font-weight: 500;">
                                    <?php echo esc_html(trim($option)); ?>
                                </span>
                            <?php endif;
                        endforeach; ?>
                    </div>
                </div>
            </div>
            <div style="display: flex; align-items: center;">
                <button class="btn btn-primary" style="padding: 12px 24px; font-size: 14px;" onclick='openEditModal(<?php echo json_encode($field); ?>)'>
                    <i class="houzez-icon icon-pencil"></i> Edit Options
                </button>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- All Partnership Fields Reference -->
<div style="background: #f8f9fa; border-radius: 8px; padding: 25px; margin-top: 40px; border: 1px solid #e0e0e0;">
    <h3 style="margin: 0 0 20px 0; color: #333; font-size: 18px; display: flex; align-items: center;">
        <i class="houzez-icon icon-task-list-text-1" style="margin-right: 10px;"></i> 
        All Partnership Fields Reference
    </h3>
    <p style="color: #666; margin-bottom: 20px;">Complete list of all 14 fields in the partnership system:</p>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">
        <div style="background: white; padding: 15px; border-radius: 6px; border-left: 4px solid #667eea;">
            <strong style="color: #333;">Text Fields (9):</strong>
            <ul style="margin: 10px 0 0 0; padding-left: 20px; color: #666; line-height: 1.8;">
                <li>Company Name</li>
                <li>Commission Rate</li>
                <li>Website</li>
                <li>XML Links</li>
                <li>Total Properties Uploaded</li>
                <li>Contact Person</li>
                <li>Mobile</li>
                <li>Email</li>
                <li>Added By (Auto)</li>
            </ul>
        </div>
        
        <div style="background: white; padding: 15px; border-radius: 6px; border-left: 4px solid #f093fb;">
            <strong style="color: #333;">Dropdown Fields (5):</strong>
            <ul style="margin: 10px 0 0 0; padding-left: 20px; color: #666; line-height: 1.8;">
                <li>Agreement Status</li>
                <li>Industry</li>
                <li>Manner of Upload</li>
                <li>Property Upload Status</li>
                <li>Person in Charge</li>
            </ul>
        </div>
        
        <div style="background: white; padding: 15px; border-radius: 6px; border-left: 4px solid #4facfe;">
            <strong style="color: #333;">Date Fields (2):</strong>
            <ul style="margin: 10px 0 0 0; padding-left: 20px; color: #666; line-height: 1.8;">
                <li>Date of Signed Agreement</li>
                <li>Date of Expiration</li>
            </ul>
        </div>
    </div>
</div>

<!-- Edit Options Modal -->
<div id="editModal" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Edit Dropdown Options</h3>
            <button class="close" onclick="closeEditModal()">&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="field_action" value="update_options">
            <input type="hidden" name="field_name" id="fieldName">
            
            <div class="modal-body">
                <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                    <strong style="color: #856404;">⚠️ Important:</strong>
                    <p style="margin: 8px 0 0 0; color: #856404; font-size: 14px; line-height: 1.6;">
                        Modifying these options will affect all partnership records. If you remove an option that's currently in use, 
                        existing records will keep their values but the option won't appear in new selections.
                    </p>
                </div>
                
                <div style="background: #e3f2fd; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                    <strong style="color: #1976d2;" id="fieldLabel"></strong>
                    <p style="margin: 5px 0 0 0; color: #424242; font-size: 14px;" id="fieldDescription"></p>
                </div>
                
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 15px; margin-bottom: 10px; display: block;">
                        Dropdown Options
                        <span style="color: #666; font-weight: normal; font-size: 13px;">(one per line)</span>
                    </label>
                    <textarea name="field_options" id="fieldOptions" class="form-control" rows="12" style="font-family: monospace; font-size: 14px; line-height: 1.8;"></textarea>
                    <small style="color: #666; display: block; margin-top: 8px;">
                        <strong>💡 Tip:</strong> Enter each option on a new line. The order here will be the order shown in dropdowns.
                    </small>
                </div>
                
                <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-top: 15px;">
                    <strong style="font-size: 14px; color: #333;">Preview:</strong>
                    <div id="optionsPreview" style="margin-top: 10px; display: flex; flex-wrap: wrap; gap: 8px;">
                        <!-- Preview will be generated here -->
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="houzez-icon icon-check-circle"></i> Save Options
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(field) {
    document.getElementById('fieldName').value = field.name;
    document.getElementById('modalTitle').textContent = 'Edit Options: ' + field.label;
    document.getElementById('fieldLabel').textContent = field.label;
    document.getElementById('fieldDescription').textContent = field.description;
    document.getElementById('fieldOptions').value = field.options;
    
    updatePreview();
    
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

function updatePreview() {
    const textarea = document.getElementById('fieldOptions');
    const preview = document.getElementById('optionsPreview');
    const options = textarea.value.split('\n').filter(opt => opt.trim());
    
    if (options.length === 0) {
        preview.innerHTML = '<span style="color: #999; font-style: italic;">No options entered yet...</span>';
        return;
    }
    
    let html = '';
    options.forEach((option, index) => {
        html += `
            <span style="display: inline-flex; align-items: center; background: white; padding: 6px 12px; border-radius: 4px; border: 1px solid #ddd; font-size: 13px;">
                <span style="background: var(--e-global-color-primary); color: white; width: 20px; height: 20px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; margin-right: 8px; font-weight: bold;">
                    ${index + 1}
                </span>
                ${option.trim()}
            </span>
        `;
    });
    preview.innerHTML = html;
}

// Update preview as user types
document.getElementById('fieldOptions')?.addEventListener('input', updatePreview);

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target === modal) {
        closeEditModal();
    }
}
</script>