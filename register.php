<?php
session_start();
require_once 'db.php';
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lastname = $_POST['lastname'] ?? '';
    $firstname = $_POST['firstname'] ?? '';
    $mi = $_POST['mi'] ?? '';
    $department = $_POST['department'] ?? '';
    $position = $_POST['position'] ?? '';
    $status = $_POST['status'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif (empty($department) || empty($lastname) || empty($firstname) || empty($position) || empty($status) || empty($email)) {
        $error = 'Please fill in all required fields.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (email, password, department, lastname, firstname, mi, position, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            if ($stmt->execute([$email, $hash, $department, $lastname, $firstname, $mi, $position, $status])) {
                $success = 'Registration successful! You can now <a href="index.php" style="color:#2563eb;">login</a>.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mabini | Register</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
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
            transform: scale(1);
            transition: transform 0.4s ease-in-out;
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
        .modal-message.error {
            color: #dc2626;
            background: #fee2e2;
        }
        .modal-message.success {
            color: #16a34a;
            background: #dcfce7;
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
            <button class="close-button" id="close-button" onclick="window.location.href='index.php';return false;">
                <i class="fas fa-times text-xl"></i>
            </button>
            <div class="modal-left">
                <h1 class="modal-title">Create Account</h1>
                <p class="modal-desc">Register a new account below.</p>
                <?php if ($error): ?>
                    <div class="modal-message error">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="modal-message success">
                        <?= $success ?>
                    </div>
                <?php endif; ?>
                <form id="register-form" method="post" action="">
                    <!-- Step 1: Name -->
                    <div class="step" id="step-1">
                        <div class="input-block">
                            <label for="lastname" class="input-label">Last Name</label>
                            <input type="text" name="lastname" id="lastname" placeholder="Last Name" required>
                        </div>
                        <div class="input-block">
                            <label for="firstname" class="input-label">First Name</label>
                            <input type="text" name="firstname" id="firstname" placeholder="First Name" required>
                        </div>
                        <div class="input-block">
                            <label for="mi" class="input-label">Middle Initial</label>
                            <input type="text" name="mi" id="mi" placeholder="M" maxlength="1">
                        </div>
                        <div class="modal-buttons">
                            <button type="button" class="input-button" id="next-1">Next</button>
                        </div>
                    </div>
                    <!-- Step 2: Department/Position -->
                    <div class="step" id="step-2" style="display:none;">
                        <div class="input-block">
                            <label for="department" class="input-label">Department</label>
                            <input type="text" name="department" id="department" placeholder="Department" required>
                        </div>
                        <div class="input-block">
                            <label for="position" class="input-label">Position</label>
                            <select name="position" id="position" required>
                                <option value="">Select Position</option>
                                <option value="HR">HR</option>
                                <option value="Dept Head">Dept Head</option>
                                <option value="Employee">Employee</option>
                            </select>
                        </div>
                        <div class="input-block">
                            <label for="status" class="input-label">Employee Status</label>
                            <select name="status" id="status" required>
                                <option value="">Select Status</option>
                                <option value="Permanent">Permanent</option>
                                <option value="Casual">Casual</option>
                                <option value="JO">JO</option>
                                <option value="OJT">OJT</option>
                            </select>
                        </div>
                        <div class="modal-buttons">
                            <button type="button" class="input-button" id="prev-2">Back</button>
                            <button type="button" class="input-button" id="next-2">Next</button>
                        </div>
                    </div>
                    <!-- Step 3: Email/Password -->
                    <div class="step" id="step-3" style="display:none;">
                        <div class="input-block">
                            <label for="email" class="input-label">Email</label>
                            <input type="email" name="email" id="email" placeholder="email@example.com" required>
                        </div>
                        <div class="input-block">
                            <label for="password" class="input-label">Password</label>
                            <input type="password" name="password" id="password" placeholder="••••••••" required>
                            <i id="password-toggle" class="fas fa-eye password-toggle"></i>
                        </div>
                        <div class="input-block">
                            <label for="confirm_password" class="input-label">Confirm Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" placeholder="••••••••" required>
                            <i id="confirm-toggle" class="fas fa-eye password-toggle"></i>
                        </div>
                        <div class="modal-buttons">
                            <button type="button" class="input-button" id="prev-3">Back</button>
                            <button type="submit" class="input-button">Register</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-right">
                <img src="assets/mabinibg1.jpg" alt="A view of the Mabini, Batangas landscape">
            </div>
        </div>
    </div>
    <script>
        // Multi-step form logic
        const step1 = document.getElementById('step-1');
        const step2 = document.getElementById('step-2');
        const step3 = document.getElementById('step-3');
        document.getElementById('next-1').onclick = function() {
            if (document.getElementById('lastname').value && document.getElementById('firstname').value) {
                step1.style.display = 'none';
                step2.style.display = 'block';
            }
        };
        document.getElementById('next-2').onclick = function() {
            if (document.getElementById('department').value && document.getElementById('position').value) {
                step2.style.display = 'none';
                step3.style.display = 'block';
            }
        };
        document.getElementById('prev-2').onclick = function() {
            step2.style.display = 'none';
            step1.style.display = 'block';
        };
        document.getElementById('prev-3').onclick = function() {
            step3.style.display = 'none';
            step2.style.display = 'block';
        };
        // Password toggle logic
        const passwordInput = document.getElementById("password");
        const passwordToggle = document.getElementById("password-toggle");
        const confirmInput = document.getElementById("confirm_password");
        const confirmToggle = document.getElementById("confirm-toggle");
        passwordToggle.addEventListener('click', () => {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            passwordToggle.classList.toggle('fa-eye');
            passwordToggle.classList.toggle('fa-eye-slash');
        });
        confirmToggle.addEventListener('click', () => {
            const type = confirmInput.type === 'password' ? 'text' : 'password';
            confirmInput.type = type;
            confirmToggle.classList.toggle('fa-eye');
            confirmToggle.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>
