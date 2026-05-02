<?php
session_start();

// Initialize WAF
require_once "../waf_init.php";

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include "../Class/Block.php";

$response = ['success' => false, 'message' => 'Unknown error'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $traffic_id = isset($_POST['traffic_id']) ? intval($_POST['traffic_id']) : 0;
    $source_ip = isset($_POST['source_ip']) ? $_POST['source_ip'] : '';

    $block = new Block();

    if ($action === 'block' && $traffic_id > 0 && !empty($source_ip)) {
        // Block the IP and mark traffic as blocked
        $result = $block->blockIP($source_ip, $traffic_id);
        if ($result) {
            $response = ['success' => true, 'message' => 'IP blocked successfully'];
        } else {
            $response = ['success' => false, 'message' => 'Failed to block IP'];
        }
    } elseif ($action === 'unblock' && $traffic_id > 0 && !empty($source_ip)) {
        // Unblock the IP and mark traffic as unblocked
        $result = $block->unblockIP($source_ip, $traffic_id);
        if ($result) {
            $response = ['success' => true, 'message' => 'IP unblocked successfully'];
        } else {
            $response = ['success' => false, 'message' => 'Failed to unblock IP'];
        }
    } else {
        $response = ['success' => false, 'message' => 'Invalid action, traffic ID, or IP address'];
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>

