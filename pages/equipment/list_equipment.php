<?php
// File: pages/equipment/list_equipment.php
// Purpose: Display equipment list with dynamic columns, filters, search, and export

define('ROOT_PATH', dirname(dirname(__DIR__)) . '/');
$pageTitle = 'Equipment List';

require_once ROOT_PATH . 'layouts/header.php';
requireLogin();

// Pagination and filters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = DEFAULT_PER_PAGE;
$offset = ($page - 1) * $perPage;

// Filters
$search = sanitize($_GET['search'] ?? '');
$typeFilter = sanitize($_GET['type'] ?? '');
$brandFilter = sanitize($_GET['brand'] ?? '');
$locationFilter = sanitize($_GET['location'] ?? '');
$floorFilter = sanitize($_GET['floor'] ?? '');
$departmentFilter = sanitize($_GET['department'] ?? '');
$conditionFilter = sanitize($_GET['condition'] ?? '');
$warrantyFilter = sanitize($_GET['warranty'] ?? '');

// Get all equipment types for filter
$typeStmt = $pdo->query("SELECT id, type_name FROM equipment_types ORDER BY type_name");
$equipmentTypes = $typeStmt->fetchAll();

// Build WHERE clause
$where = ['1=1'];
$params = [];

if (!empty($search)) {
    $where[] = "(e.label LIKE ? OR e.brand LIKE ? OR e.serial_number LIKE ? OR e.model_number LIKE ?)";
    $searchTerm = "%{$search}%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($typeFilter)) {
    $where[] = "e.equipment_type_id = ?";
    $params[] = $typeFilter;
}

if (!empty($brandFilter)) {
    $where[] = "e.brand LIKE ?";
    $params[] = "%{$brandFilter}%";
}

if (!empty($locationFilter)) {
    $where[] = "e.location LIKE ?";
    $params[] = "%{$locationFilter}%";
}

if (!empty($floorFilter)) {
    $where[] = "e.floor_no LIKE ?";
    $params[] = "%{$floorFilter}%";
}

if (!empty($departmentFilter)) {
    $where[] = "e.department LIKE ?";
    $params[] = "%{$departmentFilter}%";
}

if (!empty($conditionFilter)) {
    $where[] = "e.condition_status = ?";
    $params[] = $conditionFilter;
}

// Warranty filter
if ($warrantyFilter === 'expiring_30') {
    $where[] = "e.warranty_expiry_date IS NOT NULL AND e.warranty_expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
} elseif ($warrantyFilter === 'expiring_15') {
    $where[] = "e.warranty_expiry_date IS NOT NULL AND e.warranty_expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 15 DAY)";
} elseif ($warrantyFilter === 'expired') {
    $where[] = "e.warranty_expiry_date IS NOT NULL AND e.warranty_expiry_date < CURDATE()";
}

$whereClause = implode(' AND ', $where);

// Get total count
$countStmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM equipments e 
    JOIN equipment_types et ON e.equipment_type_id = et.id
    WHERE {$whereClause}
");
$countStmt->execute($params);
$totalEquipment = $countStmt->fetch()['total'];
$totalPages = ceil($totalEquipment / $perPage);

