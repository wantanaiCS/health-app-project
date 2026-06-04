<?php
session_start();
$page_title = "ลืมรหัสผ่าน";
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'FitMealWeek'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/stylelogin.css">
</head>

<body>
    <div class="container" id="container" style="max-width: 500px; margin-top: 50px;">
        <div class="form-container" style="text-align: center;">
            <form action="process/process_forgot_password.php" method="POST">
                <div class="logo-container">
                    <a href="index.php">
                        <img src="assets/images/logo.png" alt="logo">
                    </a>
                </div>
                <h1>ลืมรหัสผ่าน</h1>
                <span>กรุณากรอกอีเมลของคุณเพื่อรับลิงก์สำหรับตั้งรหัสผ่านใหม่</span>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger" style="font-size: 16px;"><?php echo htmlspecialchars($_GET['error']); ?></div>
                <?php endif; ?>
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success" style="font-size: 16px;"><?php echo htmlspecialchars($_GET['success']); ?></div>
                <?php endif; ?>

                <div class="input-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" placeholder="อีเมล" id="email" name="email" required>
                </div>

                <button type="submit" style="margin-top: 15px;">ส่งลิงก์ตั้งรหัสผ่านใหม่</button>
                <div style="margin-top: 20px;">
                    <a href="login.php">กลับไปหน้าเข้าสู่ระบบ</a>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
</body>

</html>