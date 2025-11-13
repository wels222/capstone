<?php
header("Content-Type: application/json");

// Name of the executable (exact filename)
$exeName = "FingerprintReader.exe";

try {
    // Escape the name to avoid injection issues
    $exeEsc = escapeshellarg($exeName);

    // Windows command to kill process by image name
    // /F = force kill
    // /IM = by executable name
    // 2>&1 captures STDERR
    $command = "taskkill /F /IM $exeEsc 2>&1";

    // Execute
    exec($command, $output, $returnVar);

    // Check results
    if ($returnVar === 0) {
        echo json_encode([
            "success" => true,
            "message" => "$exeName terminated successfully",
            "output" => $output
        ]);
    } else {
        // Sometimes returnVar = 128 → process not found
        $notRunning = false;

        foreach ($output as $line) {
            if (strpos($line, "not found") !== false ||
                strpos($line, "No tasks are running") !== false ||
                strpos($line, "ERROR: The process") !== false) {
                $notRunning = true;
                break;
            }
        }

        echo json_encode([
            "success" => false,
            "message" => $notRunning
                ? "$exeName is not running"
                : "Failed to terminate $exeName",
            "output" => $output
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Exception while killing process: " . $e->getMessage()
    ]);
}
