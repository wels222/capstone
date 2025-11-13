<?php
header("Content-Type: text/html");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QR + Fingerprint Attendance</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 10px;
        overflow: hidden;
        position: relative;
    }
    body::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-image: url('../../../assets/mabinibg.jpg');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        opacity: 0.15;
        z-index: 0;
    }
    .container {
        display: flex;
        gap: 20px;
        max-width: 100%;
        width: 100%;
        height: calc(100vh - 20px);
        margin: 0 auto;
        position: relative;
        z-index: 1;
    }
    .left {
        flex: 2;
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        padding: 24px;
        display: flex;
        flex-direction: column;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.5) inset;
        overflow: hidden;
        transition: transform 0.3s ease;
    }
    .right {
        flex: 1;
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        padding: 24px;
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.5) inset;
        overflow-y: auto;
        max-height: calc(100vh - 20px);
        transition: transform 0.3s ease;
    }
    h2 { 
        color: #1f2937; 
        margin-bottom: 12px; 
        font-size: 20px; 
        font-weight: 600; 
        flex-shrink: 0;
        letter-spacing: -0.3px;
    }
    .header-logo {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 2px solid rgba(0, 0, 0, 0.06);
        background: linear-gradient(to right, rgba(102, 126, 234, 0.05), transparent);
        padding: 12px;
        border-radius: 12px;
        margin: -12px -12px 20px -12px;
    }
    .header-logo img { 
        width: 52px; 
        height: 52px; 
        object-fit: contain;
        filter: drop-shadow(0 2px 8px rgba(102, 126, 234, 0.3));
    }
    .header-logo .logo-text { display: flex; flex-direction: column; }
    .header-logo .logo-text .main-title { 
        font-size: 19px; 
        font-weight: 700; 
        color: #1f2937; 
        line-height: 1.2;
        letter-spacing: -0.4px;
    }
    .header-logo .logo-text .sub-title { 
        font-size: 12px; 
        color: #6b7280; 
        margin-top: 5px;
        font-weight: 500;
        letter-spacing: 0.2px;
    }
    .status-indicator { 
        width: 10px; 
        height: 10px; 
        border-radius: 50%; 
        background: #10b981; 
        display: inline-block; 
        margin-right: 10px;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
        animation: pulse 2s infinite; 
    }
    @keyframes pulse { 
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.7; transform: scale(1.1); }
    }
    .clock { 
        font-size: 28px; 
        font-weight: 700; 
        background: linear-gradient(135deg, #667eea, #764ba2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 20px; 
        text-align: center; 
        flex-shrink: 0;
        letter-spacing: -0.5px;
    }
    .info-card { 
        width: 100%; 
        text-align: center; 
        padding: 24px; 
        border-radius: 16px; 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
        color: #fff; 
        box-shadow: 0 12px 32px rgba(102, 126, 234, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.1) inset;
        animation: slideIn 0.5s ease;
        transition: transform 0.3s ease;
    }
    .info-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 16px 40px rgba(102, 126, 234, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.1) inset;
    }
    .info-pic { 
        width: 100px; 
        height: 100px; 
        border-radius: 50%; 
        object-fit: cover; 
        margin: 0 auto 12px; 
        border: 4px solid rgba(255, 255, 255, 0.9); 
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
    }
    .name { font-size: 20px; font-weight: 700; margin-bottom: 6px; }
    .emp-id { font-size: 14px; opacity: 0.9; margin-bottom: 4px; }
    .dept { font-size: 13px; opacity: 0.8; margin-bottom: 12px; }
    .time { font-size: 18px; font-weight: 600; background: rgba(255,255,255,0.2); padding: 10px; border-radius: 8px; margin-bottom: 10px; display:flex; align-items:center; justify-content:center; gap:8px; }
    .status { font-size: 16px; font-weight: 700; padding: 6px 14px; border-radius: 8px; background: rgba(255,255,255,0.3); display:inline-block; }
    .idle { color: #6b7280; text-align: center; margin-top: 30px; font-size: 15px; }
    .btn { 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
        color: #fff; 
        border: none; 
        padding: 10px 20px; 
        font-size: 14px; 
        font-weight: 600; 
        border-radius: 10px; 
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        transition: all 0.3s ease;
        letter-spacing: 0.3px;
    }
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }
    .btn:active {
        transform: translateY(0);
    }
    .btn.small { padding: 7px 14px; font-size: 13px; }
    .qr-wrap { 
        background: linear-gradient(145deg, #ffffff, #f8f9fa);
        padding: 16px; 
        border-radius: 16px; 
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12), 0 0 0 1px rgba(0, 0, 0, 0.05) inset;
        width: 100%;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .qr-wrap:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.16), 0 0 0 1px rgba(0, 0, 0, 0.05) inset;
    }
    .scanner-box { 
        margin-top: 0; 
        background: linear-gradient(145deg, #f8f9fa, #ffffff);
        border: 2px dashed rgba(102, 126, 234, 0.25);
        border-radius: 16px; 
        padding: 16px;
        box-shadow: 0 4px 20px rgba(102, 126, 234, 0.08);
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }
    .scanner-box:hover {
        border-color: rgba(102, 126, 234, 0.4);
        box-shadow: 0 6px 28px rgba(102, 126, 234, 0.12);
    }
    .reader-container { 
        width: 100%; 
        height: clamp(200px, 28vh, 340px); 
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.08), rgba(118, 75, 162, 0.08));
        display: flex; 
        align-items: center; 
        justify-content: center; 
        border-radius: 12px; 
        color: #374151; 
        font-weight: 600; 
        font-size: 15px;
        letter-spacing: 0.3px;
        border: 2px solid rgba(102, 126, 234, 0.15);
        box-shadow: 0 4px 16px rgba(102, 126, 234, 0.1) inset;
        transition: all 0.3s ease;
    }
    #title-log-container { 
        margin-top: 12px; 
        text-align: center; 
        color: #4b5563; 
        font-size: 14px; 
        font-weight: 500;
        letter-spacing: 0.2px;
        padding: 8px;
        background: rgba(102, 126, 234, 0.05);
        border-radius: 8px;
        transition: background 0.3s ease;
    }
    @media (max-width: 768px) { body{padding:5px;} .container{flex-direction:column;height:auto;gap:10px;} .left,.right{padding:15px;} h2{font-size:18px;} }
