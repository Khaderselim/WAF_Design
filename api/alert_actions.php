<?php
session_start();

// Initialize WAF
require_once "../waf_init.php";

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include "../Class/Alerts.php";

$response = ['success' => false, 'message' => 'Unknown error'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $alert_id = isset($_POST['alert_id']) ? intval($_POST['alert_id']) : 0;

    $alert = new Alerts();

    if ($action === 'resolve' && $alert_id > 0) {

        $result = $alert->updateAlertStatus($alert_id, 'resolved');
        if ($result) {
            $response = ['success' => true, 'message' => 'Alert resolved successfully'];
        } else {
            $response = ['success' => false, 'message' => 'Failed to resolve alert'];
        }
    } elseif ($action === 'reopen' && $alert_id > 0) {
        $result = $alert->updateAlertStatus($alert_id, 'open');
        if ($result) {
            $response = ['success' => true, 'message' => 'Alert reopened successfully'];
        } else {
            $response = ['success' => false, 'message' => 'Failed to reopen alert'];
        }
    } else {
        $response = ['success' => false, 'message' => 'Invalid action or alert ID'];
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>

