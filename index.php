<?php
// File: index.php
// Purpose: Root index file - redirects to login or dashboard based on session

// Error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if config files exist
if (!file_exists('config/config.php')) {
    die('Error: config/config.php not found. Please ensure all configuration files are in place.');
}

if (!file_exists('config/session.php')) {
    die('Error: config/session.php not found. Please ensure all configuration files are in place.');
}

if (!file_exists('includes/functions.php')) {
    die('Error: includes/functions.php not found. Please ensure all include files are in place.');
}

// Include required files
require_once 'config/config.php';
require_once 'config/session.php';
require_once 'includes/functions.php';

// Check if user is logged in and redirect accordingly
if (isLoggedIn()) {
    // User is logged in, redirect based on role
    if (isAdmin()) {
        header('Location: pages/dashboard_admin.php');
    } else {
        header('Location: pages/dashboard_user.php');
    }
} else {
    // User is not logged in, redirect to login page
    header('Location: login.php');
}

exit();