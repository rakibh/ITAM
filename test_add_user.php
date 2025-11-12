<?php
// File: test_add_user.php
// Purpose: Debug tool to test user addition directly

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', __DIR__ . '/');

require_once ROOT_PATH . 'config/config.php';
require_once ROOT_PATH . 'config/session.php';
require_once ROOT_PATH . 'includes/functions.php';

requireAdmin();

$result = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_add'])) {
    try {
        $firstName = 'Test';
        $lastName = 'User';
        $employeeId = 'TEST-' . time();
        $password = 'Test@123';
        $role = 'user';
        $status = 'Active';
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO users (first_name, last_name, employee_id, password, role, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$firstName, $lastName, $employeeId, $hashedPassword, $role, $status]);
        $userId = $pdo->lastInsertId();
        
        $result = "✅ Success! User created with ID: {$userId}<br>";
        $result .= "Employee ID: {$employeeId}<br>";
        $result .= "Password: {$password}<br>";
        
    } catch (Exception $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}

// Test database connection
$dbTest = '';
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $count = $stmt->fetch()['count'];
    $dbTest = "✅ Database connected. Total users: {$count}";
} catch (Exception $e) {
    $dbTest = "❌ Database error: " . $e->getMessage();
}

// Test table structure
$tableTest = '';
try {
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $tableTest = "✅ Users table columns: " . implode(', ', $columns);
} catch (Exception $e) {
    $tableTest = "❌ Table error: " . $e->getMessage();
}

// Test password hashing
$passwordTest = '';
try {
    $testPass = 'Test@123';
    $hash = password_hash($testPass, PASSWORD_DEFAULT);
    $verify = password_verify($testPass, $hash);
    $passwordTest = $verify ? "✅ Password hashing works" : "❌ Password hashing failed";
} catch (Exception $e) {
    $passwordTest = "❌ Password error: " . $e->getMessage();
}

// Test CSRF token
$csrfTest = '';
try {
    $token = getCsrfToken();
    $verify = verifyCsrfToken($token);
    $csrfTest = $verify ? "✅ CSRF token works" : "❌ CSRF token failed";
} catch (Exception $e) {
    $csrfTest = "❌ CSRF error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Add Test</title>
    <link href="<?php echo BASE_URL; ?>assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">🔧 User Add Debugging Tool</h4>
                    </div>
                    <div class="card-body">
                        
                        <!-- System Tests -->
                        <h5>System Tests:</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2"><?php echo $dbTest; ?></li>
                            <li class="mb-2"><?php echo $tableTest; ?></li>
                            <li class="mb-2"><?php echo $passwordTest; ?></li>
                            <li class="mb-2"><?php echo $csrfTest; ?></li>
                            <li class="mb-2">✅ PHP Version: <?php echo PHP_VERSION; ?></li>
                            <li class="mb-2">✅ Session ID: <?php echo session_id(); ?></li>
                            <li class="mb-2">✅ Current User: <?php echo getCurrentUserName(); ?> (ID: <?php echo getCurrentUserId(); ?>)</li>
                            <li class="mb-2">✅ Is Admin: <?php echo isAdmin() ? 'Yes' : 'No'; ?></li>
                        </ul>

                        <hr>

                        <!-- Test Add User -->
                        <h5>Test User Addition:</h5>
                        <form method="POST">
                            <button type="submit" name="test_add" class="btn btn-primary">
                                Add Test User
                            </button>
                        </form>

                        <?php if ($result): ?>
                            <div class="alert alert-success mt-3">
                                <?php echo $result; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-danger mt-3">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <hr>

                        <!-- Recent Users -->
                        <h5>Recent Users (Last 5):</h5>
                        <?php
                        try {
                            $stmt = $pdo->query("SELECT id, first_name, last_name, employee_id, role, status, created_at FROM users ORDER BY created_at DESC LIMIT 5");
                            $users = $stmt->fetchAll();
                            
                            if (!empty($users)) {
                                echo '<table class="table table-sm table-striped">';
                                echo '<thead><tr><th>ID</th><th>Name</th><th>Employee ID</th><th>Role</th><th>Status</th><th>Created</th></tr></thead>';
                                echo '<tbody>';
                                foreach ($users as $user) {
                                    echo '<tr>';
                                    echo '<td>' . $user['id'] . '</td>';
                                    echo '<td>' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '</td>';
                                    echo '<td>' . htmlspecialchars($user['employee_id']) . '</td>';
                                    echo '<td>' . $user['role'] . '</td>';
                                    echo '<td>' . $user['status'] . '</td>';
                                    echo '<td>' . $user['created_at'] . '</td>';
                                    echo '</tr>';
                                }
                                echo '</tbody></table>';
                            } else {
                                echo '<p class="text-muted">No users found</p>';
                            }
                        } catch (Exception $e) {
                            echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
                        }
                        ?>

                        <hr>

                        <!-- PHP Error Log -->
                        <h5>Recent PHP Errors:</h5>
                        <?php
                        $errorLog = ROOT_PATH . 'logs/php_error.log';
                        if (file_exists($errorLog)) {
                            $errors = file($errorLog);
                            $recentErrors = array_slice($errors, -10);
                            if (!empty($recentErrors)) {
                                echo '<pre class="bg-dark text-white p-3" style="max-height: 300px; overflow-y: auto; font-size: 0.85rem;">';
                                echo htmlspecialchars(implode('', $recentErrors));
                                echo '</pre>';
                            } else {
                                echo '<p class="text-success">No errors found</p>';
                            }
                        } else {
                            echo '<p class="text-muted">Error log file not found</p>';
                        }
                        ?>

                        <hr>

                        <div class="text-center">
                            <a href="<?php echo BASE_URL; ?>pages/users/list_users.php" class="btn btn-secondary">
                                Back to User Management
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>