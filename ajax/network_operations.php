<?php
// File: ajax/network_operations.php
// Purpose: Handle all network-related AJAX operations (add, edit, delete, assign, unassign)

require_once '../config/config.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

requireLogin();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'delete':
            // Verify CSRF token
            if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
                throw new Exception('Invalid CSRF token');
            }
            
            $networkId = intval($_POST['network_id'] ?? 0);
            
            if (!$networkId) {
                throw new Exception('Network ID required');
            }
            
            // Get network info
            $stmt = $pdo->prepare("SELECT * FROM network_info WHERE id = ?");
            $stmt->execute([$networkId]);
            $network = $stmt->fetch();
            
            if (!$network) {
                throw new Exception('Network info not found');
            }
            
            // Check if assigned
            if ($network['equipment_id'] !== null) {
                http_response_code(422);
                throw new Exception('Cannot delete assigned IP. Please unassign it first.');
            }
            
            // Delete network info
            $deleteStmt = $pdo->prepare("DELETE FROM network_info WHERE id = ?");
            $deleteStmt->execute([$networkId]);
            
            // Log activity
            logActivity($pdo, 'Network', 'Delete Network Info', 
                       "Deleted network info (IP: {$network['ip_address']})", 'Warning');
            
            // Create notification
            createNotification($pdo, 'Network', 'delete', 'Network Info Deleted', 
                              getCurrentUserName() . " deleted network info (IP: {$network['ip_address']})", 
                              ['network_id' => $networkId, 'ip_address' => $network['ip_address']]);
            
            echo json_encode(['success' => true, 'message' => 'Network info deleted successfully']);
            break;
            
        case 'assign':
            if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
                throw new Exception('Invalid CSRF token');
            }
            
            $networkId = intval($_POST['network_id'] ?? 0);
            $equipmentId = intval($_POST['equipment_id'] ?? 0);
            
            if (!$networkId || !$equipmentId) {
                throw new Exception('Network ID and Equipment ID required');
            }
            
            // Get network info
            $netStmt = $pdo->prepare("SELECT * FROM network_info WHERE id = ?");
            $netStmt->execute([$networkId]);
            $network = $netStmt->fetch();
            
            if (!$network) {
                throw new Exception('Network info not found');
            }
            
            // Check if already assigned
            if ($network['equipment_id'] !== null) {
                throw new Exception('This IP is already assigned to another equipment');
            }
            
            // Get equipment info
            $eqStmt = $pdo->prepare("
                SELECT e.*, et.type_name 
                FROM equipments e
                JOIN equipment_types et ON e.equipment_type_id = et.id
                WHERE e.id = ?
            ");
            $eqStmt->execute([$equipmentId]);
            $equipment = $eqStmt->fetch();
            
            if (!$equipment) {
                throw new Exception('Equipment not found');
            }
            
            // Check if equipment already has network info
            $existingNetStmt = $pdo->prepare("SELECT id FROM network_info WHERE equipment_id = ?");
            $existingNetStmt->execute([$equipmentId]);
            if ($existingNetStmt->fetch()) {
                throw new Exception('This equipment already has network info assigned');
            }
            
            // Assign network to equipment
            $assignStmt = $pdo->prepare("UPDATE network_info SET equipment_id = ?, updated_at = NOW() WHERE id = ?");
            $assignStmt->execute([$equipmentId, $networkId]);
            
            // Add revision
            addRevision($pdo, 'network_info', $networkId, 
                       "Assigned to equipment: {$equipment['label']} ({$equipment['serial_number']})");
            
            // Log activity
            logActivity($pdo, 'Network', 'Assign Network Info', 
                       "Assigned IP {$network['ip_address']} to {$equipment['label']}", 'Info');
            
            // Create notification
            createNotification($pdo, 'Network', 'assign', 'Network Info Assigned', 
                              getCurrentUserName() . " assigned network info (IP: {$network['ip_address']}) to {$equipment['label']} ({$equipment['serial_number']})", 
                              ['network_id' => $networkId, 'equipment_id' => $equipmentId]);
            
            echo json_encode(['success' => true, 'message' => 'Network info assigned successfully']);
            break;
            
        case 'unassign':
            if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
                throw new Exception('Invalid CSRF token');
            }
            
            $networkId = intval($_POST['network_id'] ?? 0);
            
            if (!$networkId) {
                throw new Exception('Network ID required');
            }
            
            // Get network and equipment info
            $stmt = $pdo->prepare("
                SELECT ni.*, e.label as equipment_label, e.serial_number
                FROM network_info ni
                LEFT JOIN equipments e ON ni.equipment_id = e.id
                WHERE ni.id = ?
            ");
            $stmt->execute([$networkId]);
            $network = $stmt->fetch();
            
            if (!$network) {
                throw new Exception('Network info not found');
            }
            
            if ($network['equipment_id'] === null) {
                throw new Exception('Network info is not assigned to any equipment');
            }
            
            $equipmentLabel = $network['equipment_label'];
            $serialNumber = $network['serial_number'];
            
            // Unassign
            $unassignStmt = $pdo->prepare("UPDATE network_info SET equipment_id = NULL, updated_at = NOW() WHERE id = ?");
            $unassignStmt->execute([$networkId]);
            
            // Add revision
            addRevision($pdo, 'network_info', $networkId, 
                       "Unassigned from equipment: {$equipmentLabel} ({$serialNumber})");
            
            // Log activity
            logActivity($pdo, 'Network', 'Unassign Network Info', 
                       "Unassigned IP {$network['ip_address']} from {$equipmentLabel}", 'Info');
            
            // Create notification
            createNotification($pdo, 'Network', 'unassign', 'Network Info Unassigned', 
                              getCurrentUserName() . " unassigned network info (IP: {$network['ip_address']}) from {$equipmentLabel} ({$serialNumber})", 
                              ['network_id' => $networkId]);
            
            echo json_encode(['success' => true, 'message' => 'Network info unassigned successfully']);
            break;
            
        case 'get_available_equipment':
            // Get equipment that don't have network info assigned
            $stmt = $pdo->query("
                SELECT e.id, e.label, e.serial_number, et.type_name
                FROM equipments e
                JOIN equipment_types et ON e.equipment_type_id = et.id
                WHERE e.id NOT IN (
                    SELECT equipment_id FROM network_info WHERE equipment_id IS NOT NULL
                )
                ORDER BY e.label
            ");
            $equipment = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'equipment' => $equipment]);
            break;
            
        case 'export':
            $type = $_GET['type'] ?? 'all';
            
            // Build query
            $where = '1=1';
            $params = [];
            
            if ($type === 'filtered') {
                // Apply filters from GET parameters
                $search = sanitize($_GET['search'] ?? '');
                if (!empty($search)) {
                    $where .= " AND (ni.ip_address LIKE ? OR ni.mac_address LIKE ? OR ni.cable_number LIKE ?)";
                    $searchTerm = "%{$search}%";
                    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
                }
            } elseif ($type === 'selected') {
                $ids = explode(',', $_GET['ids'] ?? '');
                $ids = array_filter(array_map('intval', $ids));
                
                if (empty($ids)) {
                    throw new Exception('No network records selected');
                }
                
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $where = "ni.id IN ($placeholders)";
                $params = $ids;
            }
            
            // Fetch data
            $stmt = $pdo->prepare("
                SELECT ni.*, 
                       e.label as equipment_label, e.serial_number,
                       u.first_name as creator_name, u.employee_id as creator_id
                FROM network_info ni
                LEFT JOIN equipments e ON ni.equipment_id = e.id
                LEFT JOIN users u ON ni.created_by = u.id
                WHERE {$where}
                ORDER BY ni.created_at DESC
            ");
            $stmt->execute($params);
            $data = $stmt->fetchAll();
            
            // Export to CSV
            exportNetworkToCSV($data, 'network_export_' . date('Y-m-d'));
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (PDOException $e) {
    error_log("Network operation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Export network data to CSV
 */
function exportNetworkToCSV($data, $filename) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Headers
    if (!empty($data)) {
        $headers = ['ID', 'IP Address', 'MAC Address', 'Cable Number', 'Patch Panel Number', 
                    'Patch Panel Port', 'Patch Panel Location', 'Switch Number', 'Switch Port', 
                    'Switch Location', 'Assigned Equipment', 'Serial Number', 'Remarks', 
                    'Created By', 'Created At'];
        fputcsv($output, $headers);
    }
    
    // Data rows
    foreach ($data as $row) {
        $csvRow = [
            $row['id'],
            $row['ip_address'],
            $row['mac_address'] ?? 'N/A',
            $row['cable_number'] ?? 'N/A',
            $row['patch_panel_number'] ?? 'N/A',
            $row['patch_panel_port'] ?? 'N/A',
            $row['patch_panel_location'] ?? 'N/A',
            $row['switch_number'] ?? 'N/A',
            $row['switch_port'] ?? 'N/A',
            $row['switch_location'] ?? 'N/A',
            $row['equipment_label'] ?? 'Unassigned',
            $row['serial_number'] ?? 'N/A',
            $row['remarks'] ?? '',
            $row['creator_name'] . ' (' . $row['creator_id'] . ')',
            $row['created_at']
        ];
        fputcsv($output, $csvRow);
    }
    
    fclose($output);
    exit();
}