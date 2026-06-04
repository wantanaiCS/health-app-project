<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล
require_once '../includes/db_connect.php'; 

// --- ฟังก์ชันสำหรับส่ง response กลับไปเป็น JSON ---
function send_json_response($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit();
}

// --- ตรวจสอบว่า user ล็อกอินหรือยัง ---
if (!isset($_SESSION['user_id'])) {
    send_json_response(false, 'ไม่ได้รับอนุญาตให้เข้าถึง');
}
$user_id = $_SESSION['user_id'];

// --- รับข้อมูลที่ส่งมาจาก JavaScript ---
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['day_key']) || !isset($input['meal_name'])) {
    send_json_response(false, 'ข้อมูลที่ส่งมาไม่ถูกต้อง');
}
$day_key_to_change = $input['day_key'];
$meal_name_to_change = $input['meal_name'];


// --- เริ่มการทำงานกับฐานข้อมูล ---
try {
    // 1. ดึงแผนอาหารล่าสุดของ user คนนี้
    $sql_get = "SELECT id, plan_data FROM weekly_plans WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
    $stmt_get = $conn->prepare($sql_get);
    $stmt_get->bind_param("i", $user_id);
    $stmt_get->execute();
    $result = $stmt_get->get_result();
    $plan_row = $result->fetch_assoc();
    $stmt_get->close();

    if (!$plan_row) {
        send_json_response(false, 'ไม่พบแผนอาหาร');
    }

    $plan_id = $plan_row['id'];
    $current_plan = json_decode($plan_row['plan_data'], true);

    if (!isset($current_plan[$day_key_to_change][$meal_name_to_change])) {
        send_json_response(false, 'ไม่พบมื้ออาหารที่ระบุในแผน');
    }
    
    $current_meal = $current_plan[$day_key_to_change][$meal_name_to_change];

    // 2. หาเมนูใหม่มาทดแทน โดยหาจากในแผน 7 วันก่อน
    $new_meal = getAlternativeMealFromPlan($current_plan, $meal_name_to_change, $current_meal);
    
    // Fallback: ถ้าหาจากในแผนไม่ได้ ให้ใช้คลังเมนูสำรอง
    if (!$new_meal) {
        $new_meal = getAlternativeMealFromStaticPool($meal_name_to_change, $current_meal);
    }
    
    if (!$new_meal) {
        send_json_response(false, 'ไม่พบเมนูอื่นที่เหมาะสมสำหรับเปลี่ยน');
    }

    // 3. อัปเดตข้อมูลแผนอาหารในตัวแปร PHP
    $current_plan[$day_key_to_change][$meal_name_to_change] = $new_meal;
    
    // 4. แปลงกลับเป็น JSON และบันทึกลงฐานข้อมูล
    $updated_plan_json = json_encode($current_plan, JSON_UNESCAPED_UNICODE);

    $sql_update = "UPDATE weekly_plans SET plan_data = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("si", $updated_plan_json, $plan_id);
    
    if (!$stmt_update->execute()) {
        throw new Exception("การอัปเดตฐานข้อมูลล้มเหลว: " . $stmt_update->error);
    }
    $stmt_update->close();

    // 5. ส่งข้อมูลเมนูใหม่กลับไปให้ JavaScript
    send_json_response(true, 'เปลี่ยนเมนูสำเร็จ', ['new_meal' => $new_meal]);

} catch (Exception $e) {
    // หากมีข้อผิดพลาดร้ายแรง
    error_log($e->getMessage()); // ควรมีการบันทึก log ไว้ตรวจสอบ
    send_json_response(false, 'เกิดข้อผิดพลาดภายในระบบ');
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

/**
 * ฟังก์ชันหาเมนูทดแทนจากแผนอาหาร 7 วันที่มีอยู่
 * @param array $plan แผนอาหารทั้งหมด
 * @param string $meal_type ประเภทมื้ออาหาร ('Breakfast', 'Lunch', etc.)
 * @param array $current_meal เมนูปัจจุบัน (เพื่อไม่ให้สุ่มได้เมนูซ้ำ)
 * @return array|null ข้อมูลเมนูใหม่ที่หาได้จากในแผน
 */
function getAlternativeMealFromPlan($plan, $meal_type, $current_meal) {
    $alternatives = [];
    // 1. รวบรวมเมนูประเภทเดียวกันทั้งหมดจากทุกวัน
    foreach ($plan as $day_plan) {
        if (isset($day_plan[$meal_type]) && is_array($day_plan[$meal_type])) {
            $alternatives[] = $day_plan[$meal_type];
        }
    }

    // 2. กรองเอาเมนูที่ไม่ซ้ำกัน และไม่ใช่เมนูปัจจุบัน
    $unique_alternatives = [];
    foreach ($alternatives as $meal) {
        // ตรวจสอบว่า recipe_name ไม่ใช่ของเมนูปัจจุบัน และยังไม่มีอยู่ใน list ที่ไม่ซ้ำ
        if ($meal['recipe_name'] !== $current_meal['recipe_name']) {
            $unique_alternatives[$meal['recipe_name']] = $meal;
        }
    }
    
    // 3. ถ้ามีเมนูอื่นให้เลือก ก็สุ่มมา 1 อย่าง
    if (!empty($unique_alternatives)) {
        $random_key = array_rand($unique_alternatives);
        return $unique_alternatives[$random_key];
    }
    
    return null; // ไม่พบเมนูอื่นในแผน
}

/**
 * ฟังก์ชันจำลองการหาเมนูทดแทนจากคลังข้อมูลกลาง (Fallback)
 * @param string $meal_type ประเภทมื้ออาหาร ('Breakfast', 'Lunch', etc.)
 * @param array $current_meal เมนูปัจจุบัน (เพื่อไม่ให้สุ่มได้เมนูซ้ำ)
 * @return array|null ข้อมูลเมนูใหม่
 */
function getAlternativeMealFromStaticPool($meal_type, $current_meal) {
    // คลังสูตรอาหารจำลอง
    $recipe_pool = [
        'Breakfast' => [
            ['recipe_name' => 'ข้าวโอ๊ตตุ๋นกับผลไม้', 'calories' => '350', 'image_url' => 'https://placehold.co/150x150/F3E5F5/4A148C?text=Oatmeal'],
            ['recipe_name' => 'โยเกิร์ตกรีกกับกราโนล่า', 'calories' => '300', 'image_url' => 'https://placehold.co/150x150/E1F5FE/01579B?text=Yogurt'],
            ['recipe_name' => 'ไข่คนกับขนมปังโฮลวีท', 'calories' => '400', 'image_url' => 'https://placehold.co/150x150/FFF3E0/E65100?text=Eggs'],
        ],
        'Lunch' => [
            ['recipe_name' => 'สลัดไก่ย่าง', 'calories' => '450', 'image_url' => 'https://placehold.co/150x150/E8F5E9/1B5E20?text=Salad'],
            ['recipe_name' => 'ข้าวกล้องผัดกะเพราอกไก่', 'calories' => '500', 'image_url' => 'https://placehold.co/150x150/FFEBEE/B71C1C?text=Kaprao'],
            ['recipe_name' => 'ก๋วยเตี๋ยวลุยสวน', 'calories' => '420', 'image_url' => 'https://placehold.co/150x150/F1F8E9/33691E?text=Noodle'],
        ],
        'Dinner' => [
            ['recipe_name' => 'สเต็กปลาแซลมอนย่างกับผัก', 'calories' => '550', 'image_url' => 'https://placehold.co/150x150/FFF8E1/F57F17?text=Salmon'],
            ['recipe_name' => 'แกงจืดเต้าหู้หมูสับ', 'calories' => '380', 'image_url' => 'https://placehold.co/150x150/F3E5F5/4A148C?text=Soup'],
            ['recipe_name' => 'ต้มยำกุ้งน้ำใส', 'calories' => '400', 'image_url' => 'https://placehold.co/150x150/FCE4EC/880E4F?text=TomYum'],
        ]
    ];
    
    $alternatives = isset($recipe_pool[$meal_type]) ? $recipe_pool[$meal_type] : [];
    
    $filtered_alternatives = array_filter($alternatives, function($meal) use ($current_meal) {
        return $meal['recipe_name'] !== $current_meal['recipe_name'];
    });

    if (empty($filtered_alternatives)) {
        return null; 
    }

    $random_key = array_rand($filtered_alternatives);
    return $filtered_alternatives[$random_key];
}
?>

