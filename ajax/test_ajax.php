<?php
// Folder: ajax/
// File: test_ajax.php
// Purpose: Simple AJAX test to verify connection

require_once '../config/config.php';
require_once '../config/session.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'test';

if ($action === 'get_todos') {
    try {
        $stmt = $pdo->query("SELECT * FROM todos ORDER BY created_at DESC LIMIT 10");
        $todos = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'message' => 'Todos fetched successfully',
            'count' => count($todos),
            'todos' => $todos
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => true,
        'message' => 'AJAX is working!',
        'timestamp' => date('Y-m-d H:i:s'),
        'user_id' => getCurrentUserId() ?? 'Not logged in'
    ]);
}