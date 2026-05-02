<?php
// Initialize WAF - detects and blocks attacks
require_once 'waf_init.php';

// Start session
session_start();

// Destroy all session data
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>

