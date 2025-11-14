<?php
// File: test_sanitize.php
// Purpose: Test what the sanitize function does to role/status values

require_once 'config/config.php';
require_once 'includes/functions.php';

echo "<h3>Testing sanitize() function:</h3>";

$testValues = [
    'admin' => sanitize('admin'),
    'user' => sanitize('user'),
    'Active' => sanitize('Active'),
    'Inactive' => sanitize('Inactive'),
];

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Original</th><th>After sanitize()</th><th>Match?</th></tr>";

foreach ($testValues as $original => $sanitized) {
    $match = $original === $sanitized ? '✅ YES' : '❌ NO';
    echo "<tr>";
    echo "<td><code>{$original}</code></td>";
    echo "<td><code>{$sanitized}</code></td>";
    echo "<td>{$match}</td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";
echo "<h3>Testing htmlspecialchars():</h3>";

$testValues2 = [
    'admin' => htmlspecialchars('admin', ENT_QUOTES, 'UTF-8'),
    'user' => htmlspecialchars('user', ENT_QUOTES, 'UTF-8'),
    'Active' => htmlspecialchars('Active', ENT_QUOTES, 'UTF-8'),
    'Inactive' => htmlspecialchars('Inactive', ENT_QUOTES, 'UTF-8'),
];

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Original</th><th>After htmlspecialchars()</th><th>Match?</th></tr>";

foreach ($testValues2 as $original => $escaped) {
    $match = $original === $escaped ? '✅ YES' : '❌ NO';
    echo "<tr>";
    echo "<td><code>{$original}</code></td>";
    echo "<td><code>{$escaped}</code></td>";
    echo "<td>{$match}</td>";
    echo "</tr>";
}

echo "</table>";
?>