</style>
</head>
<body>
    <div class="container">
        <div class="left">
            <div class="header-logo">
                <img src="../../../assets/logo.png" alt="Mabini Logo">
                <div class="logo-text">
                    <div class="main-title">Mabini Municipal Office</div>
                    <div class="sub-title">QR + Fingerprint Attendance</div>
                </div>
            </div>

            <div class="scanner-box">
                <h2><span class="status-indicator"></span>Fingerprint Scanner</h2>
                <div id="reader-container" class="reader-container">Place finger on the scanner</div>
                <div id="title-log-container" style="margin-top:8px; color:#4b5563;"></div>
            </div>

            <h2 style="margin-top:10px;"><span class="status-indicator"></span>QR Attendance</h2>
            <div class="qr-panel" aria-live="polite" style="max-height: 26vh; overflow: visible;">
                <div class="qr-card" style="display:flex;flex-direction:column;align-items:center;gap:6px;">
                    <div class="qr-wrap">
                        <img id="rotating-qr-img" src="" alt="Rotating QR code for attendance" class="qr-large" style="width:100%;height:18vh;max-height:18vh;aspect-ratio:1/1;display:block;object-fit:contain;border-radius:6px;"/>
                    </div>
                    <div style="width:100%;max-width:520px;display:flex;gap:6px;align-items:center;">
                        <a id="rotating-qr-link" href="#" target="_blank" rel="noopener noreferrer" class="link-url" style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;padding:6px 8px;background:rgba(255,255,255,0.85);border-radius:8px;color:#374151;text-decoration:none;border:1px solid rgba(15,23,42,0.06);font-size:12px;">Open attendance link</a>
                        <button id="copyLinkBtn" class="btn small" style="padding:6px 10px;font-size:12px;">Copy</button>
                    </div>
                    <div id="qr-meta" class="qr-meta" style="font-size:11px;color:rgba(0,0,0,0.7);width:100%;max-width:520px;text-align:center;">
                        <div style="opacity:0.95">Next rotation in <strong id="rot-count">60</strong>s</div>
                        <div id="qr-updated" style="opacity:0.85;font-size:11px;margin-top:4px;">Last updated: —</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="right">
            <div class="clock" id="clock"></div>
            <div class="info-card" id="info-display" style="margin-top:20px;text-align:center;display:none;position:relative;">
                <img id="info-pic" class="info-pic" src="../../../assets/logo.png" alt="Employee Photo">
                <div class="name" id="info-name">—</div>
                <div class="emp-id" id="info-empid">Employee ID: —</div>
                <div class="dept" id="info-dept">—</div>
                <div class="time" id="info-time">
                    <i class="fas fa-clock" style="color:rgba(255,255,255,0.9);"></i>
                    <span id="info-time-value">—</span>
                </div>
                <div class="status" id="info-status">—</div>
            </div>
            <div class="idle" id="idle-message">Waiting for attendance scan...</div>
        </div>
    </div>

