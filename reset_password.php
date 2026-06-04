<?php
// Get the token from the URL
$token = $_GET["token"] ?? null;

if ($token === null) {
    die("ไม่พบโทเค็นสำหรับรีเซ็ตรหัสผ่าน");
}

// Hash the token to match the one in the database
$token_hash = hash("sha256", $token);

// Include the database connection
// Make sure the path is correct. This assumes db_connect.php is in an 'includes' folder.
// require __DIR__ . "/includes/db_connect.php"; // This line is commented out for demonstration purposes

// --- Mock User Data for Demonstration ---
// In a real scenario, you would fetch this from the database
$user = [
    'reset_token_hash' => $token_hash,
    'reset_token_expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour'))
];
// --- End Mock User Data ---


// Check if a user with that token was found
if ($user === null) {
    die("ลิงก์ไม่ถูกต้อง หรือไม่พบโทเค็นนี้ในระบบ");
}

// Check if the token has expired
if (strtotime($user["reset_token_expires_at"]) <= time()) {
    die("ลิงก์หมดอายุแล้ว กรุณาส่งคำขอใหม่อีกครั้ง");
}

// If we get here, the token is valid. We can show the password reset form.
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งรหัสผ่านใหม่</title>
    
    <!-- External Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <!-- Page-Specific CSS for Reset Password Form -->
    <style>
        /* Import Font */
        @import url('https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;700&display=swap');

        /* Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Prompt', sans-serif;
        }

        body {
            background: url(../images/bg-icon.png) center center repeat;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px; /* Add padding for smaller screens */
        }

        /* Container for the form */
        .form-wrapper {
            background-color: #ffffff;
            border-radius: 20px;
            box-shadow: 0 14px 28px rgba(0,0,0,0.1), 0 10px 10px rgba(0,0,0,0.1);
            padding: 30px 40px;
            width: 100%;
            max-width: 450px;
            text-align: center;
        }



        .form-wrapper h1 {
            font-size: 26px; /* Adjusted font size */
            font-weight: 700;
            margin-bottom: 8px;
            color: #333;
        }

        .form-wrapper p {
            font-size: 15px; /* Adjusted font size */
            color: #666;
            margin-bottom: 25px;
        }
        
        /* Input field styling */
        .input-group {
            position: relative;
            margin-bottom: 18px; /* Adjusted margin */
        }

        .input-group .icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            font-size: 15px;
        }

        .input-group input {
            width: 100%;
            padding: 14px 45px 14px 45px; /* Adjusted padding */
            border: 1px solid #ddd;
            border-radius: 10px; /* More rounded corners */
            background-color: #f9f9f9;
            font-size: 15px;
            outline: none;
            transition: all 0.3s ease;
        }
        
        .input-group input:focus {
            background-color: #fff;
            border-color: #B7D971; /* Highlight color from login page */
            box-shadow: 0 0 8px rgba(183, 217, 113, 0.5);
        }

        /* Password toggle icon */
        .toggle-password {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            cursor: pointer;
        }
        
        /* Submit Button */
        .submit-btn {
            background-color: #B7D971; /* Green color from login page */
            color: #fff;
            font-size: 16px;
            padding: 14px 45px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            margin-top: 10px; /* Adjusted margin */
        }

        .submit-btn:hover {
            background-color: #a3c065; /* Darker green on hover */
            transform: translateY(-2px);
        }

        .submit-btn:active {
            transform: translateY(0);
        }


    </style>
</head>

<body>
    <div class="form-wrapper">
        <!-- The form action should point to your processing script -->
        <form action="process/process_reset_password.php" method="POST">
    <a href="index.php">
        <img src="assets/images/logo.png" alt="logo" style="width: 70px; height: auto;">
    </a>
    <h1>ตั้งรหัสผ่านใหม่</h1>
            <p>กรุณากรอกรหัสผ่านใหม่ของคุณ</p>

            <!-- Display validation errors here -->
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger" style="font-size: 14px;"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>

            <!-- Hidden token field to submit with the form -->
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

            <div class="input-group">
                <i class="fas fa-lock icon"></i>
                <input type="password" placeholder="รหัสผ่านใหม่" id="password" name="password" required>
                <i class="fas fa-eye toggle-password" data-input="password"></i>
            </div>
             <div class="input-group">
                <i class="fas fa-lock icon"></i>
                <input type="password" placeholder="ยืนยันรหัสผ่านใหม่" id="password_confirmation" name="password_confirmation" required>
                <i class="fas fa-eye toggle-password" data-input="password_confirmation"></i>
            </div>

            <button type="submit" class="submit-btn">บันทึกรหัสผ่าน</button>
        </form>
    </div>

    <script>
        // JavaScript to toggle password visibility for all fields
        document.querySelectorAll('.toggle-password').forEach(item => {
            item.addEventListener('click', function() {
                const inputId = this.getAttribute('data-input');
                const input = document.getElementById(inputId);
                
                // Toggle the type attribute
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                
                // Toggle the eye icon (fa-eye to fa-eye-slash)
                this.classList.toggle('fa-eye-slash');
            });
        });
    </script>
</body>

</html>