// Get equipment list with type info
$stmt = $pdo->prepare("
    SELECT e.*, et.type_name,
           u.first_name as creator_name, u.employee_id as creator_id,
           ni.ip_address, ni.mac_address
    FROM equipments e
    JOIN equipment_types et ON e.equipment_type_id = et.id
    LEFT JOIN users u ON e.created_by = u.id
    LEFT JOIN network_info ni ON ni.equipment_id = e.id
    WHERE {$whereClause}
    ORDER BY e.created_at DESC
    LIMIT ? OFFSET ?
");
$params[] = $perPage;
$params[] = $offset;
$stmt->execute($params);
$equipments = $stmt->fetchAll();
?>

<?php require_once ROOT_PATH . 'layouts/sidebar.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-pc-display me-2"></i>Equipment Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-download"></i> Export
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" onclick="exportData('all')">Export All</a></li>
                    <li><a class="dropdown-item" href="#" onclick="exportData('filtered')">Export Filtered</a></li>
                    <li><a class="dropdown-item" href="#" onclick="exportData('selected')">Export Selected</a></li>
                </ul>
            </div>
            <a href="add_equipment.php" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-circle"></i> Add Equipment
            </a>
        </div>
    </div>

    <div id="alert-container"></div>

    <!-- Search and Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" name="search" placeholder="Search..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="type">
                        <option value="">All Types</option>
                        <?php foreach ($equipmentTypes as $type): ?>
                            <option value="<?php echo $type['id']; ?>" 
                                    <?php echo $typeFilter == $type['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['type_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="text" class="form-control" name="brand" placeholder="Brand" 
                           value="<?php echo htmlspecialchars($brandFilter); ?>">
                </div>
                <div class="col-md-2">
                    <input type="text" class="form-control" name="location" placeholder="Location" 
                           value="<?php echo htmlspecialchars($locationFilter); ?>">
                </div>
                <div class="col-md-1">
                    <input type="text" class="form-control" name="floor" placeholder="Floor" 
                           value="<?php echo htmlspecialchars($floorFilter); ?>">
                </div>
                <div class="col-md-2">
                    <input type="text" class="form-control" name="department" placeholder="Department" 
                           value="<?php echo htmlspecialchars($departmentFilter); ?>">
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="condition">
                        <option value="">All Conditions</option>
                        <option value="New" <?php echo $conditionFilter === 'New' ? 'selected' : ''; ?>>New</option>
                        <option value="Good" <?php echo $conditionFilter === 'Good' ? 'selected' : ''; ?>>Good</option>
                        <option value="Needs Service" <?php echo $conditionFilter === 'Needs Service' ? 'selected' : ''; ?>>Needs Service</option>
                        <option value="Damaged" <?php echo $conditionFilter === 'Damaged' ? 'selected' : ''; ?>>Damaged</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="warranty">
                        <option value="">All Warranties</option>
                        <option value="expiring_30" <?php echo $warrantyFilter === 'expiring_30' ? 'selected' : ''; ?>>Expiring in 30 days</option>
                        <option value="expiring_15" <?php echo $warrantyFilter === 'expiring_15' ? 'selected' : ''; ?>>Expiring in 15 days</option>
                        <option value="expired" <?php echo $warrantyFilter === 'expired' ? 'selected' : ''; ?>>Expired</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="list_equipment.php" class="btn btn-secondary w-100">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Equipment Table -->
    <div class="card shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="m-0">Total Equipment: <?php echo $totalEquipment; ?></h6>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="selectAll">
                <label class="form-check-label" for="selectAll">Select All</label>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover" id="equipmentTable">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAllHeader"></th>
                            <th>#</th>
                            <th>Type</th>
                            <th>Label/Name</th>
                            <th>Brand</th>
                            <th>Serial Number</th>
                            <?php if (!empty($typeFilter)): ?>
                                <!-- Dynamic columns based on selected type -->
                                <th>IP Address</th>
                            <?php endif; ?>
                            <th>Location</th>
                            <th>Floor</th>
                            <th>Department</th>
                            <th>Condition</th>
                            <th>Warranty</th>
                            <?php if (isAdmin()): ?>
                                <th>Created By</th>
                                <th>Updated</th>
                            <?php endif; ?>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($equipments)): ?>
                        <tr>
                            <td colspan="15" class="text-center text-muted py-4">No equipment found</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($equipments as $index => $equipment): ?>
                            <tr>
                                <td><input type="checkbox" class="equipment-checkbox" value="<?php echo $equipment['id']; ?>"></td>
                                <td><?php echo $offset + $index + 1; ?></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($equipment['type_name']); ?></span></td>
                                <td><?php echo htmlspecialchars($equipment['label']); ?></td>
                                <td><?php echo htmlspecialchars($equipment['brand'] ?? 'N/A'); ?></td>
                                <td><code><?php echo htmlspecialchars($equipment['serial_number']); ?></code></td>
                                <?php if (!empty($typeFilter)): ?>
                                    <td>
                                        <?php if ($equipment['ip_address']): ?>
                                            <a href="<?php echo BASE_URL; ?>pages/network/view_network_info.php?ip=<?php echo urlencode($equipment['ip_address']); ?>" 
                                               target="_blank">
                                                <?php echo htmlspecialchars($equipment['ip_address']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                                <td><?php echo htmlspecialchars($equipment['location'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($equipment['floor_no'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($equipment['department'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $equipment['condition_status'] === 'New' ? 'success' : 
                                            ($equipment['condition_status'] === 'Good' ? 'primary' : 
                                            ($equipment['condition_status'] === 'Needs Service' ? 'warning' : 'danger')); 
                                    ?>">
                                        <?php echo $equipment['condition_status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $warrantyStatus = getWarrantyStatus($equipment['warranty_expiry_date']);
                                    $badgeClass = 'bg-secondary';
                                    if ($warrantyStatus === 'Expired') $badgeClass = 'bg-danger';
                                    elseif (strpos($warrantyStatus, 'Expiring') !== false) $badgeClass = 'bg-warning';
                                    elseif ($warrantyStatus === 'Active') $badgeClass = 'bg-success';
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo $warrantyStatus; ?></span>
                                </td>
                                <?php if (isAdmin()): ?>
                                    <td>
                                        <small><?php echo htmlspecialchars($equipment['creator_name'] ?? 'N/A'); ?></small>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($equipment['creator_id'] ?? ''); ?></small>
                                    </td>
                                    <td><small><?php echo formatDate($equipment['updated_at']); ?></small></td>
                                <?php endif; ?>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="view_equipment.php?id=<?php echo $equipment['id']; ?>" 
                                           class="btn btn-outline-primary" target="_blank" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="edit_equipment.php?id=<?php echo $equipment['id']; ?>" 
                                           class="btn btn-outline-warning" target="_blank" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if (isAdmin()): ?>
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="deleteEquipment(<?php echo $equipment['id']; ?>, '<?php echo htmlspecialchars($equipment['label']); ?>')" 
                                                title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <?php echo getPaginationHTML($page, $totalPages, 'list_equipment.php'); ?>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
// Select all checkboxes
$('#selectAll, #selectAllHeader').on('change', function() {
    $('.equipment-checkbox').prop('checked', $(this).prop('checked'));
});

// Delete equipment
function deleteEquipment(id, label) {
    if (confirm(`Are you sure you want to delete "${label}"? This action cannot be undone.`)) {
        $.ajax({
            url: '<?php echo BASE_URL; ?>ajax/equipment_operations.php',
            type: 'POST',
            data: { 
                action: 'delete', 
                equipment_id: id,
                csrf_token: '<?php echo getCsrfToken(); ?>'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function(xhr) {
                showAlert('danger', xhr.responseJSON?.message || 'Error deleting equipment');
            }
        });
    }
}

// Export functionality
function exportData(type) {
    let url = '<?php echo BASE_URL; ?>ajax/equipment_operations.php?action=export&type=' + type;
    
    if (type === 'filtered') {
        url += '&' + new URLSearchParams(window.location.search).toString();
    } else if (type === 'selected') {
        let selected = [];
        $('.equipment-checkbox:checked').each(function() {
            selected.push($(this).val());
        });
        
        if (selected.length === 0) {
            alert('Please select at least one equipment to export');
            return;
        }
        
        url += '&ids=' + selected.join(',');
    }
    
    window.open(url, '_blank');
}

// Highlight search terms
<?php if (!empty($search)): ?>
$(document).ready(function() {
    const searchTerm = '<?php echo addslashes($search); ?>';
    $('td').each(function() {
        const text = $(this).text();
        if (text.toLowerCase().includes(searchTerm.toLowerCase())) {
            $(this).html(highlightText(text, searchTerm));
        }
    });
});
<?php endif; ?>
</script>

<?php require_once ROOT_PATH . 'layouts/footer.php'; ?>