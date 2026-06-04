<?php
// เริ่มต้น session เพื่อตรวจสอบสถานะการล็อกอิน
session_start();

// ตรวจสอบว่ามี session user_id (หมายความว่าล็อกอินอยู่) หรือไม่
if (isset($_SESSION['user_id'])) {
    // ถ้าล็อกอินอยู่ ให้ส่งไปหน้า dashboard.php
    header('Location: dashboard.php');
    exit(); // จบการทำงานของสคริปต์ทันทีหลังจาก redirect
} else {
    // ถ้ายังไม่ได้ล็อกอิน ให้ส่งไปหน้า login.php
    header('Location: login.php');
    exit(); // จบการทำงานของสคริปต์ทันทีหลังจาก redirect
}
?>