<?php
// 1. ตั้งค่า header ให้ตอบกลับเป็น JSON
header('Content-Type: application/json');

// 2. เรียกใช้ไฟล์เชื่อมต่อ Database และเริ่ม session
//    (ปรับแก้ path ตามโครงสร้างโปรเจกต์ของคุณ)
require_once '../includes/db_connect.php';
session_start();

$response = []; // ตัวแปรสำหรับเก็บผลลัพธ์ที่จะส่งกลับไป

// 3. ตรวจสอบว่าเป็นการส่ง request แบบ POST หรือไม่
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['success'] = false;
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit();
}

// 4. รับข้อมูลที่ส่งมาแบบ JSON
$data = json_decode(file_get_contents('php://input'), true);

// 5. ตรวจสอบว่ามีข้อมูล plan_id และ user_id ครบถ้วนหรือไม่
if (!isset($data['plan_id']) || !isset($_SESSION['user_id'])) {
    $response['success'] = false;
    $response['message'] = 'ข้อมูลไม่ครบถ้วน (Missing plan_id or user_id)';
    echo json_encode($response);
    exit();
}

$plan_id = $data['plan_id'];
$user_id = $_SESSION['user_id'];

try {
    // 6. เตรียมคำสั่ง SQL เพื่อลบข้อมูล โดยตรวจสอบ user_id เพื่อความปลอดภัย
    $sql = "DELETE FROM daily_plans WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $plan_id, $user_id);

    // 7. สั่งรันคำสั่ง SQL
    if ($stmt->execute()) {
        // ตรวจสอบว่ามีแถวที่ถูกลบจริงหรือไม่
        if ($stmt->affected_rows > 0) {
            // **นี่คือส่วนสำคัญ: การตอบกลับเมื่อลบสำเร็จ**
            $response['success'] = true;
            $response['message'] = 'ลบแผนสำเร็จ';
        } else {
            // ไม่มีการลบเกิดขึ้น (อาจเพราะ plan_id ไม่มีอยู่ หรือไม่ใช่ของ user คนนี้)
            $response['success'] = false;
            $response['message'] = 'ไม่พบแผนที่ต้องการลบ หรือคุณไม่มีสิทธิ์';
        }
    } else {
        // กรณีที่คำสั่ง SQL ทำงานผิดพลาด
        $response['success'] = false;
        $response['message'] = 'Server error: ' . $stmt->error;
    }
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    // กรณีเกิดข้อผิดพลาดอื่นๆ ใน Server
    $response['success'] = false;
    $response['message'] = 'Server exception: ' . $e->getMessage();
}

// 8. ส่งผลลัพธ์กลับไปในรูปแบบ JSON
echo json_encode($response);

?>