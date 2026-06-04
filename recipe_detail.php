<?php
session_start();
require_once 'includes/db_connect.php';

// ตรวจสอบและรับ ID จาก URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ไม่พบเมนูที่ต้องการ");
}

$recipe_id = (int)$_GET['id'];

// --- 1. ดึงข้อมูลเมนูหลัก ---
$sql_recipe = "SELECT * FROM recipes WHERE id = ? LIMIT 1";
$stmt_recipe = $conn->prepare($sql_recipe);
$stmt_recipe->bind_param("i", $recipe_id);
$stmt_recipe->execute();
$result_recipe = $stmt_recipe->get_result();
$recipe = $result_recipe->fetch_assoc();
$stmt_recipe->close(); // ปิดแค่ statement ของ query นี้

// --- 2. ดึงข้อมูลความสัมพันธ์ (Goals, Diseases, Diet Types) ---
$related_goals = [];
$sql_goals = "SELECT g.name, rg.suitability, rg.note FROM recipe_goals rg JOIN goals g ON rg.goal_id = g.id WHERE rg.recipe_id = ?";
$stmt_goals = $conn->prepare($sql_goals);
$stmt_goals->bind_param("i", $recipe_id);
$stmt_goals->execute();
$result_goals = $stmt_goals->get_result();
while($row = $result_goals->fetch_assoc()) {
    $related_goals[] = $row;
}
$stmt_goals->close();

$related_diseases = [];
$sql_diseases = "SELECT d.name, rd.suitability, rd.note FROM recipe_diseases rd JOIN diseases d ON rd.disease_id = d.id WHERE rd.recipe_id = ?";
$stmt_diseases = $conn->prepare($sql_diseases);
$stmt_diseases->bind_param("i", $recipe_id);
$stmt_diseases->execute();
$result_diseases = $stmt_diseases->get_result();
while($row = $result_diseases->fetch_assoc()) {
    $related_diseases[] = $row;
}
$stmt_diseases->close();

$related_diet_types = [];
$sql_diet_types = "SELECT dt.name, rdt.suitability, rdt.note FROM recipe_diet_types rdt JOIN diet_types dt ON rdt.diet_type_id = dt.id WHERE rdt.recipe_id = ?";
$stmt_diet_types = $conn->prepare($sql_diet_types);
$stmt_diet_types->bind_param("i", $recipe_id);
$stmt_diet_types->execute();
$result_diet_types = $stmt_diet_types->get_result();
while($row = $result_diet_types->fetch_assoc()) {
    $related_diet_types[] = $row;
}
$stmt_diet_types->close();

// --- 3. ปิดการเชื่อมต่อ (ย้ายมาไว้ตรงนี้ หลังจากดึงข้อมูลทั้งหมดเสร็จแล้ว) ---
$conn->close(); 

if (!$recipe) {
    die("ขออภัย ไม่พบข้อมูลเมนูนี้ในระบบ");
}


// กำหนด Title ของหน้า
$page_title = htmlspecialchars($recipe['name']);
require_once 'includes/header.php'; // เรียกใช้ Header หลังกำหนด Title
?>

