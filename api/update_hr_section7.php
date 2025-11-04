<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
session_start();

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$id = $data['id'] ?? null;
$section7 = $data['section7'] ?? null; // free text or structured (prefer structured array/object)
$signatures = $data['signatures'] ?? []; // array of either dataURI strings or {key, data_uri}

if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Missing id']); exit; }

try {
  // fetch existing details JSON
  $st = $pdo->prepare('SELECT details FROM leave_requests WHERE id = ?');
  $st->execute([$id]);
  $existing = $st->fetchColumn();
  $details = [];
  if ($existing) {
    $d = json_decode($existing, true);
    if (is_array($d)) $details = $d;
  }

  // prepare hr sub-object
  if (!isset($details['hr']) || !is_array($details['hr'])) $details['hr'] = [];
  // Merge incoming section7 with existing to avoid wiping unrelated keys (e.g., dept head 7.B)
  if (!isset($details['hr']['section7']) || !is_array($details['hr']['section7'])) $details['hr']['section7'] = [];
  if (is_array($section7)) {
    // shallow merge: incoming keys overwrite existing ones, others are preserved
    $details['hr']['section7'] = array_merge($details['hr']['section7'], $section7);
  } elseif ($section7) {
    // if section7 is a scalar or non-array, overwrite entirely
    $details['hr']['section7'] = $section7;
  }

  $savedPaths = [];
  // Save any provided signature data URIs as files. Support both plain dataURI strings (append)
  // and objects with { key: '7a', data_uri: 'data:image/...' } to map per-officer.
  if (!isset($details['hr']['signatures']) || !is_array($details['hr']['signatures'])) $details['hr']['signatures'] = [];
  foreach ($signatures as $idx => $sig) {
    $key = null; $dataUri = null;
    if (is_string($sig) && strpos($sig, 'data:image/') === 0) {
      $dataUri = $sig;
    } elseif (is_array($sig) && !empty($sig['data_uri'])) {
      $dataUri = $sig['data_uri'];
      if (!empty($sig['key'])) $key = $sig['key'];
    }
    if (!$dataUri || strpos($dataUri, 'data:image/') !== 0) continue;
    $parts = explode(',', $dataUri, 2);
    if (count($parts) !== 2) continue;
    $meta = $parts[0]; $b64 = $parts[1];
    $ext = 'png';
    if (preg_match('#data:image/(\w+);base64#i', $meta, $m)) { $ext = strtolower($m[1]); }
    $bin = base64_decode($b64);
    if ($bin === false) continue;
    $ext = $ext === 'jpeg' ? 'jpg' : $ext;
    $allowed = ['png','jpg','jpeg','gif','webp'];
    if (!in_array($ext, $allowed)) continue;

    $hash = sha1(uniqid((string)$id . '-' . $idx, true));
    $fileName = 'hrsig_' . substr($hash,0,24) . '.' . $ext;
    $relPath = 'uploads/signatures/' . $fileName;
    $absPath = __DIR__ . '/../' . $relPath;
    if (!is_dir(dirname($absPath))) @mkdir(dirname($absPath), 0777, true);
    if (@file_put_contents($absPath, $bin) !== false) {
      if ($key) {
        // map by key
        $details['hr']['signatures'][$key] = $relPath;
      } else {
        // append
        $details['hr']['signatures'][] = $relPath;
      }
      $savedPaths[] = $relPath;
    }
  }

  $detailsJson = json_encode($details);
  $up = $pdo->prepare('UPDATE leave_requests SET details = ? , updated_at = NOW() WHERE id = ?');
  $up->execute([$detailsJson, $id]);

  echo json_encode(['success'=>true,'saved'=>$savedPaths,'details'=>$details]);
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'error'=>'DB error','details'=>$e->getMessage()]);
}

?>
