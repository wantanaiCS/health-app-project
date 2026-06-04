<?php
// --- 1. เรียกใช้ไฟล์ที่จำเป็น ---
require_once '../includes/admin_guard.php';
require_once '../includes/db_connect.php';

$page_title = "จัดการผู้ใช้";
require_once '../includes/header.php';

// --- 2. จัดการการค้นหาและ Pagination (ถ้ามี) ---
$items_per_page = 15;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// รับค่าคำค้นหา
$search_term = $_GET['search'] ?? '';

// เตรียมตัวแปรสำหรับสร้าง Query แบบไดนามิก
$where_clause = "";
$params_values = [];
$param_types = "";

if (!empty($search_term)) {
    $where_clause = " WHERE username LIKE ? OR email LIKE ?";
    $param_types .= "ss";
    $params_values[] = "%" . $search_term . "%";
    $params_values[] = "%" . $search_term . "%";
}

// --- 3. นับจำนวนผู้ใช้ทั้งหมดที่ตรงกับเงื่อนไข ---
$sql_count = "SELECT COUNT(*) FROM users" . $where_clause;
$stmt_count = $conn->prepare($sql_count);
if (!empty($param_types)) {
    $stmt_count->bind_param($param_types, ...$params_values);
}
$stmt_count->execute();
$total_items = $stmt_count->get_result()->fetch_row()[0];
$total_pages = ceil($total_items / $items_per_page);
$stmt_count->close();

// --- 4. ดึงข้อมูลผู้ใช้สำหรับหน้าปัจจุบัน ---
$sql_fetch = "SELECT id, username, email, role, created_at FROM users" . $where_clause . " ORDER BY id DESC LIMIT ? OFFSET ?";
$param_types .= "ii"; // เพิ่ม type สำหรับ LIMIT และ OFFSET
$params_values[] = $items_per_page;
$params_values[] = $offset;

$stmt_fetch = $conn->prepare($sql_fetch);
$stmt_fetch->bind_param($param_types, ...$params_values);
$stmt_fetch->execute();
$result = $stmt_fetch->get_result();

?>

<div class="container my-5" style="padding-top: 50px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-people-fill me-2"></i>จัดการผู้ใช้</h1>
        <a href="edit_user.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> เพิ่มผู้ใช้ใหม่</a>
    </div>

    <div class="card planner-section mb-4">
        <div class="card-body">
            <form action="manage_users.php" method="GET" class="row g-2 align-items-center">
                <div class="col">
                    <input type="text" name="search" class="form-control" placeholder="ค้นหาตามชื่อผู้ใช้ หรืออีเมล..." value="<?php echo htmlspecialchars($search_term); ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">ค้นหา</button>
                </div>
                 <div class="col-auto">
                    <a href="manage_users.php" class="btn btn-secondary">ล้างค่า</a>
                </div>
            </form>
        </div>
    </div>
    <div class="card planner-section">
        <div class="card-body">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th scope="col" style="width: 5%;">ID</th>
                        <th scope="col" style="width: 25%;">ชื่อผู้ใช้</th>
                        <th scope="col" style="width: 30%;">อีเมล</th>
                        <th scope="col" style="width: 15%;">สิทธิ์</th>
                        <th scope="col" style="width: 15%;">สร้างเมื่อ</th>
                        <th scope="col" style="width: 10%;" class="text-end">เครื่องมือ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($user = $result->fetch_assoc()): ?>
                            <tr>
                                <th scope="row"><?php echo $user['id']; ?></th>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['role']); ?></td>
                                <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                                <td class="text-end">
                                    <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil-fill"></i> แก้ไข
                                    </a>
                                    <a href="process/user_action.php?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบผู้ใช้คนนี้อย่างถาวร?');">
                                        <i class="bi bi-trash3-fill"></i> ลบ
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">ไม่พบผู้ใช้งาน<?php if(!empty($search_term)) echo "ที่ตรงกับคำค้นหา"; ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <nav aria-label="Page navigation" class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($total_pages > 1): ?>
                <?php
                    $query_params = $_GET;
                    unset($query_params['page']);
                ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php if ($i == $current_page) echo 'active'; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($query_params, ['page' => $i])); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            <?php endif; ?>
        </ul>
    </nav>
</div>

<?php 
$stmt_fetch->close();
$conn->close();
require_once '../includes/footer.php'; 
?>