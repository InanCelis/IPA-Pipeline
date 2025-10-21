<?php
/**
 * Partners List Management
 * File: template-parts/partnership/partners-list.php
 */

global $wpdb;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $table_name = $wpdb->prefix . 'partnerships';
        
        switch($_POST['action']) {
            case 'add':
            case 'edit':
                $data = array(
                    'company_name' => sanitize_text_field($_POST['company_name']),
                    'commission_rate' => sanitize_text_field($_POST['commission_rate']),
                    'agreement_status' => sanitize_text_field($_POST['agreement_status']),
                    'date_signed' => sanitize_text_field($_POST['date_signed']),
                    'date_expiration' => sanitize_text_field($_POST['date_expiration']),
                    'number_of_leads' => intval($_POST['number_of_leads']),
                    'industry' => sanitize_text_field($_POST['industry']),
                    'country' => sanitize_text_field($_POST['country']),
                    'website' => esc_url_raw($_POST['website']),
                    'xml_links' => sanitize_textarea_field($_POST['xml_links']),
                    'manner_upload' => sanitize_text_field($_POST['manner_upload']),
                    'property_upload_status' => sanitize_text_field($_POST['property_upload_status']),
                    'total_properties' => intval($_POST['total_properties']),
                    'person_in_charge' => sanitize_text_field($_POST['person_in_charge']),
                    'contact_person' => sanitize_text_field($_POST['contact_person']),
                    'mobile' => sanitize_text_field($_POST['mobile']),
                    'email' => sanitize_email($_POST['email']),
                    'updated_at' => current_time('mysql')
                );
                
                if ($_POST['action'] === 'add') {
                    $data['added_by'] = get_current_user_id();
                    $data['created_at'] = current_time('mysql');
                    $wpdb->insert($table_name, $data);
                    echo '<div class="notice notice-success"><p>Partner added successfully!</p></div>';
                } else {
                    $wpdb->update($table_name, $data, array('id' => intval($_POST['partner_id'])));
                    echo '<div class="notice notice-success"><p>Partner updated successfully!</p></div>';
                }
                break;
                
            case 'delete':
                $wpdb->delete($table_name, array('id' => intval($_POST['partner_id'])));
                echo '<div class="notice notice-success"><p>Partner deleted successfully!</p></div>';
                break;
                
            case 'add_comment':
                $comments_table = $wpdb->prefix . 'partnership_comments';
                $wpdb->insert($comments_table, array(
                    'partnership_id' => intval($_POST['partner_id']),
                    'user_id' => get_current_user_id(),
                    'comment' => sanitize_textarea_field($_POST['comment']),
                    'created_at' => current_time('mysql')
                ));
                echo '<div class="notice notice-success"><p>Comment added successfully!</p></div>';
                break;
        }
    }
}

// Get filters
$filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';
$filter_industry = isset($_GET['filter_industry']) ? sanitize_text_field($_GET['filter_industry']) : '';
$filter_person = isset($_GET['filter_person']) ? sanitize_text_field($_GET['filter_person']) : '';
$filter_country = isset($_GET['filter_country']) ? sanitize_text_field($_GET['filter_country']) : '';
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

// Build query
$where = array("1=1");
if ($filter_status) $where[] = $wpdb->prepare("agreement_status = %s", $filter_status);
if ($filter_industry) $where[] = $wpdb->prepare("industry = %s", $filter_industry);
if ($filter_person) $where[] = $wpdb->prepare("person_in_charge = %s", $filter_person);
if ($filter_country) $where[] = $wpdb->prepare("country = %s", $filter_country);
if ($search) $where[] = $wpdb->prepare("(company_name LIKE %s OR contact_person LIKE %s)", '%'.$search.'%', '%'.$search.'%');

$where_clause = implode(' AND ', $where);
$table_name = $wpdb->prefix . 'partnerships';
$partners = $wpdb->get_results("SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY created_at DESC");

