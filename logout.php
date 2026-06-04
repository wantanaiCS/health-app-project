<?php
// เริ่ม session เพื่อที่จะเข้าถึงและทำลายมัน
session_start();

// ลบตัวแปร session ทั้งหมด
session_unset();

// ทำลาย session ที่มีอยู่
session_destroy();

// ส่งผู้ใช้กลับไปยังหน้า login
header("Location: login.php");
exit();
?>