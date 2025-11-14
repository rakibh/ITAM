<?php
// File: pages/users/list_users.php
// Purpose: List all users with search, filter, and pagination

define('ROOT_PATH', dirname(dirname(__DIR__)) . '/');
$pageTitle = 'User Management';

require_once ROOT_PATH . 'layouts/header.php';
requireAdmin();

// Pagination and filters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = DEFAULT_PER_PAGE;
$offset = ($page - 1) * $perPage;

$search = sanitize($_GET['search'] ?? '');
$roleFilter = sanitize($_GET['role'] ?? '');
$statusFilter = sanitize($_GET['status'] ?? '');

// Build query
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(first_name LIKE ? OR last_name LIKE ? OR employee_id LIKE ? OR username LIKE ? OR email LIKE ?)";
    $searchTerm = "%{$search}%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($roleFilter)) {
    $where[] = "role = ?";
    $params[] = $roleFilter;
}

if (!empty($statusFilter)) {
    $where[] = "status = ?";
    $params[] = $statusFilter;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM users {$whereClause}");
$countStmt->execute($params);
$totalUsers = $countStmt->fetch()['total'];
$totalPages = ceil($totalUsers / $perPage);

// Get users
$stmt = $pdo->prepare("
    SELECT * FROM users 
    {$whereClause}
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
");
$params[] = $perPage;
$params[] = $offset;
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<?php require_once ROOT_PATH . 'layouts/sidebar.php'; ?>

<style>
    /* Ensure badges are visible */
    .badge {
        display: inline-block !important;
        padding: 0.35em 0.65em !important;
        font-size: 0.875rem !important;
        font-weight: 600 !important;
        line-height: 1 !important;
        color: #fff !important;
        text-align: center !important;
        white-space: nowrap !important;
        vertical-align: baseline !important;
        border-radius: 0.25rem !important;
    }
    
    .badge.bg-danger {
        background-color: #dc3545 !important;
    }
    
    .badge.bg-info {
        background-color: #0dcaf0 !important;
    }
    
    .badge.bg-success {
        background-color: #198754 !important;
    }
    
    .badge.bg-secondary {
        background-color: #6c757d !important;
    }
</style>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-people me-2"></i>User Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-plus-circle me-1"></i>Add User
            </button>
        </div>
    </div>

    <div id="alert-container"></div>

    <!-- Search and Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="search" placeholder="Search users..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="role">
                        <option value="">All Roles</option>
                        <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="user" <?php echo $roleFilter === 'user' ? 'selected' : ''; ?>>User</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="Active" <?php echo $statusFilter === 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?php echo $statusFilter === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-header">
            <h6 class="m-0">Total Users: <?php echo $totalUsers; ?></h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Employee ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">No users found</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($users as $index => $user): ?>
                            <tr>
                                <td><?php echo $offset + $index + 1; ?></td>
                                <td>
                                    <?php if ($user['profile_photo']): ?>
                                        <img src="<?php echo BASE_URL; ?>uploads/profiles/<?php echo $user['profile_photo']; ?>" 
                                             class="rounded-circle" width="30" height="30" alt="Profile">
                                    <?php else: ?>
                                        <i class="bi bi-person-circle fs-4"></i>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . ($user['last_name'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($user['employee_id']); ?></td>
                                <td><?php echo htmlspecialchars($user['username'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php 
                                    // Ensure we're comparing exact string values
                                    $userRole = trim(strtolower($user['role']));
                                    if ($userRole === 'admin'): 
                                    ?>
                                        <span class="badge bg-danger">Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">User</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    // Ensure we're comparing exact string values
                                    $userStatus = trim($user['status']);
                                    if ($userStatus === 'Active'): 
                                    ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatDate($user['created_at']); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="view_user.php?id=<?php echo $user['id']; ?>" class="btn btn-outline-primary" 
                                           target="_blank" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-outline-warning" 
                                           target="_blank" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-info" 
                                                onclick="resetPassword(<?php echo $user['id']; ?>)" title="Reset Password">
                                            <i class="bi bi-key"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['first_name']); ?>')" 
                                                title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <?php echo getPaginationHTML($page, $totalPages, 'list_users.php'); ?>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addUserForm">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Employee ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="employee_id" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone 1</label>
                            <input type="tel" class="form-control" name="phone_1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone 2</label>
                            <input type="tel" class="form-control" name="phone_2">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="password" required>
                            <small class="text-muted">Min 6 chars, 1 letter, 1 number, 1 special char (!@#$%^&*)</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select" name="role" required>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" required>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="resetPasswordForm">
                <input type="hidden" name="user_id" id="reset_user_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" required>
                        <small class="text-muted">Min 6 chars, 1 letter, 1 number, 1 special char (!@#$%^&*)</small>
                    </div>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        User will be required to change this password on next login.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Store CSRF token and Base URL
const CSRF_TOKEN = '<?php echo getCsrfToken(); ?>';
const BASE_URL = '<?php echo BASE_URL; ?>';

console.log('🔐 Page loaded with CSRF Token:', CSRF_TOKEN.substring(0, 20) + '...');
console.log('🌐 Base URL:', BASE_URL);

// Add User Form Submission
$('#addUserForm').on('submit', function(e) {
    e.preventDefault();
    console.log('📝 Add User Form Submitted');
    
    // Collect form data
    const formData = {
        first_name: $('input[name="first_name"]').val(),
        last_name: $('input[name="last_name"]').val(),
        employee_id: $('input[name="employee_id"]').val(),
        username: $('input[name="username"]').val(),
        email: $('input[name="email"]').val(),
        phone_1: $('input[name="phone_1"]').val(),
        phone_2: $('input[name="phone_2"]').val(),
        password: $('input[name="password"]').val(),
        role: $('select[name="role"]').val(),
        status: $('select[name="status"]').val(),
        action: 'add',
        csrf_token: CSRF_TOKEN
    };
    
    console.log('📤 Sending data:', formData);
    
    // Disable submit button
    const submitBtn = $(this).find('button[type="submit"]');
    const originalText = submitBtn.html();
    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Creating...');
    
    $.ajax({
        url: BASE_URL + 'ajax/user_operations.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            console.log('✅ Success:', response);
            
            if (response.success) {
                $('#addUserModal').modal('hide');
                showAlert('success', response.message);
                
                // Reload page after 1.5 seconds
                setTimeout(function() {
                    window.location.reload();
                }, 1500);
            } else {
                showAlert('danger', response.message);
                submitBtn.prop('disabled', false).html(originalText);
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ AJAX Error:', {
                status: xhr.status,
                statusText: xhr.statusText,
                responseText: xhr.responseText,
                error: error
            });
            
            let errorMsg = 'Error adding user';
            
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.message) {
                    errorMsg = response.message;
                    if (response.file && response.line) {
                        errorMsg += ` (${response.file}:${response.line})`;
                    }
                }
            } catch(e) {
                errorMsg = xhr.responseText || xhr.statusText || 'Unknown error';
            }
            
            showAlert('danger', errorMsg);
            submitBtn.prop('disabled', false).html(originalText);
        }
    });
});

// Reset Password
function resetPassword(userId) {
    console.log('🔑 Reset password for user:', userId);
    $('#reset_user_id').val(userId);
    $('#resetPasswordModal').modal('show');
}

$('#resetPasswordForm').on('submit', function(e) {
    e.preventDefault();
    console.log('🔑 Reset Password Form Submitted');
    
    const submitBtn = $(this).find('button[type="submit"]');
    const originalText = submitBtn.html();
    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Resetting...');
    
    const data = $(this).serialize() + '&action=reset_password&csrf_token=' + CSRF_TOKEN;
    
    $.ajax({
        url: BASE_URL + 'ajax/user_operations.php',
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
            console.log('✅ Reset Success:', response);
            
            if (response.success) {
                $('#resetPasswordModal').modal('hide');
                showAlert('success', response.message);
            } else {
                showAlert('danger', response.message);
            }
            
            submitBtn.prop('disabled', false).html(originalText);
        },
        error: function(xhr) {
            console.error('❌ Reset Error:', xhr);
            
            let errorMsg = 'Error resetting password';
            try {
                const response = JSON.parse(xhr.responseText);
                errorMsg = response.message || errorMsg;
            } catch(e) {}
            
            showAlert('danger', errorMsg);
            submitBtn.prop('disabled', false).html(originalText);
        }
    });
});

// Delete User
function deleteUser(userId, userName) {
    console.log('🗑️ Delete user:', userId, userName);
    
    if (confirm(`Are you sure you want to delete user "${userName}"?\n\nThis action cannot be undone.`)) {
        $.ajax({
            url: BASE_URL + 'ajax/user_operations.php',
            type: 'POST',
            data: { 
                action: 'delete', 
                user_id: userId,
                csrf_token: CSRF_TOKEN
            },
            dataType: 'json',
            success: function(response) {
                console.log('✅ Delete Success:', response);
                
                if (response.success) {
                    showAlert('success', response.message);
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function(xhr) {
                console.error('❌ Delete Error:', xhr);
                
                let errorMsg = 'Error deleting user';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMsg = response.message || errorMsg;
                } catch(e) {}
                
                showAlert('danger', errorMsg);
            }
        });
    }
}

// Clear form when modal closes
$('#addUserModal').on('hidden.bs.modal', function () {
    $('#addUserForm')[0].reset();
    console.log('🧹 Form cleared');
});

// Log when modal opens
$('#addUserModal').on('show.bs.modal', function () {
    console.log('📋 Add User Modal Opened');
});
</script>

<?php require_once ROOT_PATH . 'layouts/footer.php'; ?>