// Get unique values for filters
$statuses = $wpdb->get_col("SELECT DISTINCT agreement_status FROM {$table_name} WHERE agreement_status != '' AND agreement_status IS NOT NULL");
$industries = $wpdb->get_col("SELECT DISTINCT industry FROM {$table_name} WHERE industry != '' AND industry IS NOT NULL");
$persons = $wpdb->get_col("SELECT DISTINCT person_in_charge FROM {$table_name} WHERE person_in_charge != '' AND person_in_charge IS NOT NULL");
$countries = $wpdb->get_col("SELECT DISTINCT country FROM {$table_name} WHERE country != '' AND country IS NOT NULL");


?>

<div class="partnership-header">
    <h2 class="partnership-title">Partners List (<?php echo count($partners); ?>)</h2>
    <button class="btn btn-primary" onclick="openModal('add')">
        <i class="houzez-icon icon-add-circle"></i> Add New Partner
    </button>
</div>
 
<!-- Filters -->
<div class="filter-section">
    <form method="GET" action="">
        <input type="hidden" name="hpage" value="partners">
        <div class="filter-row">
            <div class="filter-group">
                <label>Search</label>
                <input type="text" name="search" class="filter-input" placeholder="Search company or contact..." value="<?php echo esc_attr($search); ?>">
            </div>
            <div class="filter-group">
                <label>Agreement Status</label>
                <select name="filter_status" class="filter-select">
                    <option value="">All Statuses</option>
                    <?php foreach($statuses as $status): ?>
                        <option value="<?php echo esc_attr($status); ?>" <?php selected($filter_status, $status); ?>>
                            <?php echo esc_html($status); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Industry</label>
                <select name="filter_industry" class="filter-select">
                    <option value="">All Industries</option>
                    <?php foreach($industries as $industry): ?>
                        <option value="<?php echo esc_attr($industry); ?>" <?php selected($filter_industry, $industry); ?>>
                            <?php echo esc_html($industry); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Person in Charge</label>
                <select name="filter_person" class="filter-select">
                    <option value="">All Persons</option>
                    <?php foreach($persons as $person): ?>
                        <option value="<?php echo esc_attr($person); ?>" <?php selected($filter_person, $person); ?>>
                            <?php echo esc_html($person); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Country</label>
                <select name="filter_country" class="filter-select">
                    <option value="">All Countries</option>
                    <?php foreach($countries as $country): ?>
                        <option value="<?php echo esc_attr($country); ?>" <?php selected($filter_country, $country); ?>>
                            <?php echo esc_html($country); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <button type="submit" class="btn btn-primary" style="width: 100%;">Apply Filters</button>
            </div>
        </div>
    </form>
</div>

