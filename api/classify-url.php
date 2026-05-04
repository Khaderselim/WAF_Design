<?php
// Must be first — captures any PHP errors/warnings as JSON instead of breaking output
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
require_once "../waf_init.php";

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

// Buffer output so stray warnings don't break JSON
ob_start();

require_once '../vendor/autoload.php';

use WAF\Class\URLAttackChecker;

$url = $_POST['url'] ?? '';
if (empty($url)) {
    ob_end_clean();
    echo json_encode(['error' => 'No URL provided']);
    exit;
}

try {
    $checker = new URLAttackChecker();
    $result  = $checker->classifyInput(urldecode($url));

    ob_end_clean();
    echo json_encode($result);
} catch (Throwable $e) {
    $buffer = ob_get_clean();
    echo json_encode([
        'error'   => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
        'output'  => $buffer,  // any stray output that broke JSON
    ]);
}