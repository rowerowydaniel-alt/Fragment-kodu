<?php
// Simulating api.php line 1-10
$action = 'list';
session_start(); // This is what's missing in api.php
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = 'test';
}

if ($action === 'list') {
    echo json_encode([
        'csrf_token' => $_SESSION['csrf_token'] ?? '',
        'tests' => []
    ]);
    exit;
}
