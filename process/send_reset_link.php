<?php

// เรียกใช้งานคลาส PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP; // เพิ่มการ import SMTP สำหรับการดีบัก

// ใช้ try-catch ครอบโค้dทั้งหมดเพื่อดักจับข้อผิดพลาดร้ายแรง
try {
    // -----------------------------------------------------------------------------
    // ส่วนของการตั้งค่าและกำหนดค่า (CONFIGURATION & SETUP)
    // -----------------------------------------------------------------------------

    // โหลด autoloader ของ Composer
    require '../vendor/autoload.php';

    // เรียกใช้งานไฟล์เชื่อมต่อฐานข้อมูล
    require '../includes/db_connect.php';

    // -----------------------------------------------------------------------------
    // ส่วนของการจัดการคำขอ (REQUEST HANDLING)
    // -----------------------------------------------------------------------------

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        header("Location: ../index.php");
        exit();
    }

    $email = filter_input(INPUT_POST, "email", FILTER_VALIDATE_EMAIL);

    if (!$email) {
        header("Location: ../forgot_password.php?error=รูปแบบอีเมลไม่ถูกต้อง");
        exit();
    }

    // -----------------------------------------------------------------------------
    // ส่วนของการสร้างโทเค็นและอัปเดตฐานข้อมูล (TOKEN GENERATION & DATABASE UPDATE)
    // -----------------------------------------------------------------------------

    $token = bin2hex(random_bytes(32));
    $token_hash = hash("sha256", $token);
    $expiry = date("Y-m-d H:i:s", time() + 60 * 15);

    $sql = "UPDATE users SET reset_token_hash = ?, reset_token_expires_at = ? WHERE email = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        throw new Exception("การเตรียมคำสั่ง SQL ล้มเหลว: " . $conn->error);
    }

    $stmt->bind_param("sss", $token_hash, $expiry, $email);
    $stmt->execute();

    // -----------------------------------------------------------------------------
    // ส่วนของการส่งอีเมล (EMAIL SENDING)
    // -----------------------------------------------------------------------------

    if ($stmt->affected_rows > 0) {
        $mail = new PHPMailer(true);

        try {
            // --- การตั้งค่าเซิร์ฟเวอร์ (Server settings) ---

            // ปิดโหมดดีบักสำหรับการใช้งานจริง
            // $mail->SMTPDebug = SMTP::DEBUG_SERVER;

            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; // SMTP Server สำหรับ Gmail
            $mail->SMTPAuth   = true;

            // **สำคัญมาก**
            // 1. อีเมลผู้ส่ง (ต้องเป็นบัญชี Gmail จริง)
            $mail->Username   = 'fitmealweek@gmail.com'; // **อัปเดตแล้ว**

            // **สำคัญมาก: ใช้ "รหัสผ่านสำหรับแอป" (App Password) ที่สร้างจาก Google**
            $mail->Password   = 'gmotdpzrrokwimnf'; // **อัปเดตแล้ว**

            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // --- ผู้รับ (Recipients) ---
            $mail->setFrom('no-reply@fitmealweek.com', 'FitMealWeek');
            $mail->addAddress($email);

            // --- เนื้อหาอีเมล (Content) ---
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = 'ตั้งรหัสผ่านใหม่สำหรับ FitMealWeek';

            // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
            // !! จุดที่ต้องแก้ไข: URL นี้ต้องถูกต้องและตรงกับที่อยู่ของโปรเจกต์ของคุณ !!
            // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
            // **อัปเดตแล้ว: ใช้ URL ที่ถูกต้องสำหรับ XAMPP บนเครื่องของคุณ**
            $resetLink = "http://localhost/health_app/reset_password.php?token=" . $token;

            $mail->Body    = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; color: #333; }
                        .container { padding: 20px; border: 1px solid #ddd; border-radius: 8px; max-width: 600px; margin: auto; }
                        .button { background-color: #28a745; color: white !important; padding: 12px 25px; text-align: center; text-decoration: none; display: inline-block; font-size: 16px; border-radius: 5px;}
                        p { line-height: 1.6; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <h1>ตั้งรหัสผ่านใหม่</h1>
                        <p>เราได้รับคำขอให้ตั้งรหัสผ่านใหม่สำหรับบัญชีของคุณ</p>
                        <p>กรุณาคลิกที่ปุ่มด้านล่างเพื่อดำเนินการต่อ:</p>
                        <p style='text-align: center; margin: 25px 0;'>
                           <a href='{$resetLink}' class='button'>ตั้งรหัสผ่านใหม่</a>
                        </p>
                        <p>ลิงก์นี้จะหมดอายุใน 15 นาที</p>
                        <hr>
                        <p style='font-size: 12px; color: #777;'>หากคุณไม่ได้ร้องขอการตั้งรหัสผ่านใหม่ กรุณาไม่ต้องดำเนินการใดๆ และลบอีเมลนี้ทิ้ง</p>
                    </div>
                </body>
                </html>";

            $mail->send();
            header("Location: ../forgot_password.php?success=ลิงก์สำหรับตั้งรหัสผ่านใหม่ได้ถูกส่งไปยังอีเมลของคุณแล้ว");
            exit();

        } catch (Exception $e) {
            header("Location: ../forgot_password.php?error=ไม่สามารถส่งอีเมลได้: {$mail->ErrorInfo}");
            exit();
        }
    } else {
        header("Location: ../forgot_password.php?success=หากอีเมลของคุณมีอยู่ในระบบของเรา ลิงก์สำหรับตั้งรหัสผ่านใหม่จะถูกส่งไปให้");
        exit();
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    header("Location: ../forgot_password.php?error=เกิดข้อผิดพลาดบางอย่างขึ้น กรุณาลองใหม่อีกครั้ง");
    exit();
}
