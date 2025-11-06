<?php
// Compatibility redirect: if a QR or external scanner opens attendance/index.php?qr=...,
// forward it to the main site's index.php so the existing login+qr flow handles it.

// Get the raw token
$qr = $_GET['qr'] ?? null;
if ($qr) {
    // Redirect up one level to main index.php preserving the token
    // Use a relative redirect which will resolve to /capstone/index.php when this file is under /capstone/attendance/
    $target = '../index.php?qr=' . urlencode($qr);
    header('Location: ' . $target);
    exit;
}

// If opened without qr param, show a simple helpful message
?><!doctype html>
<html>
<head><meta charset="utf-8"><title>Attendance QR Redirect</title></head>
<body style="font-family:Arial,Helvetica,sans-serif;max-width:700px;margin:40px auto;color:#333;">
<h1>Attendance QR Handler</h1>
<p>This endpoint is used for backward compatibility. If you scanned a QR that points to <code>/attendance/index.php?qr=...</code>, you will be redirected to the main login page to complete attendance.</p>
<p>To use, open the QR and log in on the main site when prompted.</p>
</body>
</html>
