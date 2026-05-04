<?php
session_start();

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../db_connection.php';

$action = $_POST['action'] ?? '';

if ($action === 'delete') {
    $fileId = $_POST['file_id'] ?? 0;

    // Get file path
    $stmt = $conn->prepare("SELECT upload_path FROM uploaded_file WHERE id = ?");
    $stmt->bind_param('i', $fileId);
    $stmt->execute();
    $result = $stmt->get_result();
    $file = $result->fetch_assoc();

    if ($file && file_exists($file['upload_path'])) {
        unlink($file['upload_path']);
    }

    // Delete from database
    $stmt = $conn->prepare("DELETE FROM uploaded_file WHERE id = ?");
    $stmt->bind_param('i', $fileId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'File deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete file']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$conn->close();