<!-- Partners Table -->
<table class="partnership-table">
    <thead>
        <tr>
            <th>Company Name</th>
            <th>Status</th>
            <th>Country</th>
            <th>Industry</th>
            <th>Commission</th>
            <th>Number of Leads</th>
            <th>Properties</th>
            <th>Person in Charge</th>
            <th>Contact</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($partners)): ?>
            <tr>
                <td colspan="10" style="text-align: center; padding: 40px;">
                    No partners found. Add your first partner to get started.
                </td>
            </tr>
        <?php else: ?>
            <?php foreach($partners as $partner): 
                
                //commnents count
                $comments_table = $wpdb->prefix . 'partnership_comments';
                $comment_count = $wpdb->get_var(
                    $wpdb->prepare("SELECT COUNT(*) FROM {$comments_table} WHERE partnership_id = %d", $partner->id)
                );
                
                ?>
                <!-- <?php print_r($partner) ?> -->
                <tr class="status-upload-<?php echo esc_attr(strtolower(str_replace(' ', '-', $partner->property_upload_status))); ?>">
                    <td>
                        
                        <strong><?php echo esc_html($partner->company_name); ?></strong>
                        <?php if($partner->website): ?>
                            <br><a href="<?php echo esc_url($partner->website); ?>" target="_blank" style="font-size: 12px; color: #666;">
                                <i class="houzez-icon icon-attachment"></i> Website
                            </a>
                        <?php endif; ?>
                        <br>
                        <small><?php echo esc_html($partner->manner_upload); ?></small>
                    </td>
                    <td>
                        <span class="status-badge status-<?php echo esc_attr(strtolower(str_replace(' ', '-', $partner->agreement_status))); ?>">
                            <?php echo esc_html($partner->agreement_status ?: 'None'); ?>
                        </span>
                    </td>
                    <td><?php echo esc_html($partner->country ?: 'N/A'); ?></td>
                    <td><?php echo esc_html($partner->industry ?: 'N/A'); ?></td>
                    <td><?php echo esc_html($partner->commission_rate ?: 'N/A'); ?></td>
                    <td><strong><?php echo esc_html($partner->number_of_leads ?: '0'); ?></strong>
                    <td><strong><?php echo esc_html($partner->total_properties ?: '0'); ?></strong></td>
                    <td><?php echo esc_html($partner->person_in_charge ?: 'N/A'); ?></td>
                    <td>
                        <?php echo esc_html($partner->contact_person ?: 'N/A'); ?>
                        <?php if($partner->mobile): ?>
                            <br><small><?php echo esc_html($partner->mobile); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-primary btn-sm" onclick='openModal("edit", <?php echo json_encode($partner); ?>)'>
                                <i class="houzez-icon icon-pencil"></i>
                            </button>
                            <button class="btn btn-success btn-sm" onclick="openCommentModal(<?php echo $partner->id; ?>)">
                                <i class="houzez-icon icon-messages-bubble"></i>
                                <?php if ($comment_count > 0): ?>
                                    <span style="
                                        position: absolute;
                                        margin-top: -45px;
                                        margin-left: 8px;
                                        background: #dc3545;
                                        color: #fff;
                                        font-size: 10px;
                                        font-weight: 600;
                                        border-radius: 50%;
                                        padding: 3px 6px;
                                        min-width: 16px;
                                        height: 16px;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        line-height: 1;
                                        box-shadow: 0 0 2px rgba(0,0,0,0.2);
                                        z-index: 99;
                                    ">
                                        <?php echo intval($comment_count); ?>
                                    </span>
                                <?php endif; ?>
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deletePartner(<?php echo $partner->id; ?>)">
                                <i class="houzez-icon icon-close"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<!-- Add/Edit Modal -->
