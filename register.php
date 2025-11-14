<?php
session_start();
require_once 'db.php';
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Trim and normalize inputs
    $lastname = trim($_POST['lastname'] ?? '');
    $firstname = trim($_POST['firstname'] ?? '');
    $mi = trim($_POST['mi'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $role = trim($_POST['role'] ?? 'employee');
    $contact_no = preg_replace('/\s+/', '', $_POST['contact_no'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // Server-side validations
    $nameRegex = "/^[A-Za-z\s\-']+$/"; // letters, spaces, hyphen, apostrophe
    $miRegex = "/^[A-Za-z]?$/";         // optional single letter
    $phoneRegex = "/^09\d{9}$/";       // PH mobile: 11 digits starting with 09

    if (!preg_match($nameRegex, $lastname) || !preg_match($nameRegex, $firstname)) {
        $error = 'Names must contain letters only (no numbers).';
    } elseif ($mi !== '' && !preg_match($miRegex, $mi)) {
        $error = 'Middle initial must be a single letter.';
    } elseif ($contact_no !== '' && !preg_match($phoneRegex, $contact_no)) {
        $error = 'Contact number must be 11 digits starting with 09.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif (empty($department) || empty($lastname) || empty($firstname) || empty($position) || empty($email)) {
        $error = 'Please fill in all required fields.';
    } else {
        // Ensure position is one of the allowed employment categories
        $allowedPositions = ['Permanent','Casual','JO','OJT'];
        if (!in_array($position, $allowedPositions)) {
            $error = 'Invalid position selected.';
        } else {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email already registered.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                // All new accounts require super admin approval: set status to 'pending'
                $pendingStatus = 'pending';
                
                // Generate unique employee_id (format: EMP2025-0001)
                $year = date('Y');
                $stmt = $pdo->query("SELECT employee_id FROM users WHERE employee_id LIKE 'EMP{$year}-%' ORDER BY employee_id DESC LIMIT 1");
                $lastEmp = $stmt->fetchColumn();
                if ($lastEmp && preg_match('/EMP\d{4}-(\d+)/', $lastEmp, $m)) {
                    $seq = intval($m[1]) + 1;
                } else {
                    $seq = 1;
                }
                $employee_id = sprintf('EMP%s-%04d', $year, $seq);

                // Note: per-user QR codes are retired. We still generate an employee_id here.
                $stmt = $pdo->prepare('INSERT INTO users (email, password, department, lastname, firstname, mi, position, role, contact_no, status, employee_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                if ($stmt->execute([$email, $hash, $department, $lastname, $firstname, $mi, $position, $role, $contact_no, $pendingStatus, $employee_id])) {
                    $success = 'Registration submitted! Your Employee ID: <strong>' . $employee_id . '</strong>. Awaiting super admin approval.';
                } else {
                    $error = 'Registration failed. Please try again.';
                }
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
                <div id="client-message" class="modal-message error" style="display:none;"></div>
                <form id="register-form" method="post" action="">
                    <!-- Step 1: Name -->
                    <div class="step" id="step-1">
                        <div class="input-block">
                            <label for="lastname" class="input-label">Last Name</label>
                            <input type="text" name="lastname" id="lastname" placeholder="Last Name" required pattern="[A-Za-z \-']+" title="Letters, spaces, hyphen (-) and apostrophe (') only">
                        </div>
                        <div class="input-block">
                            <label for="firstname" class="input-label">First Name</label>
                            <input type="text" name="firstname" id="firstname" placeholder="First Name" required pattern="[A-Za-z \-']+" title="Letters, spaces, hyphen (-) and apostrophe (') only">
                        </div>
                        <div class="input-block">
                            <label for="mi" class="input-label">Middle Initial</label>
                            <input type="text" name="mi" id="mi" placeholder="M" maxlength="1" pattern="[A-Za-z]" title="Single letter only">
                        </div>
                        <div class="modal-buttons">
                            <button type="button" class="input-button" id="next-1">Next</button>
                        </div>
                    </div>
                    <!-- Step 2: Department/Position -->
                    <div class="step" id="step-2" style="display:none;">
                        <div class="input-block">
                            <label for="department" class="input-label">Department</label>
                            <select name="department" id="department" required>
                                <option value="">Select Department</option>
                                <option value="Office of the Municipal Mayor">Office of the Municipal Mayor</option>
                                <option value="Office of the Municipal Vice Mayor">Office of the Municipal Vice Mayor</option>
                                <option value="Office of the Sangguiniang Bayan">Office of the Sangguiniang Bayan</option>
                                <option value="Office of the Municipal Administrator">Office of the Municipal Administrator</option>
                                <option value="Office of the Municipal Engineer">Office of the Municipal Engineer</option>
                                <option value="Office of the MPDC">Office of the MPDC</option>
                                <option value="Office of the Municipal Budget Officer">Office of the Municipal Budget Officer</option>
                                <option value="Office of the Municipal Assessor">Office of the Municipal Assessor</option>
                                <option value="Office of the Municipal Accountant">Office of the Municipal Accountant</option>
                                <option value="Office of the Municipal Civil Registrar">Office of the Municipal Civil Registrar</option>
                                <option value="Office of the Municipal Treasurer">Office of the Municipal Treasurer</option>
                                <option value="Office of the Municipal Social Welfare and Development Officer">Office of the Municipal Social Welfare and Development Officer</option>
                                <option value="Office of the Municipal Health Officer">Office of the Municipal Health Officer</option>
                                <option value="Office of the Municipal Agriculturist">Office of the Municipal Agriculturist</option>
                                <option value="Office of the MDRRMO">Office of the MDRRMO</option>
                                <option value="Office of the Municipal Legal Officer">Office of the Municipal Legal Officer</option>
                                <option value="Office of the Municipal General Services Officer">Office of the Municipal General Services Officer</option>
                            </select>
                        </div>
                        <div class="input-block">
                            <label for="position" class="input-label">Position</label>
                            <select name="position" id="position" required>
                                <option value="">Select Position</option>
                                <option value="Permanent">Permanent</option>
                                <option value="Casual">Casual</option>
                                <option value="JO">JO</option>
                                <option value="OJT">OJT</option>
                            </select>
                        </div>
                        <div class="input-block">
                            <label for="role" class="input-label">Role (how this account will be used)</label>
                            <select name="role" id="role" required>
                                <option value="employee">Employee</option>
                                <option value="department_head">Department Head</option>
                                <option value="hr">HR</option>
                            </select>
                        </div>
                        <div class="input-block">
                            <label for="contact_no" class="input-label">Contact No.</label>
                            <input type="text" name="contact_no" id="contact_no" placeholder="09XXXXXXXXX" inputmode="numeric" pattern="09[0-9]{9}" maxlength="11" title="Must start with 09 and be 11 digits" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
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
        // Multi-step form logic with validation
        const step1 = document.getElementById('step-1');
        const step2 = document.getElementById('step-2');
        const step3 = document.getElementById('step-3');
        const clientMessage = document.getElementById('client-message');

        const nameRegex = /^[A-Za-z \-']+$/;
        const miRegex = /^[A-Za-z]?$/;
        const phoneRegex = /^09\d{9}$/;

        function showClientError(msg) {
            clientMessage.textContent = msg;
            clientMessage.style.display = 'block';
        }
        function clearClientError() {
            clientMessage.textContent = '';
            clientMessage.style.display = 'none';
        }

        document.getElementById('next-1').onclick = function() {
            const ln = document.getElementById('lastname').value.trim();
            const fn = document.getElementById('firstname').value.trim();
            const mi = document.getElementById('mi').value.trim();
            if (!ln || !fn) { showClientError('Please fill in your name.'); return; }
            if (!nameRegex.test(ln) || !nameRegex.test(fn)) { showClientError('Name fields must be letters only.'); return; }
            if (mi && !miRegex.test(mi)) { showClientError('Middle initial must be a single letter.'); return; }
            clearClientError();
            step1.style.display = 'none';
            step2.style.display = 'block';
        };
        document.getElementById('next-2').onclick = function() {
            const dept = document.getElementById('department').value;
            const pos = document.getElementById('position').value;
            const role = document.getElementById('role').value;
            const phone = document.getElementById('contact_no').value.trim();
            if (!dept || !pos || !role) { showClientError('Please select department, position, and role.'); return; }
            if (phone && !phoneRegex.test(phone)) { showClientError('Contact number must start with 09 and be 11 digits.'); return; }
            clearClientError();
            step2.style.display = 'none';
            step3.style.display = 'block';
        };
        document.getElementById('prev-2').onclick = function() {
            clearClientError();
            step2.style.display = 'none';
            step1.style.display = 'block';
        };
        document.getElementById('prev-3').onclick = function() {
            clearClientError();
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
