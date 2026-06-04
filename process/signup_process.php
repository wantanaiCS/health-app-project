<?php
// 1. เรียกใช้งานไฟล์เชื่อมต่อฐานข้อมูล
// require_once จะทำการนำโค้ดจากไฟล์ db_connect.php เข้ามารวมไว้ตรงนี้
// และถ้าหากหาไฟล์ไม่เจอ สคริปต์จะหยุดทำงานทันที (ซึ่งเป็นสิ่งที่ดี)
require_once('../includes/db_connect.php');

// --- ต่อจากนี้โค้ดจะเหมือนเดิม แต่เราสามารถใช้ตัวแปร $conn ได้เลย ---

// 2. รับค่าจากฟอร์ม
$username = $_POST['username'];
$email = $_POST['email'];
$password = $_POST['password'];

// 3. การตรวจสอบข้อมูล (Validation)
if (empty($username) || empty($email) || empty($password)) {
    die("กรุณากรอกข้อมูลให้ครบถ้วน");
}

// 4. การเข้ารหัสรหัสผ่าน (Hashing)
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// 5. บันทึกลงฐานข้อมูล
// สังเกตว่าเราสามารถใช้ตัวแปร $conn ได้เลย เพราะมันถูกสร้างมาจากไฟล์ db_connect.php แล้ว
$sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $username, $email, $hashed_password);

if ($stmt->execute()) {
  echo "สมัครสมาชิกสำเร็จ!";
  // เมื่อสำเร็จ ให้ส่งผู้ใช้ไปยังหน้า Login
  header("Location: ../login.php?error=invalid");
  exit(); // ควรใช้ exit() หลัง header() เสมอ
} else {
  echo "เกิดข้อผิดพลาดในการสมัครสมาชิก: " . $stmt->error;
}

// 6. ปิด statement และการเชื่อมต่อเมื่อทำงานเสร็จสิ้น
$stmt->close();
$conn->close();

?>