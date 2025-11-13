<?php
// File: ajax/user_operations.php
// Purpose: Handle user CRUD operations via AJAX

// Prevent any output before JSON
ob_start();

require_once '../config/config.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

// Clear any previous output
ob_clean();

requireAdmin(); // Only admins can manage users

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            // Validate CSRF token
            if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
                throw new Exception('Invalid CSRF token');
            }
            
            // Validate required fields
            $firstName = sanitize($_POST['first_name'] ?? '');
            $lastName = sanitize($_POST['last_name'] ?? '');
            $employeeId = sanitize($_POST['employee_id'] ?? '');
            $username = sanitize($_POST['username'] ?? '') ?: null;
            $email = sanitize($_POST['email'] ?? '') ?: null;
            $phone1 = sanitize($_POST['phone_1'] ?? '') ?: null;
            $phone2 = sanitize($_POST['phone_2'] ?? '') ?: null;
            $password = $_POST['password'] ?? '';
            $role = sanitize($_POST['role'] ?? 'user');
            $status = sanitize($_POST['status'] ?? 'Active');
            
            if (empty($firstName) || empty($employeeId) || empty($password)) {
                throw new Exception('First Name, Employee ID, and Password are required');
            }
            
            // Validate password policy
            if (!preg_match(PASSWORD_PATTERN, $password)) {
                throw new Exception('Password must be 6+ chars with a letter, number, and special character (!@#$%^&*)');
            }
            
            // Check if employee_id already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE employee_id = ?");
            $stmt->execute([$employeeId]);
            if ($stmt->fetch()) {
                throw new Exception('Employee ID already exists');
            }
            
            // Check if username already exists (if provided)
            if ($username) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    throw new Exception('Username already exists');
                }
            }
            
            // Check if email already exists (if provided)
            if ($email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Invalid email format');
                }
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    throw new Exception('Email already exists');
                }
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Begin transaction
            $pdo->beginTransaction();
            
            // Insert user
            $stmt = $pdo->prepare("
                INSERT INTO users (first_name, last_name, employee_id, username, email, phone_1, phone_2, password, role, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$firstName, $lastName, $employeeId, $username, $email, $phone1, $phone2, $hashedPassword, $role, $status]);
            $userId = $pdo->lastInsertId();
            
            // Add revision history
            addRevision($pdo, 'users', $userId, 'User created by ' . getCurrentUserName());
            
            // Log activity
            logActivity($pdo, 'User Management', 'Add User', "Created user: {$firstName} ({$employeeId})", 'Info');
            
            // Create notification (Admin only)
            createNotification($pdo, 'User', 'add', 'New User Created', 
                              getCurrentUserName() . " created new user: {$firstName} ({$employeeId})", 
                              ['user_id' => $userId]);
            
            // Commit transaction
            $pdo->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'User created successfully',
                'user_id' => $userId
            ]);
            break;
            
        case 'reset_password':
            if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
                throw new Exception('Invalid CSRF token');
            }
            
            $userId = intval($_POST['user_id'] ?? 0);
            $newPassword = $_POST['new_password'] ?? '';
            
            if (!$userId) {
                throw new Exception('User ID is required');
            }
            
            if (empty($newPassword)) {
                throw new Exception('Password is required');
            }
            
            if (!preg_match(PASSWORD_PATTERN, $newPassword)) {
                throw new Exception('Password must be 6+ chars with a letter, number, and special character (!@#$%^&*)');
            }
            
            // Get user info
            $stmt = $pdo->prepare("SELECT first_name, employee_id FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                throw new Exception('User not found');
            }
            
            // Begin transaction
            $pdo->beginTransaction();
            
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);
            
            // Add revision
            addRevision($pdo, 'users', $userId, 'Password reset by admin');
            
            // Log activity
            logActivity($pdo, 'User Management', 'Reset Password', 
                       "Reset password for: {$user['first_name']} ({$user['employee_id']})", 'Info');
            
            // Commit transaction
            $pdo->commit();
            
            echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
            break;
            
        case 'delete':
            if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
                throw new Exception('Invalid CSRF token');
            }
            
            $userId = intval($_POST['user_id'] ?? 0);
            
            if (!$userId) {
                throw new Exception('User ID is required');
            }
            
            // Prevent deleting self
            if ($userId == getCurrentUserId()) {
                throw new Exception('You cannot delete your own account');
            }
            
            // Get user info before deletion
            $stmt = $pdo->prepare("SELECT first_name, employee_id, profile_photo FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                throw new Exception('User not found');
            }
            
            // Begin transaction
            $pdo->beginTransaction();
            
            // Delete profile photo if exists
            if ($user['profile_photo']) {
                $photoPath = PROFILE_UPLOAD_PATH . $user['profile_photo'];
                if (file_exists($photoPath)) {
                    @unlink($photoPath);
                }
            }
            
            // Delete user (cascade will handle related records)
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            
            // Log activity
            logActivity($pdo, 'User Management', 'Delete User', 
                       "Deleted user: {$user['first_name']} ({$user['employee_id']})", 'Warning');
            
            // Create notification (for admins only)
            createNotification($pdo, 'User', 'delete', 'User Deleted', 
                              getCurrentUserName() . " deleted user: {$user['first_name']} ({$user['employee_id']})", 
                              ['user_id' => $userId]);
            
            // Commit transaction
            $pdo->commit();
            
            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            break;
            
        case 'change_password':
            $userId = intval($_POST['user_id'] ?? 0);
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            // Check if user is editing own password or admin
            if ($userId != getCurrentUserId() && !isAdmin()) {
                throw new Exception('Unauthorized');
            }

            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                throw new Exception('All fields are required');
            }

            if ($newPassword !== $confirmPassword) {
                throw new Exception('Passwords do not match');
            }

            if (!preg_match(PASSWORD_PATTERN, $newPassword)) {
                throw new Exception('Password must be 6+ chars with a letter, number, and special character (!@#$%^&*)');
            }

            // Verify current password
            $stmt = $pdo->prepare("SELECT password, first_name, employee_id FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user) {
                throw new Exception('User not found');
            }

            if (!password_verify($currentPassword, $user['password'])) {
                throw new Exception('Current password is incorrect');
            }

            // Begin transaction
            $pdo->beginTransaction();

            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);

            // Add revision
            addRevision($pdo, 'users', $userId, 'Password changed by user');

            // Log activity
            logActivity($pdo, 'User Management', 'Change Password', 
                    "Password changed for: {$user['first_name']} ({$user['employee_id']})", 'Info');

            // Commit transaction
            $pdo->commit();

            echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("User operation PDO error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    ob_clean(); // Clear any output
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("User operation error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    ob_clean(); // Clear any output
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}

// Flush output buffer
ob_end_flush();