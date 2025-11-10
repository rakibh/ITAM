<?php
// File: layouts/sidebar.php
// Purpose: Sidebar navigation with role-based menu items
?>
<!-- Sidebar -->
<nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse" id="sidebarMenu">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'dashboard') !== false) ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>pages/<?php echo isAdmin() ? 'dashboard_admin' : 'dashboard_user'; ?>.php">
                    <i class="bi bi-speedometer2 me-2"></i>
                    Dashboard
                </a>
            </li>

            <?php if (isAdmin()): ?>
            <!-- User Management (Admin Only) -->
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'users/') !== false) ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>pages/users/list_users.php">
                    <i class="bi bi-people me-2"></i>
                    Users
                </a>
            </li>
            <?php endif; ?>

            <!-- Equipment -->
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'equipment/') !== false) ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>pages/equipment/list_equipment.php">
                    <i class="bi bi-pc-display me-2"></i>
                    Equipment
                </a>
            </li>

            <!-- Network -->
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'network/') !== false) ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>pages/network/list_network_info.php">
                    <i class="bi bi-ethernet me-2"></i>
                    Network
                </a>
            </li>

            <!-- To-Do Tasks -->
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'todos/') !== false) ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>pages/todos/list_todos.php">
                    <i class="bi bi-check2-square me-2"></i>
                    Tasks
                </a>
            </li>

            <!-- Notifications -->
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'notifications/') !== false) ? 'active' : ''; ?>" 
                   href="<?php echo BASE_URL; ?>pages/notifications/list_notifications.php">
                    <i class="bi bi-bell me-2"></i>
                    Notifications
                </a>
            </li>

            <?php if (isAdmin()): ?>
            <!-- Tools & Settings (Admin Only) -->
            <li class="nav-item mt-3">
                <h6 class="sidebar-heading px-3 text-muted">
                    <span>Tools & Settings</span>
                </h6>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_URL; ?>pages/tools/system_settings.php">
                    <i class="bi bi-gear me-2"></i>
                    System Settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_URL; ?>pages/tools/optimize_database.php">
                    <i class="bi bi-lightning me-2"></i>
                    Database Optimize
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_URL; ?>pages/tools/backup_database.php">
                    <i class="bi bi-cloud-download me-2"></i>
                    Database Backup
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_URL; ?>pages/tools/system_logs.php">
                    <i class="bi bi-file-text me-2"></i>
                    System Logs
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>