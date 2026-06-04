<?php
require_once '../includes/admin_guard.php';
require_once '../includes/db_connect.php';

// --- 1. กำหนดโหมดการทำงาน (Add/Edit) และดึงข้อมูล ---
$mode = 'add';
$recipe_id = null;
$recipe_data = [];
$linked_data = [
    'categories' => [], 'tags' => [], 'diseases' => [],
    'goals' => [], 'diet_types' => []
];

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $mode = 'edit';
    $recipe_id = (int)$_GET['id'];

    // ดึงข้อมูลเมนูหลัก
    $stmt_recipe = $conn->prepare("SELECT * FROM recipes WHERE id = ?");
    $stmt_recipe->bind_param("i", $recipe_id);
    $stmt_recipe->execute();
    $recipe_data = $stmt_recipe->get_result()->fetch_assoc();
    $stmt_recipe->close();

    if (!$recipe_data) {
        die("ไม่พบเมนูที่ต้องการแก้ไข");
    }

    // ดึงข้อมูลความสัมพันธ์ที่เคยบันทึกไว้ พร้อม suitability และ note
    // เราจะปรับให้เก็บเป็น key-value pair โดย key คือ ID และ value คือ suitability/note
    $linked_data['categories'] = array_column($conn->query("SELECT category_id FROM recipe_categories WHERE recipe_id = $recipe_id")->fetch_all(MYSQLI_ASSOC), 'category_id'); // อันนี้ยังคงเดิมเพราะไม่มี suitability
    
    // สำหรับ Tags, Goals, Diseases, Diet Types ที่มี suitability และ note
    $linked_data['tags'] = [];
    $result = $conn->query("SELECT tag_id, suitability, note FROM recipe_tags WHERE recipe_id = $recipe_id");
    while ($row = $result->fetch_assoc()) {
        $linked_data['tags'][$row['tag_id']] = ['suitability' => $row['suitability'], 'note' => $row['note']];
    }

    $linked_data['diseases'] = [];
    $result = $conn->query("SELECT disease_id, suitability, note FROM recipe_diseases WHERE recipe_id = $recipe_id");
    while ($row = $result->fetch_assoc()) {
        $linked_data['diseases'][$row['disease_id']] = ['suitability' => $row['suitability'], 'note' => $row['note']];
    }

    $linked_data['goals'] = [];
    $result = $conn->query("SELECT goal_id, suitability, note FROM recipe_goals WHERE recipe_id = $recipe_id");
    while ($row = $result->fetch_assoc()) {
        $linked_data['goals'][$row['goal_id']] = ['suitability' => $row['suitability'], 'note' => $row['note']];
    }

    $linked_data['diet_types'] = [];
    $result = $conn->query("SELECT diet_type_id, suitability, note FROM recipe_diet_types WHERE recipe_id = $recipe_id");
    while ($row = $result->fetch_assoc()) {
        $linked_data['diet_types'][$row['diet_type_id']] = ['suitability' => $row['suitability'], 'note' => $row['note']];
    }
}

