<?php
session_start();
$page_title = "คลังสูตรอาหาร";
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo '<script>window.location.href = "login.php";</script>';
    exit();
}

// --- 1. กำหนดค่าสำหรับ Pagination ---
$items_per_page = 9; // กำหนดจำนวนเมนูที่จะแสดงต่อหนึ่งหน้า (ปรับได้ตามต้องการ)
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// --- 2. ดึงข้อมูลสำหรับสร้างตัวเลือกใน Filter (เหมือนเดิม) ---
$all_categories = $conn->query("SELECT * FROM categories ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$all_goals = $conn->query("SELECT * FROM goals ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$all_diseases = $conn->query("SELECT * FROM diseases ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$all_diet_types = $conn->query("SELECT * FROM diet_types ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

// --- 3. รับค่าที่ผู้ใช้เลือกมาจาก Filter (เหมือนเดิม) ---
$search_term = $_GET['search'] ?? '';
$category_id = $_GET['category_id'] ?? '';
$goal_id = $_GET['goal_id'] ?? '';
$disease_id = $_GET['disease_id'] ?? '';
$diet_type_id = $_GET['diet_type_id'] ?? '';


// --- 4. สร้าง SQL Query แบบไดนามิก (ส่วนนี้มีการเปลี่ยนแปลง) ---
$sql_base = "SELECT DISTINCT r.id FROM recipes r"; // เปลี่ยนเป็นนับ id ก่อน
$joins = [];
$wheres = [];
$params_values = [];
$param_types = "";

// สร้างเงื่อนไข Join และ Where (เหมือนเดิม)
if (!empty($search_term)) { $wheres[] = "r.name LIKE ?"; $param_types .= "s"; $params_values[] = "%" . $search_term . "%"; }
if (!empty($category_id)) { $joins['recipe_categories'] = " JOIN recipe_categories rc ON r.id = rc.recipe_id "; $wheres[] = "rc.category_id = ?"; $param_types .= "i"; $params_values[] = $category_id; }
if (!empty($goal_id)) { $joins['recipe_goals'] = " JOIN recipe_goals rg ON r.id = rg.recipe_id "; $wheres[] = "rg.goal_id = ?"; $wheres[] = "rg.suitability = 'good'"; $param_types .= "i"; $params_values[] = $goal_id; }
if (!empty($disease_id)) { $joins['recipe_diseases'] = " JOIN recipe_diseases rd ON r.id = rd.recipe_id "; $wheres[] = "rd.disease_id = ?"; $wheres[] = "rd.suitability = 'good'"; $param_types .= "i"; $params_values[] = $disease_id; }
if (!empty($diet_type_id)) { $joins['recipe_diet_types'] = " JOIN recipe_diet_types rdt ON r.id = rdt.recipe_id "; $wheres[] = "rdt.diet_type_id = ?"; $wheres[] = "rdt.suitability = 'good'"; $param_types .= "i"; $params_values[] = $diet_type_id; }

// --- 5. สร้าง SQL เพื่อ 'นับจำนวน' ผลลัพธ์ทั้งหมด (เวอร์ชันแก้ไข) ---
$sql_count_base = "SELECT COUNT(DISTINCT r.id) FROM recipes r";
$sql_count = $sql_count_base . implode(" ", $joins);
if (!empty($wheres)) {
    $sql_count .= " WHERE " . implode(" AND ", $wheres);
}

$stmt_count = $conn->prepare($sql_count);
if (!empty($param_types)) { $stmt_count->bind_param($param_types, ...$params_values); }
$stmt_count->execute();
$total_items = $stmt_count->get_result()->fetch_row()[0];
$stmt_count->close();
$total_pages = ceil($total_items / $items_per_page);


// --- 6. สร้าง SQL Query เพื่อ 'ดึงข้อมูล' มาแสดงผลในหน้าปัจจุบัน ---
$sql_fetch = "SELECT r.* FROM recipes r" . implode(" ", $joins);
if (!empty($wheres)) { $sql_fetch .= " WHERE " . implode(" AND ", $wheres); }
$sql_fetch .= " ORDER BY r.name ASC LIMIT ? OFFSET ?";
$param_types .= "ii"; // เพิ่ม type integer 2 ตัวสำหรับ LIMIT และ OFFSET
$params_values[] = $items_per_page;
$params_values[] = $offset;

$stmt_fetch = $conn->prepare($sql_fetch);
if (!empty($param_types)) { $stmt_fetch->bind_param($param_types, ...$params_values); }
$stmt_fetch->execute();
$result = $stmt_fetch->get_result();

?>

    <div class="container my-5" style="padding-top: 50px;">
        <div class="search-header">
            <h1 class="text-center mb-3 gradient-text"><i class="bi bi-funnel-fill text-primary me-2"></i>ค้นหาเมนูอาหารอย่างละเอียด</h1>
            <form action="recipes.php" method="GET">
                <div class="row g-2 align-items-end">
                    <div class="col-lg-3 col-md-12">
                        <label for="search" class="form-label small">ชื่อเมนู</label>
                        <input type="text" name="search" id="search" class="form-control" placeholder="เช่น 'ไก่', 'ปลา', 'ผัด'..." value="<?php echo htmlspecialchars($search_term); ?>">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label for="category_id" class="form-label small">ประเภทมื้ออาหาร</label>
                        <select name="category_id" id="category_id" class="form-select">
                            <option value="">ทั้งหมด</option>
                            <?php foreach($all_categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php if($category_id == $cat['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label for="goal_id" class="form-label small">เป้าหมายสุขภาพ</label>
                        <select name="goal_id" id="goal_id" class="form-select">
                            <option value="">ทั้งหมด</option>
                            <?php foreach($all_goals as $goal): ?>
                                <option value="<?php echo $goal['id']; ?>" <?php if($goal_id == $goal['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($goal['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label for="disease_id" class="form-label small">เหมาะสำหรับโรค</label>
                        <select name="disease_id" id="disease_id" class="form-select">
                            <option value="">ทั้งหมด</option>
                            <?php foreach($all_diseases as $disease): ?>
                                <option value="<?php echo $disease['id']; ?>" <?php if($disease_id == $disease['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($disease['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label for="diet_type_id" class="form-label small">ประเภทการกิน</label>
                        <select name="diet_type_id" id="diet_type_id" class="form-select">
                            <option value="">ทั้งหมด</option>
                            <?php foreach($all_diet_types as $diet): ?>
                                <option value="<?php echo $diet['id']; ?>" <?php if($diet_type_id == $diet['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($diet['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-1 col-md-12 d-grid mt-3 mt-lg-0">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>กรอง</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mt-4">
            <?php if ($result->num_rows > 0): ?>
                <?php while($recipe = $result->fetch_assoc()): ?>
<div class="col ">
    <div class="recipe-card card shadow-sm border border border-dark rounded-4 overflow-hidden h-100 ">
        <img src="<?php echo htmlspecialchars($recipe['image_url']); ?>" 
             class="card-img-top" 
             alt="<?php echo htmlspecialchars($recipe['name']); ?>">

        <div class="card-body d-flex flex-column  p-4">
            <h5 class="card-title fw-bold text-center text-dark mb-1">
                <?php echo htmlspecialchars($recipe['name']); ?>
            </h5>
            <p class="text-muted small mb-2">
                <?php echo htmlspecialchars(mb_substr($recipe['description'], 0, 100) . '...'); ?>
            </p>

            <!-- Nutri info ล็อกไว้ล่าง -->
            <div class="nutri-info mt-auto">
                <div><strong>แคลอรี่:</strong> <?php echo $recipe['calories']; ?> kcal &nbsp;|&nbsp; <strong>โปรตีน:</strong> <?php echo $recipe['protein']; ?>g</div>
                <div><strong>ไขมัน:</strong> <?php echo $recipe['fat']; ?>g &nbsp;|&nbsp; <strong>คาร์บ:</strong> <?php echo $recipe['carbs']; ?>g &nbsp;|&nbsp; <strong><br>โซเดียม:</strong> <?php echo $recipe['sodium_mg']; ?>mg</div>
            </div>
        </div>

        <div class="card-footer text-center bg-white border-0 pb-4">
            <a href="recipe_detail.php?id=<?php echo $recipe['id']; ?>" 
               class="btn btn-sm btn-outline-primary rounded-pill px-4">
               ดูวิธีทำและรายละเอียด
            </a>
        </div>
    </div>
</div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-warning text-center">ไม่พบเมนูอาหารที่ตรงกับเงื่อนไขที่คุณเลือก</div>
                </div>
            <?php endif; ?>
        </div>
        <nav aria-label="Page navigation" class="mt-5">
            <ul class="pagination justify-content-center">
                <?php if ($total_pages > 1): ?>
                    <?php
                        $query_params = $_GET;
                        unset($query_params['page']);
                    ?>

                    <li class="page-item <?php if($current_page <= 1) echo 'disabled'; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($query_params, ['page' => $current_page - 1])); ?>">ก่อนหน้า</a>
                    </li>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php if ($i == $current_page) echo 'active'; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($query_params, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?php if($current_page >= $total_pages) echo 'disabled'; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($query_params, ['page' => $current_page + 1])); ?>">ถัดไป</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
<?php require_once 'includes/footer.php'; ?>