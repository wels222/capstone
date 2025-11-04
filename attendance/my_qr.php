<?php
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT employee_id, firstname, lastname, department, qr_code, profile_picture FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || !$user['employee_id']) {
    die('No employee ID found. Please contact administrator.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My QR Code</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Poppins', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            max-width: 500px;
            width: 100%;
            background: #fff;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }
        .profile {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 20px;
            border: 5px solid #667eea;
        }
        h1 { color: #1f2937; margin-bottom: 8px; font-size: 28px; }
        .emp-id {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
            margin: 12px 0;
        }
        .dept {
            color: #6b7280;
            margin-bottom: 24px;
            font-size: 16px;
        }
        .qr-container {
            background: #f9fafb;
            padding: 24px;
            border-radius: 12px;
            margin: 24px 0;
        }
        .qr-container img {
            width: 100%;
            max-width: 300px;
            height: auto;
        }
        .instructions {
            background: #fef3c7;
            padding: 16px;
            border-radius: 8px;
            font-size: 14px;
            color: #92400e;
            margin-bottom: 16px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            margin: 8px;
        }
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #6b7280;
        }
        .btn-secondary:hover {
            background: #4b5563;
        }
    </style>
</head>
<body>
    <div class="card">
        <?php if($user['profile_picture']): ?>
            <img class="profile" src="<?= htmlspecialchars('../' . $user['profile_picture']) ?>" alt="Profile">
        <?php endif; ?>
        
        <h1><i class="fas fa-user-circle"></i> <?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?></h1>
        <div class="emp-id"><i class="fas fa-id-card"></i> <?= htmlspecialchars($user['employee_id']) ?></div>
        <div class="dept"><i class="fas fa-building"></i> <?= htmlspecialchars($user['department']) ?></div>
        
        <div class="instructions">
            <i class="fas fa-info-circle"></i> <strong>Instructions:</strong> Right-click on the QR code and select "Save image as..." to download. Print this QR code and use it for attendance scanning.
        </div>
        
        <div class="qr-container">
            <?php
            // Generate QR code using Google Charts API with better parameters
            $qrData = $user['employee_id'];
            $qrSize = '300x300';
            $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $qrSize . '&data=' . urlencode($qrData);
            ?>
            <img src="<?= $qrUrl ?>" alt="QR Code" id="qr-image" onerror="handleQRError(this)">
            <p style="font-size:12px;color:#6b7280;margin-top:12px;">Scan this code for attendance</p>
        </div>
        
        <a href="<?= $qrUrl ?>" class="btn" download="QR_<?= $user['employee_id'] ?>.png"><i class="fas fa-download"></i> Download QR</a>
        <a href="../employee/dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Go Back</a>
    </div>

    <script>
        function handleQRError(img) {
            console.error('QR image failed to load');
            // Fallback to Google Charts API
            const empId = '<?= $user['employee_id'] ?>';
            img.src = 'https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=' + encodeURIComponent(empId);
            img.onerror = function() {
                // If both fail, show error message
                img.parentElement.innerHTML = '<div style="padding:40px;color:#ef4444;"><i class="fas fa-exclamation-triangle" style="font-size:48px;margin-bottom:16px;"></i><br>Failed to generate QR code<br><small style="color:#6b7280;">Employee ID: ' + empId + '</small></div>';
            };
        }

        function downloadQR() {
            const empId = '<?= $user['employee_id'] ?>';
            const link = document.createElement('a');
            link.href = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' + encodeURIComponent(empId);
            link.download = 'QR_' + empId + '.png';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>
