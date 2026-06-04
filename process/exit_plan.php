<?php
session_start();
header('Content-Type: application/json');

// ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'จำเป็นต้องเข้าสู่ระบบ']);
    exit();
}

require_once '../includes/db_connect.php';

$user_id = $_SESSION['user_id'];
$response = ['success' => false, 'message' => 'เกิดข้อผิดพลาดที่ไม่รู้จัก'];

// เริ่ม transaction เพื่อความปลอดภัย
$conn->begin_transaction();

try {
    // ลบข้อมูลแผนที่กำลังใช้งานทั้งหมดของผู้ใช้ออกจากตาราง plan_progress
    $sql_delete_progress = "DELETE FROM plan_progress WHERE user_id = ?";
    $stmt = $conn->prepare($sql_delete_progress);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $conn->commit();
        $response = ['success' => true, 'message' => 'คุณได้ออกจากแผนปัจจุบันเรียบร้อยแล้ว'];
    } else {
        throw new Exception('ไม่สามารถลบข้อมูลแผนได้');
    }
    $stmt->close();

} catch (Exception $e) {
    $conn->rollback();
    $response = ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
}

$conn->close();
echo json_encode($response);
?>