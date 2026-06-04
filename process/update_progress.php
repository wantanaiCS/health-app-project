<?php
session_start();
require_once('../includes/db_connect.php');

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'ไม่ได้รับอนุญาต']);
    exit();
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if ($data === null || !isset($data['plan_date']) || !isset($data['is_completed'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่สมบูรณ์']);
    exit();
}

$plan_date = $data['plan_date'];
$is_completed = $data['is_completed'] ? 1 : 0; // แปลง boolean เป็น 1 หรือ 0

$sql = "INSERT INTO plan_progress (user_id, plan_date, is_completed) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE is_completed = VALUES(is_completed)";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("isi", $user_id, $plan_date, $is_completed);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการบันทึกฐานข้อมูล']);
}

$stmt->close();
$conn->close();
?>