<div id="partnerModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Add New Partner</h3>
            <button class="close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="partner_id" id="partnerId">
            
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Company Name *</label>
                        <input type="text" name="company_name" id="companyName" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Commission Rate</label>
                        <input type="text" name="commission_rate" id="commissionRate" class="form-control" placeholder="e.g., 5%, 10%">
                    </div>
                    
                    <div class="form-group">
                        <label>Agreement Status</label>
                        <select name="agreement_status" id="agreementStatus" class="form-control">
                            <?php
                            $agreement_status_options = get_option('partnership_field_agreement_status', "None\nSigned\nPreparing\nPending\nPending Signature\nDeclined");
                            $status_options = array_filter(explode("\n", $agreement_status_options));
                            foreach($status_options as $option):
                                $option = trim($option);
                                if($option):
                            ?>
                                <option value="<?php echo esc_attr($option); ?>"><?php echo esc_html($option); ?></option>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Industry</label>
                        <select name="industry" id="industry" class="form-control">
                            <?php
                            $industry_options = get_option('partnership_field_industry', "None\nDeveloper\nReal Estate Agency\nIndividual Owner\nIndividual Broker\nIndividual Agent\nCurrency/Broker");
                            $industries = array_filter(explode("\n", $industry_options));
                            foreach($industries as $option):
                                $option = trim($option);
                                if($option):
                            ?>
                                <option value="<?php echo esc_attr($option); ?>"><?php echo esc_html($option); ?></option>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Country</label>
                        <select name="country" id="country" class="form-control">
                            <?php
                            $country_options = get_option('partnership_field_country', "Philippines\nSpain\nCyprus");
                            $countries_list = array_filter(explode("\n", $country_options));
                            foreach($countries_list as $option):
                                $option = trim($option);
                                if($option):
                            ?>
                                <option value="<?php echo esc_attr($option); ?>"><?php echo esc_html($option); ?></option>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Date of Signed Agreement</label>
                        <input type="date" name="date_signed" id="dateSigned" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>Date of Expiration</label>
                        <input type="date" name="date_expiration" id="dateExpiration" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Number of Leads</label>
                        <input type="number" name="number_of_leads" id="numberOfLeads" class="form-control" value="0" min="0">
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Website</label>
                        <input type="url" name="website" id="website" class="form-control" placeholder="https://">
                    </div>
                    
                    <div class="form-group full-width">
                        <label>XML Links</label>
                        <textarea name="xml_links" id="xmlLinks" class="form-control" placeholder="Enter XML feed URLs (one per line)"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Manner of Upload</label>
                        <select name="manner_upload" id="mannerUpload" class="form-control">
                            <?php
                            $manner_upload_options = get_option('partnership_field_manner_upload', "TBD\nManual\nXML Feed\nWeb Scrape");
                            $manner_options = array_filter(explode("\n", $manner_upload_options));
                            foreach($manner_options as $option):
                                $option = trim($option);
                                if($option):
                            ?>
                                <option value="<?php echo esc_attr($option); ?>"><?php echo esc_html($option); ?></option>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Property Upload Status</label>
                        <select name="property_upload_status" id="propertyUploadStatus" class="form-control">
                            <?php
                            $property_upload_options = get_option('partnership_field_property_upload_status', "None\nOngoing\nCompleted");
                            $upload_status_options = array_filter(explode("\n", $property_upload_options));
                            foreach($upload_status_options as $option):
                                $option = trim($option);
                                if($option):
                            ?>
                                <option value="<?php echo esc_attr($option); ?>"><?php echo esc_html($option); ?></option>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Total Properties Uploaded</label>
                        <input type="number" name="total_properties" id="totalProperties" class="form-control" value="0" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label>Person in Charge</label>
                        <select name="person_in_charge" id="personInCharge" class="form-control">
                            <option value="">Select...</option>
                            <?php
                            $person_in_charge_options = get_option('partnership_field_person_in_charge', "Aya Piad\nElly Herriman\nPhilip Clarke");
                            $person_options = array_filter(explode("\n", $person_in_charge_options));
                            foreach($person_options as $option):
                                $option = trim($option);
                                if($option):
                            ?>
                                <option value="<?php echo esc_attr($option); ?>"><?php echo esc_html($option); ?></option>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Contact Person</label>
                        <input type="text" name="contact_person" id="contactPerson" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>Mobile</label>
                        <input type="text" name="mobile" id="mobile" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="email" class="form-control">
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Partner</button>
            </div>
        </form>
    </div>
</div>

