<?php
// Folder: pages/todos/
// File: test_todos.php
// Purpose: Debug page to test todo functionality

define('ROOT_PATH', dirname(dirname(__DIR__)) . '/');

require_once ROOT_PATH . 'config/config.php';
require_once ROOT_PATH . 'config/session.php';
require_once ROOT_PATH . 'includes/functions.php';

requireLogin();

echo "<!DOCTYPE html><html><head><title>Todo Debug</title>";
echo "<style>body{font-family:monospace;padding:20px;} .success{color:green;} .error{color:red;} pre{background:#f5f5f5;padding:10px;}</style>";
echo "</head><body>";

echo "<h1>Todo Module Debug Information</h1>";

// Test 1: Check if tables exist
echo "<h2>1. Database Tables Check</h2>";
try {
    $tables = ['todos', 'todo_assignments', 'todo_comments'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>✓ Table '$table' exists</p>";
            
            // Show table structure
            $cols = $pdo->query("DESCRIBE $table")->fetchAll();
            echo "<details><summary>Show columns</summary><pre>";
            print_r($cols);
            echo "</pre></details>";
        } else {
            echo "<p class='error'>✗ Table '$table' NOT found!</p>";
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

// Test 2: Check current user
echo "<h2>2. Current User Info</h2>";
echo "<p>User ID: " . getCurrentUserId() . "</p>";
echo "<p>User Name: " . getCurrentUserName() . "</p>";
echo "<p>User Role: " . getCurrentUserRole() . "</p>";

// Test 3: Count existing todos
echo "<h2>3. Existing Todos Count</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM todos");
    $count = $stmt->fetch()['count'];
    echo "<p>Total todos in database: <strong>$count</strong></p>";
    
    if ($count > 0) {
        echo "<h3>Sample todos:</h3>";
        $stmt = $pdo->query("SELECT * FROM todos ORDER BY created_at DESC LIMIT 5");
        $todos = $stmt->fetchAll();
        echo "<pre>" . print_r($todos, true) . "</pre>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

// Test 4: Try to insert a test todo
echo "<h2>4. Test Todo Insert</h2>";
if (isset($_GET['test_insert'])) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO todos (title, description, priority, deadline_date, deadline_time, created_by, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $result = $stmt->execute([
            'Test Task ' . date('H:i:s'),
            'This is a test task created by debug page',
            'Medium',
            date('Y-m-d', strtotime('+1 day')),
            '12:00:00',
            getCurrentUserId(),
            'Assigned'
        ]);
        
        if ($result) {
            $todoId = $pdo->lastInsertId();
            echo "<p class='success'>✓ Test todo inserted successfully! ID: $todoId</p>";
            
            // Assign to current user
            $stmt = $pdo->prepare("INSERT INTO todo_assignments (todo_id, user_id) VALUES (?, ?)");
            $stmt->execute([$todoId, getCurrentUserId()]);
            echo "<p class='success'>✓ Task assigned to current user</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Insert failed: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p><a href='?test_insert=1' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:4px;'>Click to Test Insert</a></p>";
}

// Test 5: Check AJAX endpoint
echo "<h2>5. AJAX Endpoint Test</h2>";
echo "<p>AJAX URL: " . BASE_URL . "ajax/todo_operations.php</p>";
$ajaxFile = ROOT_PATH . 'ajax/todo_operations.php';
if (file_exists($ajaxFile)) {
    echo "<p class='success'>✓ AJAX file exists</p>";
    echo "<p>File path: $ajaxFile</p>";
} else {
    echo "<p class='error'>✗ AJAX file NOT found!</p>";
}

// Test 6: Check active users
echo "<h2>6. Active Users (for assignment)</h2>";
try {
    $stmt = $pdo->query("SELECT id, first_name, last_name, employee_id FROM users WHERE status = 'Active'");
    $users = $stmt->fetchAll();
    echo "<p>Active users count: " . count($users) . "</p>";
    if (count($users) > 0) {
        echo "<ul>";
        foreach ($users as $user) {
            echo "<li>{$user['first_name']} {$user['last_name']} ({$user['employee_id']})</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

// Test 7: Check PDO error mode
echo "<h2>7. PDO Configuration</h2>";
echo "<p>PDO Error Mode: " . $pdo->getAttribute(PDO::ATTR_ERRMODE) . "</p>";
echo "<p>Expected: " . PDO::ERRMODE_EXCEPTION . " (Exception mode)</p>";

// Test 8: Browser console check
echo "<h2>8. Browser Console Check</h2>";
echo "<p>Open your browser's Developer Tools (F12) and check the Console tab for JavaScript errors.</p>";

// Test 9: Check if jQuery is loaded
echo "<h2>9. jQuery Check</h2>";
echo "<script src='" . BASE_URL . "assets/js/jquery-3.6.0.min.js'></script>";
echo "<script>
if (typeof jQuery !== 'undefined') {
    document.write('<p class=\"success\">✓ jQuery is loaded (version: ' + jQuery.fn.jquery + ')</p>');
} else {
    document.write('<p class=\"error\">✗ jQuery NOT loaded!</p>');
}
</script>";

echo "<hr>";
echo "<p><a href='list_todos.php'>← Back to Todo List</a></p>";

echo "</body></html>";