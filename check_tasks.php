<?php
// File: check_tasks.php (place in root)
// Purpose: Quick view of all tasks in database

define('ROOT_PATH', __DIR__ . '/');
require_once ROOT_PATH . 'config/config.php';
require_once ROOT_PATH . 'config/session.php';
require_once ROOT_PATH . 'includes/functions.php';

requireLogin();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Task Database Check</title>
    <style>
        body { font-family: monospace; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>

<h1>Task Database Check</h1>

<?php
$userId = getCurrentUserId();
echo "<p>Current User ID: <strong>$userId</strong></p>";
echo "<p>Current User: <strong>" . getCurrentUserName() . "</strong></p>";
echo "<hr>";

// Check all tasks
echo "<h2>All Tasks in Database</h2>";
try {
    $stmt = $pdo->query("
        SELECT t.*, 
               CONCAT(u.first_name, ' ', COALESCE(u.last_name, '')) as creator_name,
               (SELECT GROUP_CONCAT(ta.user_id) FROM todo_assignments ta WHERE ta.todo_id = t.id) as assigned_user_ids
        FROM todos t
        LEFT JOIN users u ON t.created_by = u.id
        ORDER BY t.id DESC
    ");
    $tasks = $stmt->fetchAll();
    
    if (empty($tasks)) {
        echo "<p class='error'>No tasks found in database!</p>";
    } else {
        echo "<p class='success'>Found " . count($tasks) . " tasks</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Priority</th><th>Deadline</th><th>Creator</th><th>Created By ID</th><th>Assigned To IDs</th><th>Created At</th></tr>";
        foreach ($tasks as $task) {
            echo "<tr>";
            echo "<td>{$task['id']}</td>";
            echo "<td>" . htmlspecialchars($task['title']) . "</td>";
            echo "<td>{$task['status']}</td>";
            echo "<td>{$task['priority']}</td>";
            echo "<td>{$task['deadline_date']} {$task['deadline_time']}</td>";
            echo "<td>" . htmlspecialchars($task['creator_name']) . "</td>";
            echo "<td>{$task['created_by']}</td>";
            echo "<td>{$task['assigned_user_ids']}</td>";
            echo "<td>{$task['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

// Check tasks that should show in "All" tab
echo "<hr>";
echo "<h2>Tasks That Should Show in 'All' Tab</h2>";
echo "<p>Criteria: status NOT IN ('Completed', 'Cancelled') AND (created_by = $userId OR assigned to $userId)</p>";

try {
    $stmt = $pdo->prepare("
        SELECT t.*, 
               CONCAT(u.first_name, ' ', COALESCE(u.last_name, '')) as creator_name,
               EXISTS (SELECT 1 FROM todo_assignments WHERE todo_id = t.id AND user_id = ?) as is_assigned
        FROM todos t
        LEFT JOIN users u ON t.created_by = u.id
        WHERE t.status NOT IN ('Completed', 'Cancelled')
          AND (t.created_by = ? OR EXISTS (SELECT 1 FROM todo_assignments WHERE todo_id = t.id AND user_id = ?))
        ORDER BY t.deadline_date ASC
    ");
    $stmt->execute([$userId, $userId, $userId]);
    $tasks = $stmt->fetchAll();
    
    if (empty($tasks)) {
        echo "<p class='error'>No tasks match the 'All' tab criteria for this user!</p>";
        echo "<p>This means either:</p>";
        echo "<ul>";
        echo "<li>All tasks are Completed or Cancelled</li>";
        echo "<li>No tasks are created by you OR assigned to you</li>";
        echo "</ul>";
    } else {
        echo "<p class='success'>Found " . count($tasks) . " tasks for 'All' tab</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Created By</th><th>Is Assigned to You?</th><th>Deadline</th></tr>";
        foreach ($tasks as $task) {
            echo "<tr>";
            echo "<td>{$task['id']}</td>";
            echo "<td>" . htmlspecialchars($task['title']) . "</td>";
            echo "<td>{$task['status']}</td>";
            echo "<td>{$task['creator_name']} (ID: {$task['created_by']})</td>";
            echo "<td>" . ($task['is_assigned'] ? 'YES' : 'NO') . "</td>";
            echo "<td>{$task['deadline_date']} {$task['deadline_time']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

// Check assignments
echo "<hr>";
echo "<h2>Task Assignments</h2>";
try {
    $stmt = $pdo->query("
        SELECT ta.*, 
               t.title as task_title,
               CONCAT(u.first_name, ' ', COALESCE(u.last_name, '')) as user_name
        FROM todo_assignments ta
        JOIN todos t ON ta.todo_id = t.id
        JOIN users u ON ta.user_id = u.id
        ORDER BY ta.todo_id DESC
    ");
    $assignments = $stmt->fetchAll();
    
    if (empty($assignments)) {
        echo "<p class='error'>No task assignments found!</p>";
    } else {
        echo "<p class='success'>Found " . count($assignments) . " assignments</p>";
        echo "<table>";
        echo "<tr><th>Assignment ID</th><th>Task ID</th><th>Task Title</th><th>Assigned To</th><th>User ID</th><th>Assigned At</th></tr>";
        foreach ($assignments as $assign) {
            echo "<tr>";
            echo "<td>{$assign['id']}</td>";
            echo "<td>{$assign['todo_id']}</td>";
            echo "<td>" . htmlspecialchars($assign['task_title']) . "</td>";
            echo "<td>" . htmlspecialchars($assign['user_name']) . "</td>";
            echo "<td>{$assign['user_id']}</td>";
            echo "<td>{$assign['assigned_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

// Test the exact SQL that list page uses
echo "<hr>";
echo "<h2>Test Exact SQL from list_todos.php</h2>";
try {
    $tab = 'all';
    $where = [];
    $params = [];
    
    // Same logic as list page
    $where[] = "t.status NOT IN ('Completed', 'Cancelled')";
    $where[] = "(t.created_by = ? OR EXISTS (SELECT 1 FROM todo_assignments WHERE todo_id = t.id AND user_id = ?))";
    $params[] = $userId;
    $params[] = $userId;
    
    $whereClause = 'WHERE ' . implode(' AND ', $where);
    
    $sql = "
        SELECT t.*,
               CONCAT(creator.first_name, ' ', COALESCE(creator.last_name, '')) as creator_name,
               (SELECT GROUP_CONCAT(CONCAT(u.first_name, ' ', COALESCE(u.last_name, '')) SEPARATOR ', ')
                FROM todo_assignments ta
                JOIN users u ON ta.user_id = u.id
                WHERE ta.todo_id = t.id) as assigned_to,
               (SELECT COUNT(*) FROM todo_comments WHERE todo_id = t.id) as comment_count
        FROM todos t
        JOIN users creator ON t.created_by = creator.id
        {$whereClause}
        ORDER BY t.deadline_date ASC, t.id DESC
    ";
    
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    echo "<p>Parameters: " . print_r($params, true) . "</p>";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll();
    
    echo "<p class='success'>Query returned " . count($tasks) . " tasks</p>";
    
    if (!empty($tasks)) {
        echo "<pre>" . print_r($tasks, true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>SQL Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>

<hr>
<p><a href="pages/todos/list_todos.php">Go to Task List</a> | <a href="check_tasks.php">Refresh</a></p>

</body>
</html>