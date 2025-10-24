<?php
/**
 * Pipeline - Leads Management
 */

global $wpdb;
$table_leads = $wpdb->prefix . 'pipeline_leads';
$table_partnerships = $wpdb->prefix . 'partnerships';

// Get filter parameters
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$assigned_filter = isset($_GET['assigned']) ? sanitize_text_field($_GET['assigned']) : '';
$source_filter = isset($_GET['source']) ? sanitize_text_field($_GET['source']) : '';

// Pagination
$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where = array('is_active = 1');

if (!empty($search)) {
    $where[] = $wpdb->prepare(
        "(fullname LIKE %s OR email LIKE %s OR contact_number LIKE %s)",
        '%' . $wpdb->esc_like($search) . '%',
        '%' . $wpdb->esc_like($search) . '%',
        '%' . $wpdb->esc_like($search) . '%'
    );
}

if (!empty($status_filter)) {
    $where[] = $wpdb->prepare("status = %s", $status_filter);
}

if (!empty($assigned_filter)) {
    $where[] = $wpdb->prepare("assigned_to = %s", $assigned_filter);
}

if (!empty($source_filter)) {
    $where[] = $wpdb->prepare("lead_source = %s", $source_filter);
}

$where_clause = implode(' AND ', $where);

// Get total count
$total_leads = $wpdb->get_var("SELECT COUNT(*) FROM $table_leads WHERE $where_clause");
$total_pages = ceil($total_leads / $per_page);

