<?php
// Set header to return JSON content
header('Content-Type: application/json; charset=utf-8');
require_once '../includes/db_connect.php';

/**
 * Sends a standardized JSON response.
 * @param bool $success Whether the operation was successful.
 * @param string $message A message describing the result.
 * @param array|null $data Optional data to include in the response.
 */
function send_json_response($success, $message, $data = null) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit();
}

// Check if a recipe ID is provided in the GET request
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    send_json_response(false, 'Recipe ID is missing or invalid.');
}

$recipe_id = (int)$_GET['id'];

try {
    // Prepare and execute SQL statement to fetch all details for a specific recipe
    $sql = "SELECT * FROM recipes WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("SQL statement preparation failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $recipe_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $recipe = $result->fetch_assoc();
    $stmt->close();
    $conn->close();

    if ($recipe) {
        // Rename the 'name' key to 'recipe_name' for consistency with the front-end code
        $recipe['recipe_name'] = $recipe['name'];
        unset($recipe['name']);
        send_json_response(true, 'Recipe details fetched successfully.', $recipe);
    } else {
        send_json_response(false, 'Recipe not found.');
    }

} catch (Exception $e) {
    // Log the error for debugging purposes and send a generic error message to the client
    error_log($e->getMessage());
    send_json_response(false, 'An internal server error occurred.');
}
?>
