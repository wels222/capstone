<?php
header("Content-Type: application/json");

// ---------------------------
// Get base URL from request or use default
// ---------------------------
$baseURL = $_REQUEST['base_url'] ?? "http://127.0.0.1:18080";

// ---------------------------
// Fetch fingerprint ID
// ---------------------------
$idData = @file_get_contents("$baseURL/api/fingerprint/id");
$idData = json_decode($idData, true);

// ---------------------------
// Optional: set payload if you have it already
// ---------------------------
$payload = $idData['payload'] ?? []; // fallback to empty array

// ---------------------------
// Return final JSON
// ---------------------------
echo json_encode([
    "templates_loaded"   => "All fingerprints sent successfully (base64)",
    "identification"     => $idData,
    "fingerprint_count"  => count($payload)
]);
