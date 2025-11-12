<?php
// File: test_todo.php (place in root directory)
// Purpose: Direct test for todo creation - helps diagnose issues

define('ROOT_PATH', __DIR__ . '/');
require_once ROOT_PATH . 'config/config.php';
require_once ROOT_PATH . 'config/session.php';
require_once ROOT_PATH . 'includes/functions.php';

requireLogin();

echo "<h1>Todo Creation Test</h1>";
echo "<pre>";

// Test 1: Check if user is logged in
echo "1. User Check:\n";
echo "   User ID: " . getCurrentUserId() . "\n";
echo "   User Name: " . getCurrentUserName() . "\n";
echo "   User Role: " . getCurrentUserRole() . "\n\n";

// Test 2: Check database connection
echo "2. Database Check:\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM todos");
    $count = $stmt->fetch()['count'];
    echo "   ✓ Database connected\n";
    echo "   ✓ Current tasks count: $count\n\n";
} catch (Exception $e) {
    echo "   ✗ Database error: " . $e->getMessage() . "\n\n";
    exit;
}

// Test 3: Check if todos table exists and has correct structure
echo "3. Table Structure Check:\n";
try {
    $stmt = $pdo->query("DESCRIBE todos");
    echo "   ✓ Todos table exists\n";
    echo "   Columns:\n";
    while ($row = $stmt->fetch()) {
        echo "     - {$row['Field']} ({$row['Type']})\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Table error: " . $e->getMessage() . "\n\n";
}

// Test 4: Check if there are active users to assign
echo "4. Active Users Check:\n";
try {
    $stmt = $pdo->query("SELECT id, first_name, last_name, employee_id FROM users WHERE status = 'Active' ORDER BY first_name LIMIT 5");
    $users = $stmt->fetchAll();
    echo "   ✓ Found " . count($users) . " active users:\n";
    foreach ($users as $user) {
        echo "     - ID: {$user['id']}, Name: {$user['first_name']} {$user['last_name']}, EID: {$user['employee_id']}\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Users error: " . $e->getMessage() . "\n\n";
}

// Test 5: Try to create a test task
echo "5. Test Task Creation:\n";
try {
    // Get first active user
    $stmt = $pdo->query("SELECT id FROM users WHERE status = 'Active' LIMIT 1");
    $firstUser = $stmt->fetch();
    
    if (!$firstUser) {
        echo "   ✗ No active users found\n\n";
    } else {
        $title = "Test Task - " . date('Y-m-d H:i:s');
        $description = "This is a test task created by test_todo.php";
        $priority = "Medium";
        $deadlineDate = date('Y-m-d', strtotime('+1 day'));
        $deadlineTime = '23:59:00';
        $createdBy = getCurrentUserId();
        
        echo "   Attempting to create task:\n";
        echo "   - Title: $title\n";
        echo "   - Priority: $priority\n";
        echo "   - Deadline: $deadlineDate $deadlineTime\n";
        echo "   - Created by: $createdBy\n";
        echo "   - Assigned to: User ID {$firstUser['id']}\n\n";
        
        // Insert task
        $stmt = $pdo->prepare("
            INSERT INTO todos (title, description, tags, priority, status, deadline_date, deadline_time, created_by)
            VALUES (?, ?, ?, ?, 'Assigned', ?, ?, ?)
        ");
        $stmt->execute([$title, $description, 'test', $priority, $deadlineDate, $deadlineTime, $createdBy]);
        $todoId = $pdo->lastInsertId();
        
        echo "   ✓ Task inserted with ID: $todoId\n\n";
        
        // Assign user
        $stmt = $pdo->prepare("INSERT INTO todo_assignments (todo_id, user_id) VALUES (?, ?)");
        $stmt->execute([$todoId, $firstUser['id']]);
        
        echo "   ✓ User assigned successfully\n\n";
        
        // Verify task exists
        $stmt = $pdo->prepare("SELECT * FROM todos WHERE id = ?");
        $stmt->execute([$todoId]);
        $task = $stmt->fetch();
        
        echo "   ✓ Task verified in database:\n";
        echo "   - ID: {$task['id']}\n";
        echo "   - Title: {$task['title']}\n";
        echo "   - Status: {$task['status']}\n";
        echo "   - Created at: {$task['created_at']}\n\n";
        
        echo "   ✓ TEST PASSED - Task creation works!\n\n";
        echo "   <a href='pages/todos/list_todos.php'>View Tasks</a> | ";
        echo "   <a href='test_todo.php'>Run Test Again</a>\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error creating task: " . $e->getMessage() . "\n";
    echo "   ✗ Stack trace:\n" . $e->getTraceAsString() . "\n\n";
}

// Test 6: Check AJAX handler
echo "6. AJAX Handler Check:\n";
if (file_exists('ajax/todo_operations.php')) {
    echo "   ✓ ajax/todo_operations.php exists\n";
    echo "   File size: " . filesize('ajax/todo_operations.php') . " bytes\n\n";
} else {
    echo "   ✗ ajax/todo_operations.php NOT FOUND\n\n";
}

// Test 7: Check CSRF token
echo "7. CSRF Token Check:\n";
echo "   Token exists: " . (isset($_SESSION[CSRF_TOKEN_NAME]) ? 'Yes' : 'No') . "\n";
if (isset($_SESSION[CSRF_TOKEN_NAME])) {
    echo "   Token: " . substr($_SESSION[CSRF_TOKEN_NAME], 0, 10) . "...\n";
}
echo "\n";

// Test 8: Simulate AJAX POST
echo "8. Simulate AJAX POST:\n";
echo "   You can test the AJAX endpoint directly with this curl command:\n\n";
echo "   curl -X POST '" . BASE_URL . "ajax/todo_operations.php' \\\n";
echo "     -H 'Content-Type: application/x-www-form-urlencoded' \\\n";
echo "     -b 'session_cookie_here' \\\n";
echo "     -d 'action=add&title=Test&deadline_date=" . date('Y-m-d', strtotime('+1 day')) . "&deadline_time=&priority=Medium&assigned_users[]=" . ($firstUser['id'] ?? '1') . "&csrf_token=" . getCsrfToken() . "'\n\n";

echo "</pre>";

echo "<hr>";
echo "<h2>Test Form</h2>";
echo "<form method='POST' action='ajax/todo_operations.php' target='_blank'>";
echo csrfField();
echo "<input type='hidden' name='action' value='add'>";
echo "<p><label>Title: <input type='text' name='title' value='Form Test Task " . date('H:i:s') . "'></label></p>";
echo "<p><label>Description: <textarea name='description'>Test description</textarea></label></p>";
echo "<p><label>Priority: <select name='priority'><option>Low</option><option selected>Medium</option><option>High</option></select></label></p>";
echo "<p><label>Deadline Date: <input type='date' name='deadline_date' value='" . date('Y-m-d', strtotime('+1 day')) . "'></label></p>";
echo "<p><label>Deadline Time: <input type='time' name='deadline_time' value=''></label> (optional)</p>";
echo "<p><label>Assign To: <select name='assigned_users[]' multiple>";
$stmt = $pdo->query("SELECT id, first_name, last_name FROM users WHERE status = 'Active' LIMIT 10");
while ($user = $stmt->fetch()) {
    echo "<option value='{$user['id']}'>{$user['first_name']} {$user['last_name']}</option>";
}
echo "</select></label></p>";
echo "<p><button type='submit'>Submit Direct (opens in new tab)</button></p>";
echo "</form>";
?>