// Get leads
$leads = $wpdb->get_results("
    SELECT * FROM $table_leads
    WHERE $where_clause
    ORDER BY date_inquiry DESC
    LIMIT $offset, $per_page
");

// Get statistics
$stats = array(
    'total' => $wpdb->get_var("SELECT COUNT(*) FROM $table_leads WHERE is_active = 1"),
    'new_lead' => $wpdb->get_var("SELECT COUNT(*) FROM $table_leads WHERE status = 'New Lead' AND is_active = 1"),
    'qualifying' => $wpdb->get_var("SELECT COUNT(*) FROM $table_leads WHERE status = 'Qualifying' AND is_active = 1"),
    'qualified' => $wpdb->get_var("SELECT COUNT(*) FROM $table_leads WHERE status = 'Qualified' AND is_active = 1"),
    'wrong_contact' => $wpdb->get_var("SELECT COUNT(*) FROM $table_leads WHERE status = 'Wrong Contact' AND is_active = 1"),
    'cold_lead' => $wpdb->get_var("SELECT COUNT(*) FROM $table_leads WHERE is_cold_lead = 1")
);

// Get person in charge options
$person_in_charge_option = get_option('partnership_field_person_in_charge', '');
$person_in_charge_list = !empty($person_in_charge_option) ? explode("\n", $person_in_charge_option) : array();
$person_in_charge_list = array_map('trim', $person_in_charge_list);
$person_in_charge_list = array_filter($person_in_charge_list);

// Get lead sources
$lead_sources_table = $wpdb->prefix . 'pipeline_field_options';
$lead_sources_row = $wpdb->get_row("SELECT options FROM $lead_sources_table WHERE field_name = 'lead_source'");
$lead_sources = $lead_sources_row ? json_decode($lead_sources_row->options, true) : array();

// Get signed partnerships
$partnerships = $wpdb->get_results("SELECT id, company_name FROM $table_partnerships WHERE agreement_status = 'Signed' ORDER BY company_name");
?>

<div class="pipeline-header">
    <h2 class="pipeline-title">Leads Management</h2>
    <button class="btn btn-primary" onclick="openAddLeadModal()">
        <i class="houzez-icon icon-add-circle"></i> Add New Lead
    </button>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Leads</div>
        <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">New Leads</div>
        <div class="stat-value"><?php echo number_format($stats['new_lead']); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Qualifying</div>
        <div class="stat-value"><?php echo number_format($stats['qualifying']); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Qualified</div>
        <div class="stat-value"><?php echo number_format($stats['qualified']); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Wrong Contact</div>
        <div class="stat-value"><?php echo number_format($stats['wrong_contact']); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Cold Leads</div>
        <div class="stat-value"><?php echo number_format($stats['cold_lead']); ?></div>
    </div>
</div>

<!-- Filters -->
<div class="filter-section">
    <form method="GET" action="">
        <input type="hidden" name="hpage" value="leads">
        <div class="filter-row">
            <div class="filter-group">
                <label>Search</label>
                <input type="text" name="search" class="filter-input" placeholder="Search by name, email, or phone" value="<?php echo esc_attr($search); ?>">
            </div>
            <div class="filter-group">
                <label>Status</label>
                <select name="status" class="filter-select">
                    <option value="">All Statuses</option>
                    <option value="New Lead" <?php selected($status_filter, 'New Lead'); ?>>New Lead</option>
                    <option value="Qualifying" <?php selected($status_filter, 'Qualifying'); ?>>Qualifying</option>
                    <option value="Wrong Contact" <?php selected($status_filter, 'Wrong Contact'); ?>>Wrong Contact</option>
                    <option value="Qualified" <?php selected($status_filter, 'Qualified'); ?>>Qualified</option>
                    <option value="Cold Lead" <?php selected($status_filter, 'Cold Lead'); ?>>Cold Lead</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Assigned To</label>
                <select name="assigned" class="filter-select">
                    <option value="">All Assignees</option>
                    <?php foreach ($person_in_charge_list as $person) : ?>
                        <option value="<?php echo esc_attr($person); ?>" <?php selected($assigned_filter, $person); ?>>
                            <?php echo esc_html($person); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Lead Source</label>
                <select name="source" class="filter-select">
                    <option value="">All Sources</option>
                    <?php foreach ($lead_sources as $source) : ?>
                        <option value="<?php echo esc_attr($source); ?>" <?php selected($source_filter, $source); ?>>
                            <?php echo esc_html($source); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="?hpage=leads" class="btn btn-secondary">Reset</a>
            </div>
        </div>
    </form>
</div>

<!-- Leads Table -->
<table class="pipeline-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Date of Inquiry</th>
            <th>Lead Source</th>
            <th>Status</th>
            <th>Assigned To</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($leads)) : ?>
            <tr>
                <td colspan="9" class="no-results">No leads found</td>
            </tr>
        <?php else : ?>
            <?php foreach ($leads as $lead) :
                $status_class = 'status-' . strtolower(str_replace(' ', '-', $lead->status));
            ?>
                <tr>
                    <td>#<?php echo $lead->id; ?></td>
                    <td><?php echo esc_html($lead->fullname); ?></td>
                    <td><?php echo esc_html($lead->email); ?></td>
                    <td><?php echo esc_html($lead->contact_number); ?></td>
                    <td><?php echo date('M d, Y', strtotime($lead->date_inquiry)); ?></td>
                    <td><?php echo esc_html($lead->lead_source); ?></td>
                    <td><span class="status-badge <?php echo $status_class; ?>"><?php echo esc_html($lead->status); ?></span></td>
                    <td><?php echo esc_html($lead->assigned_to); ?></td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-primary" onclick='viewLead(<?php echo json_encode($lead); ?>)'>
                                <i class="houzez-icon icon-messages-bubble"></i> View
                            </button>
                            <button class="btn btn-sm btn-info" onclick='editLead(<?php echo json_encode($lead); ?>)'>
                                <i class="houzez-icon icon-edit-1"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteLead(<?php echo $lead->id; ?>)">
                                <i class="houzez-icon icon-remove-circle"></i> Delete
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<!-- Pagination -->
<?php if ($total_pages > 1) : ?>
    <div class="pagination">
        <button onclick="window.location.href='?hpage=leads&paged=1<?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>'"
                <?php echo $page <= 1 ? 'disabled' : ''; ?>>First</button>
        <button onclick="window.location.href='?hpage=leads&paged=<?php echo max(1, $page - 1); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>'"
                <?php echo $page <= 1 ? 'disabled' : ''; ?>>Previous</button>
        <span class="page-info">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
        <button onclick="window.location.href='?hpage=leads&paged=<?php echo min($total_pages, $page + 1); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>'"
                <?php echo $page >= $total_pages ? 'disabled' : ''; ?>>Next</button>
        <button onclick="window.location.href='?hpage=leads&paged=<?php echo $total_pages; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>'"
                <?php echo $page >= $total_pages ? 'disabled' : ''; ?>>Last</button>
    </div>
<?php endif; ?>

