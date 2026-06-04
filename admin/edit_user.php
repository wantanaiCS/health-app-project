<?php
require_once '../includes/admin_guard.php';
require_once '../includes/db_connect.php';

// --- 1. กำหนดโหมดการทำงาน (Add/Edit) และดึงข้อมูล ---
$mode = 'add';
$user_id = null;
$user_data = [];

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $mode = 'edit';
    $user_id = (int)$_GET['id'];

    // ดึงข้อมูลผู้ใช้หลัก
    $stmt_user = $conn->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $user_data = $stmt_user->get_result()->fetch_assoc();
    $stmt_user->close();

    if (!$user_data) {
        die("ไม่พบผู้ใช้ที่ต้องการแก้ไข");
    }
}

$page_title = ($mode == 'edit') ? "แก้ไขผู้ใช้: " . htmlspecialchars($user_data['username']) : "เพิ่มผู้ใช้ใหม่";
require_once '../includes/header.php';
?>

<div class="container my-5">
    <h1><i class="bi <?php echo ($mode == 'edit') ? 'bi-person-badge-fill' : 'bi-person-plus-fill'; ?> me-2"></i><?php echo $page_title; ?></h1>

    <form action="process/user_action.php" method="POST">
        <input type="hidden" name="action" value="<?php echo $mode; ?>">
        <?php if ($mode == 'edit'): ?>
            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
        <?php endif; ?>

        <div class="card planner-section">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="username" class="form-label">ชื่อผู้ใช้</label>
                        <input type="text" class="form-control" name="username" id="username" value="<?php echo htmlspecialchars($user_data['username'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">อีเมล</label>
                        <input type="email" class="form-control" name="email" id="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                    </div>
                    <?php if ($mode == 'add'): // รหัสผ่านจะใส่เฉพาะตอนเพิ่ม ?>
                    <div class="col-md-6">
                        <label for="password" class="form-label">รหัสผ่าน</label>
                        <input type="password" class="form-control" name="password" id="password" required>
                    </div>
                    <div class="col-md-6">
                        <label for="confirm_password" class="form-label">ยืนยันรหัสผ่าน</label>
                        <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
                    </div>
                    <?php else: // ตอนแก้ไขอาจจะเปลี่ยนรหัสผ่านแยก หรือให้ใส่แค่ตอนเพิ่มก็พอ ?>
                    <div class="col-12">
                        <small class="text-muted">หากต้องการเปลี่ยนรหัสผ่าน ให้สร้างฟังก์ชันเปลี่ยนรหัสผ่านแยกต่างหาก หรือเพิ่มช่องสำหรับเปลี่ยนรหัสผ่านตรงนี้</small>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-6">
                        <label for="role" class="form-label">สิทธิ์การใช้งาน</label>
                        <select class="form-select" name="role" id="role" required>
                            <option value="user" <?php if(isset($user_data['role']) && $user_data['role'] == 'user') echo 'selected'; ?>>User</option>
                            <option value="admin" <?php if(isset($user_data['role']) && $user_data['role'] == 'admin') echo 'selected'; ?>>Admin</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <a href="manage_users.php" class="btn btn-secondary me-2">ยกเลิก</a>
                    <button type="submit" class="btn btn-primary">บันทึกข้อมูล</button>
                </div>
            </div>
        </div>
    </form>
</div>

<?php 
$conn->close();
require_once '../includes/footer.php'; 
?>