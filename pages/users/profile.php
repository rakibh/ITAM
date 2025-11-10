<?php
// File: pages/users/profile.php
// Purpose: User's own profile page with ability to edit and change password

define('ROOT_PATH', dirname(dirname(__DIR__)) . '/');
$pageTitle = 'My Profile';

require_once ROOT_PATH . 'layouts/header.php';
requireLogin();

$userId = getCurrentUserId();

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Get flash message
$flash = getFlashMessage();
?>

<?php require_once ROOT_PATH . 'layouts/sidebar.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-person-circle me-2"></i>My Profile</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="edit_user.php?id=<?php echo $userId; ?>" class="btn btn-primary me-2">
                <i class="bi bi-pencil"></i> Edit Profile
            </a>
            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                <i class="bi bi-key"></i> Change Password
            </button>
        </div>
    </div>

    <div id="alert-container">
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show">
                <?php echo $flash['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Profile Card -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <!-- Profile Photo -->
                <div class="col-md-3 text-center mb-3">
                    <?php if ($user['profile_photo']): ?>
                        <img src="<?php echo BASE_URL; ?>uploads/profiles/<?php echo $user['profile_photo']; ?>" 
                             class="img-thumbnail rounded-circle" style="width: 200px; height: 200px; object-fit: cover;" 
                             alt="Profile Photo">
                    <?php else: ?>
                        <i class="bi bi-person-circle text-muted" style="font-size: 200px;"></i>
                    <?php endif; ?>
                </div>

                <!-- User Details -->
                <div class="col-md-9">
                    <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . ($user['last_name'] ?? '')); ?></h3>
                    <p class="text-muted mb-3">
                        <span class="badge <?php echo $user['role'] === 'admin' ? 'bg-danger' : 'bg-info'; ?>">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                        <span class="badge status-<?php echo strtolower($user['status']); ?> ms-2">
                            <?php echo $user['status']; ?>
                        </span>
                    </p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="fw-bold"><i class="bi bi-person-badge me-2"></i>Employee ID</label>
                            <p><?php echo htmlspecialchars($user['employee_id']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold"><i class="bi bi-person me-2"></i>Username</label>
                            <p><?php echo htmlspecialchars($user['username'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold"><i class="bi bi-envelope me-2"></i>Email</label>
                            <p>
                                <?php if ($user['email']): ?>
                                    <a href="mailto:<?php echo $user['email']; ?>"><?php echo htmlspecialchars($user['email']); ?></a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold"><i class="bi bi-telephone me-2"></i>Phone 1</label>
                            <p>
                                <?php if ($user['phone_1']): ?>
                                    <a href="tel:<?php echo $user['phone_1']; ?>"><?php echo htmlspecialchars($user['phone_1']); ?></a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold"><i class="bi bi-telephone me-2"></i>Phone 2</label>
                            <p>
                                <?php if ($user['phone_2']): ?>
                                    <a href="tel:<?php echo $user['phone_2']; ?>"><?php echo htmlspecialchars($user['phone_2']); ?></a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold"><i class="bi bi-calendar me-2"></i>Member Since</label>
                            <p><?php echo formatDate($user['created_at']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">My Tasks</h5>
                    <h2>
                        <?php
                        $taskStmt = $pdo->prepare("
                            SELECT COUNT(*) as count 
                            FROM todo_assignments 
                            WHERE user_id = ?
                        ");
                        $taskStmt->execute([$userId]);
                        echo $taskStmt->fetch()['count'];
                        ?>
                    </h2>
                    <a href="<?php echo BASE_URL; ?>pages/todos/list_todos.php" class="text-white">
                        View Tasks <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">Completed Tasks</h5>
                    <h2>
                        <?php
                        $completedStmt = $pdo->prepare("
                            SELECT COUNT(*) as count 
                            FROM todos t
                            JOIN todo_assignments ta ON t.id = ta.todo_id
                            WHERE ta.user_id = ? AND t.status = 'Completed'
                        ");
                        $completedStmt->execute([$userId]);
                        echo $completedStmt->fetch()['count'];
                        ?>
                    </h2>
                    <small>Total completed</small>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h5 class="card-title">Notifications</h5>
                    <h2>
                        <?php
                        $notifStmt = $pdo->prepare("
                            SELECT COUNT(*) as count 
                            FROM notification_user_status 
                            WHERE user_id = ? AND is_acknowledged = FALSE
                        ");
                        $notifStmt->execute([$userId]);
                        echo $notifStmt->fetch()['count'];
                        ?>
                    </h2>
                    <a href="<?php echo BASE_URL; ?>pages/notifications/list_notifications.php" class="text-white">
                        View All <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="changePasswordForm">
                <?php echo csrfField(); ?>
                <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Current Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="new_password" id="new_password" required>
                        <small class="text-muted">Min 6 chars, 1 letter, 1 number, 1 special char (!@#$%^&*)</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
                    </div>
                    <div id="password-alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Change Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$('#changePasswordForm').on('submit', function(e) {
    e.preventDefault();
    
    const newPassword = $('#new_password').val();
    const confirmPassword = $('#confirm_password').val();
    
    // Validate passwords match
    if (newPassword !== confirmPassword) {
        $('#password-alert').html('<div class="alert alert-danger">Passwords do not match</div>');
        return false;
    }
    
    // Validate password policy
    const passwordRegex = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{6,}$/;
    if (!passwordRegex.test(newPassword)) {
        $('#password-alert').html('<div class="alert alert-danger">Password must be 6+ chars with a letter, number, and special character</div>');
        return false;
    }
    
    $.ajax({
        url: '<?php echo BASE_URL; ?>ajax/user_operations.php',
        type: 'POST',
        data: $(this).serialize() + '&action=change_password',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#changePasswordModal').modal('hide');
                showAlert('success', response.message);
                $('#changePasswordForm')[0].reset();
                $('#password-alert').html('');
            } else {
                $('#password-alert').html('<div class="alert alert-danger">' + response.message + '</div>');
            }
        },
        error: function(xhr) {
            $('#password-alert').html('<div class="alert alert-danger">' + 
                (xhr.responseJSON?.message || 'Error changing password') + '</div>');
        }
    });
});

// Clear alert when modal closes
$('#changePasswordModal').on('hidden.bs.modal', function () {
    $('#password-alert').html('');
    $('#changePasswordForm')[0].reset();
});
</script>

<?php require_once ROOT_PATH . 'layouts/footer.php'; ?>