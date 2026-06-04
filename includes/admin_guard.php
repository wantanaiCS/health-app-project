<?php
// ไฟล์นี้จะถูกเรียกใช้ที่ด้านบนสุดของทุกหน้าในระบบ Admin

// ตรวจสอบว่า session เริ่มแล้วหรือยัง
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบว่า 1. ล็อกอินหรือยัง? 2. มี role หรือไม่? 3. role เป็น admin หรือไม่?
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // ถ้าเงื่อนไขไม่ผ่าน ให้ส่งกลับไปหน้าแรกทันที
    // เราใช้ ../index.php เพราะไฟล์ admin จะอยู่ในโฟลเดอร์ย่อย
    header('Location: ../index.php');
    exit();
}
?>