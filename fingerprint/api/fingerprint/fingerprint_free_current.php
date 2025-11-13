<?php
header("Content-Type: application/json");

// ---------------------------
// CONFIG: get baseURL from request or use default
// ---------------------------
$baseURL = $_REQUEST['base_url'] ?? "http://127.0.0.1:18080";

try {
    // ---------------------------
    // 1️⃣ Call the device free endpoint
    // ---------------------------
    $freeDataRaw = @file_get_contents("$baseURL/api/device/free");
    if ($freeDataRaw === false) {
        throw new Exception("Failed to reach device endpoint: $baseURL/api/device/free");
    }

    $freeData = json_decode($freeDataRaw, true);

    // ---------------------------
    // 2️⃣ Validate response
    // ---------------------------
    if (!isset($freeData["status"]) || $freeData["status"] !== "success") {
        echo json_encode([
            "success" => false,
            "error" => "Failed to free fingerprint template",
            "raw" => $freeData
        ]);
        exit;
    }

    // ---------------------------
    // 3️⃣ Return success
    // ---------------------------
    echo json_encode([
        "success" => true,
        "message" => "Saved fingerprint template cleared from memory.",
        "data" => $freeData
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => "Exception occurred: " . $e->getMessage()
    ]);
}
?>
