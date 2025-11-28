<?php
require_once __DIR__ . '/auth_guard.php';
prevent_if_authenticated();
require_once 'db.php';
$error = '';
$success = '';
$step = 1; // Step 1: Email, Step 2: Code verification, Step 3: New password

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'verify_code') {
            // Step 2: Verify code
            $email = trim($_POST['email'] ?? '');
            $code = trim($_POST['code'] ?? '');
            
            if (!$email || !$code) {
                $error = 'Email and code are required.';
                $step = 2;
            } else {
                // Verify code directly without using curl
                if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_code'])) {
                    $error = 'No reset code requested for this session. Please request a new code.';
                    $step = 1;
                } elseif ($_SESSION['reset_email'] !== $email) {
                    $error = 'Email mismatch. Request a new code.';
                    $step = 1;
                } elseif (time() > (int)$_SESSION['reset_expires']) {
                    $error = 'Reset code expired. Request a new one.';
                    $step = 1;
                } elseif ($_SESSION['reset_code'] !== $code) {
                    // Wrong code - allow retry without clearing session
                    $error = 'Incorrect reset code. Please try again.';
                    $step = 2;
                } else {
                    // Success: mark verified
                    $_SESSION['reset_verified_email'] = $email;
                    // Clear code to prevent reuse
                    unset($_SESSION['reset_code']);
                    unset($_SESSION['reset_expires']);
                    // Redirect to step 3 to show password form
                    header('Location: forgot_password.php?step=3');
                    exit();
                }
            }
        } elseif ($_POST['action'] === 'reset_password') {
            // Step 3: Reset password
            $email = $_SESSION['reset_verified_email'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            
            if (!$email) {
                $error = 'Session expired. Please start over.';
                $step = 1;
            } elseif ($password !== $confirm) {
                $error = 'Passwords do not match.';
                $step = 3;
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters.';
                $step = 3;
            } else {
                // Update password
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE email = ?');
                if ($stmt->execute([$hash, $email])) {
                    unset($_SESSION['reset_verified_email']);
                    unset($_SESSION['verification_email']);
                    unset($_SESSION['verification_code']);
                    $success = 'Password reset successful! You can now log in.';
                    $step = 4; // Success state
                } else {
                    $error = 'Failed to update password. Please try again.';
                    $step = 3;
                }
            }
        }
    }
}