<script type="module">
import { TitleLog } from './TitleLog.js';
import { ScannerReader } from './ScannerReader.js';

// ==== Clock ====
function updateClock(){
    const d = new Date();
    const opts = { hour:'2-digit', minute:'2-digit', second:'2-digit', hour12:true };
    const time = d.toLocaleTimeString('en-US', opts);
    const date = d.toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
    document.getElementById('clock').innerHTML = time + '<br><span style="font-size:14px;font-weight:400;color:#6b7280">' + date + '</span>';
}
setInterval(updateClock, 1000);
updateClock();

// ==== Rotating QR (copied from attendance/scan.html, paths adjusted) ====
let rotInterval = null;
let countdownInterval = null;
let serverTimeOffset = 0;

async function fetchRotatingQR() {
    try {
        const res = await fetch('../../../attendance/generate_qr.php');
        const data = await res.json();
        const url = data.url;
        if (data.serverTime) {
            const clientTime = Math.floor(Date.now() / 1000);
            serverTimeOffset = data.serverTime - clientTime;
        }
        const img = document.getElementById('rotating-qr-img');
        const link = document.getElementById('rotating-qr-link');
        // Compute best-fit QR size based on container width (keeps image sharp)
        const container = img.parentElement;
        const containerWidth = container ? Math.min(container.clientWidth, window.innerWidth) : 320;
        // Keep inside 18vh to match visual cap, convert vh to px
        const vhCap = Math.floor(window.innerHeight * 0.18);
        const target = Math.min(containerWidth, vhCap);
        const size = Math.max(220, Math.min(600, target));
        const qrApi = 'https://api.qrserver.com/v1/create-qr-code/?size=' + size + 'x' + size + '&data=' + encodeURIComponent(url);
        img.src = qrApi;
        link.href = url;
        link.textContent = url;
        document.getElementById('qr-updated').textContent = 'Last updated: ' + new Date().toLocaleTimeString();
    } catch (err) { console.error('Failed to load rotating QR', err); }
}

function startRotation() {
    fetchRotatingQR();
    function schedule() {
        const now = Date.now() + (serverTimeOffset * 1000);
        const msToNextMinute = 60000 - (now % 60000);
        let secondsLeft = Math.ceil(msToNextMinute / 1000);
        const counterEl = document.getElementById('rot-count');
        if (countdownInterval) clearInterval(countdownInterval);
        counterEl.textContent = secondsLeft;
        countdownInterval = setInterval(() => { secondsLeft--; if (secondsLeft < 0) secondsLeft = 0; counterEl.textContent = secondsLeft; }, 1000);
        setTimeout(() => { fetchRotatingQR(); if (rotInterval) clearInterval(rotInterval); rotInterval = setInterval(fetchRotatingQR, 60000); schedule(); }, msToNextMinute + 200);
    }
    schedule();
}

document.getElementById('copyLinkBtn').addEventListener('click', async () => {
    const link = document.getElementById('rotating-qr-link').href;
    try { await navigator.clipboard.writeText(link); alert('Link copied to clipboard'); } 
    catch (e) { prompt('Copy this link', link); }
});

// ==== Poll last scan and show info (copied from scan.html) ====
async function pollLastScan() {
    try {
        const r = await fetch('../../../attendance/last_scan.php', { cache: 'no-store' });
        if (!r.ok) return;
        const js = await r.json();
        if (js && js.success && js.scan) {
            const s = js.scan;
            const name = s.name || (s.employee_id ? s.employee_id : 'Employee');
            let actionLabel = '';
            if (s.action === 'time_in') actionLabel = 'Time In';
            else if (s.action === 'time_out') actionLabel = 'Time Out';
            else if (s.action === 'already_timedout') actionLabel = 'Time Out (Already Recorded)';
            else actionLabel = 'Attendance';
            const status = s.status || s.time_in_status || '';
            const time = s.time || '';
            const employeeId = s.employee_id || '—';
            const profilePicture = s.profile_picture || '';
            showEmployeeInfo({ name:name, action:actionLabel, status:status, time:time, employeeId:employeeId, photo:profilePicture });
        }
    } catch (e) { /* ignore */ }
}

