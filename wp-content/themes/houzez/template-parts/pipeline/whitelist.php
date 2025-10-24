<?php
/**
 * Pipeline - Whitelisted Users
 */

if (!current_user_can('administrator')) {
    echo '<div class="alert alert-danger">Only administrators can access this page.</div>';
    return;
}

global $wpdb;
$table_whitelist = $wpdb->prefix . 'pipeline_whitelist';

// Get whitelisted users
$whitelisted = $wpdb->get_results("
    SELECT w.*, u.display_name, u.user_login, u.user_email
    FROM $table_whitelist w
    LEFT JOIN {$wpdb->users} u ON w.user_id = u.ID
    WHERE w.is_active = 1
    ORDER BY u.display_name
");

// Get all users for dropdown (excluding already whitelisted)
$whitelisted_ids = array_column($whitelisted, 'user_id');
$whitelisted_ids[] = 0; // Prevent SQL error if empty

$all_users = get_users(array(
    'exclude' => $whitelisted_ids,
    'orderby' => 'display_name'
));
?>

<div class="pipeline-header">
    <h2 class="pipeline-title">Whitelisted Users</h2>
    <button class="btn btn-primary" onclick="openAddUserModal()">
        <i class="houzez-icon icon-add-circle"></i> Add User
    </button>
</div>

<div class="alert alert-info">
    <strong>Note:</strong> Only users in this whitelist (and administrators) can access the Sales Pipeline system. Add sales team members here to grant them access.
</div>

<!-- Whitelisted Users Table -->
<table class="pipeline-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>User</th>
            <th>Username</th>
            <th>Email</th>
            <th>Permissions</th>
            <th>Added Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($whitelisted)) : ?>
            <tr>
                <td colspan="7" class="no-results">No whitelisted users found</td>
            </tr>
        <?php else : ?>
            <?php foreach ($whitelisted as $user) :
                $permissions = $user->permissions ? json_decode($user->permissions, true) : array();
            ?>
                <tr>
                    <td>#<?php echo $user->id; ?></td>
                    <td><?php echo esc_html($user->display_name); ?></td>
                    <td><?php echo esc_html($user->user_login); ?></td>
                    <td><?php echo esc_html($user->user_email); ?></td>
                    <td>
                        <?php if (is_array($permissions)) :
                            echo implode(', ', array_map('ucfirst', $permissions));
                        else :
                            echo 'Full Access';
                        endif; ?>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($user->created_at)); ?></td>
                    <td>
                        <button class="btn btn-sm btn-danger" onclick="removeUser(<?php echo $user->id; ?>, '<?php echo esc_js($user->display_name); ?>')">
                            <i class="houzez-icon icon-remove-circle"></i> Remove
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<!-- Add User Modal -->
<div id="addUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Add User to Whitelist</h3>
            <button class="close" onclick="closeAddUserModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="addUserForm">
                <div class="form-group">
                    <label class="required">Select User</label>
                    <select class="form-control" id="user_id" name="user_id" required>
                        <option value="">Select a user...</option>
                        <?php foreach ($all_users as $user) : ?>
                            <option value="<?php echo $user->ID; ?>">
                                <?php echo esc_html($user->display_name); ?> (<?php echo esc_html($user->user_email); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Permissions</label>
                    <div>
                        <label style="display: block; margin: 5px 0;">
                            <input type="checkbox" name="permissions[]" value="view" checked> View
                        </label>
                        <label style="display: block; margin: 5px 0;">
                            <input type="checkbox" name="permissions[]" value="edit" checked> Edit
                        </label>
                        <label style="display: block; margin: 5px 0;">
                            <input type="checkbox" name="permissions[]" value="delete" checked> Delete
                        </label>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeAddUserModal()">Cancel</button>
            <button class="btn btn-primary" onclick="addUser()">Add User</button>
        </div>
    </div>
</div>

<script>
function openAddUserModal() {
    <?php if (empty($all_users)) : ?>
        alert('All users are already whitelisted!');
        return;
    <?php endif; ?>

    document.getElementById('addUserModal').style.display = 'block';
}

function closeAddUserModal() {
    document.getElementById('addUserModal').style.display = 'none';
}

function addUser() {
    const userId = document.getElementById('user_id').value;
    if (!userId) {
        alert('Please select a user');
        return;
    }

    jQuery.ajax({
        url: '<?php echo admin_url("admin-ajax.php"); ?>',
        type: 'POST',
        data: {
            action: 'add_whitelist_user',
            user_id: userId,
            nonce: '<?php echo wp_create_nonce("add_whitelist_user"); ?>'
        },
        success: function(response) {
            if (response.success) {
                alert('User added to whitelist successfully!');
                location.reload();
            } else {
                alert('Error: ' + (response.data || 'Unknown error'));
            }
        },
        error: function() {
            alert('An error occurred while adding the user.');
        }
    });
}

function removeUser(whitelistId, userName) {
    if (!confirm('Are you sure you want to remove ' + userName + ' from the whitelist?')) {
        return;
    }

    jQuery.ajax({
        url: '<?php echo admin_url("admin-ajax.php"); ?>',
        type: 'POST',
        data: {
            action: 'remove_whitelist_user',
            whitelist_id: whitelistId,
            nonce: '<?php echo wp_create_nonce("remove_whitelist_user"); ?>'
        },
        success: function(response) {
            if (response.success) {
                alert('User removed from whitelist successfully!');
                location.reload();
            } else {
                alert('Error: ' + (response.data || 'Unknown error'));
            }
        },
        error: function() {
            alert('An error occurred while removing the user.');
        }
    });
}

window.onclick = function(event) {
    if (event.target.id === 'addUserModal') {
        closeAddUserModal();
    }
}
</script>
