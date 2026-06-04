<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/functions.php');

// --- 1. ตรวจสอบและดึงข้อมูลโปรไฟล์ผู้ใช้ทั้งหมด ---
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?error=session_expired");
    exit();
}
$user_id = $_SESSION['user_id'];

$sql_profile = "SELECT * FROM user_profiles WHERE user_id = ?";
$stmt_profile = $conn->prepare($sql_profile);
$stmt_profile->bind_param("i", $user_id);
$stmt_profile->execute();
$profile = $stmt_profile->get_result()->fetch_assoc();
$stmt_profile->close();

if (!$profile || empty($profile['goal']) || empty($profile['target_calories'])) {
    $_SESSION['error_message'] = "กรุณากรอกข้อมูลโปรไฟล์ของคุณให้ครบถ้วนก่อนสร้างแผนอาหาร";
    header("Location: ../profile.php");
    exit();
}

// --- 2. เริ่มสร้างแผน 7 วัน ---
$weekly_plan = [];
$used_recipe_ids = []; 

for ($day = 1; $day <= 7; $day++) {
    $daily_plan = [];
    $meal_budgets = [];
    $target_calories_for_day = $profile['target_calories'];

    if ($profile['goal'] == 'เพิ่มน้ำหนัก') {
        $meal_budgets = [
            'มื้อเช้า'     => $target_calories_for_day * 0.25,
            'มื้อว่างเช้า' => $target_calories_for_day * 0.10,
            'มื้อกลางวัน'   => $target_calories_for_day * 0.30,
            'มื้อว่างบ่าย'  => $target_calories_for_day * 0.10,
            'มื้อเย็น'      => $target_calories_for_day * 0.25,
        ];
    } else { // 'ลดน้ำหนัก' หรือ 'รักษาน้ำหนัก'
        $meal_budgets = [
            'มื้อเช้า'     => $target_calories_for_day * 0.30,
            'มื้อกลางวัน'   => $target_calories_for_day * 0.40,
            'มื้อเย็น'      => $target_calories_for_day * 0.30,
        ];
    }

    foreach ($meal_budgets as $meal_name => $budget) {
        
        // --- ฟังก์ชันสำหรับรัน Query ---
        $find_recipe = function($conn, $sql, $types, $params) {
            $stmt = $conn->prepare($sql);
            if (!$stmt) return null;
            if (!empty($types)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $result;
        };

        // --- เตรียม Query หลัก ---
        $sql_base = "SELECT r.id, r.name AS recipe_name, r.description, r.calories, r.image_url FROM recipes r";
        $joins = [];
        $wheres = [];
        $params_values = [];
        $param_types = "";
        
        // --- **BUG FIX:** ทำให้ช่วงแคลอรี่ยืดหยุ่นขึ้นมาก ---
        $min_cal = $budget * 0.4; // ลดจาก 0.7 เป็น 0.4 (ยืดหยุ่นขึ้น)
        $max_cal = $budget * 1.8; // เพิ่มจาก 1.3 เป็น 1.8 (ยืดหยุ่นขึ้น)

        // 1. Join และ Where สำหรับ Category (บังคับ)
        $category_to_search = ($meal_name == 'มื้อว่างเช้า' || $meal_name == 'มื้อว่างบ่าย') ? 'ของว่าง' : $meal_name;
        $joins['categories'] = " JOIN recipe_categories rc ON r.id = rc.recipe_id JOIN categories c ON rc.category_id = c.id ";
        $wheres[] = "c.name = ?";
        $param_types .= "s";
        $params_values[] = $category_to_search;
        
        // 2. Where สำหรับช่วงแคลอรี่ (ยืดหยุ่น)
        $wheres[] = "r.calories BETWEEN ? AND ?";
        $param_types .= "dd";
        array_push($params_values, $min_cal, $max_cal);
        
        // 3. Join และ Where สำหรับ Disease
        if (!empty($profile['disease']) && $profile['disease'] != 'ไม่มี') {
            $joins['diseases'] = " JOIN recipe_diseases rd ON r.id = rd.recipe_id JOIN diseases d ON rd.disease_id = d.id ";
            $wheres[] = "d.name = ?";
            $wheres[] = "(rd.suitability = 'good' OR rd.suitability = 'caution')";
            $param_types .= "s";
            $params_values[] = $profile['disease'];
        }

        // 4. Join และ Where สำหรับ Goal
        if (!empty($profile['goal'])) {
            $joins['goals'] = " JOIN recipe_goals rg ON r.id = rg.recipe_id JOIN goals g ON rg.goal_id = g.id ";
            $wheres[] = "g.name = ?";
            $param_types .= "s";
            $params_values[] = $profile['goal'];
        }

        // 5. Join และ Where สำหรับไม่เอาเมนูซ้ำ
        $used_ids_params = [];
        if (!empty($used_recipe_ids)) {
            $placeholders = implode(',', array_fill(0, count($used_recipe_ids), '?'));
            $wheres[] = "r.id NOT IN ($placeholders)";
            $param_types .= str_repeat('i', count($used_recipe_ids));
            $used_ids_params = $used_recipe_ids;
        }

        // --- พยายามค้นหาเมนู ---
        
        // ครั้งที่ 1: ค้นหาแบบเต็มเงื่อนไข
        $sql_recipe = $sql_base . implode(" ", $joins) . " WHERE " . implode(" AND ", $wheres) . " ORDER BY RAND() LIMIT 1";
        $recipe = $find_recipe($conn, $sql_recipe, $param_types, array_merge($params_values, $used_ids_params));
        
        // ครั้งที่ 2 (ถ้าครั้งแรกไม่เจอ): ลองค้นหาใหม่โดยไม่สนใจ Goal
        if (!$recipe) {
            // เอาเงื่อนไข Goal ออก
            if (isset($joins['goals'])) {
                unset($joins['goals']);
                // ต้องลบ parameter ของ goal ออกด้วย ซึ่งจะซับซ้อน
                // วิธีที่ง่ายกว่าคือ สร้าง query ใหม่ที่ง่ายลง
                $sql_fallback_1 = $sql_base . " JOIN recipe_categories rc ON r.id = rc.recipe_id JOIN categories c ON rc.category_id = c.id WHERE c.name = ? AND r.id NOT IN (" . implode(',', array_fill(0, count($used_recipe_ids) + 1, '?')) . ") ORDER BY ABS(r.calories - ?) LIMIT 1";
                $params_fallback_1 = array_merge([$category_to_search], $used_recipe_ids, [0, $budget]);
                $types_fallback_1 = 's' . str_repeat('i', count($used_recipe_ids) + 1) . 'd';
                $recipe = $find_recipe($conn, $sql_fallback_1, $types_fallback_1, $params_fallback_1);
            }
        }

        if ($recipe) {
            $daily_plan[$meal_name] = $recipe;
            $used_recipe_ids[] = $recipe['id'];
        } else {
            $daily_plan[$meal_name] = ['recipe_name' => 'ไม่พบเมนูที่เหมาะสม', 'calories' => 0, 'id' => 0, 'image_url' => ''];
        }
    } 

    $weekly_plan['Day ' . $day] = $daily_plan;
}

// --- บันทึกแผนและ Redirect ---
$plan_json = json_encode($weekly_plan, JSON_UNESCAPED_UNICODE);

$sql_delete_old = "DELETE FROM weekly_plans WHERE user_id = ?";
$stmt_delete = $conn->prepare($sql_delete_old);
$stmt_delete->bind_param("i", $user_id);
$stmt_delete->execute();
$stmt_delete->close();

$sql_save_plan = "INSERT INTO weekly_plans (user_id, plan_data) VALUES (?, ?)";
$stmt_save = $conn->prepare($sql_save_plan);
$stmt_save->bind_param("is", $user_id, $plan_json);
$stmt_save->execute();
$stmt_save->close();

$conn->close();

header("Location: ../weekly_plan_dashboard.php");
exit();
?>