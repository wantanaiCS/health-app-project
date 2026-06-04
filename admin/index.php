<?php
require_once '../includes/admin_guard.php'; // เรียกใช้การ์ดป้องกันเป็นอันดับแรก

$page_title = "Admin Dashboard";
require_once '../includes/header.php';
?>

<div class="container my-5" style="padding-top: 50px;">
    <h1><i class="bi bi-shield-lock-fill me-2"></i>Admin Dashboard</h1>
    <p class="text-muted">ยินดีต้อนรับสู่ระบบจัดการหลังบ้าน</p>
    <hr>
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">จัดการเมนูอาหาร</h5>
                    <p class="card-text">เพิ่ม, แก้ไข, หรือลบเมนูอาหารและข้อมูลโภชนาการทั้งหมด</p>
                    <a href="manage_recipes.php" class="btn btn-primary">ไปที่หน้าจัดการเมนู</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">จัดการผู้ใช้</h5>
                    <p class="card-text">ดูรายชื่อผู้ใช้ทั้งหมดและจัดการสิทธิ์การเข้าถึง</p>
                    <a href="manage_users.php" class="btn btn-primary">ไปที่หน้าจัดการผู้ใช้</a> </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>