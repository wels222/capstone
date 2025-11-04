<?php
session_start();
require_once __DIR__ . '/../db.php';

// Basic user info if logged in
$user_email = $_SESSION['email'] ?? '';
$user = null;
if ($user_email) {
  try {
    $st = $pdo->prepare('SELECT firstname, lastname, mi, department, position FROM users WHERE email = ?');
    $st->execute([$user_email]);
    $user = $st->fetch(PDO::FETCH_ASSOC);
  } catch (Exception $e) {}
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>HR — Civil Form (E-Sign)</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body { font-family: Inter, Arial, sans-serif; background: #f3f4f6; }
    .form-box { max-width: 900px; margin: 2rem auto; background: #fff; padding: 1rem; border-radius: 8px; box-shadow: 0 6px 18px rgba(0,0,0,0.06); }
    canvas { border: 1px solid #ddd; border-radius: 4px; background: #fff; }
  </style>
</head>
<body>
  <div class="form-box">
    <h1 class="text-xl font-bold mb-4">CS Form No.6 — HR Copy (E-Sign)</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
      <div>
        <label class="block text-sm font-semibold">Office / Department</label>
        <input type="text" id="office" class="mt-1 block w-full border rounded px-2 py-1" value="<?= htmlspecialchars($user['department'] ?? '') ?>" />
      </div>
      <div>
        <label class="block text-sm font-semibold">Name (Last, First, Middle)</label>
        <input type="text" id="name" class="mt-1 block w-full border rounded px-2 py-1" value="<?= htmlspecialchars(($user['lastname'] ?? '') . ', ' . ($user['firstname'] ?? '') . ' ' . ($user['mi'] ?? '')) ?>" />
      </div>
    </div>

    <hr class="my-4" />

    <h2 class="font-semibold mb-2">Signature (E-Sign)</h2>
    <p class="text-sm text-gray-600 mb-3">You can draw your signature, clear and redraw, or upload an image file. Click "Save Signature" to persist it to the server for reuse.</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <canvas id="sigCanvas" width="600" height="180"></canvas>
        <div class="mt-2 flex gap-2">
          <button id="clearBtn" class="px-3 py-1 bg-gray-200 rounded">Clear</button>
          <button id="saveBtn" class="px-3 py-1 bg-blue-600 text-white rounded">Save Signature</button>
        </div>
        <p id="saveStatus" class="text-sm mt-2"></p>
      </div>

      <div>
        <label class="block text-sm font-medium">Or upload a signature image</label>
        <input id="fileInput" type="file" accept="image/*" class="mt-2" />
        <p class="text-xs text-gray-500 mt-2">If you upload, the uploaded image will be saved as your official e-signature.</p>

        <div class="mt-4">
          <p class="text-sm font-semibold">Existing saved signature</p>
          <div id="existingSig" class="mt-2"></div>
        </div>
      </div>
    </div>

    <div class="mt-6">
      <button id="useExistingBtn" class="px-3 py-1 bg-green-600 text-white rounded">Use Saved Signature</button>
    </div>

  </div>

  <script>
    // Basic drawing on canvas
    const canvas = document.getElementById('sigCanvas');
    const ctx = canvas.getContext('2d');
    let drawing = false;
    let last = { x: 0, y: 0 };

    function pointerPos(e) {
      const rect = canvas.getBoundingClientRect();
      const clientX = e.touches ? e.touches[0].clientX : e.clientX;
      const clientY = e.touches ? e.touches[0].clientY : e.clientY;
      return { x: clientX - rect.left, y: clientY - rect.top };
    }

    function start(e) { drawing = true; last = pointerPos(e); }
    function end(e) { drawing = false; }
    function draw(e) {
      if (!drawing) return;
      const p = pointerPos(e);
      ctx.strokeStyle = '#000'; ctx.lineWidth = 2; ctx.lineCap = 'round';
      ctx.beginPath(); ctx.moveTo(last.x, last.y); ctx.lineTo(p.x, p.y); ctx.stroke();
      last = p;
    }

    canvas.addEventListener('mousedown', start); canvas.addEventListener('touchstart', start);
    canvas.addEventListener('mouseup', end); canvas.addEventListener('mouseleave', end); canvas.addEventListener('touchend', end);
    canvas.addEventListener('mousemove', draw); canvas.addEventListener('touchmove', function(e){ e.preventDefault(); draw(e); }, { passive:false });

    document.getElementById('clearBtn').addEventListener('click', function(){ ctx.clearRect(0,0,canvas.width,canvas.height); });

    // Load existing signature if any
    async function loadExisting() {
      try {
        const r = await fetch('/capstone/api/employee_signature.php');
        const j = await r.json();
        if (j && j.success && j.hasSignature && j.url) {
          const div = document.getElementById('existingSig');
          div.innerHTML = `<img src="${j.url}" alt="Saved signature" style="max-height:80px; border:1px solid #e5e7eb; padding:4px;" />`;
        }
      } catch (e) { /* ignore */ }
    }
    loadExisting();

    // When user clicks Save: prefer uploaded file; otherwise use canvas dataURL
    document.getElementById('saveBtn').addEventListener('click', async function(){
      const status = document.getElementById('saveStatus');
      const fileInput = document.getElementById('fileInput');
      let dataUri = null;
      if (fileInput && fileInput.files && fileInput.files[0]) {
        // convert file to data URI
        const f = fileInput.files[0];
        dataUri = await new Promise((res, rej) => {
          const r = new FileReader(); r.onload = () => res(r.result); r.onerror = () => rej(); r.readAsDataURL(f);
        });
      } else {
        // canvas
        // if canvas empty (all pixels transparent), warn
        const blank = document.createElement('canvas'); blank.width = canvas.width; blank.height = canvas.height;
        if (canvas.toDataURL() === blank.toDataURL()) {
          if (!confirm('Canvas is blank — do you still want to save an empty signature?')) return;
        }
        dataUri = canvas.toDataURL('image/png');
      }

      if (!dataUri) { alert('No signature data available'); return; }

      status.textContent = 'Saving...';
      try {
        const resp = await fetch('/capstone/api/save_signature.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ signature_data_uri: dataUri })
        });
        const js = await resp.json();
        if (js && js.success) {
          status.textContent = 'Saved — signature updated.';
          loadExisting();
        } else {
          status.textContent = 'Failed to save: ' + (js.error || 'unknown');
        }
      } catch (e) {
        status.textContent = 'Save failed (network)';
      }
    });

    // Use saved signature: copy to clipboard as URL or open in new tab
    document.getElementById('useExistingBtn').addEventListener('click', async function(){
      try {
        const r = await fetch('/capstone/api/employee_signature.php');
        const j = await r.json();
        if (j && j.success && j.hasSignature && j.url) {
          const ok = confirm('Open saved signature in a new tab?');
          if (ok) window.open(j.url, '_blank');
        } else {
          alert('No saved signature found on the server.');
        }
      } catch (e) { alert('Failed to check saved signature.'); }
    });
  </script>
</body>
</html>