<!-- Comment Modal -->
<div id="commentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" style="margin: 0;">
                <i class="houzez-icon icon-messages-bubble"></i> Comments
            </h3>
            <button class="close" onclick="closeCommentModal()">&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add_comment">
            <input type="hidden" name="partner_id" id="commentPartnerId">
            
            <div class="modal-body" style="background: #f8f9fa; padding: 0;">
                <div id="commentsContainer" style="margin-bottom: 0; max-height: 400px; overflow-y: auto; background: white; min-height: 300px;">
                    <!-- Comments will be loaded here -->
                </div>
                
                <div style="padding: 20px; background: white; border-top: 2px solid #e0e0e0;">
                    <div class="form-group" style="margin: 0;">
                        <textarea name="comment" 
                                  class="form-control" 
                                  rows="3" 
                                  required 
                                  placeholder="Type your comment here..."
                                  style="border-radius: 20px; border: 2px solid #e0e0e0; padding: 12px 16px; resize: none;"></textarea>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer" style="background: #f8f9fa; border-top: none; padding: 15px 20px;">
                <button type="button" class="btn btn-secondary" onclick="closeCommentModal()">
                    <i class="houzez-icon icon-close"></i> Close
                </button>
                <button type="submit" class="btn btn-primary" style="padding: 10px 30px;">
                    <i class="houzez-icon icon-send"></i> Send Comment
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(action, data = null) {
    const modal = document.getElementById('partnerModal');
    const modalTitle = document.getElementById('modalTitle');
    const formAction = document.getElementById('formAction');
    
    if (action === 'edit' && data) {
        modalTitle.textContent = 'Edit Partner';
        formAction.value = 'edit';
        document.getElementById('partnerId').value = data.id;
        document.getElementById('companyName').value = data.company_name || '';
        document.getElementById('commissionRate').value = data.commission_rate || '';
        document.getElementById('agreementStatus').value = data.agreement_status || '';
        document.getElementById('industry').value = data.industry || '';
        document.getElementById('country').value = data.country || '';
        document.getElementById('dateSigned').value = data.date_signed || '';
        document.getElementById('dateExpiration').value = data.date_expiration || '';
        document.getElementById('numberOfLeads').value = data.number_of_leads || 0;
        document.getElementById('website').value = data.website || '';
        document.getElementById('xmlLinks').value = data.xml_links || '';
        document.getElementById('mannerUpload').value = data.manner_upload || 'TBD';
        document.getElementById('propertyUploadStatus').value = data.property_upload_status || '';
        document.getElementById('totalProperties').value = data.total_properties || 0;
        document.getElementById('personInCharge').value = data.person_in_charge || '';
        document.getElementById('contactPerson').value = data.contact_person || '';
        document.getElementById('mobile').value = data.mobile || '';
        document.getElementById('email').value = data.email || '';
    } else {
        modalTitle.textContent = 'Add New Partner';
        formAction.value = 'add';
        document.querySelector('#partnerModal form').reset();
        document.getElementById('partnerId').value = '';
    }
    
    modal.style.display = 'block';
}

function closeModal() {
    document.getElementById('partnerModal').style.display = 'none';
}

function openCommentModal(partnerId) {
    document.getElementById('commentPartnerId').value = partnerId;
    loadComments(partnerId);
    document.getElementById('commentModal').style.display = 'block';
}

function closeCommentModal() {
    document.getElementById('commentModal').style.display = 'none';
}


