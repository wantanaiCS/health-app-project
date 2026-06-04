<?php
// เริ่ม session เสมอเมื่อมีการทำงานเกี่ยวกับ user
session_start();

// เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล
require_once('../includes/db_connect.php');

// ตรวจสอบว่ามีการส่งข้อมูลมาแบบ POST หรือไม่
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit();
}

// รับค่าจากฟอร์ม
$username = $_POST['username'];
$password = $_POST['password'];

// ตรวจสอบข้อมูลเบื้องต้น
if (empty($username) || empty($password)) {
    header('Location: ../login.php?error=empty');
    exit();
}

// ค้นหาผู้ใช้ในฐานข้อมูล
$sql = "SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// ตรวจสอบว่าเจอผู้ใช้ และรหัสผ่านตรงกันหรือไม่
// password_verify() เป็นฟังก์ชันสำหรับเช็ครหัสผ่านที่ถูก hash ไว้
if ($user && password_verify($password, $user['password'])) {
    // ถ้ารหัสผ่านถูกต้อง ให้เก็บข้อมูล user ลงใน session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    // ส่งผู้ใช้ไปยังหน้า dashboard
    header("Location: ../dashboard.php");
    exit();
} else {
    // ถ้าไม่เจอผู้ใช้ หรือรหัสผ่านผิด
    // ให้ส่งกลับไปหน้า login พร้อมกับ error
    header("Location: ../login.php?error=1");
    exit();
}

$stmt->close();
$conn->close();
?>