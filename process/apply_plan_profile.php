<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit();
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

$start_date = $input['start_date'] ?? null;
$plan_id = $input['plan_id'] ?? null;
$plan_type = $input['plan_type'] ?? null;

if (!$start_date || !$plan_id || !$plan_type) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit();
}

try {
    $conn->begin_transaction();

    // ลบแผนเก่าที่ active อยู่
    $delete_sql = "DELETE FROM plan_progress WHERE user_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $user_id);
    $delete_stmt->execute();
    $delete_stmt->close();

    // ดึงข้อมูลแผนตาม plan_type
    $plan_data = null;
    
    if ($plan_type === 'custom') {
        // ดึงจาก plan_profiles
        $fetch_sql = "SELECT plan_data FROM plan_profiles WHERE id = ? AND user_id = ?";
        $fetch_stmt = $conn->prepare($fetch_sql);
        $fetch_stmt->bind_param("ii", $plan_id, $user_id);
        $fetch_stmt->execute();
        $result = $fetch_stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $plan_data = json_decode($row['plan_data'], true);
        }
        $fetch_stmt->close();
        
    } elseif ($plan_type === 'ai') {
        // ดึงจาก weekly_plans
        $fetch_sql = "SELECT plan_data FROM weekly_plans WHERE id = ? AND user_id = ?";
        $fetch_stmt = $conn->prepare($fetch_sql);
        $fetch_stmt->bind_param("ii", $plan_id, $user_id);
        $fetch_stmt->execute();
        $result = $fetch_stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $plan_data = json_decode($row['plan_data'], true);
        }
        $fetch_stmt->close();
    }

    if (!$plan_data) {
        throw new Exception('ไม่พบข้อมูลแผน');
    }

    // สร้าง plan_progress ตามประเภทแผน
    $insert_sql = "INSERT INTO plan_progress (user_id, plan_id, plan_date, is_completed) VALUES (?, ?, ?, 0)";
    $insert_stmt = $conn->prepare($insert_sql);
    
    $start_date_obj = new DateTime($start_date);
    $day_index = 0;

    if ($plan_type === 'custom') {
        // Custom Profile: วนตาม date keys ที่มีในแผน
        $sorted_dates = array_keys($plan_data);
        sort($sorted_dates); // เรียงวันที่
        
        foreach ($sorted_dates as $original_date) {
            $current_date = clone $start_date_obj;
            $current_date->modify("+{$day_index} days");
            $plan_date_str = $current_date->format('Y-m-d');
            
            // ใช้ plan_id จากตาราง plan_profiles
            $insert_stmt->bind_param("iis", $user_id, $plan_id, $plan_date_str);
            $insert_stmt->execute();
            
            $day_index++;
        }
        
    } elseif ($plan_type === 'ai') {
        // AI Plan: วนตาม Day 1 - Day 7
        for ($i = 1; $i <= 7; $i++) {
            $day_key = "Day {$i}";
            if (!isset($plan_data[$day_key])) continue;
            
            $current_date = clone $start_date_obj;
            $current_date->modify("+{$day_index} days");
            $plan_date_str = $current_date->format('Y-m-d');
            
            // ใช้ plan_id จากตาราง weekly_plans
            $insert_stmt->bind_param("iis", $user_id, $plan_id, $plan_date_str);
            $insert_stmt->execute();
            
            $day_index++;
        }
    }

    $insert_stmt->close();
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'นำแผนไปใช้สำเร็จ']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>