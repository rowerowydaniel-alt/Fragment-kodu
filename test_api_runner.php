<?php
// Simulate the environment
session_start();
$_SESSION['csrf_token'] = 'test';
$_GET['action'] = 'scan';
$_POST['url'] = 'https://poznajslowo.pl';
$_POST['test_type'] = 'html_elements';
$_POST['csrf_token'] = 'test';

// Include the actual api.php
require 'api/api.php';
