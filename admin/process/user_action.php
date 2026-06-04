<?php
require_once '../../includes/admin_guard.php'; // สังเกตว่าเราต้องถอยหลัง 2 ชั้น
require_once '../../includes/db_connect.php';

// --- ตรวจสอบ Action ที่ถูกส่งมา ---
$action = $_POST['action'] ?? $_GET['action'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action == 'add' || $action == 'edit')) {
    
    // ดึงข้อมูลที่ส่งมา
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    // --- เริ่ม Transaction ---
    $conn->begin_transaction();

    try {
        // --- ส่วนของการเพิ่ม (Add) ---
        if ($action == 'add') {
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];

            if ($password !== $confirm_password) {
                throw new Exception("รหัสผ่านไม่ตรงกัน");
            }
            
            // Hash รหัสผ่านก่อนบันทึกลงฐานข้อมูล
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // เตรียม SQL สำหรับ INSERT
            $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
            $stmt->execute();
            $stmt->close();
        } 
        // --- ส่วนของการแก้ไข (Edit) ---
        else { 
            $user_id = $_POST['user_id'];
            // เตรียม SQL สำหรับ UPDATE
            // (ไม่ได้รวมการเปลี่ยนรหัสผ่านตรงนี้ เพราะปกติจะแยกฟังก์ชันเปลี่ยนรหัสผ่าน)
            $sql = "UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $username, $email, $role, $user_id);
            $stmt->execute();
            $stmt->close();
        }

        // ถ้าทุกอย่างสำเร็จ ให้ Commit Transaction
        $conn->commit();
        header('Location: ../manage_users.php?status=success');

    } catch (Exception $e) {
        // ถ้ามีข้อผิดพลาด ให้ Rollback Transaction (ยกเลิกทั้งหมด)
        $conn->rollback();
        die("เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage());
    }

} 
// --- ส่วนของการลบ (Delete) ---
elseif ($action == 'delete' && isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    
    // ป้องกันไม่ให้ลบตัวเอง
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
        die("ไม่สามารถลบบัญชีผู้ใช้ที่คุณกำลังเข้าสู่ระบบอยู่ได้");
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        header('Location: ../manage_users.php?status=deleted');
    } else {
        die("เกิดข้อผิดพลาดในการลบข้อมูล");
    }
    $stmt->close();
}
// --- ถ้าไม่มี Action ที่ถูกต้อง ---
else {
    header('Location: ../manage_users.php');
}

$conn->close();
?>