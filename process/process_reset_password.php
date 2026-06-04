<?php

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit('Invalid request method');
}

// 1. Validate Passwords
if (strlen($_POST["password"]) < 8) {
    header("Location: ../reset_password.php?token={$_POST['token']}&error=รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร");
    exit();
}

if ($_POST["password"] !== $_POST["password_confirmation"]) {
    header("Location: ../reset_password.php?token={$_POST['token']}&error=รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน");
    exit();
}

// 2. Hash the new password
$password_hash = password_hash($_POST["password"], PASSWORD_DEFAULT);

// 3. Find user and update password
$token = $_POST["token"];
$token_hash = hash("sha256", $token);

require __DIR__ . '/../includes/db_connect.php';

$sql = "SELECT id FROM users WHERE reset_token_hash = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $token_hash);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user === null) {
    die("Token not found or invalid.");
}

// Update the password and clear the reset token fields
$sql_update = "UPDATE users
               SET password = ?,
                   reset_token_hash = NULL,
                   reset_token_expires_at = NULL
               WHERE id = ?";

$stmt_update = $conn->prepare($sql_update);
$stmt_update->bind_param("si", $password_hash, $user["id"]);
$stmt_update->execute();

// Redirect to login page with a success message
header("Location: ../login.php?signup=success&message=เปลี่ยนรหัสผ่านสำเร็จ!+กรุณาเข้าสู่ระบบด้วยรหัสผ่านใหม่");
exit();