<?php
require_once '../../includes/admin_guard.php'; // สังเกตว่าเราต้องถอยหลัง 2 ชั้น
require_once '../../includes/db_connect.php';

// --- ฟังก์ชันสำหรับจัดการความสัมพันธ์ (เราจะสร้างเป็นฟังก์ชันเพื่อลดการเขียนโค้ดซ้ำซ้อน) ---
// เพิ่ม $has_suitability_note เพื่อบอกว่าตารางนี้มี suitability และ note หรือไม่
function update_relationships($conn, $recipe_id, $data, $table_name, $column_name, $has_suitability_note = false) {
    // 1. ลบความสัมพันธ์เก่าทั้งหมดของเมนูนี้ทิ้งก่อน
    $stmt_delete = $conn->prepare("DELETE FROM $table_name WHERE recipe_id = ?");
    $stmt_delete->bind_param('i', $recipe_id);
    $stmt_delete->execute();
    $stmt_delete->close();

    // 2. ถ้ามีการส่งข้อมูลใหม่มา ให้เพิ่มความสัมพันธ์ใหม่เข้าไป
    if (!empty($data)) {
        $sql_insert = "INSERT INTO $table_name (recipe_id, $column_name";
        $placeholders = [];
        $params_values = [];
        $param_types = "";

        if ($has_suitability_note) {
            $sql_insert .= ", suitability, note)";
        } else {
            $sql_insert .= ")";
        }
        $sql_insert .= " VALUES ";
        
        foreach ($data as $key => $item_info) { // เปลี่ยนเป็น $key => $item_info
            $itemId = null;
            $suitability = null;
            $note = null;

            if ($has_suitability_note) {
                // สำหรับข้อมูลที่มี suitability/note จะส่งมาในรูปแบบ [item_id] => ['id' => item_id, 'suitability' => ..., 'note' => ...]
                if (isset($item_info['id'])) {
                    $itemId = $item_info['id'];
                    $suitability = $item_info['suitability'] ?? 'good';
                    $note = $item_info['note'] ?? null;
                } else {
                    continue; // ถ้าไม่มี 'id' แสดงว่า checkbox ไม่ถูกเลือก
                }
            } else {
                // สำหรับข้อมูลที่ไม่มี suitability/note จะส่งมาในรูปแบบ [0] => item_id, [1] => item_id
                $itemId = $item_info; // $item_info คือ ID โดยตรง
            }

            if ($itemId !== null) { // ตรวจสอบว่ามี ID ที่จะบันทึก
                if ($has_suitability_note) {
                    $placeholders[] = "(?, ?, ?, ?)";
                    $param_types .= "isss";
                    array_push($params_values, $recipe_id, $itemId, $suitability, $note);
                } else {
                    $placeholders[] = "(?, ?)";
                    $param_types .= "ii";
                    array_push($params_values, $recipe_id, $itemId);
                }
            }
        }

        if (!empty($placeholders)) { // ตรวจสอบว่ามีข้อมูลที่จะ insert จริงๆ
            $sql_insert .= implode(', ', $placeholders);
            $stmt_insert = $conn->prepare($sql_insert);
            if ($stmt_insert === false) {
                die('Prepare failed: ' . htmlspecialchars($conn->error));
            }
            $stmt_insert->bind_param($param_types, ...$params_values);
            $stmt_insert->execute();
            $stmt_insert->close();
        }
    }
}

