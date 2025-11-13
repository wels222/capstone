<?php
header("Content-Type: text/html");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Fingerprint Scanner</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
<style>
body {
    font-family: 'Poppins', sans-serif;
    background-image: url('../../../assets/mabinibg.jpg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    background-attachment: fixed;
    backdrop-filter: blur(5px);
    background-color: rgba(255, 255, 255, 0.3);
    min-height: 100vh;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
}

.main-card {
    width: 72vw;              /* preferred width */ /* minimum width */       /* minimum width */
    height: 60vh; 
    min-width: 900px;             /* preferred height */
    min-height: 370px;  
    background-color: white;
    box-shadow: 0 8px 24px rgba(0,0,0,0.2);
    display: flex;
    flex-direction: column;
    padding: 10px;
    border-radius: 12px;

       flex: 0 0 auto;         /* ensure it participates in flex layout */
    position: relative;
}

.main-card .title {
    font-family: 'Poppins', sans-serif; /* inherit bold styling if needed */
    font-weight: 600;                    /* bold */
    font-size: 1.5rem;                     /* 14px font size */
    text-align: left;                    /* align left */
    margin: 12px 12px 1px 12px;                /* optional spacing below title */
    color: #333;                         /* optional: dark text for visibility */
}

.content-row {
    display: flex;
    width: 100%;

     flex: 1;  
    /* margin-top: 20px; */
    gap: 10px;
}

.left-column {
    flex: 0 0 40%;
    display: flex;
    justify-content: center;
    align-items: flex-start;
     flex: 1; 
    
}

.right-column {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: flex-start; /* ✅ scanner sticks to left of column */
    justify-content: flex-start;
    padding-right: 20px;
}

.reader-container {
    flex: 0 0 auto;               /* now grows with right-column */
    width: 100%;          /* optional: take full width of column */ 
    height: 78%;
    background-color: rgba(0,0,0,0.05);
    display: flex;
    justify-content: center;
    align-items: center;
    margin-top: 20px;
    border-radius: 8px;
    border: 2px dashed #ccc;
    font-size: 18px;
    color: #666;
}
</style>
</head>
<body>

<div class="main-card">
    <div class="title">Register Fingerprint</div>
    <div class="content-row">
        <div id="left-column" class="left-column">
            <!-- RegisterForms will be injected here -->
        </div>

        <div id="right-column" class="right-column">
    <div id="reader-container" class="reader-container">Scanner Here</div>
</div>

<script type="module">
import { Register_Forms } from './RegisterForms.js';
import { RegisterScan } from './RegisterScan.js';
import { RegisterResponseModal } from "./RegisterResponseModal.js";
function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}


async function callAPI(url, postData = null) {
    try {
        const response = await fetch(url, {
            method: postData ? "POST" : "GET",
            headers: { "Content-Type": "application/json" },
            body: postData ? JSON.stringify(postData) : null
        });
        return await response.json();
    } catch (err) {
        return { success: false, message: "Fetch error: " + err.message };
    }
}

function updateScanButtonIcon() {
    scanButtonIcon.className = scannerState === 0 
        ? "fa-solid fa-fingerprint" 
        : "fa-solid fa-rotate";
}

let scannerState = 0;
let alreadyRunning = 0;
let scannerAlreadyRunning = 0;
let crowUrl;
let stateFree = 0;
// const ident = idData.identification;

// Left column: RegisterForms
const leftCol = document.getElementById('left-column');

const formEl = await Register_Forms(false); // no callback passed


leftCol.appendChild(formEl);

const rightCol = document.getElementById('right-column');
const readerContainer = document.getElementById('reader-container');
RegisterScan('reader-container', 'disconnected');

const scanButton = document.createElement('button');
const scanButtonIcon = document.createElement('i');
scanButtonIcon.className = scannerState === 0 ? "fa-solid fa-fingerprint" : "fa-solid fa-rotate";
scanButton.textContent = " Scan"; // space after icon
scanButton.prepend(scanButtonIcon);


scanButtonIcon.style.cssText = `
    margin-right : 8px
`;
scanButton.style.cssText = `
     display: block;        /* make button behave like a block for centering */
    margin: 15px auto 0 auto; /* top 15px, horizontally centered, bottom 0 */
    padding: 10px 35px;
    font-size: 14px;
    font-weight: 500;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    background-color: #007bff;
    color: white;
    text-align: center;
    gap: 10px;
`;

scanButton.addEventListener("click", async () => {
    console.log("Scan button clicked");
    try {
        await initializeScanner();  // <-- await the async function
        console.log("Scanner initialized successfully");
    } catch (error) {
        console.error("Failed to initialize scanner:", error);
    }
    if (scannerAlreadyRunning === 0){
        startScanning();
    }
     
});


rightCol.appendChild(scanButton);



//AWAIT HELL
async function waitForValidID(baseUrl) {
    while (true) {
        const idData = await callAPI(
            `http://localhost/capstone/fingerprint/api/fingerprint/fingerprint_identify_user.php?base_url=${encodeURIComponent(baseUrl)}`
        );

        console.log("Raw API result:", idData);

        if (!idData || !idData.identification) {
            console.log("No fingerprint or unregistered.");
            await sleep(500);
            continue;
        }

        return idData; // only return when ident exists
    }
}
async function handleFingerprintScan(baseUrl) {
    while (true) {
        const idData = await waitForValidID(baseUrl);
        const ident = idData.identification;

        // If ident exists but id is null, it's a new fingerprint → proceed
        if (ident && ident.status === "error" && ident.id === null) {
            console.log("New fingerprint detected, eligible for registration");
            return true; // stop scanning and proceed
        }

        // If ident exists and is registered
        if (ident && ident.status === "success" && ident.id > 0) {
            const employeeIDResult = await callAPI(
                `http://localhost/capstone/fingerprint/services/reader_identify_user.php?id=${ident.id}`
            );
            const employeeIdString = employeeIDResult.employee_id;

            if (employeeIdString) {
                // Already registered user
                RegisterScan('reader-container', 'activated');
                RegisterResponseModal(false, "Fingerprint Already Exists.");
                await sleep(500);
                RegisterScan('reader-container', 'idle');
                continue; // keep scanning
            } else {
                console.log("Eligible to register");
                return true;
            }
        }

        // Otherwise, just keep scanning
        console.log("No fingerprint detected yet, retrying...");
        await sleep(500);
    }
}



