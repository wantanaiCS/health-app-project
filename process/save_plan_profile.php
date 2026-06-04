<?php
session_start();
require_once '../includes/db_connect.php'; 

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];
$profile_name = $data['profile_name'] ?? '';
$description = $data['description'] ?? '';
$plan_data_json = json_encode($data['plan_data'] ?? []);
$tags = $data['tags'] ?? [];

if (empty($profile_name) || empty($plan_data_json)) {
    echo json_encode(['success' => false, 'message' => 'Missing required data.']);
    exit();
}

// --- Use a transaction for data integrity ---
$conn->begin_transaction();

try {
    // 1. Insert the main profile
    $sql_profile = "INSERT INTO plan_profiles (user_id, profile_name, description, plan_data) VALUES (?, ?, ?, ?)";
    $stmt_profile = $conn->prepare($sql_profile);
    $stmt_profile->bind_param("isss", $user_id, $profile_name, $description, $plan_data_json);
    $stmt_profile->execute();
    $plan_profile_id = $conn->insert_id;
    $stmt_profile->close();

    if (!$plan_profile_id) {
        throw new Exception("Failed to create plan profile.");
    }

    // 2. Handle tags
    if (!empty($tags)) {
        $stmt_find_tag = $conn->prepare("SELECT id FROM tags WHERE name = ?");
        $stmt_insert_tag = $conn->prepare("INSERT INTO tags (name) VALUES (?)");
        $stmt_link_tag = $conn->prepare("INSERT INTO plan_profile_tags (plan_profile_id, tag_id) VALUES (?, ?)");

        foreach ($tags as $tag_name) {
            $tag_name = trim($tag_name);
            if (empty($tag_name)) continue;
            
            // Find or create the tag
            $stmt_find_tag->bind_param("s", $tag_name);
            $stmt_find_tag->execute();
            $result = $stmt_find_tag->get_result();
            $tag_id = null;

            if ($row = $result->fetch_assoc()) {
                $tag_id = $row['id'];
            } else {
                $stmt_insert_tag->bind_param("s", $tag_name);
                $stmt_insert_tag->execute();
                $tag_id = $conn->insert_id;
            }

            // Link tag to profile
            if ($tag_id) {
                $stmt_link_tag->bind_param("ii", $plan_profile_id, $tag_id);
                $stmt_link_tag->execute();
            }
        }
        $stmt_find_tag->close();
        $stmt_insert_tag->close();
        $stmt_link_tag->close();
    }

    // If all is well, commit the transaction
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Profile saved successfully.']);

} catch (Exception $e) {
    // Something went wrong, roll back
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>