<?php
// Returns the last scan JSON to the scanner station and clears it so it's shown only once.
header('Content-Type: application/json');
$path = __DIR__ . '/last_scan.json';
if (!file_exists($path)) {
    echo json_encode(['success' => false]);
    exit;
}
$raw = file_get_contents($path);
if (!$raw) {
    echo json_encode(['success' => false]);
    exit;
}
$data = json_decode($raw, true);
if (!$data) {
    echo json_encode(['success' => false]);
    // attempt to remove if malformed
    @unlink($path);
    exit;
}
// Return and remove so the scanner gets it once
echo json_encode(['success' => true, 'scan' => $data]);
@unlink($path);
exit;
