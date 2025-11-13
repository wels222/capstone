<?php
header("Content-Type: application/json");

// Current PHP script directory
$scriptDir = __DIR__; // e.g., C:\xampp\htdocs\capstone\fingerprint\api\application

// Relative path to the executable
$exePath = realpath($scriptDir . "/../../server/FingerprintReader.exe");

if (!$exePath || !file_exists($exePath)) {
    echo json_encode([
        "success" => false,
        "message" => "Executable not found at: $exePath"
    ]);
    exit;
}

// Determine the exe directory
$exeDir = dirname($exePath);

// Change working directory to exe folder so BMP exports there
chdir($exeDir);

try {
    // Escape path and start exe hidden (no window)
    $cmd = "start \"\" /B " . escapeshellarg($exePath);

    // Run exe without blocking PHP
    pclose(popen($cmd, "r"));

    echo json_encode([
        "success" => true,
        "message" => "Fingerprint server launched (hidden mode)"
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Launch error: " . $e->getMessage()
    ]);
}
