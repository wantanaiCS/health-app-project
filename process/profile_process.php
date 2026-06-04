<?php
session_start();
require_once '../includes/db_connect.php'; 

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. Sanitize and retrieve all form data
$dob_day = filter_input(INPUT_POST, 'dob_day', FILTER_VALIDATE_INT);
$dob_month = filter_input(INPUT_POST, 'dob_month', FILTER_VALIDATE_INT);
$dob_year_be = filter_input(INPUT_POST, 'dob_year', FILTER_VALIDATE_INT);
$gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_STRING);
$weight = filter_input(INPUT_POST, 'weight', FILTER_VALIDATE_FLOAT);
$height = filter_input(INPUT_POST, 'height', FILTER_VALIDATE_FLOAT);
$activity_level = filter_input(INPUT_POST, 'activity_level', FILTER_VALIDATE_FLOAT);
$goal = filter_input(INPUT_POST, 'goal', FILTER_SANITIZE_STRING);

// 2. Correctly handle the 'disease' checkbox array
$disease_string = 'ไม่มี';
if (isset($_POST['disease']) && is_array($_POST['disease'])) {
    $sanitized_diseases = array_map('htmlspecialchars', $_POST['disease']);
    if (!empty($sanitized_diseases)) {
        $disease_string = implode(',', $sanitized_diseases);
    }
}


// 3. Validate Date of Birth and other fields
if (!$dob_day || !$dob_month || !$dob_year_be) {
    $_SESSION['error_message'] = "กรุณาเลือกวันเกิดให้ครบถ้วน";
    header('Location: ../profile.php');
    exit();
}
$dob_year_ad = $dob_year_be - 543;
if (!checkdate($dob_month, $dob_day, $dob_year_ad)) {
     $_SESSION['error_message'] = "วันที่เลือกไม่ถูกต้อง (เช่น วันที่ 31 เดือนกุมภาพันธ์)";
    header('Location: ../profile.php');
    exit();
}
if (!$gender || !$weight || !$height || !$activity_level || !$goal) {
    $_SESSION['error_message'] = "กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน";
    header('Location: ../profile.php');
    exit();
}

// 4. Create A.D. date string and calculate age
$dob_string_ad = sprintf("%04d-%02d-%02d", $dob_year_ad, $dob_month, $dob_day);
try {
    $dob = new DateTime($dob_string_ad);
    $today = new DateTime('today');
    $age = $dob->diff($today)->y;
} catch (Exception $e) {
    $_SESSION['error_message'] = "รูปแบบวันเกิดไม่ถูกต้อง";
    header('Location: ../profile.php');
    exit();
}

// 5. Calculate BMI and BMR
$height_m = $height / 100;
$bmi = ($height_m > 0) ? round($weight / ($height_m * $height_m), 2) : 0;
$bmr = (10 * $weight) + (6.25 * $height) - (5 * $age);
if ($gender == 'male') {
    $bmr += 5;
} else {
    $bmr -= 161;
}
$bmr = round($bmr);

// 6. Calculate TDEE and Target Calories
$tdee = round($bmr * $activity_level);
$target_calories = $tdee;
if ($goal == 'ลดน้ำหนัก') {
    $target_calories -= 400;
} elseif ($goal == 'เพิ่มน้ำหนัก') {
    $target_calories += 400;
}

// 7. Calculate Macronutrients
if ($goal == 'ลดน้ำหนัก') {
    $target_protein_g = (int)round(($target_calories * 0.40) / 4);
    $target_carbs_g = (int)round(($target_calories * 0.30) / 4);
    $target_fat_g = (int)round(($target_calories * 0.30) / 9);
} else {
    $target_protein_g = (int)round(($target_calories * 0.30) / 4);
    $target_carbs_g = (int)round(($target_calories * 0.45) / 4);
    $target_fat_g = (int)round(($target_calories * 0.25) / 9);
}

// 8. Prepare SQL statement to include 'date_of_birth'
$sql = "INSERT INTO user_profiles (user_id, age, date_of_birth, gender, weight, height, activity_level, disease, goal, bmi, bmr, target_calories, target_protein_g, target_carbs_g, target_fat_g)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        age = VALUES(age), date_of_birth = VALUES(date_of_birth), gender = VALUES(gender),
        weight = VALUES(weight), height = VALUES(height), activity_level = VALUES(activity_level),
        disease = VALUES(disease), goal = VALUES(goal), bmi = VALUES(bmi), bmr = VALUES(bmr),
        target_calories = VALUES(target_calories), target_protein_g = VALUES(target_protein_g),
        target_carbs_g = VALUES(target_carbs_g), target_fat_g = VALUES(target_fat_g)";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: " . $conn->error;
    header('Location: ../profile.php');
    exit();
}

// Type string ที่ถูกต้อง: i, i, s, s, d, d, d, s, s, d, d, d, i, i, i
$stmt->bind_param("iissdddssdddiii", 
    $user_id, $age, $dob_string_ad, $gender, $weight, $height, $activity_level, 
    $disease_string, $goal, $bmi, $bmr, $target_calories, $target_protein_g, 
    $target_carbs_g, $target_fat_g
);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "บันทึกข้อมูลสุขภาพของคุณเรียบร้อยแล้ว!";
} else {
    $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $stmt->error;
}

$stmt->close();
$conn->close();

header('Location: ../dashboard.php');
exit();
?>

