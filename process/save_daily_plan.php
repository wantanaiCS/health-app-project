<?php
// --- Custom Error Handler: บังคับให้แสดง Error ทั้งหมดเป็น JSON ---
// ส่วนนี้จะช่วยให้เราเห็นข้อผิดพลาดที่แท้จริง
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code(500);
        }
        echo json_encode([
            'success' => false,
            'message' => 'FATAL ERROR: ' . $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ]);
        exit();
    }
});

// --- เริ่มโค้ดหลัก ---
session_start();
header('Content-Type: application/json');

// เรียกใช้ไฟล์เชื่อมต่อ Database
// หาก Path นี้ผิด, Error Handler ด้านบนจะทำงานและแสดงข้อความออกมา
require_once '../includes/db_connect.php';

// ตรวจสอบ User Login และ Request Method
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ไม่ได้รับอนุญาต']);
    exit();
}

// รับและถอดรหัสข้อมูลที่ส่งมา
$data = json_decode(file_get_contents('php://input'), true);

// ตรวจสอบความสมบูรณ์ของข้อมูล
if ($data === null || !isset($data['plan_date']) || !isset($data['plan_data'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ข้อมูลที่ส่งมาไม่สมบูรณ์']);
    exit();
}

// เตรียมข้อมูลสำหรับบันทึกลง DB
$user_id = (int)$_SESSION['user_id'];
$plan_date = $data['plan_date'];
$plan_data_json = json_encode($data['plan_data'], JSON_UNESCAPED_UNICODE);
$total_calories = (int)($data['total_calories'] ?? 0);
$total_protein = (float)($data['total_protein'] ?? 0);
$total_carbs_g = (float)($data['total_carbs'] ?? 0);
$total_fat_g = (float)($data['total_fat'] ?? 0);

// SQL Statement
$sql = "INSERT INTO daily_plans (user_id, plan_date, plan_data, total_calories, total_protein, total_carbs_g, total_fat_g, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            plan_data = VALUES(plan_data),
            total_calories = VALUES(total_calories),
            total_protein = VALUES(total_protein),
            total_carbs_g = VALUES(total_carbs_g),
            total_fat_g = VALUES(total_fat_g)";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'SQL prepare failed: ' . $conn->error]);
    exit();
}

$bind_result = $stmt->bind_param("issiddd", 
    $user_id, $plan_date, $plan_data_json, $total_calories, 
    $total_protein, $total_carbs_g, $total_fat_g
);

if ($bind_result === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'SQL bind_param failed: ' . $stmt->error]);
    exit();
}

// Execute และส่งผลลัพธ์
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'บันทึกแผนอาหารของคุณเรียบร้อยแล้ว!']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'SQL execute failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