async function initializeScanner() {

    // 0️⃣ Stop any running server
    if (alreadyRunning === 0) {
        console.log("Stopping old server...");
        const stopData = await callAPI("http://localhost/capstone/fingerprint/api/application/application_close_server.php");
        if (!stopData.success && !(stopData.message || "").includes("not running")) {
            console.log("Failed to stop server: " + (stopData.message || ""));
            return;
        }
        console.log("Old server closed or not running.");
    
        // 1️⃣ Start server
        console.log("Starting server...");
        const startData = await callAPI("http://localhost/capstone/fingerprint/api/application/application_start_server.php");
        if (!startData.success) {
            console.log("Failed to start server: " + (startData.message || "Unknown error"));
            return;
        }
        console.log("Server started successfully.");

        // 2️⃣ Fetch server port
        console.log("Fetching server port...");
        const portData = await callAPI("http://localhost/capstone/fingerprint/api/application/application_fetch_port.php");
        if (!portData.success) {
            console.log("Failed to fetch server port.");
            return;
        }
        const [host, port] = portData.server.split(":");
        const baseUrl = `http://${host}:${port}`;
        crowUrl = baseUrl;
        console.log("Base URL: " + baseUrl);

        // 3️⃣ Connect device
        console.log("Connecting device...");
        await sleep(500);
        const connectData = await callAPI("http://localhost/capstone/fingerprint/api/fingerprint/fingerprint_connect_device.php", { base_url: baseUrl });
        if (!connectData.success) {
            console.log("Device connection failed");
            return;
        }
        console.log("Device connected.");
      

        // Mark server as running
        alreadyRunning = 1;
    }

}



async function startScanning() {
    console.log("Loading fingerprint templates...");
    await sleep(500);
    const fetchData = await callAPI("http://localhost/capstone/fingerprint/api/fingerprint/fingerprint_fetch_templates.php", { base_url: crowUrl });
    if (!fetchData.success) {
        console.log("Failed to load fingerprints");
        return;
    }
        console.log("Fingerprints loaded.");
if(alreadyRunning === 1 ){    
    scannerAlreadyRunning = 1;
RegisterScan('reader-container', 'idle');
const validForStorage = await handleFingerprintScan(crowUrl);
if (validForStorage) {
handleFingerprintScan(crowUrl);
console.log("running bmp exporter read");
    const inputData = await callAPI(
    "http://localhost/capstone/fingerprint/api/fingerprint/fingerprint_read_finger.php",
    { base_url: crowUrl }
);

console.log("Fingerprint read response:", inputData);

// if (inputData.status === "success") {
//     RegisterScan('reader-container', 'activated');
//     await sleep(500);
//     RegisterScan('reader-container', 'display');
//     scannerState = 1;
//     scannerAlreadyRunning = 0;
//     updateScanButtonIcon();
    
//     const updatedFormEl = await Register_Forms(true, () => {
//         RegisterScan('reader-container', 'disconnected');
//         scannerAlreadyRunning = 0;
//     });

//     leftCol.replaceChildren(updatedFormEl); // update form in left column
// }

if (inputData.status === "success") {
    RegisterScan('reader-container', 'activated');
    await sleep(500);
    RegisterScan('reader-container', 'display');
    scannerState = 1;
    scannerAlreadyRunning = 0;
    updateScanButtonIcon();

    // Save previous employee data
    const previousEmployeeDetails = formEl.employeeDetails || null;

    // Recreate form with isFingerReady = true
    const updatedFormEl = await Register_Forms(true, () => {
        RegisterScan('reader-container', 'disconnected');
        scannerAlreadyRunning = 0;
           scannerState = 0;
            updateScanButtonIcon();
    });

    // Ensure isReady is set
    updatedFormEl.isReady = true;

    // Restore previously selected employee details, if any
    if (previousEmployeeDetails) {
        updatedFormEl.employeeDetails = previousEmployeeDetails;

        // ✅ Safe call to fillForm
        if (typeof updatedFormEl.fillForm === "function") {
            updatedFormEl.fillForm(previousEmployeeDetails);
        } else {
            console.error("fillForm not defined on updatedFormEl!", updatedFormEl);
        }
    }

    // Replace old form in left column after restoring details
    leftCol.replaceChildren(updatedFormEl);
}


 else {
    console.log("Error reading fingerprint:", inputData.message || inputData);
    return;
}
}
}


}

if (!document.querySelector('link[href*="font-awesome"]')) {
    const faLink = document.createElement('link');
    faLink.rel = "stylesheet";
    faLink.href = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css";
    document.head.appendChild(faLink);
}

if (!document.querySelector('link[href*="poppins"]')) {
    const fontLink = document.createElement('link');
    fontLink.rel = "stylesheet";
    fontLink.href = "https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap";
    document.head.appendChild(fontLink);
}
</script>
</body>
</html>