// Check if returning from step 2 or step 3
if (isset($_GET['step'])) {
    if ($_GET['step'] == 2 && isset($_GET['email'])) {
        $step = 2;
    } elseif ($_GET['step'] == 3 && isset($_SESSION['reset_verified_email'])) {
        $step = 3;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mabini | Forgot Password</title>
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
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem 0;
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
        .modal-container {
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            background: #fff;
            padding: 2.5rem;
        }
        .modal-title {
            font-size: 1.75rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        .modal-desc {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 1.5rem;
        }
        .modal-message {
            margin-bottom: 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            text-align: center;
            padding: 0.5rem;
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
        .input-button {
            padding: 0.65rem 1.25rem;
            outline: none;
            border: none;
            color: #fff;
            border-radius: 9999px;
            background: #1e40af;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 0.95rem;
            transition: background 0.3s ease;
            cursor: pointer;
            width: 100%;
        }
        .input-button:hover {
            background: #1e3a8a;
        }
        .input-button:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }
        .input-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.05em;
            color: #1e40af;
        }
        .input-block {
            display: flex;
            flex-direction: column;
            padding: 0.6rem 0.85rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 1rem;
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
        }
        .input-block .password-toggle:hover {
            color: #4b5563;
        }
        .back-link {
            display: inline-block;
            margin-top: 1rem;
            color: #6b7280;
            font-size: 0.875rem;
            text-decoration: none;
            transition: color 0.2s;
        }
        .back-link:hover {
            color: #1e40af;
        }
    </style>
</head>
<body>
    <div class="modal-container">
        <h1 class="modal-title">
            <?php if ($step === 1): ?>
                Forgot Password
            <?php elseif ($step === 2): ?>
                Enter Verification Code
            <?php elseif ($step === 3): ?>
                Reset Password
            <?php else: ?>
                Success!
            <?php endif; ?>
        </h1>
        <p class="modal-desc">
            <?php if ($step === 1): ?>
                Enter your email to receive a verification code.
            <?php elseif ($step === 2): ?>
                We've sent a 6-digit code to your email.
            <?php elseif ($step === 3): ?>
                Enter your new password below.
            <?php else: ?>
                Your password has been reset successfully.
            <?php endif; ?>
        </p>

        <?php if ($error): ?>
            <div class="modal-message error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="modal-message success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        <div id="client-message" class="modal-message error" style="display:none;"></div>

        <?php if ($step === 1): ?>
            <!-- Step 1: Enter Email -->
            <form id="email-form">
                <div class="input-block">
                    <label for="email" class="input-label">Email</label>
                    <input type="email" name="email" id="email" placeholder="your-email@gmail.com" required>
                </div>
                <button type="button" class="input-button" id="send-code-btn">Send Verification Code</button>
            </form>

        <?php elseif ($step === 2): ?>
            <!-- Step 2: Verify Code -->
            <form method="post" action="">
                <input type="hidden" name="action" value="verify_code">
                <input type="hidden" name="email" id="hidden-email" value="<?= htmlspecialchars($_GET['email'] ?? '') ?>">
                <div class="input-block">
                    <label for="code" class="input-label">6-Digit Code</label>
                    <input type="text" name="code" id="code" placeholder="123456" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" required>
                </div>
                <button type="submit" class="input-button">Verify Code</button>
            </form>
            <button type="button" class="input-button" id="resend-code-btn" style="background:#6b7280;margin-top:0.75rem;">Resend Code</button>

        <?php elseif ($step === 3): ?>
            <!-- Step 3: New Password -->
            <form method="post" action="">
                <input type="hidden" name="action" value="reset_password">
                <div class="input-block">
                    <label for="password" class="input-label">New Password</label>
                    <input type="password" name="password" id="password" placeholder="••••••••" required>
                    <i id="password-toggle" class="fas fa-eye password-toggle"></i>
                </div>
                <div class="input-block">
                    <label for="confirm_password" class="input-label">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="••••••••" required>
                    <i id="confirm-toggle" class="fas fa-eye password-toggle"></i>
                </div>
                <button type="submit" class="input-button">Reset Password</button>
            </form>

        <?php else: ?>
            <!-- Step 4: Success -->
            <a href="index.php" class="input-button" style="display:block;text-align:center;text-decoration:none;">Go to Login</a>
        <?php endif; ?>

        <a href="index.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Login
        </a>
    </div>

    <script>
        const clientMessage = document.getElementById('client-message');

        function showClientError(msg) {
            clientMessage.textContent = msg;
            clientMessage.classList.remove('success');
            clientMessage.classList.add('error');
            clientMessage.style.display = 'block';
        }
        function showClientSuccess(msg) {
            clientMessage.textContent = msg;
            clientMessage.classList.remove('error');
            clientMessage.classList.add('success');
            clientMessage.style.display = 'block';
        }
        function clearClientMessage() {
            clientMessage.textContent = '';
            clientMessage.style.display = 'none';
        }

        // Step 1: Send verification code
        const sendCodeBtn = document.getElementById('send-code-btn');
        if (sendCodeBtn) {
            sendCodeBtn.addEventListener('click', async () => {
                clearClientMessage();
                const email = document.getElementById('email').value.trim();
                if (!email) { showClientError('Please enter your email.'); return; }
                
                sendCodeBtn.disabled = true;
                sendCodeBtn.textContent = 'Sending...';
                
                try {
                    const res = await fetch('api/send_password_reset_code.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({email: email})
                    });
                    const data = await res.json();
                    if (!res.ok || data.status !== 'ok') {
                        showClientError(data.message || 'Failed to send code.');
                        sendCodeBtn.disabled = false;
                        sendCodeBtn.textContent = 'Send Verification Code';
                        return;
                    }
                    showClientSuccess('Code sent! Redirecting...');
                    setTimeout(() => {
                        window.location.href = '?step=2&email=' + encodeURIComponent(email);
                    }, 1500);
                } catch (e) {
                    showClientError('Network error. Please try again.');
                    sendCodeBtn.disabled = false;
                    sendCodeBtn.textContent = 'Send Verification Code';
                }
            });
        }

        // Step 2: Resend code button
        const resendCodeBtn = document.getElementById('resend-code-btn');
        if (resendCodeBtn) {
            resendCodeBtn.addEventListener('click', async () => {
                clearClientMessage();
                const email = document.getElementById('hidden-email').value.trim();
                if (!email) { showClientError('Email missing. Please start over.'); return; }
                
                resendCodeBtn.disabled = true;
                resendCodeBtn.textContent = 'Sending...';
                
                try {
                    const res = await fetch('api/send_password_reset_code.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({email: email})
                    });
                    const data = await res.json();
                    if (!res.ok || data.status !== 'ok') {
                        showClientError(data.message || 'Failed to resend code.');
                        resendCodeBtn.disabled = false;
                        resendCodeBtn.textContent = 'Resend Code';
                        return;
                    }
                    showClientSuccess('New code sent! Please check your email.');
                    resendCodeBtn.disabled = false;
                    resendCodeBtn.textContent = 'Resend Code';
                } catch (e) {
                    showClientError('Network error. Please try again.');
                    resendCodeBtn.disabled = false;
                    resendCodeBtn.textContent = 'Resend Code';
                }
            });
        }

        // Password toggle
        const passwordToggle = document.getElementById('password-toggle');
        const confirmToggle = document.getElementById('confirm-toggle');
        if (passwordToggle) {
            passwordToggle.addEventListener('click', () => {
                const passwordInput = document.getElementById('password');
                const type = passwordInput.type === 'password' ? 'text' : 'password';
                passwordInput.type = type;
                passwordToggle.classList.toggle('fa-eye');
                passwordToggle.classList.toggle('fa-eye-slash');
            });
        }
        if (confirmToggle) {
            confirmToggle.addEventListener('click', () => {
                const confirmInput = document.getElementById('confirm_password');
                const type = confirmInput.type === 'password' ? 'text' : 'password';
                confirmInput.type = type;
                confirmToggle.classList.toggle('fa-eye');
                confirmToggle.classList.toggle('fa-eye-slash');
            });
        }
    </script>
</body>
</html>