<!-- Add/Edit Lead Modal -->
<div id="leadModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="leadModalTitle">Add New Lead</h3>
            <button class="close" onclick="closeLeadModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="leadForm">
                <input type="hidden" id="lead_id" name="lead_id">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="required">Full Name</label>
                        <input type="text" class="form-control" id="fullname" name="fullname" required>
                    </div>
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" class="form-control" id="firstname" name="firstname">
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" class="form-control" id="lastname" name="lastname">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" class="form-control" id="contact_number" name="contact_number">
                    </div>
                    <div class="form-group">
                        <label>Lead Source</label>
                        <select class="form-control" id="lead_source" name="lead_source">
                            <option value="">Select Source</option>
                            <?php foreach ($lead_sources as $source) : ?>
                                <option value="<?php echo esc_attr($source); ?>"><?php echo esc_html($source); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="required">Status</label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="New Lead">New Lead</option>
                            <option value="Qualifying">Qualifying</option>
                            <option value="Wrong Contact">Wrong Contact</option>
                            <option value="Qualified">Qualified</option>
                            <option value="Cold Lead">Cold Lead</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Assigned To</label>
                        <select class="form-control" id="assigned_to" name="assigned_to">
                            <option value="">Select Person</option>
                            <?php foreach ($person_in_charge_list as $person) : ?>
                                <option value="<?php echo esc_attr($person); ?>"><?php echo esc_html($person); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label>Partners (Multiple Selection)</label>
                        <select class="form-control select2-partners" id="partners" name="partners[]" multiple>
                            <?php foreach ($partnerships as $partnership) : ?>
                                <option value="<?php echo $partnership->id; ?>"><?php echo esc_html($partnership->company_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeLeadModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveLead()">Save Lead</button>
        </div>
    </div>
</div>

<!-- View Lead Modal -->
<div id="viewLeadModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Lead Details</h3>
            <button class="close" onclick="closeViewLeadModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="viewLeadContent"></div>

            <div class="comments-section">
                <h4>Comments</h4>
                <div id="commentsContainer"></div>

                <div class="comment-form">
                    <textarea class="form-control" id="newComment" placeholder="Add a comment..." rows="3"></textarea>
                    <button class="btn btn-primary" style="margin-top: 10px;" onclick="addLeadComment()">Add Comment</button>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeViewLeadModal()">Close</button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize Select2 for multiple selection
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2-partners').select2({
            placeholder: 'Select partners',
            allowClear: true,
            width: '100%'
        });
    }
});

let currentViewLeadId = null;

function openAddLeadModal() {
    document.getElementById('leadModalTitle').textContent = 'Add New Lead';
    document.getElementById('leadForm').reset();
    document.getElementById('lead_id').value = '';
    jQuery('.select2-partners').val(null).trigger('change');
    document.getElementById('leadModal').style.display = 'block';
}

function closeLeadModal() {
    document.getElementById('leadModal').style.display = 'none';
}

function editLead(lead) {
    document.getElementById('leadModalTitle').textContent = 'Edit Lead';
    document.getElementById('lead_id').value = lead.id;
    document.getElementById('fullname').value = lead.fullname || '';
    document.getElementById('firstname').value = lead.firstname || '';
    document.getElementById('lastname').value = lead.lastname || '';
    document.getElementById('email').value = lead.email || '';
    document.getElementById('contact_number').value = lead.contact_number || '';
    document.getElementById('lead_source').value = lead.lead_source || '';
    document.getElementById('status').value = lead.status || 'New Lead';
    document.getElementById('assigned_to').value = lead.assigned_to || '';

    // Set partners
    if (lead.partners) {
        try {
            const partnerIds = JSON.parse(lead.partners);
            jQuery('.select2-partners').val(partnerIds).trigger('change');
        } catch (e) {
            console.error('Error parsing partners:', e);
        }
    }

    document.getElementById('leadModal').style.display = 'block';
}

function saveLead() {
    const formData = new FormData(document.getElementById('leadForm'));
    formData.append('action', 'save_pipeline_lead');
    formData.append('nonce', '<?php echo wp_create_nonce("save_pipeline_lead"); ?>');

    jQuery.ajax({
        url: '<?php echo admin_url("admin-ajax.php"); ?>',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                alert('Lead saved successfully!');
                location.reload();
            } else {
                alert('Error: ' + (response.data || 'Unknown error'));
            }
        },
        error: function() {
            alert('An error occurred while saving the lead.');
        }
    });
}