function showEmployeeInfo(data) {
    const infoDisplay = document.getElementById('info-display');
    const idleMsg = document.getElementById('idle-message');
    const pic = document.getElementById('info-pic');
    const nameEl = document.getElementById('info-name');
    const empIdEl = document.getElementById('info-empid');
    const deptEl = document.getElementById('info-dept');
    const timeEl = document.getElementById('info-time-value');
    const statusEl = document.getElementById('info-status');

    nameEl.textContent = data.name || '—';
    empIdEl.textContent = 'Employee ID: ' + (data.employeeId || '—');
    deptEl.textContent = data.action || 'Attendance';
    pic.src = data.photo ? data.photo : '../../../assets/logo.png';
    if (data.time) {
        const t = new Date(data.time.replace(' ', 'T'));
        timeEl.textContent = t.toLocaleTimeString('en-US', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
    } else { timeEl.textContent = '—'; }

    const status = data.status || '—';
    statusEl.textContent = status;
    statusEl.className = 'status';
    const s = status.toLowerCase();
    if (s.includes('present')) { statusEl.style.background = '#10b981'; statusEl.style.color = '#fff'; statusEl.innerHTML = '<i class="fas fa-check-circle" style="margin-right:6px;"></i>' + status; }
    else if (s.includes('late')) { statusEl.style.background = '#f59e0b'; statusEl.style.color = '#fff'; statusEl.innerHTML = '<i class="fas fa-clock" style="margin-right:6px;"></i>' + status; }
    else if (s.includes('absent')) { statusEl.style.background = '#ef4444'; statusEl.style.color = '#fff'; statusEl.innerHTML = '<i class="fas fa-times-circle" style="margin-right:6px;"></i>' + status; }
    else if (s.includes('on-time') || s.includes('ontime') || s === 'out' || s.includes(' out')) { statusEl.style.background = '#14b8a6'; statusEl.style.color = '#fff'; statusEl.innerHTML = '<i class="fas fa-user-check" style="margin-right:6px;"></i>' + status; }
    else if (s.includes('undertime')) { statusEl.style.background = '#fb923c'; statusEl.style.color = '#fff'; statusEl.innerHTML = '<i class="fas fa-hourglass-half" style="margin-right:6px;"></i>' + status; }
    else if (s.includes('unregistered')) { statusEl.style.background = '#ef4444'; statusEl.style.color = '#fff'; statusEl.innerHTML = '<i class="fas fa-user-slash" style="margin-right:6px;"></i>' + status; }
    else if (s.includes('overtime')) { statusEl.style.background = '#3b82f6'; statusEl.style.color = '#fff'; statusEl.innerHTML = '<i class="fas fa-clock" style="margin-right:6px;"></i>' + status; }
    else { statusEl.style.background = 'rgba(255,255,255,0.3)'; statusEl.style.color = '#fff'; statusEl.textContent = status; }

    idleMsg.style.display = 'none';
    infoDisplay.style.display = 'block';
    infoDisplay.style.opacity = '1';
    infoDisplay.style.transform = 'scale(1)';
    setTimeout(() => { infoDisplay.style.display = 'none'; idleMsg.style.display = 'block'; pic.src='../../../assets/logo.png'; }, 3000);
}

pollLastScan();
setInterval(pollLastScan, 2000);
startRotation();

// ==== Fingerprint logic (auto time in/out using QR backend) ====
const titleLogContainer = document.getElementById('title-log-container');
const titleLog = TitleLog('Initializing...');
titleLogContainer.innerHTML = '';
titleLogContainer.appendChild(titleLog);
ScannerReader('reader-container', 'disconnected');

function setTitleLog(msg){ titleLog.textContent = msg; }

async function callAPI(url, postData = null) {
    try {
        const response = await fetch(url, {
            method: postData ? 'POST' : 'GET',
            headers: { 'Content-Type': 'application/json' },
            body: postData ? JSON.stringify(postData) : null
        });
        return await response.json();
    } catch (err) { return { success:false, message:'Fetch error: '+err.message }; }
}

function sleep(ms){ return new Promise(r=>setTimeout(r,ms)); }

async function waitForValidID(baseUrl){
    while(true){
        const idData = await callAPI(`http://localhost/capstone/fingerprint/api/fingerprint/fingerprint_identify_user.php?base_url=${encodeURIComponent(baseUrl)}`);
        const ident = idData.identification;
        if (!ident){ setTitleLog('No fingerprint detected'); ScannerReader('reader-container','idle'); await sleep(500); setTitleLog('Identifying user...'); continue; }
        if (ident.status === 'success' && ident.id > 0){ ScannerReader('reader-container','activated'); setTitleLog('Read Successful'); return idData; }
        if (ident.status === 'error'){
            setTitleLog('Unregistered Fingerprint');
            // Show status on the right info card
            try {
                const now = new Date();
                const iso = now.toISOString();
                showEmployeeInfo({
                    name: 'Unknown',
                    action: 'Fingerprint',
                    status: 'Unregistered account',
                    time: iso,
                    employeeId: '—',
                    photo: ''
                });
            } catch (e) { /* ignore UI errors */ }
            ScannerReader('reader-container','idle');
            await sleep(2000);
            setTitleLog('Identifying user...');
            await sleep(500);
            continue;
        }
        setTitleLog('No fingerprint detected'); ScannerReader('reader-container','idle'); await sleep(500); setTitleLog('Identifying user...');
    }
}

async function handleFingerprintScan(baseUrl){
    const idData = await waitForValidID(baseUrl);
    const fingerprintId = idData.identification.id;
    if (fingerprintId > 0){
        const employeeIDResult = await callAPI(`http://localhost/capstone/fingerprint/services/reader_identify_user.php?id=${fingerprintId}`);
        const employeeIdString = employeeIDResult.employee_id;
        if (employeeIdString){
            // Auto record attendance via unified QR backend logic
            setTitleLog('Recording attendance...');
            const rec = await callAPI('../../../attendance/fingerprint_record.php', { employee_id: employeeIdString });
            if (rec && rec.success){
                const act = rec.action === 'time_in' ? 'Time In' : (rec.action === 'time_out' ? 'Time Out' : 'Attendance');
                setTitleLog(`${act} recorded (${rec.status || ''})`);
            } else {
                setTitleLog('Failed to record. ' + (rec && rec.message ? rec.message : ''));
            }
            // Give the right panel time to poll and show result
            await sleep(1500);
        } else {
            setTitleLog('No employee_id mapped to fingerprint');
            await sleep(1000);
        }
    }
    ScannerReader('reader-container','idle');
    setTitleLog('Identifying user...');
    handleFingerprintScan(baseUrl);
}

async function initializeScanner(){
    setTitleLog('Stopping old server...');
    const stopData = await callAPI('http://localhost/capstone/fingerprint/api/application/application_close_server.php');
    if (!stopData.success && !(stopData.message || '').includes('not running')) { setTitleLog('Failed to stop server'); }
    else setTitleLog('Old server closed or not running.');

    setTitleLog('Starting server...');
    const startData = await callAPI('http://localhost/capstone/fingerprint/api/application/application_start_server.php');
    if (!startData.success){ setTitleLog('Failed to start server: ' + (startData.message || 'Unknown')); return; }
    setTitleLog('Server started successfully.');

    setTitleLog('Fetching server port...');
    const portData = await callAPI('http://localhost/capstone/fingerprint/api/application/application_fetch_port.php');
    if (!portData.success){ setTitleLog('Failed to fetch server port.'); return; }
    const [host, port] = portData.server.split(':');
    const baseUrl = `http://${host}:${port}`;
    setTitleLog('Base URL: ' + baseUrl);

    setTitleLog('Connecting device...');
    await sleep(500);
    const connectData = await callAPI('http://localhost/capstone/fingerprint/api/fingerprint/fingerprint_connect_device.php', { base_url: baseUrl });
    if (!connectData.success){ setTitleLog('Device connection failed'); return; }
    setTitleLog('Device connected.');
    ScannerReader('reader-container','idle');

    setTitleLog('Loading fingerprint templates...');
    await sleep(500);
    const fetchData = await callAPI('http://localhost/capstone/fingerprint/api/fingerprint/fingerprint_fetch_templates.php', { base_url: baseUrl });
    if (!fetchData.success){ setTitleLog('Failed to load fingerprints'); return; }
    setTitleLog('Fingerprints loaded.');
    ScannerReader('reader-container','idle');

    setTitleLog('Identifying user...');
    handleFingerprintScan(baseUrl);
}

window.addEventListener('DOMContentLoaded', initializeScanner);
</script>

</body>
</html>
