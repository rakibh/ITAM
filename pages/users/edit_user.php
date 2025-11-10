<?php
// File: pages/users/edit_user.php
// Purpose: Edit user information (Admin can edit all, User can edit own profile except role/employee_id/status)

define('ROOT_PATH', dirname(dirname(__DIR__)) . '/');
$pageTitle = 'Edit User';

require_once ROOT_PATH . 'layouts/header.php';
requireLogin();

$userId = intval($_GET['id'] ?? 0);
$currentUserId = getCurrentUserId();
$isAdmin = isAdmin();

if (!$userId) {
    header('Location: ' . ($isAdmin ? 'list_users.php' : 'profile.php'));
    exit();
}

// Check permissions: Admin can edit anyone, User can only edit self
if (!$isAdmin && $userId != $currentUserId) {
    header('Location: profile.php');
    exit();
}

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: ' . ($isAdmin ? 'list_users.php' : 'profile.php'));
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if (verifyCsrfToken($_POST['csrf_token'])) {
        try {
            $firstName = sanitize($_POST['first_name'] ?? '');
            $lastName = sanitize($_POST['last_name'] ?? '');
            $username = sanitize($_POST['username'] ?? '') ?: null;
            $email = sanitize($_POST['email'] ?? '') ?: null;
            $phone1 = sanitize($_POST['phone_1'] ?? '') ?: null;
            $phone2 = sanitize($_POST['phone_2'] ?? '') ?: null;
            
            // Admin-only fields
            $employeeId = $isAdmin ? sanitize($_POST['employee_id'] ?? '') : $user['employee_id'];
            $role = $isAdmin ? sanitize($_POST['role'] ?? 'user') : $user['role'];
            $status = $isAdmin ? sanitize($_POST['status'] ?? 'Active') : $user['status'];
            
            // Validation
            if (empty($firstName) || empty($employeeId)) {
                throw new Exception('First Name and Employee ID are required');
            }
            
            // Check username uniqueness
            if ($username) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                $stmt->execute([$username, $userId]);
                if ($stmt->fetch()) {
                    throw new Exception('Username already exists');
                }
            }
            
            // Check email uniqueness
            if ($email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Invalid email format');
                }
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $userId]);
                if ($stmt->fetch()) {
                    throw new Exception('Email already exists');
                }
            }
            
            // Handle profile photo upload
            $profilePhoto = $user['profile_photo'];
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                $uploadResult = uploadFile($_FILES['profile_photo'], PROFILE_UPLOAD_PATH, $allowedTypes, MAX_PROFILE_SIZE);
                
                if ($uploadResult['success']) {
                    // Delete old photo
                    if ($user['profile_photo']) {
                        deleteFile(PROFILE_UPLOAD_PATH . $user['profile_photo']);
                    }
                    $profilePhoto = $uploadResult['filename'];
                } else {
                    throw new Exception($uploadResult['message']);
                }
            }
            
            // Update user
            $stmt = $pdo->prepare("
                UPDATE users SET 
                    profile_photo = ?,
                    first_name = ?,
                    last_name = ?,
                    employee_id = ?,
                    username = ?,
                    email = ?,
                    phone_1 = ?,
                    phone_2 = ?,
                    role = ?,
                    status = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $profilePhoto, $firstName, $lastName, $employeeId, $username, $email,
                $phone1, $phone2, $role, $status, $userId
            ]);
            
            // Add revision
            addRevision($pdo, 'users', $userId, 'Profile updated by ' . getCurrentUserName());
            
            // Log activity
            logActivity($pdo, 'User Management', 'Update User', 
                       "Updated user: {$firstName} ({$employeeId})", 'Info');
            
            // Create notification
            createNotification($pdo, 'User', 'update', 'User Profile Updated', 
                              getCurrentUserName() . " updated profile: {$firstName} ({$employeeId})", 
                              ['user_id' => $userId]);
            
            setFlashMessage('success', 'User updated successfully');
            header('Location: ' . ($isAdmin ? 'view_user.php?id=' . $userId : 'profile.php'));
            exit();
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } else {
        $error = 'Invalid CSRF token';
    }
}