function deleteLead(leadId) {
    if (!confirm('Are you sure you want to delete this lead?')) {
        return;
    }

    jQuery.ajax({
        url: '<?php echo admin_url("admin-ajax.php"); ?>',
        type: 'POST',
        data: {
            action: 'delete_pipeline_lead',
            lead_id: leadId,
            nonce: '<?php echo wp_create_nonce("delete_pipeline_lead"); ?>'
        },
        success: function(response) {
            if (response.success) {
                alert('Lead deleted successfully!');
                location.reload();
            } else {
                alert('Error: ' + (response.data || 'Unknown error'));
            }
        },
        error: function() {
            alert('An error occurred while deleting the lead.');
        }
    });
}

function viewLead(lead) {
    currentViewLeadId = lead.id;

    let html = '<div class="form-grid">';
    html += '<div class="form-group"><strong>Full Name:</strong><p>' + (lead.fullname || 'N/A') + '</p></div>';
    html += '<div class="form-group"><strong>Email:</strong><p>' + (lead.email || 'N/A') + '</p></div>';
    html += '<div class="form-group"><strong>Phone:</strong><p>' + (lead.contact_number || 'N/A') + '</p></div>';
    html += '<div class="form-group"><strong>Date of Inquiry:</strong><p>' + new Date(lead.date_inquiry).toLocaleDateString() + '</p></div>';
    html += '<div class="form-group"><strong>Lead Source:</strong><p>' + (lead.lead_source || 'N/A') + '</p></div>';
    html += '<div class="form-group"><strong>Status:</strong><p><span class="status-badge status-' + lead.status.toLowerCase().replace(/ /g, '-') + '">' + lead.status + '</span></p></div>';
    html += '<div class="form-group"><strong>Assigned To:</strong><p>' + (lead.assigned_to || 'N/A') + '</p></div>';
    html += '<div class="form-group"><strong>Last Update:</strong><p>' + new Date(lead.last_update).toLocaleString() + '</p></div>';
    html += '</div>';

    document.getElementById('viewLeadContent').innerHTML = html;
    loadLeadComments(lead.id);
    document.getElementById('viewLeadModal').style.display = 'block';
}

function closeViewLeadModal() {
    document.getElementById('viewLeadModal').style.display = 'none';
    currentViewLeadId = null;
}

function loadLeadComments(leadId) {
    jQuery.ajax({
        url: '<?php echo admin_url("admin-ajax.php"); ?>',
        type: 'POST',
        data: {
            action: 'get_pipeline_comments',
            entity_type: 'lead',
            entity_id: leadId,
            nonce: '<?php echo wp_create_nonce("get_pipeline_comments"); ?>'
        },
        success: function(response) {
            if (response.success) {
                let html = '';
                if (response.data.length > 0) {
                    response.data.forEach(function(comment) {
                        html += '<div class="comment-item">';
                        html += '<div class="comment-header">';
                        html += '<span class="comment-author">' + comment.author + '</span>';
                        html += '<span class="comment-date">' + comment.date + '</span>';
                        html += '</div>';
                        html += '<div class="comment-text">' + comment.comment + '</div>';
                        html += '</div>';
                    });
                } else {
                    html = '<p style="color: #999;">No comments yet.</p>';
                }
                document.getElementById('commentsContainer').innerHTML = html;
            }
        }
    });
}

function addLeadComment() {
    const comment = document.getElementById('newComment').value.trim();
    if (!comment) {
        alert('Please enter a comment.');
        return;
    }

    jQuery.ajax({
        url: '<?php echo admin_url("admin-ajax.php"); ?>',
        type: 'POST',
        data: {
            action: 'add_pipeline_comment',
            entity_type: 'lead',
            entity_id: currentViewLeadId,
            comment: comment,
            nonce: '<?php echo wp_create_nonce("add_pipeline_comment"); ?>'
        },
        success: function(response) {
            if (response.success) {
                document.getElementById('newComment').value = '';
                loadLeadComments(currentViewLeadId);
            } else {
                alert('Error adding comment: ' + (response.data || 'Unknown error'));
            }
        }
    });
}

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target.id === 'leadModal') {
        closeLeadModal();
    }
    if (event.target.id === 'viewLeadModal') {
        closeViewLeadModal();
    }
}
</script>
