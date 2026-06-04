<?php
session_start();
header('Content-Type: application/json');

require_once '../includes/db_connect.php';

$response = ['success' => false, 'message' => 'An error occurred.'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Authentication required.';
    http_response_code(401);
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

$plan_date = $input['plan_date'] ?? null;
$is_completed = isset($input['is_completed']) ? ($input['is_completed'] ? 1 : 0) : null;

if (!$plan_date || is_null($is_completed)) {
    $response['message'] = 'Missing required data.';
    http_response_code(400);
    echo json_encode($response);
    exit();
}

try {
    $sql = "UPDATE plan_progress SET is_completed = ? WHERE user_id = ? AND plan_date = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $is_completed, $user_id, $plan_date);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Progress updated successfully.';
        } else {
            // It's not an error if no rows were updated (e.g., already set to that value)
            $response['success'] = true;
            $response['message'] = 'No change in status or no plan found for the date.';
        }
    } else {
        $response['message'] = 'Database execute failed.';
    }
    $stmt->close();
} catch (Exception $e) {
    $response['message'] = 'Database exception: ' . $e->getMessage();
    http_response_code(500);
}

$conn->close();
echo json_encode($response);
?>