// Get flash message
$flash = getFlashMessage();
?>

<?php require_once ROOT_PATH . 'layouts/sidebar.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-pencil-square me-2"></i>Edit User</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?php echo $isAdmin ? 'view_user.php?id=' . $userId : 'profile.php'; ?>" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> Cancel
            </a>
        </div>
    </div>

    <div id="alert-container">
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show">
                <?php echo $flash['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data" id="editUserForm">
                <?php echo csrfField(); ?>
                
                <div class="row g-3">
                    <!-- Profile Photo -->
                    <div class="col-12 text-center mb-3">
                        <div class="mb-3">
                            <?php if ($user['profile_photo']): ?>
                                <img src="<?php echo BASE_URL; ?>uploads/profiles/<?php echo $user['profile_photo']; ?>" 
                                     class="img-thumbnail rounded-circle" id="profilePreview"
                                     style="width: 150px; height: 150px; object-fit: cover;" alt="Profile">
                            <?php else: ?>
                                <i class="bi bi-person-circle text-muted" id="profilePreview" style="font-size: 150px;"></i>
                            <?php endif; ?>
                        </div>
                        <input type="file" class="form-control" name="profile_photo" id="profile_photo" 
                               accept="image/jpeg,image/jpg,image/png">
                        <small class="text-muted">Max 5MB, formats: JPEG, JPG, PNG</small>
                    </div>

                    <!-- First Name -->
                    <div class="col-md-6">
                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="first_name" 
                               value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                    </div>

                    <!-- Last Name -->
                    <div class="col-md-6">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control" name="last_name" 
                               value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                    </div>

                    <!-- Employee ID (Admin only can edit) -->
                    <div class="col-md-6">
                        <label class="form-label">Employee ID <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="employee_id" 
                               value="<?php echo htmlspecialchars($user['employee_id']); ?>" 
                               <?php echo !$isAdmin ? 'readonly' : ''; ?> required>
                    </div>

                    <!-- Username -->
                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" 
                               value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>">
                    </div>

                    <!-- Email -->
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" 
                               value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                    </div>

                    <!-- Phone 1 -->
                    <div class="col-md-6">
                        <label class="form-label">Phone 1</label>
                        <input type="tel" class="form-control" name="phone_1" 
                               value="<?php echo htmlspecialchars($user['phone_1'] ?? ''); ?>">
                    </div>

                    <!-- Phone 2 -->
                    <div class="col-md-6">
                        <label class="form-label">Phone 2</label>
                        <input type="tel" class="form-control" name="phone_2" 
                               value="<?php echo htmlspecialchars($user['phone_2'] ?? ''); ?>">
                    </div>

                    <?php if ($isAdmin): ?>
                    <!-- Role (Admin only) -->
                    <div class="col-md-6">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select" name="role" required>
                            <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>

                    <!-- Status (Admin only) -->
                    <div class="col-md-6">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select" name="status" required>
                            <option value="Active" <?php echo $user['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo $user['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <?php endif; ?>

                    <!-- Submit Button -->
                    <div class="col-12">
                        <hr class="my-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Update User
                        </button>
                        <a href="<?php echo $isAdmin ? 'view_user.php?id=' . $userId : 'profile.php'; ?>" 
                           class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
// Profile photo preview
document.getElementById('profile_photo').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('profilePreview');
            if (preview.tagName === 'IMG') {
                preview.src = e.target.result;
            } else {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'img-thumbnail rounded-circle';
                img.style.cssText = 'width: 150px; height: 150px; object-fit: cover;';
                preview.replaceWith(img);
            }
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php require_once ROOT_PATH . 'layouts/footer.php'; ?>