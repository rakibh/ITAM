<?php
// File: ajax/equipment_operations.php
// Purpose: Handle equipment CRUD operations via AJAX

require_once '../config/config.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireLogin();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'delete':
            requireAdmin(); // Only admins can delete
            
            if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
                throw new Exception('Invalid CSRF token');
            }
            
            $equipmentId = intval($_POST['equipment_id'] ?? 0);
            
            if (!$equipmentId) {
                throw new Exception('Equipment ID required');
            }
            
            // Get equipment info
            $stmt = $pdo->prepare("
                SELECT e.*, et.type_name
                FROM equipments e
                JOIN equipment_types et ON e.equipment_type_id = et.id
                WHERE e.id = ?
            ");
            $stmt->execute([$equipmentId]);
            $equipment = $stmt->fetch();
            
            if (!$equipment) {
                throw new Exception('Equipment not found');
            }
            
            $pdo->beginTransaction();
            
            // Check if has network info
            $networkStmt = $pdo->prepare("SELECT id FROM network_info WHERE equipment_id = ?");
            $networkStmt->execute([$equipmentId]);
            $hasNetwork = $networkStmt->fetch();
            
            if ($hasNetwork) {
                // Ask user what to do with network info
                // For now, we'll unassign it (set equipment_id to NULL)
                $updateNetworkStmt = $pdo->prepare("UPDATE network_info SET equipment_id = NULL WHERE equipment_id = ?");
                $updateNetworkStmt->execute([$equipmentId]);
            }
            
            // Delete warranty documents
            $docStmt = $pdo->prepare("SELECT file_path FROM warranty_documents WHERE equipment_id = ?");
            $docStmt->execute([$equipmentId]);
            $docs = $docStmt->fetchAll();
            
            foreach ($docs as $doc) {
                deleteFile(WARRANTY_UPLOAD_PATH . $doc['file_path']);
            }
            
            // Delete equipment (cascade will handle custom values, revisions, etc.)
            $deleteStmt = $pdo->prepare("DELETE FROM equipments WHERE id = ?");
            $deleteStmt->execute([$equipmentId]);
            
            // Log activity
            logActivity($pdo, 'Equipment', 'Delete Equipment', 
                       "Deleted equipment: {$equipment['label']} ({$equipment['serial_number']})", 'Warning');
            
            // Create notification
            createNotification($pdo, 'Equipment', 'delete', 'Equipment Deleted', 
                              getCurrentUserName() . " deleted equipment: {$equipment['label']} ({$equipment['serial_number']})", 
                              ['equipment_id' => $equipmentId]);
            
            $pdo->commit();
            
            echo json_encode(['success' => true, 'message' => 'Equipment deleted successfully']);
            break;
            
        case 'export':
            $type = $_GET['type'] ?? 'all';
            $format = $_GET['format'] ?? 'csv';
            
            // Build query based on export type
            $where = '1=1';
            $params = [];
            
            if ($type === 'filtered') {
                // Apply same filters as list page
                // ... (copy filter logic from list_equipment.php)
            } elseif ($type === 'selected') {
                $ids = explode(',', $_GET['ids'] ?? '');
                $ids = array_filter(array_map('intval', $ids));
                
                if (empty($ids)) {
                    throw new Exception('No equipment selected');
                }
                
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $where = "e.id IN ($placeholders)";
                $params = $ids;
            }
            
            // Fetch equipment data
            $stmt = $pdo->prepare("
                SELECT e.*, et.type_name,
                       u.first_name as creator_name, u.employee_id as creator_id,
                       ni.ip_address, ni.mac_address
                FROM equipments e
                JOIN equipment_types et ON e.equipment_type_id = et.id
                LEFT JOIN users u ON e.created_by = u.id
                LEFT JOIN network_info ni ON ni.equipment_id = e.id
                WHERE {$where}
                ORDER BY e.created_at DESC
            ");
            $stmt->execute($params);
            $data = $stmt->fetchAll();
            
            if ($format === 'csv') {
                exportToCSV($data, 'equipment_export_' . date('Y-m-d'));
            }
            break;
            
        case 'get_warranty_status':
            $equipmentId = intval($_GET['equipment_id'] ?? 0);
            
            $stmt = $pdo->prepare("SELECT warranty_expiry_date FROM equipments WHERE id = ?");
            $stmt->execute([$equipmentId]);
            $equipment = $stmt->fetch();
            
            if (!$equipment) {
                throw new Exception('Equipment not found');
            }
            
            $status = getWarrantyStatus($equipment['warranty_expiry_date']);
            
            echo json_encode([
                'success' => true,
                'status' => $status,
                'expiry_date' => $equipment['warranty_expiry_date']
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Equipment operation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Export data to CSV
 */
function exportToCSV($data, $filename) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Headers
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
    }
    
    // Data rows
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit();
}