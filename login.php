<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - FitMealWeek</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/stylelogin.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Prompt', sans-serif;
        }

        body {
            background-color: #ffffff;
            background: url(assets/images/bg-icon.png) center center repeat;
            background-size: contain;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            min-height: 100vh;
            padding: 20px;
        }

        h1 {
            font-size: clamp(24px, 5vw, 38px);
            margin-bottom: 10px;
        }

        .container {
            background-color: #fff;
            border-radius: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.35);
            position: relative;
            overflow: hidden;
            width: 100%;
            max-width: 768px;
            min-height: 480px;
            font-size: clamp(18px, 3.5vw, 26px);
        }

        .container p {
            font-size: clamp(14px, 2.5vw, 16px);
            line-height: 1.6;
            letter-spacing: 0.3px;
            margin: 15px 0;
        }

        .container span {
            font-size: clamp(12px, 2.5vw, 14px);
            display: block;
            margin-bottom: 15px;
        }

        .container a {
            color: #333;
            font-size: clamp(12px, 2.5vw, 14px);
            text-decoration: none;
            margin: 10px 0;
            display: inline-block;
        }

        .container button {
            background-color: #B7D971;
            color: #fff;
            font-size: clamp(14px, 3vw, 18px);
            padding: 10px 30px;
            border: 1px solid transparent;
            border-radius: 8px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-top: 10px;
            cursor: pointer;
            width: 100%;
            max-width: 250px;
        }

        .container button:hover {
            background-color: #008b13;
            transition: 0.5s;
        }

        .container button.hidden {
            background-color: transparent;
            border-color: #fff;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo-container img {
            width: clamp(20px, 10vw, 50px);
            height: auto;
        }

        .container form {
            background-color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 20px;
            height: 100%;
        }

        .container input {
            background-color: #eee;
            border: 1px solid transparent;
            margin: 8px 0;
            padding: 10px 15px;
            font-size: clamp(12px, 2.5vw, 14px);
            border-radius: 8px;
            width: 100%;
            outline: none;
            transition: all 0.3s ease;
        }

        .container input:hover {
            border: 2px solid #B7D971;
            box-shadow: 0 0 5px rgba(0, 255, 34, 0.3);
        }

        .container input:focus {
            border-color: #B7D971;
            box-shadow: 0 0 5px rgba(0, 255, 8, 0.5);
        }

        .form-container {
            position: absolute;
            top: 0;
            height: 100%;
            transition: all 0.6s ease-in-out;
            width: 50%;
        }

        .input-icon {
            position: relative;
            margin-bottom: 15px;
            width: 100%;
            max-width: 350px;
        }

        .input-icon i {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: #00000075;
            font-size: clamp(14px, 3vw, 18px);
        }

        .input-icon input {
            width: 100%;
            padding: 10px 10px 10px 45px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .sign-in {
            left: 0;
            width: 50%;
            z-index: 2;
        }

        .container.active .sign-in {
            transform: translateX(100%);
        }

        .sign-up {
            left: 0;
            width: 50%;
            opacity: 0;
            z-index: 1;
        }

        .container.active .sign-up {
            transform: translateX(100%);
            opacity: 1;
            z-index: 5;
            animation: move 0.6s;
        }

        @keyframes move {
            0%, 49.99% {
                opacity: 0;
                z-index: 1;
            }
            50%, 100% {
                opacity: 1;
                z-index: 5;
            }
        }

        .toggle-container {
            position: absolute;
            top: 0;
            left: 50%;
            width: 50%;
            height: 100%;
            overflow: hidden;
            transition: all 0.6s ease-in-out;
            border-radius: 40px 0 0 40px;
            z-index: 1000;
        }

        .container.active .toggle-container {
            transform: translateX(-100%);
            border-radius: 0 40px 40px 0;
        }

        .toggle {
            background-color: #B7D971;
            height: 100%;
            background: linear-gradient(to right, #2f89aa, #2FAAA8);
            color: #fff;
            position: relative;
            left: -100%;
            width: 200%;
            transform: translateX(0);
            transition: all 0.6s ease-in-out;
        }

        .container.active .toggle {
            transform: translateX(50%);
        }

        .toggle-panel {
            position: absolute;
            width: 50%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 0 20px;
            text-align: center;
            top: 0;
            transform: translateX(0);
            transition: all 0.6s ease-in-out;
        }

        .toggle-left {
            transform: translateX(-200%);
        }

        .container.active .toggle-left {
            transform: translateX(0);
        }

        .toggle-right {
            right: 0;
            transform: translateX(0);
        }

        .container.active .toggle-right {
            transform: translateX(200%);
        }

        .alert {
            font-size: clamp(14px, 2.5vw, 20px);
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            width: 100%;
            text-align: center;
        }

        /* Mobile Styles */
        @media screen and (max-width: 768px) {
            body {
                padding: 10px;
            }

            .container {
                border-radius: 20px;
                min-height: auto;
            }

            .form-container {
                position: static;
                width: 100%;
                display: block;
            }

            .sign-in, .sign-up {
                position: relative;
                width: 100%;
                left: auto;
                transform: none;
                opacity: 1;
                display: none;
            }

            .sign-in.active-form {
                display: block;
            }

            .sign-up.active-form {
                display: block;
            }

            .container.active .sign-in,
            .container.active .sign-up {
                transform: none;
            }

            .toggle-container {
                position: static;
                width: 100%;
                height: auto;
                transform: none;
                border-radius: 0;
                margin-bottom: 20px;
            }

            .container.active .toggle-container {
                transform: none;
                border-radius: 0;
            }

            .toggle {
                position: static;
                width: 100%;
                left: auto;
                transform: none;
                padding: 30px 20px;
                border-radius: 20px 20px 0 0;
            }

            .container.active .toggle {
                transform: none;
            }

            .toggle-panel {
                position: static;
                width: 100%;
                transform: none;
                padding: 0;
            }

            .toggle-left,
            .toggle-right {
                transform: none;
                display: none;
            }

            .toggle-left.active-panel,
            .toggle-right.active-panel {
                display: flex;
            }

            .container.active .toggle-left,
            .container.active .toggle-right {
                transform: none;
            }

            .container button {
                padding: 12px 20px;
                width: 90%;
            }

            .input-icon {
                max-width: 100%;
            }

            .input-icon i {
                left: 12px;
            }

            .input-icon input {
                padding: 12px 12px 12px 40px;
                font-size: 14px;
            }

            h1 {
                font-size: 24px;
            }

            .container p {
                font-size: 14px;
                padding: 0 10px;
            }
        }

        @media screen and (max-width: 480px) {
            h1 {
                font-size: 20px;
            }

            .logo-container img {
                width: 60px;
            }

            .container button {
                font-size: 14px;
                padding: 10px 20px;
            }

            .input-icon input {
                padding: 10px 10px 10px 38px;
            }
        }
    </style>
</head>

<body>
    <div class="container" id="container">
        <div class="form-container sign-up" id="signUpForm">
            <form action="process/signup_process.php" method="POST">
                <div class="logo-container">
                    <a href="index.php">
                        <img src="assets/images/logo.png" alt="logo "style>
                    </a>
                </div>
                <h1>สร้างบัญชีของคุณ</h1>
                <span>เริ่มต้นเส้นทางสุขภาพที่ดีกับเราได้เลย</span>

                <div class="input-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" placeholder="ชื่อผู้ใช้" id="username" name="username" required>
                </div>

                <div class="input-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" placeholder="อีเมล" id="email" name="email" required>
                </div>

                <div class="input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" placeholder="รหัสผ่าน" id="password" name="password" required>
                </div>

                <button type="submit">สร้างบัญชี</button>
                <a href="#" class="mobile-toggle" onclick="toggleMobileForms('signin')">มีบัญชีแล้ว? เข้าสู่ระบบ</a>
            </form>
        </div>

        <div class="form-container sign-in active-form" id="signInForm">
            <form action="process/login_process.php" method="POST">
                <div class="logo-container">
                    <a href="index.php">
                         <img src="assets/images/logo.png" alt="logo "style>
                    </a>
                </div>
                <h1>เข้าสู่ระบบ</h1>
                <span>ป้อนชื่อผู้ใช้และรหัสผ่านของคุณ</span>
                
                <div class="input-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" placeholder="ชื่อผู้ใช้" id="username" name="username" required>
                </div>
                
                <div class="input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" placeholder="รหัสผ่าน" id="password" name="password" required>
                </div>
                
                <a href="forgot_password.php">ลืมรหัสผ่าน?</a>
                <button type="submit">เข้าสู่ระบบ</button>
                <a href="#" class="mobile-toggle" onclick="toggleMobileForms('signup')">ยังไม่มีบัญชี? สมัครสมาชิก</a>
            </form>
        </div>

        <div class="toggle-container">
            <div class="toggle">
                <div class="toggle-panel toggle-left" id="toggleLeft">
                    <h1 id="welcome-text">ยินดีต้อนรับ!</h1>
                    <p>เข้าสู่ระบบเพื่อใช้งานคุณสมบัติทั้งหมดของไซต์</p>
                    <button class="hidden" id="login">เข้าสู่ระบบที่นี่</button>
                </div>
                <div class="toggle-panel toggle-right active-panel" id="toggleRight">
                    <h1 id="greeting-text">สวัสดี, เพื่อน!</h1>
                    <p>ยังไม่มีบัญชีของคุณใช่ไหม</p>
                    <button class="hidden" id="register">สร้างบัญชีที่นี่</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const container = document.getElementById('container');
        const registerBtn = document.getElementById('register');
        const loginBtn = document.getElementById('login');

        // Desktop toggle
        if (registerBtn) {
            registerBtn.addEventListener('click', () => {
                container.classList.add("active");
                typeWriterEffect("greeting-text", "สวัสดี, เพื่อน!", 100);
            });
        }

        if (loginBtn) {
            loginBtn.addEventListener('click', () => {
                container.classList.remove("active");
                typeWriterEffect("welcome-text", "ยินดีต้อนรับ!", 100);
            });
        }

        // Mobile toggle function
        function toggleMobileForms(formType) {
            const signInForm = document.getElementById('signInForm');
            const signUpForm = document.getElementById('signUpForm');
            const toggleLeft = document.getElementById('toggleLeft');
            const toggleRight = document.getElementById('toggleRight');

            if (window.innerWidth <= 768) {
                if (formType === 'signup') {
                    signInForm.classList.remove('active-form');
                    signUpForm.classList.add('active-form');
                    toggleLeft.classList.add('active-panel');
                    toggleRight.classList.remove('active-panel');
                } else {
                    signInForm.classList.add('active-form');
                    signUpForm.classList.remove('active-form');
                    toggleLeft.classList.remove('active-panel');
                    toggleRight.classList.add('active-panel');
                }
            }
        }

        // Typewriter effect
        let typingTimers = {};

        function typeWriterEffect(elementId, text, speed) {
            const element = document.getElementById(elementId);
            if (!element) return;

            if (typingTimers[elementId]) {
                clearTimeout(typingTimers[elementId]);
            }

            element.innerHTML = "";
            let index = 0;

            function type() {
                if (index < text.length) {
                    element.innerHTML += text.charAt(index);
                    index++;
                    typingTimers[elementId] = setTimeout(type, speed);
                } else {
                    typingTimers[elementId] = null;
                }
            }
            type();
        }

        // Initialize typewriter on load
        window.onload = () => {
            typeWriterEffect("welcome-text", "ยินดีต้อนรับ!", 100);
            typeWriterEffect("greeting-text", "สวัสดี, เพื่อน!", 100);
        };

        // Handle window resize
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                if (window.innerWidth > 768) {
                    // Reset to desktop view
                    document.querySelectorAll('.form-container').forEach(el => {
                        el.classList.remove('active-form');
                    });
                    document.querySelectorAll('.toggle-panel').forEach(el => {
                        el.classList.remove('active-panel');
                    });
                } else {
                    // Ensure one form is active on mobile
                    const signInForm = document.getElementById('signInForm');
                    const toggleRight = document.getElementById('toggleRight');
                    if (!document.querySelector('.form-container.active-form')) {
                        signInForm.classList.add('active-form');
                        toggleRight.classList.add('active-panel');
                    }
                }
            }, 250);
        });

        // Check initial screen size
        if (window.innerWidth <= 768) {
            document.getElementById('signInForm').classList.add('active-form');
            document.getElementById('toggleRight').classList.add('active-panel');
        }

        // Style mobile toggle links
        document.querySelectorAll('.mobile-toggle').forEach(link => {
            link.style.display = window.innerWidth <= 768 ? 'inline-block' : 'none';
        });

        window.addEventListener('resize', () => {
            document.querySelectorAll('.mobile-toggle').forEach(link => {
                link.style.display = window.innerWidth <= 768 ? 'inline-block' : 'none';
            });
        });
    </script>
</body>

</html>