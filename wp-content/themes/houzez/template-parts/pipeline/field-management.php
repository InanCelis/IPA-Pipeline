<?php
/**
 * Pipeline - Field Management
 */

if (!current_user_can('administrator')) {
    echo '<div class="alert alert-danger">Only administrators can access this page.</div>';
    return;
}

global $wpdb;
$table_fields = $wpdb->prefix . 'pipeline_field_options';

// Get all field options
$fields = $wpdb->get_results("SELECT * FROM $table_fields WHERE is_active = 1 ORDER BY field_label");
?>

<div class="pipeline-header">
    <h2 class="pipeline-title">Field Management</h2>
    <p style="color: #666;">Manage dropdown options for various fields in the pipeline system.</p>
</div>

<div class="chart-container">
    <?php foreach ($fields as $field) :
        $options = json_decode($field->options, true);
        if (!is_array($options)) $options = array();
    ?>
        <div class="field-item">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h4 style="margin: 0;"><?php echo esc_html($field->field_label); ?></h4>
                <button class="btn btn-sm btn-info" onclick="editField('<?php echo esc_js($field->field_name); ?>', <?php echo esc_js(json_encode($options)); ?>)">
                    <i class="houzez-icon icon-edit-1"></i> Edit Options
                </button>
            </div>

            <div class="field-options">
                <strong>Current Options:</strong>
                <ul class="option-list">
                    <?php foreach ($options as $option) : ?>
                        <li><?php echo esc_html($option); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Edit Field Modal -->
<div id="fieldModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="fieldModalTitle">Edit Field Options</h3>
            <button class="close" onclick="closeFieldModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="fieldForm">
                <input type="hidden" id="field_name" name="field_name">

                <div class="form-group">
                    <label>Options (One per line)</label>
                    <textarea class="form-control" id="field_options" name="field_options" rows="10" placeholder="Enter each option on a new line"></textarea>
                    <small style="color: #666;">Add each option on a new line. Example:<br>Option 1<br>Option 2<br>Option 3</small>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeFieldModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveFieldOptions()">Save Options</button>
        </div>
    </div>
</div>

<script>
function editField(fieldName, options) {
    document.getElementById('field_name').value = fieldName;
    document.getElementById('field_options').value = options.join('\n');
    document.getElementById('fieldModal').style.display = 'block';
}

function closeFieldModal() {
    document.getElementById('fieldModal').style.display = 'none';
}

function saveFieldOptions() {
    const fieldName = document.getElementById('field_name').value;
    const optionsText = document.getElementById('field_options').value;
    const options = optionsText.split('\n').map(o => o.trim()).filter(o => o.length > 0);

    jQuery.ajax({
        url: '<?php echo admin_url("admin-ajax.php"); ?>',
        type: 'POST',
        data: {
            action: 'save_field_options',
            field_name: fieldName,
            options: options,
            nonce: '<?php echo wp_create_nonce("save_field_options"); ?>'
        },
        success: function(response) {
            if (response.success) {
                alert('Field options saved successfully!');
                location.reload();
            } else {
                alert('Error: ' + (response.data || 'Unknown error'));
            }
        },
        error: function() {
            alert('An error occurred while saving field options.');
        }
    });
}

window.onclick = function(event) {
    if (event.target.id === 'fieldModal') {
        closeFieldModal();
    }
}
</script>
