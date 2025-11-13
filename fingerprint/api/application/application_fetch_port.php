<?php
header("Content-Type: application/json");

// Relative path to the info file, from this PHP file
$filePath = __DIR__ . "/../../server/crow_server_info.txt"; // adjust ../.. if necessary

// Check if file exists
if (!file_exists($filePath)) {
    echo json_encode([
        "success" => false,
        "message" => "Info file not found at $filePath"
    ]);
    exit;
}

try {
    // Read the content
    $content = trim(file_get_contents($filePath));

    if ($content === "") {
        echo json_encode([
            "success" => false,
            "message" => "Info file is empty"
        ]);
        exit;
    }

    // Return as JSON
    echo json_encode([
        "success" => true,
        "server" => $content
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error reading file: " . $e->getMessage()
    ]);
}
