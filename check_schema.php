<?php
// File: check_schema.php
// Purpose: Check the actual database schema for users table

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', __DIR__ . '/');

require_once ROOT_PATH . 'config/config.php';
require_once ROOT_PATH . 'config/session.php';

requireAdmin();

// Get table structure
$stmt = $pdo->query("DESCRIBE users");
$columns = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Database Schema</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">🗄️ Users Table Schema</h4>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Field</th>
                            <th>Type</th>
                            <th>Null</th>
                            <th>Key</th>
                            <th>Default</th>
                            <th>Extra</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($columns as $col): ?>
                            <tr <?php echo in_array($col['Field'], ['role', 'status']) ? 'class="table-warning"' : ''; ?>>
                                <td><strong><?php echo $col['Field']; ?></strong></td>
                                <td><code><?php echo $col['Type']; ?></code></td>
                                <td><?php echo $col['Null']; ?></td>
                                <td><?php echo $col['Key']; ?></td>
                                <td><?php echo $col['Default'] ?? 'NULL'; ?></td>
                                <td><?php echo $col['Extra']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="alert alert-info mt-4">
                    <h5>📋 Key Information:</h5>
                    <ul>
                        <?php foreach ($columns as $col): ?>
                            <?php if ($col['Field'] === 'role'): ?>
                                <li><strong>Role Field:</strong> Type is <code><?php echo $col['Type']; ?></code></li>
                            <?php endif; ?>
                            <?php if ($col['Field'] === 'status'): ?>
                                <li><strong>Status Field:</strong> Type is <code><?php echo $col['Type']; ?></code></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                    
                    <?php
                    $roleCol = array_filter($columns, fn($c) => $c['Field'] === 'role')[0] ?? null;
                    if ($roleCol && strpos($roleCol['Type'], 'enum') !== false):
                    ?>
                        <div class="alert alert-warning">
                            <strong>⚠️ Role is ENUM type:</strong> This means it only accepts specific values.
                            <?php
                            // Extract ENUM values
                            preg_match("/^enum\('(.*)'\)$/", $roleCol['Type'], $matches);
                            if (isset($matches[1])) {
                                $values = explode("','", $matches[1]);
                                echo "<br>Allowed values: <code>" . implode('</code>, <code>', $values) . "</code>";
                            }
                            ?>
                        </div>
                    <?php endif; ?>

                    <?php
                    $statusCol = array_filter($columns, fn($c) => $c['Field'] === 'status')[0] ?? null;
                    if ($statusCol && strpos($statusCol['Type'], 'enum') !== false):
                    ?>
                        <div class="alert alert-warning">
                            <strong>⚠️ Status is ENUM type:</strong> This means it only accepts specific values.
                            <?php
                            // Extract ENUM values
                            preg_match("/^enum\('(.*)'\)$/", $statusCol['Type'], $matches);
                            if (isset($matches[1])) {
                                $values = explode("','", $matches[1]);
                                echo "<br>Allowed values: <code>" . implode('</code>, <code>', $values) . "</code>";
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                </div>

                <a href="pages/users/list_users.php" class="btn btn-primary">Back to User Management</a>
            </div>
        </div>
    </div>
</body>
</html>