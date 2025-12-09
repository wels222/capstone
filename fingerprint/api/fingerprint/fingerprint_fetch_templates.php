<?php
header("Content-Type: application/json");

// ---------------------------
// CONFIG: get baseURL from request or use default
// ---------------------------
$baseURL = $_REQUEST['base_url'] ?? "http://127.0.0.1:18080";

try {
    // ---------------------------
    // 1. Free device memory before loading templates
    // ---------------------------
    $freeDataRaw = @file_get_contents("$baseURL/api/fingerprint/free");
    $freeData = json_decode($freeDataRaw, true);

    if (!isset($freeData["status"]) || $freeData["status"] !== "success") {
        echo json_encode([
            "error" => "Failed to free device memory",
            "raw" => $freeData
        ]);
        exit;
    }

    // ---------------------------
    // 2. Fetch fingerprints from DB
    // ---------------------------
    $pdo = new PDO(
        "mysql:host=127.0.0.1;dbname=capstone;charset=utf8mb4",
        "root",
        "",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    $stmt = $pdo->query("SELECT id, template FROM fingerprints");

    // ---------------------------
    // 3. Convert each fingerprint BLOB to base64 BMP
    // ---------------------------
    $payload = [];
    foreach ($stmt as $row) {
        if (!empty($row["template"])) {
            $bmpData = $row["template"]; // raw BLOB
            $base64BMP = base64_encode($bmpData);
            $payload[(int)$row["id"]] = $base64BMP;
        }
    }

    // === DEBUG: Save payload locally ===
    file_put_contents(__DIR__ . "/debug_payload.json", json_encode($payload, JSON_PRETTY_PRINT));

    // ---------------------------
    // 4. Send all fingerprints via POST to Crow server
    // ---------------------------
    $ch = curl_init("$baseURL/api/fingerprint/fetch");
    curl_setopt($ch, CURLOPT_POST, 1);
    $jsonPayload = json_encode($payload);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);

    // === DEBUG: Capture raw cURL response ===
    $fetchResponse = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Save response for debugging
    file_put_contents(__DIR__ . "/debug_response.json", $fetchResponse);

    if ($curlError) {
        echo json_encode([
            "error" => "cURL error sending fingerprints",
            "curl_error" => $curlError
        ]);
        exit;
    }

    // ---------------------------
    // 5. Decode Crow response
    // ---------------------------
    $fetchData = json_decode($fetchResponse, true);
    if (!isset($fetchData["status"]) || $fetchData["status"] !== "success") {
        echo json_encode([
            "error" => "Failed to load fingerprints",
            "raw_response" => $fetchResponse,
            "decoded_response" => $fetchData
        ]);
        exit;
    }

    // ---------------------------
    // 6. Return success
    // ---------------------------
    echo json_encode([
        "success" => true,
        "message" => "Fingerprints fetched and loaded successfully",
        "data" => $fetchData
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => "Exception occurred: " . $e->getMessage()
    ]);
}
