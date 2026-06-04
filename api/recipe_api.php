<?php
require_once '../includes/db_connect.php'; // ตรวจสอบว่า Path ถูกต้อง

header('Content-Type: application/json');

// --- รับค่า Filter ทั้งหมด ---
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_name = isset($_GET['category']) ? trim($_GET['category']) : '';
$tag_name = isset($_GET['tag']) ? trim($_GET['tag']) : '';

// ✅ ส่วนที่ 1: เพิ่มการรับค่า disease_id และ diet_type_id
$disease_id = isset($_GET['disease_id']) && is_numeric($_GET['disease_id']) ? (int)$_GET['disease_id'] : 0;
$diet_type_id = isset($_GET['diet_type_id']) && is_numeric($_GET['diet_type_id']) ? (int)$_GET['diet_type_id'] : 0;

$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = ($page - 1) * $limit;


// --- สร้าง SQL Query ---
$sql_base = "FROM recipes r";
$joins = [];
$wheres = [];
$params_values = [];
$param_types = "";

// ค้นหาตามชื่อ
if (!empty($searchTerm)) {
    $wheres[] = "r.name LIKE ?";
    $param_types .= "s";
    $params_values[] = "%" . $searchTerm . "%";
}

// กรองตาม Category
if (!empty($category_name)) {
    $joins['recipe_categories'] = " JOIN recipe_categories rc ON r.id = rc.recipe_id JOIN categories c ON rc.category_id = c.id ";
    $wheres[] = "c.name = ?";
    $param_types .= "s";
    $params_values[] = $category_name;
}

// กรองตาม Tag
if (!empty($tag_name)) {
    $joins['recipe_tags'] = " JOIN recipe_tags rt ON r.id = rt.recipe_id JOIN tags t ON rt.tag_id = t.id ";
    $wheres[] = "t.name = ?";
    $param_types .= "s";
    $params_values[] = $tag_name;
}

// ✅ ส่วนที่ 2: เพิ่มเงื่อนไขการกรองตาม Disease และ Diet Type
// กรองตาม Disease
if ($disease_id > 0) {
    $joins['recipe_diseases'] = " JOIN recipe_diseases rd ON r.id = rd.recipe_id ";
    $wheres[] = "rd.disease_id = ? AND rd.suitability = 'good'";
    $param_types .= "i";
    $params_values[] = $disease_id;
}

// กรองตาม Diet Type
if ($diet_type_id > 0) {
    $joins['recipe_diet_types'] = " JOIN recipe_diet_types rdt ON r.id = rdt.recipe_id ";
    $wheres[] = "rdt.diet_type_id = ? AND rdt.suitability = 'good'";
    $param_types .= "i";
    $params_values[] = $diet_type_id;
}


// --- ประกอบ Query สำหรับนับจำนวนทั้งหมด (Pagination) ---
$count_sql = "SELECT COUNT(DISTINCT r.id) as total " . $sql_base . implode(" ", $joins);
if (!empty($wheres)) {
    $count_sql .= " WHERE " . implode(" AND ", $wheres);
}

$total_records = 0;
$stmt_count = $conn->prepare($count_sql);
if (!empty($param_types)) {
    $stmt_count->bind_param($param_types, ...$params_values);
}
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$total_records = $result_count->fetch_assoc()['total'];
$stmt_count->close();


// --- ประกอบ Query สำหรับดึงข้อมูล ---
$data_sql = "SELECT DISTINCT r.* " . $sql_base . implode(" ", $joins);
if (!empty($wheres)) {
    $data_sql .= " WHERE " . implode(" AND ", $wheres);
}

$data_sql .= " ORDER BY r.name ASC LIMIT ?, ?";
$param_types .= "ii";
$params_values[] = $offset;
$params_values[] = $limit;


// --- ดึงข้อมูลและส่งผลลัพธ์ ---
$stmt_data = $conn->prepare($data_sql);
if (!empty($param_types)) {
    $stmt_data->bind_param($param_types, ...$params_values);
}
$stmt_data->execute();
$result_data = $stmt_data->get_result();
$recipes = [];
while($row = $result_data->fetch_assoc()) {
    $recipes[] = $row;
}
$stmt_data->close();
$conn->close();

// ส่งผลลัพธ์ในรูปแบบ JSON
echo json_encode([
    'total' => $total_records,
    'page' => $page,
    'limit' => $limit,
    'data' => $recipes
]);
?>