// --- 2. ดึง "ตัวเลือก" ทั้งหมดมาเตรียมไว้ ---
$all_categories = $conn->query("SELECT * FROM categories ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$all_tags = $conn->query("SELECT * FROM tags ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$all_diseases = $conn->query("SELECT * FROM diseases ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$all_goals = $conn->query("SELECT * FROM goals ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$all_diet_types = $conn->query("SELECT * FROM diet_types ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);


$page_title = ($mode == 'edit') ? "แก้ไขเมนู: " . htmlspecialchars($recipe_data['name']) : "เพิ่มเมนูใหม่";
require_once '../includes/header.php';
?>

<div class="container my-5">
    <h1><i class="bi <?php echo ($mode == 'edit') ? 'bi-pencil-square' : 'bi-plus-circle-fill'; ?> me-2"></i><?php echo $page_title; ?></h1>

    <form action="process/recipe_action.php" method="POST">
        <input type="hidden" name="action" value="<?php echo $mode; ?>">
        <?php if ($mode == 'edit'): ?>
            <input type="hidden" name="recipe_id" value="<?php echo $recipe_id; ?>">
        <?php endif; ?>

        <div class="card planner-section">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">ชื่อเมนู</label>
                        <input type="text" class="form-control" name="name" id="name" value="<?php echo htmlspecialchars($recipe_data['name'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="image_url" class="form-label">URL รูปภาพ</label>
                        <input type="text" class="form-control" name="image_url" id="image_url" value="<?php echo htmlspecialchars($recipe_data['image_url'] ?? ''); ?>">
                    </div>
                    <div class="col-12">
                        <label for="description" class="form-label">คำอธิบายเมนู</label>
                        <textarea class="form-control" name="description" id="description" rows="3"><?php echo htmlspecialchars($recipe_data['description'] ?? ''); ?></textarea>
                    </div>
                     <div class="col-12">
                        <label for="ingredients" class="form-label">ส่วนผสม (คั่นด้วยเครื่องหมาย ,)</label>
                        <textarea class="form-control" name="ingredients" id="ingredients" rows="3"><?php echo htmlspecialchars($recipe_data['ingredients'] ?? ''); ?></textarea>
                    </div>
                     <div class="col-12">
                        <label for="instructions" class="form-label">ขั้นตอนการทำ (แต่ละขั้นตอนให้ขึ้นบรรทัดใหม่)</label>
                        <textarea class="form-control" name="instructions" id="instructions" rows="5"><?php echo htmlspecialchars($recipe_data['instructions'] ?? ''); ?></textarea>
                    </div>
                </div>

                <hr class="my-4">

                <h5>หมวดหมู่และประเภท</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <h6>ประเภทมื้ออาหาร</h6>
                        <div class="checkbox-group">
                            <?php foreach($all_categories as $item): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="categories[]" value="<?php echo $item['id']; ?>" id="cat-<?php echo $item['id']; ?>" <?php if(in_array($item['id'], $linked_data['categories'])) echo 'checked'; ?>>
                                    <label class="form-check-label" for="cat-<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['name']); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <h6 class="mb-2">Tags ทั่วไป
                             <button class="btn btn-sm btn-outline-secondary ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTags" aria-expanded="false" aria-controls="collapseTags">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        </h6>
                        <div class="collapse" id="collapseTags"> <div class="checkbox-group">
                                <?php foreach($all_tags as $item): ?>
                                    <?php
                                        $checked = isset($linked_data['tags'][$item['id']]);
                                        $current_suitability = $checked ? $linked_data['tags'][$item['id']]['suitability'] : 'good';
                                        $current_note = $checked ? htmlspecialchars($linked_data['tags'][$item['id']]['note']) : '';
                                    ?>
                                    <div class="form-check mb-2 p-0 border rounded px-3 pt-2 pb-2">
                                        <input class="form-check-input" type="checkbox" name="tags[<?php echo $item['id']; ?>][id]" value="<?php echo $item['id']; ?>" id="tag-<?php echo $item['id']; ?>" <?php if($checked) echo 'checked'; ?>>
                                        <label class="form-check-label" for="tag-<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['name']); ?></label>
                                        
                                        <div class="ms-4 mt-2">
                                            <label for="tag-suitability-<?php echo $item['id']; ?>" class="form-label mb-1"><small>ความเหมาะสม:</small></label>
                                            <select class="form-select form-select-sm" name="tags[<?php echo $item['id']; ?>][suitability]" id="tag-suitability-<?php echo $item['id']; ?>">
                                                <option value="good" <?php if($current_suitability == 'good') echo 'selected'; ?>>ดี</option>
                                                <option value="caution" <?php if($current_suitability == 'caution') echo 'selected'; ?>>ระมัดระวัง</option>
                                                <option value="unsuitable" <?php if($current_suitability == 'unsuitable') echo 'selected'; ?>>ไม่เหมาะสม</option>
                                            </select>
                                            <label for="tag-note-<?php echo $item['id']; ?>" class="form-label mt-2 mb-1"><small>บันทึก:</small></label>
                                            <textarea class="form-control form-control-sm" name="tags[<?php echo $item['id']; ?>][note]" id="tag-note-<?php echo $item['id']; ?>" rows="2"><?php echo $current_note; ?></textarea>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <h6>เป้าหมายสุขภาพ</h6>
                        <div class="checkbox-group">
                            <?php foreach($all_goals as $item): ?>
                                <?php
                                    $checked = isset($linked_data['goals'][$item['id']]);
                                    $current_suitability = $checked ? $linked_data['goals'][$item['id']]['suitability'] : 'good';
                                    $current_note = $checked ? htmlspecialchars($linked_data['goals'][$item['id']]['note']) : '';
                                ?>
                                <div class="form-check mb-2 p-0 border rounded px-3 pt-2 pb-2">
                                    <input class="form-check-input" type="checkbox" name="goals[<?php echo $item['id']; ?>][id]" value="<?php echo $item['id']; ?>" id="goal-<?php echo $item['id']; ?>" <?php if($checked) echo 'checked'; ?>>
                                    <label class="form-check-label" for="goal-<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['name']); ?></label>
                                    
                                    <div class="ms-4 mt-2">
                                        <label for="goal-suitability-<?php echo $item['id']; ?>" class="form-label mb-1"><small>ความเหมาะสม:</small></label>
                                        <select class="form-select form-select-sm" name="goals[<?php echo $item['id']; ?>][suitability]" id="goal-suitability-<?php echo $item['id']; ?>">
                                            <option value="good" <?php if($current_suitability == 'good') echo 'selected'; ?>>ดี</option>
                                            <option value="caution" <?php if($current_suitability == 'caution') echo 'selected'; ?>>ระมัดระวัง</option>
                                            <option value="unsuitable" <?php if($current_suitability == 'unsuitable') echo 'selected'; ?>>ไม่เหมาะสม</option>
                                        </select>
                                        <label for="goal-note-<?php echo $item['id']; ?>" class="form-label mt-2 mb-1"><small>บันทึก:</small></label>
                                        <textarea class="form-control form-control-sm" name="goals[<?php echo $item['id']; ?>][note]" id="goal-note-<?php echo $item['id']; ?>" rows="2"><?php echo $current_note; ?></textarea>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h6>เหมาะสำหรับโรค</h6>
                        <div class="checkbox-group">
                            <?php foreach($all_diseases as $item): ?>
                                <?php
                                    $checked = isset($linked_data['diseases'][$item['id']]);
                                    $current_suitability = $checked ? $linked_data['diseases'][$item['id']]['suitability'] : 'good';
                                    $current_note = $checked ? htmlspecialchars($linked_data['diseases'][$item['id']]['note']) : '';
                                ?>
                                <div class="form-check mb-2 p-0 border rounded px-3 pt-2 pb-2">
                                    <input class="form-check-input" type="checkbox" name="diseases[<?php echo $item['id']; ?>][id]" value="<?php echo $item['id']; ?>" id="disease-<?php echo $item['id']; ?>" <?php if($checked) echo 'checked'; ?>>
                                    <label class="form-check-label" for="disease-<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['name']); ?></label>
                                    
                                    <div class="ms-4 mt-2">
                                        <label for="disease-suitability-<?php echo $item['id']; ?>" class="form-label mb-1"><small>ความเหมาะสม:</small></label>
                                        <select class="form-select form-select-sm" name="diseases[<?php echo $item['id']; ?>][suitability]" id="disease-suitability-<?php echo $item['id']; ?>">
                                            <option value="good" <?php if($current_suitability == 'good') echo 'selected'; ?>>ดี</option>
                                            <option value="caution" <?php if($current_suitability == 'caution') echo 'selected'; ?>>ระมัดระวัง</option>
                                            <option value="unsuitable" <?php if($current_suitability == 'unsuitable') echo 'selected'; ?>>ไม่เหมาะสม</option>
                                        </select>
                                        <label for="disease-note-<?php echo $item['id']; ?>" class="form-label mt-2 mb-1"><small>บันทึก:</small></label>
                                        <textarea class="form-control form-control-sm" name="diseases[<?php echo $item['id']; ?>][note]" id="disease-note-<?php echo $item['id']; ?>" rows="2"><?php echo $current_note; ?></textarea>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h6>ประเภทการกิน</h6>
                        <div class="checkbox-group">
                            <?php foreach($all_diet_types as $item): ?>
                                <?php
                                    $checked = isset($linked_data['diet_types'][$item['id']]);
                                    $current_suitability = $checked ? $linked_data['diet_types'][$item['id']]['suitability'] : 'good';
                                    $current_note = $checked ? htmlspecialchars($linked_data['diet_types'][$item['id']]['note']) : '';
                                ?>
                                <div class="form-check mb-2 p-0 border rounded px-3 pt-2 pb-2">
                                    <input class="form-check-input" type="checkbox" name="diet_types[<?php echo $item['id']; ?>][id]" value="<?php echo $item['id']; ?>" id="diet-<?php echo $item['id']; ?>" <?php if($checked) echo 'checked'; ?>>
                                    <label class="form-check-label" for="diet-<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['name']); ?></label>
                                    
                                    <div class="ms-4 mt-2">
                                        <label for="diet-suitability-<?php echo $item['id']; ?>" class="form-label mb-1"><small>ความเหมาะสม:</small></label>
                                        <select class="form-select form-select-sm" name="diet_types[<?php echo $item['id']; ?>][suitability]" id="diet-suitability-<?php echo $item['id']; ?>">
                                            <option value="good" <?php if($current_suitability == 'good') echo 'selected'; ?>>ดี</option>
                                            <option value="caution" <?php if($current_suitability == 'caution') echo 'selected'; ?>>ระมัดระวัง</option>
                                            <option value="unsuitable" <?php if($current_suitability == 'unsuitable') echo 'selected'; ?>>ไม่เหมาะสม</option>
                                        </select>
                                        <label for="diet-note-<?php echo $item['id']; ?>" class="form-label mt-2 mb-1"><small>บันทึก:</small></label>
                                        <textarea class="form-control form-control-sm" name="diet_types[<?php echo $item['id']; ?>][note]" id="diet-note-<?php echo $item['id']; ?>" rows="2"><?php echo $current_note; ?></textarea>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                 <hr class="my-4">

                <hr class="my-4">
                <h5><i class="bi bi-bar-chart-line-fill me-2"></i>ข้อมูลโภชนาการ</h5>

                <h6 class="mt-4 text-muted">สารอาหารหลัก (Macros)</h6>
                <div class="row g-3">
                    <div class="col-md-3"><label>พลังงาน (Kcal)</label><input type="number" step="0.01" name="calories" class="form-control" value="<?php echo $recipe_data['calories'] ?? ''; ?>"></div>
                    <div class="col-md-3"><label>โปรตีน (g)</label><input type="number" step="0.01" name="protein" class="form-control" value="<?php echo $recipe_data['protein'] ?? ''; ?>"></div>
                    <div class="col-md-3"><label>คาร์โบไฮเดรต (g)</label><input type="number" step="0.01" name="carbs" class="form-control" value="<?php echo $recipe_data['carbs'] ?? ''; ?>"></div>
                    <div class="col-md-3"><label>ไขมันทั้งหมด (g)</label><input type="number" step="0.01" name="fat" class="form-control" value="<?php echo $recipe_data['fat'] ?? ''; ?>"></div>
                </div>

                <h6 class="mt-4 text-muted">รายละเอียดไขมันและคอเลสเตอรอล</h6>
                <div class="row g-3">
                    <div class="col-md-4"><label>ไขมันอิ่มตัว (g)</label><input type="number" step="0.01" name="saturated_fat_g" class="form-control" value="<?php echo $recipe_data['saturated_fat_g'] ?? ''; ?>"></div>
                    <div class="col-md-4"><label>ไขมันทรานส์ (g)</label><input type="number" step="0.01" name="trans_fat_g" class="form-control" value="<?php echo $recipe_data['trans_fat_g'] ?? ''; ?>"></div>
                    <div class="col-md-4"><label>คอเลสเตอรอล (mg)</label><input type="number" step="1" name="cholesterol_mg" class="form-control" value="<?php echo $recipe_data['cholesterol_mg'] ?? ''; ?>"></div>
                </div>

                <h6 class="mt-4 text-muted">น้ำตาล, ใยอาหาร, และโซเดียม</h6>
                <div class="row g-3">
                    <div class="col-md-4"><label>น้ำตาล (g)</label><input type="number" step="0.01" name="sugar_g" class="form-control" value="<?php echo $recipe_data['sugar_g'] ?? ''; ?>"></div>
                    <div class="col-md-4"><label>ใยอาหาร (g)</label><input type="number" step="0.01" name="fiber_g" class="form-control" value="<?php echo $recipe_data['fiber_g'] ?? ''; ?>"></div>
                    <div class="col-md-4"><label>โซเดียม (mg)</label><input type="number" step="0.01" name="sodium_mg" class="form-control" value="<?php echo $recipe_data['sodium_mg'] ?? ''; ?>"></div>
                </div>

                <h6 class="mt-4 text-muted">วิตามิน</h6>
                <div class="row g-3">
                    <div class="col-md-4 col-lg-2"><label>วิตามิน A (µg)</label><input type="number" step="0.01" name="vitamin_a_ug" class="form-control" value="<?php echo $recipe_data['vitamin_a_ug'] ?? ''; ?>"></div>
                    <div class="col-md-4 col-lg-2"><label>วิตามิน B (mg)</label><input type="number" step="0.01" name="vitamin_b_mg" class="form-control" value="<?php echo $recipe_data['vitamin_b_mg'] ?? ''; ?>"></div>
                    <div class="col-md-4 col-lg-2"><label>วิตามิน C (mg)</label><input type="number" step="0.01" name="vitamin_c_mg" class="form-control" value="<?php echo $recipe_data['vitamin_c_mg'] ?? ''; ?>"></div>
                    <div class="col-md-4 col-lg-2"><label>วิตามิน D (µg)</label><input type="number" step="0.01" name="vitamin_d_ug" class="form-control" value="<?php echo $recipe_data['vitamin_d_ug'] ?? ''; ?>"></div>
                    <div class="col-md-4 col-lg-2"><label>วิตามิน E (mg)</label><input type="number" step="0.01" name="vitamin_e_mg" class="form-control" value="<?php echo $recipe_data['vitamin_e_mg'] ?? ''; ?>"></div>
                    <div class="col-md-4 col-lg-2"><label>วิตามิน K (µg)</label><input type="number" step="0.01" name="vitamin_k_ug" class="form-control" value="<?php echo $recipe_data['vitamin_k_ug'] ?? ''; ?>"></div>
                </div>

                <h6 class="mt-4 text-muted">แร่ธาตุและอื่นๆ</h6>
                <div class="row g-3">
                    <div class="col-md-4 col-lg-3"><label>แคลเซียม (mg)</label><input type="number" step="0.01" name="calcium_mg" class="form-control" value="<?php echo $recipe_data['calcium_mg'] ?? ''; ?>"></div>
                    <div class="col-md-4 col-lg-3"><label>ธาตุเหล็ก (mg)</label><input type="number" step="0.01" name="iron_mg" class="form-control" value="<?php echo $recipe_data['iron_mg'] ?? ''; ?>"></div>
                    <div class="col-md-4 col-lg-3"><label>โพแทสเซียม (mg)</label><input type="number" step="0.01" name="potassium_mg" class="form-control" value="<?php echo $recipe_data['potassium_mg'] ?? ''; ?>"></div>
                    <div class="col-md-4 col-lg-3"><label>แมกนีเซียม (mg)</label><input type="number" step="0.01" name="magnesium_mg" class="form-control" value="<?php echo $recipe_data['magnesium_mg'] ?? ''; ?>"></div>
                    <div class="col-md-4 col-lg-3"><label>โอเมก้า 3 (g)</label><input type="number" step="0.01" name="omega_3_g" class="form-control" value="<?php echo $recipe_data['omega_3_g'] ?? ''; ?>"></div>
                </div>

                <div class="d-flex justify-content-end mt-4 sticky-bottom py-3 bg-white border-top" style="z-index: 1020;">
                    <a href="manage_recipes.php" class="btn btn-secondary me-2">ยกเลิก</a>
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