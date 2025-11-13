<?php
header("Content-Type: application/json");

// ---------------------------
// Get base URL from request or use default
// ---------------------------
$baseURL = $_REQUEST['base_url'] ?? "http://127.0.0.1:18080";

// ---------------------------
// Helper function: call API endpoint
// ---------------------------
function callAPI(string $url) {
    $response = @file_get_contents($url);
    return $response ? json_decode($response, true) : null;
}

// ---------------------------
// Step 1: Free existing template
// ---------------------------
$freeResponse = callAPI("$baseURL/api/device/free");

if (!$freeResponse || ($freeResponse['status'] ?? '') !== 'success') {
    echo json_encode([
        "status"  => "error",
        "message" => "Failed to free existing fingerprint template."
    ]);
    exit;
}

// ---------------------------
// Step 2: Read fingerprint (loop until acquired)
// ---------------------------
$acquired = false;
while (!$acquired) {
    $readResponse = callAPI("$baseURL/api/device/read");
    if ($readResponse && ($readResponse['status'] ?? '') === 'success') {
        $acquired = true;
    } else {
        // wait 1 second before retry
        sleep(1);
    }
}

// ---------------------------
// Step 3: Store fingerprint as BMP
// ---------------------------
$storeResponse = callAPI("$baseURL/api/fingerprint/store?filename=fingerprint.bmp");

if ($storeResponse && ($storeResponse['status'] ?? '') === 'success') {
    echo json_encode([
        "status"  => "success",
        "message" => "Successfully stored fingerprint as BMP.",
        "file"    => $storeResponse['file'] ?? 'fingerprint.bmp'
    ]);
} else {
    echo json_encode([
        "status"  => "error",
        "message" => "Failed to store fingerprint as BMP."
    ]);
}
