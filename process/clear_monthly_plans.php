<?php
session_start();
header('Content-Type: application/json');

require_once '../includes/db_connect.php';

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Authentication required. Please log in.';
    echo json_encode($response);
    exit();
}

// Get input data from the request body
$input = json_decode(file_get_contents('php://input'), true);
$month = $input['month'] ?? null;
$user_id = $_SESSION['user_id'];

// Validate the month format (YYYY-MM)
if (!$month || !preg_match('/^\d{4}-\d{2}$/', $month)) {
    $response['message'] = 'Invalid month format provided.';
    echo json_encode($response);
    exit();
}

try {
    // Calculate the start and end dates of the given month
    $month_start = date('Y-m-01', strtotime($month));
    $month_end = date('Y-m-t', strtotime($month));

    // Prepare the DELETE statement
    $sql = "DELETE FROM daily_plans WHERE user_id = ? AND plan_date BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    $stmt->bind_param("iss", $user_id, $month_start, $month_end);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'All plans for the month have been cleared successfully.';
    } else {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
?>