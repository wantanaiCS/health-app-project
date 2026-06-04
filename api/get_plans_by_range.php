<?php
session_start();
header('Content-Type: application/json');

require_once '../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Authentication required.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$start_date = $_GET['start'] ?? null;
$end_date = $_GET['end'] ?? null;

// Validate dates
if (!$start_date || !$end_date) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Start and end dates are required.']);
    exit();
}

try {
    $sql = "SELECT id, plan_date, plan_data, total_calories, total_protein, total_carbs_g, total_fat_g
            FROM daily_plans
            WHERE user_id = ? AND plan_date BETWEEN ? AND ?
            ORDER BY plan_date ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $user_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $plans_to_return = [];
    while ($plan = $result->fetch_assoc()) {
        $plans_to_return[$plan['plan_date']] = [
            'plan_id'  => $plan['id'],
            'plan'     => json_decode($plan['plan_data'], true),
            'totals'   => [
                'calories' => $plan['total_calories'],
                'protein'  => $plan['total_protein'],
                'carbs'    => $plan['total_carbs_g'],
                'fat'      => $plan['total_fat_g']
            ]
        ];
    }

    $stmt->close();
    $conn->close();

    echo json_encode($plans_to_return);

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    error_log("API Error in get_plans_by_range.php: " . $e->getMessage());
    echo json_encode(['error' => 'A server error occurred.']);
}
?>