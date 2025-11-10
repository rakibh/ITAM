<?php
// File: pages/users/view_user.php
// Purpose: View complete user profile with revision history (Admin only)

define('ROOT_PATH', dirname(dirname(__DIR__)) . '/');
$pageTitle = 'View User';

require_once ROOT_PATH . 'layouts/header.php';
requireAdmin();

$userId = intval($_GET['id'] ?? 0);

if (!$userId) {
    header('Location: list_users.php');
    exit();
}

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: list_users.php');
    exit();
}

// Get revision history (last 15)
$revisionStmt = $pdo->prepare("
    SELECT ur.*, 
           u.first_name, u.last_name, u.employee_id
    FROM user_revisions ur
    LEFT JOIN users u ON ur.changed_by = u.id
    WHERE ur.user_id = ?
    ORDER BY ur.changed_at DESC
    LIMIT 15
");
$revisionStmt->execute([$userId]);
$revisions = $revisionStmt->fetchAll();
?>

<?php require_once ROOT_PATH . 'layouts/sidebar.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-person-badge me-2"></i>User Profile</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-warning">
                    <i class="bi bi-pencil"></i> Edit
                </a>
                <a href="list_users.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>

    <div id="alert-container"></div>

    <!-- User Information Card -->
    <div class="card shadow mb-4">
        <div class="card-header">
            <h5 class="mb-0">User Information</h5>
        </div>
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
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">First Name</label>
                            <p class="form-control-plaintext"><?php echo htmlspecialchars($user['first_name']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Last Name</label>
                            <p class="form-control-plaintext"><?php echo htmlspecialchars($user['last_name'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Employee ID</label>
                            <p class="form-control-plaintext">
                                <span class="badge bg-primary"><?php echo htmlspecialchars($user['employee_id']); ?></span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Username</label>
                            <p class="form-control-plaintext"><?php echo htmlspecialchars($user['username'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Email</label>
                            <p class="form-control-plaintext">
                                <?php if ($user['email']): ?>
                                    <a href="mailto:<?php echo $user['email']; ?>"><?php echo htmlspecialchars($user['email']); ?></a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Phone 1</label>
                            <p class="form-control-plaintext">
                                <?php if ($user['phone_1']): ?>
                                    <a href="tel:<?php echo $user['phone_1']; ?>"><?php echo htmlspecialchars($user['phone_1']); ?></a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Phone 2</label>
                            <p class="form-control-plaintext">
                                <?php if ($user['phone_2']): ?>
                                    <a href="tel:<?php echo $user['phone_2']; ?>"><?php echo htmlspecialchars($user['phone_2']); ?></a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Role</label>
                            <p class="form-control-plaintext">
                                <span class="badge <?php echo $user['role'] === 'admin' ? 'bg-danger' : 'bg-info'; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Status</label>
                            <p class="form-control-plaintext">
                                <span class="badge status-<?php echo strtolower($user['status']); ?>">
                                    <?php echo $user['status']; ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Created At</label>
                            <p class="form-control-plaintext"><?php echo formatDate($user['created_at']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Last Updated</label>
                            <p class="form-control-plaintext"><?php echo formatDate($user['updated_at']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Revision History (Admin Only) -->
    <div class="card shadow">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-clock-history me-2"></i>Revision History
                <span class="badge bg-secondary"><?php echo count($revisions); ?> recent changes</span>
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($revisions)): ?>
                <p class="text-muted text-center py-3">No revision history available</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th width="200">Time</th>
                                <th width="200">Changed By</th>
                                <th>What Changed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($revisions as $revision): ?>
                            <tr>
                                <td>
                                    <small title="<?php echo formatDate($revision['changed_at']); ?>">
                                        <?php echo timeAgo($revision['changed_at']); ?>
                                    </small>
                                </td>
                                <td>
                                    <?php 
                                    if ($revision['first_name']) {
                                        echo htmlspecialchars($revision['first_name'] . ' ' . ($revision['last_name'] ?? ''));
                                        echo '<br><small class="text-muted">' . htmlspecialchars($revision['employee_id']) . '</small>';
                                    } else {
                                        echo '<span class="text-muted">System</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($revision['change_description']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once ROOT_PATH . 'layouts/footer.php'; ?>