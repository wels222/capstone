<?php
header("Content-Type: application/json");

// ---------------------------
// CONFIG: get baseURL from request or use default
// ---------------------------
$baseURL = $_REQUEST['base_url'] ?? "http://127.0.0.1:18080";

try {
    // ---------------------------
    // 1️⃣ Connect the device
    // ---------------------------
    $connectDataRaw = @file_get_contents("$baseURL/api/device/connect");
    $connectData = json_decode($connectDataRaw, true);

    if (!isset($connectData["status"]) || $connectData["status"] !== "success") {
        echo json_encode([
            "success" => false,
            "error" => "Device connect failed",
            "raw" => $connectData
        ]);
        exit;
    }

    // ---------------------------
    // 2️⃣ Return success
    // ---------------------------
    echo json_encode([
        "success" => true,
        "message" => "Device connected successfully",
        "data" => $connectData
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => "Exception occurred: " . $e->getMessage()
    ]);
}