function loadComments(partnerId) {
    const container = document.getElementById('commentsContainer');
    container.innerHTML = '<div style="text-align: center; padding: 20px; color: #666;"><i class="houzez-icon icon-refresh-1"></i><br>Loading comments...</div>';
    
    const currentUserId = <?php echo get_current_user_id(); ?>;
    console.log('Current User ID:', currentUserId);
    
    fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=get_partnership_comments&partner_id=' + partnerId, {
        method: 'GET',
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.status);
        }
        return response.json();
    }) 
    .then(data => {
        if (data.success) {
            const comments = data.data.comments;
            
            if (comments && comments.length > 0) {
                const reversedComments = [...comments].reverse();
                
                let html = '<div style="padding: 15px;">';
                
                reversedComments.forEach(comment => {
                    // Convert both to strings and trim for comparison
                    const commentUserId = String(comment.user_id).trim();
                    const currentUserIdStr = String(currentUserId).trim();
                    const isCurrentUser = commentUserId === currentUserIdStr;
                    
                    // DEBUG: Log each comparison
                    console.log('Comment ID:', comment.id);
                    console.log('Comment user_id:', commentUserId, 'Type:', typeof comment.user_id);
                    console.log('Current user_id:', currentUserIdStr, 'Type:', typeof currentUserId);
                    console.log('Is match?', isCurrentUser);
                    console.log('---');
                    
                    const alignStyle = isCurrentUser ? 'flex-end' : 'flex-start';
                    const bgColor = isCurrentUser ? '#0084ff' : '#e4e6eb';
                    const textColor = isCurrentUser ? '#ffffff' : '#050505';
                    const marginStyle = isCurrentUser ? 'margin-left: 60px;' : 'margin-right: 60px;';
                    
                    let userColor = bgColor;
                    if (!isCurrentUser) {
                        const colors = ['#f5f5f5', '#f8f8f8', '#fafafa', '#f0f0f0', '#f3f3f3', '#ebebeb'];
                        userColor = colors[parseInt(comment.user_id) % colors.length];
                    }
                    
                    html += `
                        <div style="display: flex; justify-content: ${alignStyle}; margin-bottom: 16px; width: 100%;">
                            <div style="max-width: 75%; ${marginStyle}">
                                ${!isCurrentUser ? `
                                    <div style="font-size: 15px; font-weight: 600; color: #333; margin-bottom: 5px; padding-left: 12px; text-transform: capitalize;">
                                        ${comment.user_name}
                                    </div>
                                ` : `<div style="font-size: 15px; font-weight: 600; color: #333; margin-bottom: 5px; text-align: right; padding-right: 12px; text-transform: capitalize;">
                                        You
                                    </div>`}
                                
                                <div style="background: ${isCurrentUser ? bgColor : userColor}; 
                                            color: ${textColor}; 
                                            padding: 12px 16px; 
                                            border-radius: 18px; 
                                            word-wrap: break-word;
                                            box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                                    <div style="margin-bottom: 6px; line-height: 1.5; font-size: 14px; white-space: pre-wrap;">${comment.comment}</div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px;">
                                        <small style="font-size: 11px; opacity: 0.8;">${comment.created_at}</small>
                                        ${isCurrentUser ? `
                                            <button onclick="deleteComment(${comment.id}, ${partnerId})" 
                                                    class="delete-comment-btn"
                                                    style="background: rgba(255,255,255,0.2); 
                                                           border: 1px solid rgba(255,255,255,0.3); 
                                                           color: ${textColor}; 
                                                           cursor: pointer; 
                                                           padding: 4px 10px; 
                                                           border-radius: 12px;
                                                           font-size: 11px;
                                                           font-weight: 600;
                                                           transition: all 0.2s ease;"
                                                    onmouseover="this.style.background='rgba(255,255,255,0.3)'"
                                                    onmouseout="this.style.background='rgba(255,255,255,0.2)'"
                                                    title="Delete this comment">
                                                🗑️ Delete
                                            </button>
                                        ` : ''}
                                    </div>
                                </div>
                             
                            </div>
                        </div>
                    `;
                });
                
                html += '</div>';
                container.innerHTML = html;
                
                setTimeout(() => {
                    container.scrollTop = container.scrollHeight;
                }, 100);
            } else {
                container.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #999;">
                        <i class="houzez-icon icon-messages-bubble" style="font-size: 48px; opacity: 0.3;"></i>
                        <p style="margin-top: 15px;">No comments yet. Be the first to add one!</p>
                    </div>
                `;
            }
        } else {
            container.innerHTML = `<div style="text-align: center; padding: 20px; color: #dc3545;">Error: ${data.data ? data.data.message : 'Unknown error'}</div>`;
        }
    })
    .catch(error => {
        console.error('Load comments error:', error);
        container.innerHTML = `<div style="text-align: center; padding: 20px; color: #dc3545;">Failed to load comments: ${error.message}</div>`;
    });
}

// Add delete comment function
function deleteComment(commentId, partnerId) {
    if (!confirm('Are you sure you want to delete this comment?')) {
        return;
    }
    
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=delete_partnership_comment&comment_id=' + commentId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload comments
            loadComments(partnerId);
        } else {
            alert('Error: ' + (data.data ? data.data.message : 'Failed to delete comment'));
        }
    })
    .catch(error => {
        console.error('Delete error:', error);
        alert('Failed to delete comment. Please try again.');
    });
}

function deletePartner(partnerId) {
    if (confirm('Are you sure you want to delete this partner? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="partner_id" value="${partnerId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const partnerModal = document.getElementById('partnerModal');
    const commentModal = document.getElementById('commentModal');
    if (event.target === partnerModal) {
        closeModal();
    }
    if (event.target === commentModal) {
        closeCommentModal();
    }
}
</script>

