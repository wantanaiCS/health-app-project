<?php
// --- 1. เรียกใช้ไฟล์ที่จำเป็น ---
require_once '../includes/admin_guard.php';
require_once '../includes/db_connect.php';

$page_title = "จัดการเมนูอาหาร";
require_once '../includes/header.php';

// --- 2. จัดการการค้นหาและ Pagination ---
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
    $where_clause = " WHERE name LIKE ?";
    $param_types .= "s";
    $params_values[] = "%" . $search_term . "%";
}

// --- 3. นับจำนวนเมนูทั้งหมดที่ตรงกับเงื่อนไข ---
$sql_count = "SELECT COUNT(*) FROM recipes" . $where_clause;
$stmt_count = $conn->prepare($sql_count);
if (!empty($param_types)) {
    $stmt_count->bind_param($param_types, ...$params_values);
}
$stmt_count->execute();
$total_items = $stmt_count->get_result()->fetch_row()[0];
$total_pages = ceil($total_items / $items_per_page);
$stmt_count->close();


// --- 4. ดึงข้อมูลเมนูสำหรับหน้าปัจจุบัน ---
$sql_fetch = "SELECT id, name FROM recipes" . $where_clause . " ORDER BY id DESC LIMIT ? OFFSET ?";
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
        <h1><i class="bi bi-card-checklist me-2"></i>จัดการเมนูอาหาร</h1>
        <a href="edit_recipe.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> เพิ่มเมนูใหม่</a>
    </div>

    <div class="card planner-section mb-4">
        <div class="card-body">
            <form action="manage_recipes.php" method="GET" class="row g-2 align-items-center">
                <div class="col">
                    <input type="text" name="search" class="form-control" placeholder="ค้นหาตามชื่อเมนู..." value="<?php echo htmlspecialchars($search_term); ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">ค้นหา</button>
                </div>
                 <div class="col-auto">
                    <a href="manage_recipes.php" class="btn btn-secondary">ล้างค่า</a>
                </div>
            </form>
        </div>
    </div>
    <div class="card planner-section">
        <div class="card-body">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th scope="col" style="width: 10%;">ID</th>
                        <th scope="col">ชื่อเมนู</th>
                        <th scope="col" style="width: 20%;" class="text-end">เครื่องมือ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($recipe = $result->fetch_assoc()): ?>
                            <tr>
                                <th scope="row"><?php echo $recipe['id']; ?></th>
                                <td><?php echo htmlspecialchars($recipe['name']); ?></td>
                                <td class="text-end">
                                    <a href="edit_recipe.php?id=<?php echo $recipe['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil-fill"></i> แก้ไข
                                    </a>
                                    <a href="process/recipe_action.php?action=delete&id=<?php echo $recipe['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบเมนูนี้อย่างถาวร?');">
                                        <i class="bi bi-trash3-fill"></i> ลบ
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted">ไม่พบเมนูอาหาร<?php if(!empty($search_term)) echo "ที่ตรงกับคำค้นหา"; ?></td>
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