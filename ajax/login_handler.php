<?php
// File: ajax/login_handler.php
// Purpose: Process login requests with security validation

require_once '../config/config.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Verify request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

// Sanitize inputs
$loginId = sanitize($_POST['login_id'] ?? '');
$password = $_POST['password'] ?? '';

// Validate inputs
if (empty($loginId) || empty($password)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

// Validate password policy
if (!preg_match(PASSWORD_PATTERN, $password)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Password must be 6+ chars incl. a letter, a number, and a special character.']);
    exit();
}

// Check rate limiting
$ipAddress = $_SERVER['REMOTE_ADDR'];
$stmt = $pdo->prepare("
    SELECT COUNT(*) as attempt_count 
    FROM login_attempts 
    WHERE (login_identifier = ? OR ip_address = ?)
    AND attempt_time > DATE_SUB(NOW(), INTERVAL " . LOGIN_ATTEMPT_TIMEOUT . " SECOND)
    AND success = FALSE
");
$stmt->execute([$loginId, $ipAddress]);
$attempts = $stmt->fetch();

if ($attempts['attempt_count'] >= MAX_LOGIN_ATTEMPTS) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many attempts. Try again later.']);
    exit();
}

try {
    // Query user by employee_id or username
    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, employee_id, username, password, role, status 
        FROM users 
        WHERE (employee_id = ? OR username = ?)
        LIMIT 1
    ");
    $stmt->execute([$loginId, $loginId]);
    $user = $stmt->fetch();

    // Log attempt
    $logStmt = $pdo->prepare("
        INSERT INTO login_attempts (login_identifier, ip_address, attempt_time, success)
        VALUES (?, ?, NOW(), ?)
    ");

    if (!$user) {
        // Invalid credentials
        $logStmt->execute([$loginId, $ipAddress, false]);
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
        exit();
    }

    // Check if account is active
    if ($user['status'] !== 'Active') {
        $logStmt->execute([$loginId, $ipAddress, false]);
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Your account is inactive. Contact admin.']);
        exit();
    }

    // Verify password
    if (!password_verify($password, $user['password'])) {
        $logStmt->execute([$loginId, $ipAddress, false]);
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
        exit();
    }

    // Successful login
    $logStmt->execute([$loginId, $ipAddress, true]);

    // Regenerate session ID
    regenerateSession();

    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . ($user['last_name'] ?? '');
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['employee_id'] = $user['employee_id'];

    // Log activity
    logActivity($pdo, 'Authentication', 'Login', $user['first_name'] . ' logged in successfully', 'Info');

    // Determine redirect
    $redirect = ($user['role'] === 'admin') 
        ? BASE_URL . 'pages/dashboard_admin.php' 
        : BASE_URL . 'pages/dashboard_user.php';

    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'redirect' => $redirect
    ]);

} catch (PDOException $e) {
    error_log("Login error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again.']);
}