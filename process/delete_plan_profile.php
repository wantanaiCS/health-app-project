<?php
/**
 * Backend script to delete a plan profile.
 * Handles deletion from 'plan_profiles' for custom plans
 * and 'weekly_plans' for AI-generated plans.
 */

// 1. ตั้งค่า header, เริ่ม session, และเรียกใช้ไฟล์เชื่อมต่อ Database
header('Content-Type: application/json');
session_start();
require_once '../includes/db_connect.php';

// 2. ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่ และ Method ที่ส่งมาเป็น POST หรือไม่
if (!isset($_SESSION['user_id'])) {
    // ส่งข้อความกลับและหยุดการทำงานทันทีหากยังไม่ได้ล็อกอิน
    echo json_encode(['success' => false, 'message' => 'จำเป็นต้องเข้าสู่ระบบ (Authentication required)']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // ส่งข้อความกลับและหยุดการทำงานหาก Method ไม่ถูกต้อง
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

// 3. รับข้อมูลที่ถูกส่งมาในรูปแบบ JSON
$data = json_decode(file_get_contents("php://input"), true);
$user_id = $_SESSION['user_id'];

// 4. ตรวจสอบว่าข้อมูลที่จำเป็นถูกส่งมาครบถ้วนหรือไม่
if (!isset($data['profile_id']) || !isset($data['plan_type'])) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลที่ส่งมาไม่ครบถ้วน (Missing profile_id or plan_type)']);
    exit();
}

// 5. เตรียมข้อมูลและกำหนดตารางเป้าหมาย
$profile_id = (int)$data['profile_id'];
$plan_type = $data['plan_type'];
$table_name = '';

// ใช้เงื่อนไขเพื่อเลือกตารางที่จะลบข้อมูล (Whitelist)
if ($plan_type === 'custom') {
    $table_name = 'plan_profiles'; // โปรไฟล์ที่ผู้ใช้สร้างเอง
} elseif ($plan_type === 'ai') {
    $table_name = 'weekly_plans';  // แผนที่สร้างโดย AI
} else {
    // ถ้า plan_type ไม่ตรงกับที่กำหนดไว้ ให้แจ้งข้อผิดพลาด
    echo json_encode(['success' => false, 'message' => 'ประเภทของแผนไม่ถูกต้อง']);
    exit();
}

// 6. เตรียมคำสั่ง SQL และดำเนินการลบข้อมูล
try {
    // เตรียม SQL Statement โดยใช้ชื่อตารางที่กำหนดไว้
    // เพิ่มเงื่อนไข user_id = ? เพื่อความปลอดภัยสูงสุด (ให้ผู้ใช้ลบได้เฉพาะข้อมูลของตัวเอง)
    $sql = "DELETE FROM {$table_name} WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    
    // ผูกค่า المتغيرات กับ SQL Statement
    $stmt->bind_param("ii", $profile_id, $user_id);

    // สั่งให้ SQL ทำงาน
    if ($stmt->execute()) {
        // ตรวจสอบว่ามีแถวที่ถูกลบจริงหรือไม่
        if ($stmt->affected_rows > 0) {
            // หากสำเร็จ ส่งข้อความยืนยันกลับไป
            echo json_encode(['success' => true, 'message' => 'ลบโปรไฟล์แผนสำเร็จ']);
        } else {
            // หากไม่พบ ID หรือ ID นั้นไม่ใช่ของ user คนปัจจุบัน
            echo json_encode(['success' => false, 'message' => 'ไม่พบโปรไฟล์ที่ต้องการลบ หรือคุณไม่มีสิทธิ์']);
        }
    } else {
        // กรณีที่คำสั่ง SQL ทำงานผิดพลาด
        throw new Exception('Database execution failed: ' . $stmt->error);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    // กรณีเกิดข้อผิดพลาดอื่นๆ
    // ควร log error ไว้ดู มากกว่าจะแสดงให้ user เห็นโดยตรง
    error_log($e->getMessage()); 
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในฝั่งเซิร์ฟเวอร์']);
}

?>