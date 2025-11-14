<?php
// File: test_add_detailed.php
// Purpose: Test user addition with detailed debugging

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', __DIR__ . '/');

require_once ROOT_PATH . 'config/config.php';
require_once ROOT_PATH . 'config/session.php';
require_once ROOT_PATH . 'includes/functions.php';

requireAdmin();

$result = '';
$error = '';
$userId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_add'])) {
    try {
        $firstName = $_POST['first_name'];
        $lastName = $_POST['last_name'];
        $employeeId = $_POST['employee_id'];
        $password = $_POST['password'];
        $role = $_POST['role']; // Don't sanitize
        $status = $_POST['status']; // Don't sanitize
        
        $result .= "<h5>📝 Input Values:</h5>";
        $result .= "<pre>";
        $result .= "First Name: " . var_export($firstName, true) . "\n";
        $result .= "Last Name: " . var_export($lastName, true) . "\n";
        $result .= "Employee ID: " . var_export($employeeId, true) . "\n";
        $result .= "Password: " . var_export($password, true) . "\n";
        $result .= "Role: " . var_export($role, true) . " (type: " . gettype($role) . ")\n";
        $result .= "Status: " . var_export($status, true) . " (type: " . gettype($status) . ")\n";
        $result .= "</pre>";
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user with exact values
        $stmt = $pdo->prepare("
            INSERT INTO users (first_name, last_name, employee_id, password, role, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $result .= "<h5>💾 Values Being Saved:</h5>";
        $result .= "<pre>";
        $result .= "Role: '$role'\n";
        $result .= "Status: '$status'\n";
        $result .= "</pre>";
        
        $stmt->execute([$firstName, $lastName, $employeeId, $hashedPassword, $role, $status]);
        $userId = $pdo->lastInsertId();
        
        // Verify what was actually saved
        $verifyStmt = $pdo->prepare("SELECT role, status FROM users WHERE id = ?");
        $verifyStmt->execute([$userId]);
        $saved = $verifyStmt->fetch();
        
        $result .= "<h5>✅ User Created with ID: {$userId}</h5>";
        
        $result .= "<h5>🔍 Verification - What's in Database:</h5>";
        $result .= "<pre>";
        $result .= "Saved Role: " . var_export($saved['role'], true) . "\n";
        $result .= "Saved Status: " . var_export($saved['status'], true) . "\n";
        $result .= "</pre>";
        
        $result .= "<h5>🔎 Comparison:</h5>";
        $result .= "<table class='table table-bordered'>";
        $result .= "<tr><th>Field</th><th>Sent</th><th>Saved</th><th>Match?</th></tr>";
        $result .= "<tr><td>Role</td><td><code>{$role}</code></td><td><code>{$saved['role']}</code></td><td>" . ($role === $saved['role'] ? '✅' : '❌') . "</td></tr>";
        $result .= "<tr><td>Status</td><td><code>{$status}</code></td><td><code>{$saved['status']}</code></td><td>" . ($status === $saved['status'] ? '✅' : '❌') . "</td></tr>";
        $result .= "</table>";
        
    } catch (Exception $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detailed User Add Test</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">🔬 Detailed User Add Test</h4>
                    </div>
                    <div class="card-body">
                        
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">First Name</label>
                                    <input type="text" class="form-control" name="first_name" value="Test" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" class="form-control" name="last_name" value="User">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Employee ID</label>
                                    <input type="text" class="form-control" name="employee_id" 
                                           value="TEST-<?php echo time(); ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Password</label>
                                    <input type="text" class="form-control" name="password" value="Test@123" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Role</label>
                                    <select class="form-select" name="role" required>
                                        <option value="user">User</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status" required>
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="test_add" class="btn btn-primary btn-lg">
                                        🧪 Test Add User
                                    </button>
                                </div>
                            </div>
                        </form>

                        <?php if ($error): ?>
                            <div class="alert alert-danger mt-3">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($result): ?>
                            <hr class="my-4">
                            <div class="alert alert-info">
                                <?php echo $result; ?>
                            </div>
                            
                            <?php if ($userId): ?>
                                <div class="mt-3">
                                    <a href="pages/users/list_users.php" class="btn btn-success">
                                        View User List
                                    </a>
                                    <a href="pages/users/view_user.php?id=<?php echo $userId; ?>" 
                                       class="btn btn-info" target="_blank">
                                        View Created User
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <hr>

                        <!-- All Users -->
                        <h5>All Users in Database:</h5>
                        <?php
                        $stmt = $pdo->query("SELECT id, first_name, employee_id, role, status FROM users ORDER BY id DESC LIMIT 10");
                        $users = $stmt->fetchAll();
                        ?>
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Employee ID</th>
                                    <th>Role (raw)</th>
                                    <th>Status (raw)</th>
                                    <th>Role Type</th>
                                    <th>Status Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['first_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['employee_id']); ?></td>
                                        <td><code><?php echo var_export($user['role'], true); ?></code></td>
                                        <td><code><?php echo var_export($user['status'], true); ?></code></td>
                                        <td><?php echo gettype($user['role']); ?></td>
                                        <td><?php echo gettype($user['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>