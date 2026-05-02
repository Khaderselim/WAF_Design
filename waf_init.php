<?php
/**
 * Global WAF Initializer
 * 
 * Include this file at the TOP of every PHP page to:
 * 1. Check if IP is banned
 * 2. Detect and log attacks
 * 3. Block malicious requests
 * 
 * Usage: Add this line at the VERY TOP of dashboard.php, login.php, etc:
 *    require_once 'waf_init.php';
 */

// Prevent multiple inclusions
if (defined('WAF_INITIALIZED')) {
    return;
}
include 'Class/URLAttackChecker.php';
use WAF\Class\URLAttackChecker;

define('WAF_INITIALIZED', true);

try {
    // Step 1: Check if IP is banned (blocks request if needed)
    require_once __DIR__ . '/Class/banlist.php';
    
    // Step 2: Initialize WAF and check current request
    require_once __DIR__ . '/vendor/autoload.php';

    // Create WAF instance (singleton pattern)
    static $waf = null;
    if ($waf === null) {
        $waf = new URLAttackChecker();
    }
    
    // Build input string from current request
    $inputParts = array_filter([
        $_SERVER['REQUEST_URI'] ?? '',
        $_SERVER['QUERY_STRING'] ?? '',
        http_build_query($_GET ?? []),
        http_build_query($_POST ?? []),
        (isset($_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'] > 0) 
            ? file_get_contents('php://input') 
            : '',
    ]);
    $inputString = implode(' ', $inputParts) ?: 'GET /';
    
    // Process the request (detects attacks, logs, alerts, blocks IPs)
    $wafResult = $waf->processRequest($inputString);
    
    // If IP was blocked by threat detection, return 403
    if ($wafResult['success'] && $wafResult['ip_blocked']) {
        http_response_code(403);
        header('Content-Type: application/json');
        die(json_encode([
            'error' => 'Your request has been blocked by our security system',
            'code' => 'THREAT_DETECTED',
            'threat_score' => $wafResult['classification']['threat_score'],
            'ip' => $wafResult['ip'],
            'log_id' => $wafResult['traffic_log_id']
        ]));
    }
    
    // Make WAF result available globally (optional, for logging/debugging)
    global $WAF_RESULT;
    $WAF_RESULT = $wafResult;
    
} catch (Exception $e) {
    // Log WAF errors but don't break the application
    error_log("WAF Error: " . $e->getMessage());
    // Continue with page - WAF should not break the application
}
?>