// --- ตรวจสอบ Action ที่ถูกส่งมา ---
$action = $_POST['action'] ?? $_GET['action'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action == 'add' || $action == 'edit')) {
    
    // --- เริ่ม Transaction ---
    $conn->begin_transaction();

    try {
        // --- ส่วนของการเพิ่ม (Add) ---
        if ($action == 'add') {
            // เตรียม SQL สำหรับ INSERT
            $sql = "INSERT INTO recipes (name, description, ingredients, instructions, image_url, calories, protein, carbs, fat, saturated_fat_g, trans_fat_g, cholesterol_mg, sodium_mg, sugar_g, fiber_g, vitamin_a_ug, vitamin_b_mg, vitamin_c_mg, vitamin_d_ug, vitamin_e_mg, vitamin_k_ug, calcium_mg, iron_mg, potassium_mg, magnesium_mg, omega_3_g) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"; // 26 placeholders
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssidddddiiddiddidiiddid", // แก้ไขตรงนี้ให้มี 26 ตัวอักษร
                $_POST['name'], $_POST['description'], $_POST['ingredients'], $_POST['instructions'], $_POST['image_url'],
                $_POST['calories'], $_POST['protein'], $_POST['carbs'], $_POST['fat'],
                $_POST['saturated_fat_g'], $_POST['trans_fat_g'], $_POST['cholesterol_mg'], 
                $_POST['sodium_mg'], $_POST['sugar_g'], $_POST['fiber_g'],
                $_POST['vitamin_a_ug'], $_POST['vitamin_b_mg'], $_POST['vitamin_c_mg'], $_POST['vitamin_d_ug'],
                $_POST['vitamin_e_mg'], $_POST['vitamin_k_ug'], $_POST['calcium_mg'], $_POST['iron_mg'],
                $_POST['potassium_mg'], $_POST['magnesium_mg'], $_POST['omega_3_g']
            );
            $stmt->execute();
            $recipe_id = $conn->insert_id; // ดึง ID ของเมนูที่เพิ่งสร้าง
            $stmt->close();
        } 
        // --- ส่วนของการแก้ไข (Edit) ---
        else { 
            $recipe_id = $_POST['recipe_id'];
            // เตรียม SQL สำหรับ UPDATE
            $sql = "UPDATE recipes SET name = ?, description = ?, ingredients = ?, instructions = ?, image_url = ?, calories = ?, protein = ?, carbs = ?, fat = ?, saturated_fat_g = ?, trans_fat_g = ?, cholesterol_mg = ?, sodium_mg = ?, sugar_g = ?, fiber_g = ?, vitamin_a_ug = ?, vitamin_b_mg = ?, vitamin_c_mg = ?, vitamin_d_ug = ?, vitamin_e_mg = ?, vitamin_k_ug = ?, calcium_mg = ?, iron_mg = ?, potassium_mg = ?, magnesium_mg = ?, omega_3_g = ? WHERE id = ?"; // เพิ่มคอลัมน์ใหม่ที่นี่
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssidddddiiddiddidiiddidi", // แก้ไขตรงนี้ให้มี 27 ตัวอักษร
                $_POST['name'], $_POST['description'], $_POST['ingredients'], $_POST['instructions'], $_POST['image_url'],
                $_POST['calories'], $_POST['protein'], $_POST['carbs'], $_POST['fat'],
                $_POST['saturated_fat_g'], $_POST['trans_fat_g'], $_POST['cholesterol_mg'], 
                $_POST['sodium_mg'], $_POST['sugar_g'], $_POST['fiber_g'],
                $_POST['vitamin_a_ug'], $_POST['vitamin_b_mg'], $_POST['vitamin_c_mg'], $_POST['vitamin_d_ug'],
                $_POST['vitamin_e_mg'], $_POST['vitamin_k_ug'], $_POST['calcium_mg'], $_POST['iron_mg'],
                $_POST['potassium_mg'], $_POST['magnesium_mg'], $_POST['omega_3_g'],
                $recipe_id 
            );
            $stmt->execute();
            $stmt->close();
        }

        // --- อัปเดตความสัมพันธ์ทั้งหมด (ใช้ฟังก์ชันที่เราสร้างไว้) ---
        update_relationships($conn, $recipe_id, $_POST['categories'] ?? [], 'recipe_categories', 'category_id', false); // Categories ไม่มี suitability/note
        update_relationships($conn, $recipe_id, $_POST['tags'] ?? [], 'recipe_tags', 'tag_id', true);
        update_relationships($conn, $recipe_id, $_POST['diseases'] ?? [], 'recipe_diseases', 'disease_id', true);
        update_relationships($conn, $recipe_id, $_POST['goals'] ?? [], 'recipe_goals', 'goal_id', true);
        update_relationships($conn, $recipe_id, $_POST['diet_types'] ?? [], 'recipe_diet_types', 'diet_type_id', true);

        // ถ้าทุกอย่างสำเร็จ ให้ Commit Transaction
        $conn->commit();
        header('Location: ../manage_recipes.php?status=success');

    } catch (Exception $e) {
        // ถ้ามีข้อผิดพลาด ให้ Rollback Transaction (ยกเลิกทั้งหมด)
        $conn->rollback();
        die("เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage());
    }

} 
// --- ส่วนของการลบ (Delete) ---
elseif ($action == 'delete' && isset($_GET['id'])) {
    $recipe_id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM recipes WHERE id = ?");
    $stmt->bind_param("i", $recipe_id);
    if ($stmt->execute()) {
        header('Location: ../manage_recipes.php?status=deleted');
    } else {
        die("เกิดข้อผิดพลาดในการลบข้อมูล");
    }
    $stmt->close();
}
// --- ถ้าไม่มี Action ที่ถูกต้อง ---
else {
    header('Location: ../manage_recipes.php');
}

$conn->close();
?>