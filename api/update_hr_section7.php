<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
session_start();

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$id = $data['id'] ?? null;
$section7 = $data['section7'] ?? null; // free text or structured (prefer structured array/object)
$signatures = $data['signatures'] ?? []; // array of either dataURI strings or {key, data_uri}
$recommendation = $data['recommendation'] ?? null; // 'For approval' or 'For disapproval'
$disapproval_reason1 = $data['disapproval_reason1'] ?? '';
$disapproval_reason2 = $data['disapproval_reason2'] ?? '';
$disapproval_reason3 = $data['disapproval_reason3'] ?? '';
$leave_credits = $data['leave_credits'] ?? null; // Leave credits data from HR

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

  // Mirror recommendation and disapproval reasons into hr.section7 so that
  // Dept Head, HR and Municipal views can read a single canonical place.
  if (!isset($details['hr']['section7']) || !is_array($details['hr']['section7'])) $details['hr']['section7'] = is_array($details['hr']['section7']) ? $details['hr']['section7'] : [];
  if ($recommendation !== null) $details['hr']['section7']['recommendation'] = $recommendation;
  if ($disapproval_reason1 !== null) $details['hr']['section7']['disapproval_reason1'] = $disapproval_reason1;
  if ($disapproval_reason2 !== null) $details['hr']['section7']['disapproval_reason2'] = $disapproval_reason2;
  if ($disapproval_reason3 !== null) $details['hr']['section7']['disapproval_reason3'] = $disapproval_reason3;
  // Also mirror leave credits into hr.section7 for visibility
  if ($leave_credits && is_array($leave_credits)) {
    $details['hr']['section7']['leave_credits'] = $leave_credits;
  }

  $savedPaths = [];
  // Save any provided signature data URIs as files. Support both plain dataURI strings (append)
  // and objects with { key: '7a', data_uri: 'data:image/...' } to map per-officer.
  if (!isset($details['hr']['signatures']) || !is_array($details['hr']['signatures'])) $details['hr']['signatures'] = [];

  // Build a normalized array of incoming signatures keyed by their role when provided
  $incomingSigs = [];
  foreach ($signatures as $idx => $sig) {
    if (is_array($sig) && !empty($sig['data_uri'])) {
      $k = isset($sig['key']) && $sig['key'] !== '' ? (string)$sig['key'] : null;
      $incomingSigs[] = ['key' => $k, 'data_uri' => $sig['data_uri'], 'idx' => $idx];
    } elseif (is_string($sig) && strpos($sig, 'data:image/') === 0) {
      // No explicit key provided; keep as is
      $incomingSigs[] = ['key' => null, 'data_uri' => $sig, 'idx' => $idx];
    }
  }

  // When replacing a specific keyed signature (e.g., '7b' from Dept Head), only replace that key,
  // and preserve other existing keys (prevents wiping 7b when HR uploads certifier, and vice versa).
  foreach ($incomingSigs as $in) {
    $key = $in['key'];
    $dataUri = $in['data_uri'];
    $key = null; $dataUri = null;
    // Already normalized above; keep for backward compatibility in the rest of the logic
    $key = $in['key'];
    $dataUri = $in['data_uri'];
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
      if ($key !== null && $key !== '') {
        // If replacing existing keyed signature, attempt to delete the old file for that key
        if (!empty($details['hr']['signatures'][$key]) && is_string($details['hr']['signatures'][$key])) {
          $old = __DIR__ . '/../' . ltrim($details['hr']['signatures'][$key], '/');
          if (strpos(realpath($old) ?: '', realpath(__DIR__ . '/../uploads/signatures')) === 0 && file_exists($old)) {
            @unlink($old);
          }
        }
        $details['hr']['signatures'][$key] = $relPath;
      } else {
        // Append as an unkeyed signature; do NOT clear existing signatures
        $details['hr']['signatures'][] = $relPath;
      }
      $savedPaths[] = $relPath;
    }
  }
  
  // If no new signature provided, try to use saved HR signature from hr_signatures table
  $hasNewSignature = count($incomingSigs) > 0; // recompute using normalized list
  if (!$hasNewSignature && empty($details['hr']['signatures']['certifier'])) {
    try {
      $hr_email = $_SESSION['email'] ?? null;
      if ($hr_email) {
        $stmt = $pdo->prepare('SELECT file_path FROM hr_signatures WHERE email = ? LIMIT 1');
        $stmt->execute([$hr_email]);
        $savedSigPath = $stmt->fetchColumn();
        if ($savedSigPath) {
          $details['hr']['signatures']['certifier'] = $savedSigPath;
        }
      }
    } catch (PDOException $e) {
      // Non-fatal, continue without saved signature
    }
  }

  $detailsJson = json_encode($details);
  
  // Build UPDATE query dynamically based on available columns
  $updateFields = ['details = ?', 'updated_at = NOW()'];
  $updateParams = [$detailsJson];
  
  // Check if recommendation columns exist in the table
  try {
    $checkColumns = $pdo->query("SHOW COLUMNS FROM leave_requests LIKE 'recommendation'");
    $hasRecommendation = $checkColumns->rowCount() > 0;
  } catch (PDOException $e) {
    $hasRecommendation = false;
  }
  
  if ($hasRecommendation) {
    if ($recommendation !== null) {
      $updateFields[] = 'recommendation = ?';
      $updateParams[] = $recommendation;
    }
    
    if ($disapproval_reason1 !== null) {
      $updateFields[] = 'disapproval_reason1 = ?';
      $updateParams[] = $disapproval_reason1;
    }
    
    if ($disapproval_reason2 !== null) {
      $updateFields[] = 'disapproval_reason2 = ?';
      $updateParams[] = $disapproval_reason2;
    }
    
    if ($disapproval_reason3 !== null) {
      $updateFields[] = 'disapproval_reason3 = ?';
      $updateParams[] = $disapproval_reason3;
    }
    
    // Add leave credits fields if provided
    if ($leave_credits && is_array($leave_credits)) {
      if (!empty($leave_credits['certification_date'])) {
        $updateFields[] = 'certification_date = ?';
        $updateParams[] = $leave_credits['certification_date'];
      }
      if (isset($leave_credits['vl_total_earned']) && $leave_credits['vl_total_earned'] !== '') {
        $updateFields[] = 'vl_total_earned = ?';
        $updateParams[] = $leave_credits['vl_total_earned'];
      }
      if (isset($leave_credits['vl_less_this_application']) && $leave_credits['vl_less_this_application'] !== '') {
        $updateFields[] = 'vl_less_this_application = ?';
        $updateParams[] = $leave_credits['vl_less_this_application'];
      }
      if (isset($leave_credits['vl_balance']) && $leave_credits['vl_balance'] !== '') {
        $updateFields[] = 'vl_balance = ?';
        $updateParams[] = $leave_credits['vl_balance'];
      }
      if (isset($leave_credits['sl_total_earned']) && $leave_credits['sl_total_earned'] !== '') {
        $updateFields[] = 'sl_total_earned = ?';
        $updateParams[] = $leave_credits['sl_total_earned'];
      }
      if (isset($leave_credits['sl_less_this_application']) && $leave_credits['sl_less_this_application'] !== '') {
        $updateFields[] = 'sl_less_this_application = ?';
        $updateParams[] = $leave_credits['sl_less_this_application'];
      }
      if (isset($leave_credits['sl_balance']) && $leave_credits['sl_balance'] !== '') {
        $updateFields[] = 'sl_balance = ?';
        $updateParams[] = $leave_credits['sl_balance'];
      }
    }
  } else {
    // Columns don't exist - store in details JSON as fallback
    if ($recommendation !== null || $disapproval_reason1 || $disapproval_reason2 || $disapproval_reason3 || $leave_credits) {
      if (!isset($details['recommendation_fallback'])) $details['recommendation_fallback'] = [];
      $details['recommendation_fallback']['recommendation'] = $recommendation;
      $details['recommendation_fallback']['disapproval_reason1'] = $disapproval_reason1;
      $details['recommendation_fallback']['disapproval_reason2'] = $disapproval_reason2;
      $details['recommendation_fallback']['disapproval_reason3'] = $disapproval_reason3;
      if ($leave_credits) {
        $details['recommendation_fallback']['leave_credits'] = $leave_credits;
      }
      $detailsJson = json_encode($details);
      $updateParams[0] = $detailsJson; // Update the details param
    }
  }
  
  $updateParams[] = $id;
  
  $sql = 'UPDATE leave_requests SET ' . implode(', ', $updateFields) . ' WHERE id = ?';
  $up = $pdo->prepare($sql);
  $up->execute($updateParams);

  echo json_encode(['success'=>true,'saved'=>$savedPaths,'details'=>$details,'hasRecommendationColumns'=>$hasRecommendation]);
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'error'=>'DB error','details'=>$e->getMessage()]);
}

?>
