<?php
session_start();
require_once 'db.php';
// Redirect to dashboard if already logged in
$redirected = false;
if (isset($_SESSION['user_id']) && isset($_SESSION['position'])) {
    if ($_SESSION['position'] === 'HR') {
        header('Location: hr/dashboard.php');
        $redirected = true;
    } elseif ($_SESSION['position'] === 'Dept Head') {
        header('Location: dept_head/dashboard.php');
        $redirected = true;
    } elseif ($_SESSION['position'] === 'Employee') {
        header('Location: employee/dashboard.php');
        $redirected = true;
    } else {
        header('Location: dashboard.php');
        $redirected = true;
    }
    if ($redirected) exit();
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    // Super admin bypass: no DB check needed
    if ($email === 'mabiniadmin@gmail.com' && $password === 'MabiniAdminOfficial') {
        $_SESSION['user_id'] = 'superadmin';
        $_SESSION['position'] = 'Super Admin';
        $_SESSION['email'] = $email;
        header('Location: super_admin.html');
        exit();
    }
    // Normal user login
    $stmt = $pdo->prepare('SELECT id, password, position FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['position'] = $user['position'];
        $_SESSION['email'] = $email;
        if ($user['position'] === 'HR') {
            header('Location: hr/dashboard.php');
        } elseif ($user['position'] === 'Dept Head') {
            header('Location: dept_head/dashboard.php');
        } elseif ($user['position'] === 'Employee') {
            header('Location: employee/dashboard.php');
        } else {
            header('Location: dashboard.php');
        }
        exit();
    } else {
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mabini | Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: #4b5563;
            background-image: url('assets/mabinibg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(8px);
            z-index: -1;
        }

        .modal-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: all 0.5s ease-in-out;
            opacity: 0;
            pointer-events: none;
        }

        .modal-bg.is-open {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-container {
            display: flex;
            max-width: 800px;
            width: 90%;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            position: relative;
            background: #fff;
            transform: scale(0.9);
            transition: transform 0.4s ease-in-out;
        }
        
        .modal-bg.is-open .modal-container {
            transform: scale(1);
        }

        .modal-title {
            font-size: 2rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .modal-desc {
            font-size: 1rem;
            color: #6b7280;
            margin-bottom: 2rem;
        }
        
        .modal-message {
            margin-bottom: 1rem;
            color: #1e40af;
            font-size: 0.875rem;
            font-weight: 500;
            text-align: center;
            padding: 0.5rem;
            background-color: #e0f2fe;
            border-radius: 4px;
        }

        .modal-left {
            padding: 2.5rem;
            flex: 1.5;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .modal-right {
            flex: 2;
            display: none;
            position: relative;
        }
        
        .modal-right img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .input-button {
            padding: 0.75rem 1.5rem;
            outline: none;
            border: none;
            color: #fff;
            border-radius: 9999px;
            background: #1e40af;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 1rem;
            transition: background 0.3s ease;
            cursor: pointer;
        }

        .input-button:hover {
            background: #1e3a8a;
        }
        
        .input-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.05em;
            color: #1e40af;
            transition: color 0.3s;
        }

        .input-block {
            display: flex;
            flex-direction: column;
            padding: 0.75rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 1.25rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            position: relative;
        }

        .input-block input {
            outline: none;
            border: none;
            padding: 0.25rem 0 0;
            font-size: 1rem;
            background: transparent;
        }

        .input-block input::placeholder {
            color: #d1d5db;
        }

        .input-block:focus-within {
            border-color: #1e40af;
            box-shadow: 0 0 0 2px rgba(30, 64, 175, 0.2);
        }

        .input-block .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #9ca3af;
            transition: color 0.2s;
        }

        .input-block .password-toggle:hover {
            color: #4b5563;
        }

        .modal-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }

        .modal-buttons a {
            color: #6b7280;
            font-size: 0.875rem;
            transition: color 0.2s;
        }
        
        .modal-buttons a:hover {
            color: #1e40af;
        }

        .close-button {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 32px;
            height: 32px;
            background: transparent;
            border: none;
            cursor: pointer;
            color: #9ca3af;
            transition: color 0.2s;
            z-index: 10;
        }

        .close-button:hover {
            color: #4b5563;
        }

        .logo-button {
            cursor: pointer;
            border-radius: 50%;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .logo-button:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        @media(min-width: 768px) {
            .modal-right {
                display: block;
            }
        }
    </style>
</head>
<body>
    <img src="assets/logo.png" alt="Mabini Logo" class="logo-button" id="open-modal-logo">

    <div class="modal-bg">
        <div class="modal-container">
            <button class="close-button" id="close-button">
                <i class="fas fa-times text-xl"></i>
            </button>
            <div class="modal-left">
                <h1 class="modal-title">Welcome Back!</h1>
                <p class="modal-desc">Please log in to your account.</p>
                <?php if ($error): ?>
                    <div class="modal-message" style="color:#dc2626;background:#fee2e2;display:block;">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                <form id="login-form" method="post" action="">
                    <div class="input-block">
                        <label for="email" class="input-label">Email</label>
                        <input type="email" name="email" id="email" placeholder="email@example.com" required>
                    </div>
                    <div class="input-block">
                        <label for="password" class="input-label">Password</label>
                        <input type="password" name="password" id="password" placeholder="••••••••" required>
                        <i id="password-toggle" class="fas fa-eye password-toggle"></i>
                    </div>
                    <div class="modal-buttons">
                        <a href="#" id="forgot-password-link">Forgot your password?</a>
                        <button type="submit" class="input-button">Login</button>
                    </div>
                </form>
            </div>
            <div class="modal-right">
                <img src="assets/mabinibg1.jpg" alt="A view of the Mabini, Batangas landscape">
            </div>
        </div>
    </div>

    <script>
        const body = document.querySelector("body");
        const modalBg = document.querySelector(".modal-bg");
        const openButton = document.getElementById("open-modal-logo");
        const closeButton = document.getElementById("close-button");
        const loginForm = document.getElementById("login-form");
        const passwordInput = document.getElementById("password");
        const passwordToggle = document.getElementById("password-toggle");
        const forgotPasswordLink = document.getElementById("forgot-password-link");
        // const statusMessage = document.getElementById("status-message");

        const openModal = () => {
            modalBg.classList.add("is-open");
            body.style.overflow = "hidden";
        };

        const closeModal = () => {
            modalBg.classList.remove("is-open");
            body.style.overflow = "auto";
        };

        openButton.addEventListener("click", openModal);
        closeButton.addEventListener("click", closeModal);
        modalBg.addEventListener("click", (e) => {
            if (e.target === modalBg) {
                closeModal();
            }
        });

        document.onkeydown = evt => {
            evt = evt || window.event;
            if (evt.key === "Escape" || evt.keyCode === 27) {
                closeModal();
            }
        };

        // Remove JS form redirect, let PHP handle login
        // loginForm.addEventListener('submit', (e) => {
        //     e.preventDefault();
        //     window.location.href = 'index.html';
        // });

        passwordToggle.addEventListener('click', () => {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            passwordToggle.classList.toggle('fa-eye');
            passwordToggle.classList.toggle('fa-eye-slash');
        });

        forgotPasswordLink.addEventListener('click', (e) => {
            e.preventDefault();
            alert('A password reset link has been sent to your email.');
        });
    </script>
</body>
</html>