<?php
// File: fix_user_roles_status.php
// Purpose: Fix any users with missing role or status

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', __DIR__ . '/');

require_once ROOT_PATH . 'config/config.php';
require_once ROOT_PATH . 'config/session.php';
require_once ROOT_PATH . 'includes/functions.php';

requireAdmin();

$fixed = [];
$errors = [];

// Check for users with NULL or empty role
try {
    $stmt = $pdo->query("SELECT id, first_name, last_name, employee_id, role, status FROM users");
    $users = $stmt->fetchAll();
    
    foreach ($users as $user) {
        $needsUpdate = false;
        $updates = [];
        
        // Fix role if empty or NULL
        if (empty($user['role']) || $user['role'] === NULL) {
            $updates[] = "role = 'user'";
            $needsUpdate = true;
        }
        
        // Fix status if empty or NULL
        if (empty($user['status']) || $user['status'] === NULL) {
            $updates[] = "status = 'Active'";
            $needsUpdate = true;
        }
        
        if ($needsUpdate) {
            $updateSQL = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            $updateStmt = $pdo->prepare($updateSQL);
            $updateStmt->execute([$user['id']]);
            
            $fixed[] = [
                'id' => $user['id'],
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'employee_id' => $user['employee_id'],
                'updates' => $updates
            ];
        }
    }
    
} catch (Exception $e) {
    $errors[] = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix User Data</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">🔧 Fix User Roles & Status</h4>
                    </div>
                    <div class="card-body">
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <h5>❌ Errors:</h5>
                                <ul>
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (empty($fixed)): ?>
                            <div class="alert alert-success">
                                <h5>✅ All Good!</h5>
                                <p class="mb-0">All users have valid role and status values.</p>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <h5>✅ Fixed <?php echo count($fixed); ?> User(s)</h5>
                            </div>
                            
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Employee ID</th>
                                        <th>Updates Applied</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fixed as $item): ?>
                                        <tr>
                                            <td><?php echo $item['id']; ?></td>
                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['employee_id']); ?></td>
                                            <td>
                                                <?php foreach ($item['updates'] as $update): ?>
                                                    <span class="badge bg-info"><?php echo $update; ?></span>
                                                <?php endforeach; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                        
                        <hr>
                        
                        <!-- Current Users Table -->
                        <h5>Current Users:</h5>
                        <?php
                        $stmt = $pdo->query("SELECT id, first_name, last_name, employee_id, role, status FROM users ORDER BY id");
                        $allUsers = $stmt->fetchAll();
                        ?>
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Employee ID</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allUsers as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['employee_id']); ?></td>
                                        <td>
                                            <?php if ($user['role'] === 'admin'): ?>
                                                <span class="badge bg-danger">Admin</span>
                                            <?php elseif ($user['role'] === 'user'): ?>
                                                <span class="badge bg-info">User</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">NULL/Empty</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($user['status'] === 'Active'): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php elseif ($user['status'] === 'Inactive'): ?>
                                                <span class="badge bg-warning">Inactive</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">NULL/Empty</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="text-center mt-4">
                            <a href="pages/users/list_users.php" class="btn btn-primary">
                                Go to User Management
                            </a>
                            <button class="btn btn-secondary" onclick="location.reload()">
                                Run Check Again
                            </button>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>