<div class="container my-5">
    <div class="recipe-detail-header">
        <h1 class="display-5"><?php echo htmlspecialchars($recipe['name']); ?></h1>
        <p class="lead text-muted"><?php echo htmlspecialchars($recipe['description']); ?></p>
    </div>

    <img src="<?php echo htmlspecialchars($recipe['image_url']); ?>" class="recipe-main-image" alt="<?php echo htmlspecialchars($recipe['name']); ?>">

    <div class="recipe-stats-bar">
        <div class="row g-2 w-100">
            <div class="col-6 col-md-4 col-lg-2">
                <div class="recipe-stat">
                    <div class="icon"><i class="bi bi-fire"></i></div>
                    <div class="value"><?php echo round($recipe['calories']); ?></div>
                    <div class="label">แคลอรี่ (Kcal)</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="recipe-stat">
                    <div class="icon"><i class="bi bi-egg-fried"></i></div>
                    <div class="value"><?php echo number_format($recipe['protein'], 1); ?></div>
                    <div class="label">โปรตีน (g)</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="recipe-stat">
                    <div class="icon"><i class="bi bi-basket-fill"></i></div>
                    <div class="value"><?php echo number_format($recipe['carbs'], 1); ?></div>
                    <div class="label">คาร์โบไฮเดรต (g)</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="recipe-stat">
                    <div class="icon"><i class="bi bi-droplet-half"></i></div>
                    <div class="value"><?php echo number_format($recipe['fat'], 1); ?></div>
                    <div class="label">ไขมัน (g)</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="recipe-stat">
                    <div class="icon">
                        <img src="assets/images/icons/sodium-icon.jpg" alt="Sodium Icon" class="stat-icon-img">
                    </div>
                    <div class="value"><?php echo round($recipe['sodium_mg']); ?></div>
                    <div class="label">โซเดียม (mg)</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="recipe-stat">
                    <div class="icon"><i class="bi bi-heart-pulse"></i></div>
                    <div class="value"><?php echo round($recipe['cholesterol_mg']); ?></div>
                    <div class="label">คอเลสเตอรอล (mg)</div>
                </div>
            </div>
        </div>
    </div>

        <div class="text-center mb-4">
        <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNutrition" aria-expanded="false" aria-controls="collapseNutrition">
            <i class="bi bi-chevron-down me-2"></i>แสดง/ซ่อนข้อมูลโภชนาการทั้งหมด
        </button>
    </div>

    <div class="collapse" id="collapseNutrition">
        <div class="recipe-section-card mb-4">
            <h4><i class="bi bi-info-circle-fill me-2"></i>ข้อมูลโภชนาการทั้งหมด (โดยประมาณ)</h4>
            <table class="table table-sm table-borderless table-striped">
                <tbody>
                    <tr>
                        <td>พลังงาน</td>
                        <td class="text-end"><strong><?php echo round($recipe['calories']); ?></strong> Kcal</td>
                    </tr>
                    <tr>
                        <td>โปรตีน</td>
                        <td class="text-end"><strong><?php echo number_format($recipe['protein'], 1); ?></strong> กรัม</td>
                    </tr>
                    <tr>
                        <td>คาร์โบไฮเดรต</td>
                        <td class="text-end"><strong><?php echo number_format($recipe['carbs'], 1); ?></strong> กรัม</td>
                    </tr>
                    <tr>
                        <td>ไขมันทั้งหมด</td>
                        <td class="text-end"><strong><?php echo number_format($recipe['fat'], 1); ?></strong> กรัม</td>
                    </tr>
                    <?php if (isset($recipe['saturated_fat_g'])): ?>
                    <tr class="table-secondary">
                        <td>ไขมันอิ่มตัว</td>
                        <td class="text-end"><strong><?php echo number_format($recipe['saturated_fat_g'], 1); ?></strong> กรัม</td>
                    </tr>
                    <?php endif; ?>
                    <?php if (isset($recipe['sugar_g'])): ?>
                    <tr>
                        <td>น้ำตาล</td>
                        <td class="text-end"><strong><?php echo number_format($recipe['sugar_g'], 1); ?></strong> กรัม</td>
                    </tr>
                    <?php endif; ?>
                    <?php if (isset($recipe['fiber_g'])): ?>
                    <tr>
                        <td>ใยอาหาร</td>
                        <td class="text-end"><strong><?php echo number_format($recipe['fiber_g'], 1); ?></strong> กรัม</td>
                    </tr>
                    <?php endif; ?>
                    <?php if (isset($recipe['cholesterol_mg'])): ?>
                    <tr>
                        <td>คอเลสเตอรอล</td>
                        <td class="text-end"><strong><?php echo round($recipe['cholesterol_mg']); ?></strong> มิลลิกรัม</td>
                    </tr>
                    <?php endif; ?>
                    <?php if (isset($recipe['sodium_mg'])): ?>
                    <tr>
                        <td>โซเดียม</td>
                        <td class="text-end"><strong><?php echo round($recipe['sodium_mg']); ?></strong> มิลลิกรัม</td>
                    </tr>
                    <?php endif; ?>
                    </tbody>
            </table>
        </div>
    </div>
    
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="recipe-section-card">
                <h4><i class="bi bi-list-check me-2"></i>ส่วนผสม</h4>
                <ul class="recipe-ingredients-list">
                    <?php
                        $ingredients = explode(',', $recipe['ingredients']);
                        foreach ($ingredients as $item) {
                            echo '<li>' . htmlspecialchars(trim($item)) . '</li>';
                        }
                    ?>
                </ul>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="recipe-section-card">
                <h4><i class="bi bi-card-checklist me-2"></i>ขั้นตอนการทำ</h4>
                <ol class="recipe-instructions-list">
                    <?php
                        // ใช้ preg_split เพื่อแยกบรรทัดใหม่ที่อาจจะมีทั้ง \n และ \r\n
                        $instructions = preg_split('/\\r\\n|\\r|\\n/', $recipe['instructions']);
                        foreach ($instructions as $step) {
                            $trimmed_step = trim($step);
                            if (!empty($trimmed_step)) {
                                echo '<li>' . htmlspecialchars($trimmed_step) . '</li>';
                            }
                        }
                    ?>
                </ol>
            </div>
        </div>
    </div>

    <div class="mt-5">
        <h3 class="mb-4">สรุปความเหมาะสมของเมนู</h3>
        <div class="row g-4">

            <div class="col-md-4">
                <div class="recipe-section-card">
                    <h5><i class="bi bi-bullseye me-2"></i>เป้าหมายสุขภาพ</h5>
                    <ul class="list-unstyled">
                        <?php foreach($related_goals as $item): ?>
                            <li class="mb-2 d-flex">
                                <?php
                                    if ($item['suitability'] == 'good') {
                                        echo '<i class="bi bi-check-circle-fill text-success me-2 fs-5"></i>';
                                    } elseif ($item['suitability'] == 'caution') {
                                        echo '<i class="bi bi-exclamation-triangle-fill text-warning me-2 fs-5"></i>';
                                    } else {
                                        echo '<i class="bi bi-x-circle-fill text-danger me-2 fs-5"></i>';
                                    }
                                ?>
                                <div>
                                    <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                    <?php if (!empty($item['note'])): ?>
                                        <small class="d-block text-muted">(<?php echo htmlspecialchars($item['note']); ?>)</small>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="col-md-4">
                <div class="recipe-section-card">
                    <h5><i class="bi bi-bandaid-fill me-2"></i>ภาวะสุขภาพ/โรค</h5>
                    <ul class="list-unstyled">
                        <?php foreach($related_diseases as $item): ?>
                            <li class="mb-2 d-flex">
                                <?php
                                    if ($item['suitability'] == 'good') {
                                        echo '<i class="bi bi-check-circle-fill text-success me-2 fs-5"></i>';
                                    } elseif ($item['suitability'] == 'caution') {
                                        echo '<i class="bi bi-exclamation-triangle-fill text-warning me-2 fs-5"></i>';
                                    } else {
                                        echo '<i class="bi bi-x-circle-fill text-danger me-2 fs-5"></i>';
                                    }
                                ?>
                                <div>
                                    <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                    <?php if (!empty($item['note'])): ?>
                                        <small class="d-block text-muted">(<?php echo htmlspecialchars($item['note']); ?>)</small>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="col-md-4">
                <div class="recipe-section-card">
                    <h5><i class="bi bi-leaf-fill me-2"></i>ประเภทการกิน</h5>
                    <ul class="list-unstyled">
                        <?php foreach($related_diet_types as $item): ?>
                            <li class="mb-2 d-flex">
                                <?php
                                    if ($item['suitability'] == 'good') {
                                        echo '<i class="bi bi-check-circle-fill text-success me-2 fs-5"></i>';
                                    } elseif ($item['suitability'] == 'caution') {
                                        echo '<i class="bi bi-exclamation-triangle-fill text-warning me-2 fs-5"></i>';
                                    } else {
                                        echo '<i class="bi bi-x-circle-fill text-danger me-2 fs-5"></i>';
                                    }
                                ?>
                                <div>
                                    <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                    <?php if (!empty($item['note'])): ?>
                                        <small class="d-block text-muted">(<?php echo htmlspecialchars($item['note']); ?>)</small>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

        </div>
    </div>

    <div class="text-center mt-5">
        <a href="recipes.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> กลับไปที่คลังสูตรอาหาร</a>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>