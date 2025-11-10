<?php
// File: logout.php
// Purpose: Logout user and redirect to login page

require_once 'config/config.php';
require_once 'config/session.php';
require_once 'includes/functions.php';

// Log activity before destroying session
if (isLoggedIn()) {
    logActivity($pdo, 'Authentication', 'Logout', getCurrentUserName() . ' logged out', 'Info');
}

// Destroy session
destroySession();

// Prevent back button
preventBackButton();

// Redirect to login
header('Location: login.php');
exit();