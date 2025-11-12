<?php
session_start();
require_once '../db.php';

// Check if municipal admin is logged in
if (!isset($_SESSION['municipal_logged_in']) || $_SESSION['municipal_logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Bayan ng Mabini | Municipal - Approved Leaves</title>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"
    />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
      @import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap");

      body {
        font-family: "Inter", sans-serif;
        background-color: #f0f4f8;
        margin: 0;
        padding: 0;
      }

      .container {
        display: flex;
        min-height: 100vh;
      }

      .sidebar {
        width: 280px;
        background-color: #ffffff;
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 1.5rem 1rem;
        border-right: 4px solid #3b82f6;
        flex-shrink: 0;
        position: fixed;
        top: 60px;
        left: 0;
        bottom: 0;
        overflow-y: auto;
      }

      .nav-menu ul {
        list-style: none;
        padding: 0;
        margin: 1rem 0;
      }

      .nav-item a {
        display: flex;
        align-items: center;
        padding: 0.75rem 1rem;
        margin-bottom: 0.5rem;
        color: #4b5563;
        text-decoration: none;
        font-size: 0.95rem;
        font-weight: 500;
        border-radius: 0.5rem;
        transition: background-color 0.2s, color 0.2s, transform 0.2s;
      }

      .nav-item a:hover,
      .nav-item.active a {
        background-color: #dbeafe;
        color: #1d4ed8;
        font-weight: 600;
        transform: translateY(-2px);
      }

      .nav-item a i {
        width: 20px;
        text-align: center;
        margin-right: 1rem;
      }

      .sign-out {
        margin-top: auto;
      }

      .sign-out a {
        display: flex;
        align-items: center;
        padding: 0.75rem 1rem;
        color: #dc2626;
        text-decoration: none;
        font-size: 0.95rem;
        font-weight: 500;
        border-radius: 0.5rem;
        transition: background-color 0.2s, transform 0.2s;
      }

      .sign-out a:hover {
        background-color: #fee2e2;
        transform: translateY(-2px);
      }

      .main-content {
        flex-grow: 1;
        padding: 2.5rem;
        margin-left: 280px;
        margin-top: 60px;
        overflow-y: auto;
      }

      .top-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.5rem;
        background-color: #ffffff;
        border-bottom: 1px solid #a7c4ff;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
      }

      .header-left {
        display: flex;
        align-items: center;
      }

      .header-logo .logo-image {
        width: 40px;
        height: 40px;
        margin-right: 10px;
      }

      .header-text {
        font-size: 1.2rem;
        font-weight: 600;
        color: #1e3a8a;
      }

      .header-profile {
        display: flex;
        align-items: center;
      }

      .header-profile .profile-image {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
        cursor: pointer;
        margin-left: 10px;
      }

      .table-container {
        background-color: #ffffff;
        padding: 1.5rem;
        border-radius: 1rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        overflow-x: auto;
      }

      .table-header-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
      }

      .table-header-controls .filter-icon,
      .table-header-controls .export-button {
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
      }

      .table-header-controls .filter-icon {
        color: #6b7280;
        border: 1px solid #d1d5db;
        background-color: #fff;
      }

      .table-header-controls .export-button {
        background-color: #22c55e;
        color: #ffffff;
        border: none;
      }

      table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
      }

      th,
      td {
        text-align: left;
        padding: 0.75rem;
        border-bottom: 1px solid #e5e7eb;
      }

      th {
        color: #6b7280;
        font-weight: 600;
        text-transform: uppercase;
      }

      .view-button {
        background-color: #3b82f6;
        color: #ffffff;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        border: none;
        font-size: 0.85rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
      }

      .view-button:hover {
        background-color: #2563eb;
        box-shadow: 0 4px 6px rgba(59, 130, 246, 0.2);
      }

      .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        background-color: #d1fae5;
        color: #065f46;
      }

      .action-button {
        padding: 0.4rem 0.8rem;
        border-radius: 0.375rem;
        font-size: 0.8rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
        margin: 0 0.25rem;
      }

      .approve-btn {
        background-color: #10b981;
        color: white;
      }

      .approve-btn:hover {
        background-color: #059669;
        box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
      }

      .decline-btn {
        background-color: #ef4444;
        color: white;
      }

      .decline-btn:hover {
        background-color: #dc2626;
        box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
      }

      .modal {
        display: none;
        position: fixed;
        z-index: 2000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.6);
        backdrop-filter: blur(4px);
      }

      .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 2rem;
        border-radius: 1rem;
        width: 90%;
        max-width: 600px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        animation: slideIn 0.3s ease-out;
      }

      @keyframes slideIn {
        from {
          transform: translateY(-50px);
          opacity: 0;
        }
        to {
          transform: translateY(0);
          opacity: 1;
        }
      }

      .close-modal {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        line-height: 20px;
      }

      .close-modal:hover,
      .close-modal:focus {
        color: #000;
      }

      .signature-pad-container {
        border: 2px solid #e5e7eb;
        border-radius: 0.5rem;
        margin: 1rem 0;
        background: white;
      }

      .signature-upload-container {
        border: 2px dashed #e5e7eb;
        border-radius: 0.5rem;
        padding: 2rem;
        text-align: center;
        margin: 1rem 0;
        transition: all 0.3s ease;
      }

      .signature-upload-container:hover {
        border-color: #3b82f6;
        background-color: #f0f9ff;
      }

      .signature-pad {
        width: 100%;
        height: 200px;
        cursor: crosshair;
        display: block;
      }

      .signature-controls {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.5rem;
        flex-wrap: wrap;
      }

      .signature-controls button {
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        border: 1px solid #d1d5db;
        background: white;
        transition: all 0.2s;
      }

      .signature-controls button:hover {
        background: #f3f4f6;
      }

      .existing-signature {
        max-width: 300px;
        max-height: 150px;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 0.5rem;
        margin: 1rem 0;
      }

      @media (max-width: 768px) {
        .container {
          flex-direction: column;
        }

        .sidebar {
          width: 100%;
          height: auto;
          position: relative;
          border-right: none;
          border-bottom: 4px solid #3b82f6;
        }

        .main-content {
          padding: 1.5rem;
          margin-left: 0;
          margin-top: 60px;
        }
      }
    </style>
  </head>
  <body>
    <header class="top-header">
      <div class="header-left">
        <div class="header-logo">
          <img src="../assets/logo.png" alt="Mabini Logo" class="logo-image" />
        </div>
        <span class="header-text">Bayan ng Mabini - Municipal Office</span>
      </div>
      <div class="header-profile">
        <i class="fas fa-bell notification-icon"></i>
        <img src="../assets/logo.png" alt="Profile" class="profile-image" />
      </div>
    </header>

    <div class="container">
      <aside class="sidebar">
        <nav class="nav-menu">
          <ul>
            <li class="nav-item active" id="pending-nav">
              <a href="#" onclick="showSection('pending'); return false;"
                ><i class="fas fa-clock"></i> Pending Approval</a
              >
            </li>
            <li class="nav-item" id="history-nav">
              <a href="#" onclick="showSection('history'); return false;"
                ><i class="fas fa-history"></i> Approval History</a
              >
            </li>
          </ul>
        </nav>
        <div class="sign-out">
          <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
        </div>
      </aside>

      <main class="main-content">
        <div class="main-content-area">
          <!-- Pending Approval Section -->
          <section id="pending-section" class="content-section active">
            <h2 class="text-2xl font-bold mb-6">Pending Municipal Approval</h2>

            <div class="table-container">
              <div class="table-header-controls">
                <div>
                  <h3 class="text-lg font-semibold text-gray-700">HR Approved - Awaiting Your Approval</h3>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                  <i class="fas fa-filter filter-icon"></i>
                  <button class="export-button" onclick="exportTable('pending')">
                    Export &nbsp;<i class="fas fa-download"></i>
                  </button>
                </div>
              </div>
              <table class="min-w-full border border-gray-300">
                <thead>
                  <tr>
                    <th class="border border-gray-300 px-4 py-2 text-left">
                      NAME(S)
                    </th>
                    <th class="border border-gray-300 px-4 py-2 text-left">
                      DURATION(S)
                    </th>
                    <th class="border border-gray-300 px-4 py-2 text-left">
                      DATES
                    </th>
                    <th class="border border-gray-300 px-4 py-2 text-center">
                      FORM
                    </th>
                    <th class="border border-gray-300 px-4 py-2 text-center">
                      STATUS
                    </th>
                    <th class="border border-gray-300 px-4 py-2 text-center">
                      ACTIONS
                    </th>
                  </tr>
                </thead>
                <tbody id="approved-leaves-table">
                  <!-- Dynamic rows will be rendered here by JavaScript -->
                </tbody>
              </table>
            </div>
          </section>

          <!-- History Section -->
          <section id="history-section" class="content-section" style="display: none;">
            <h2 class="text-2xl font-bold mb-6">Municipal Admin Approval History</h2>

            <div class="table-container">
              <div class="table-header-controls">
                <div>
                  <h3 class="text-lg font-semibold text-gray-700">All Processed Leave Requests</h3>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                  <i class="fas fa-filter filter-icon"></i>
                  <button class="export-button" onclick="exportTable('history')">
                    Export &nbsp;<i class="fas fa-download"></i>
                  </button>
                </div>
              </div>
              <table class="min-w-full border border-gray-300">
                <thead>
                  <tr>
                    <th class="border border-gray-300 px-4 py-2 text-left">
                      NAME(S)
                    </th>
                    <th class="border border-gray-300 px-4 py-2 text-left">
                      DURATION(S)
                    </th>
                    <th class="border border-gray-300 px-4 py-2 text-left">
                      DATES
                    </th>
                    <th class="border border-gray-300 px-4 py-2 text-center">
                      FORM
                    </th>
                    <th class="border border-gray-300 px-4 py-2 text-center">
                      STATUS
                    </th>
                    <th class="border border-gray-300 px-4 py-2 text-center">
                      APPROVED DATE
                    </th>
                  </tr>
                </thead>
                <tbody id="history-leaves-table">
                  <!-- Dynamic rows will be rendered here by JavaScript -->
                </tbody>
              </table>
            </div>
          </section>
        </div>
      </main>
    </div>

    <!-- Signature Modal for Approval -->
    <div id="signatureModal" class="modal">
      <div class="modal-content">
        <span class="close-modal" onclick="closeSignatureModal()">&times;</span>
        <h3 class="text-xl font-bold mb-4">Upload Signature to Approve Leave Request</h3>
        
        <div id="existing-signature-section" style="display: none;">
          <p class="text-sm text-gray-600 mb-2">Your saved e-signature:</p>
          <img id="existing-signature-img" class="existing-signature" alt="Existing Signature">
          <div class="mt-3">
            <button onclick="replaceSignature()" class="action-button approve-btn">
              <i class="fas fa-upload"></i> Replace Signature
            </button>
          </div>
        </div>

        <div id="new-signature-section">
          <!-- Existing Signature Display -->
          <div id="municipal_existing_signature_container" style="display: none; margin-bottom: 1rem;">
            <p style="color: #059669; font-size: 0.875rem; margin-bottom: 0.5rem;">
              ✓ You have an existing signature on file
            </p>
            <div style="border: 2px solid #d1d5db; border-radius: 0.5rem; padding: 1rem; background: #f9fafb;">
              <img id="municipal_existing_signature_img" src="" alt="Existing Signature" style="max-width: 300px; max-height: 100px; display: block; margin: 0 auto;" />
            </div>
            <p style="color: #6b7280; font-size: 0.75rem; margin-top: 0.5rem;">
              Upload a new file below to replace it (old signature will be deleted)
            </p>
          </div>
          
          <p class="text-sm text-gray-600 mb-2">Upload your e-signature (PNG, JPG, or GIF):</p>
          <div class="signature-upload-container" style="border: 2px dashed #e5e7eb; border-radius: 0.5rem; padding: 2rem; text-align: center; margin: 1rem 0;">
            <input type="file" id="signatureFileInput" accept="image/png,image/jpeg,image/jpg,image/gif" style="display: none;" onchange="handleSignatureUpload(event)">
            <div id="upload-placeholder" onclick="document.getElementById('signatureFileInput').click()" style="cursor: pointer;">
              <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; color: #9ca3af; margin-bottom: 1rem;"></i>
              <p class="text-gray-600">Click to upload your signature</p>
              <p class="text-xs text-gray-400 mt-1">Supported formats: PNG, JPG, GIF</p>
              <p class="text-xs text-gray-400 mt-1">Upload once and it will be saved to your account for future use</p>
            </div>
            <div id="signature-preview" style="display: none;">
              <img id="signature-preview-img" style="max-width: 300px; max-height: 150px; margin: 0 auto; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 0.5rem;">
              <button onclick="clearUploadedSignature()" class="mt-2" style="padding: 0.5rem 1rem; background: #ef4444; color: white; border-radius: 0.375rem; border: none; cursor: pointer;">
                <i class="fas fa-trash"></i> Remove
              </button>
            </div>
          </div>
          <div class="signature-controls" style="display: none;">
            <button onclick="useExistingSignature()" id="use-existing-btn">
              <i class="fas fa-check"></i> Use Saved Signature
            </button>
          </div>
        </div>

        <div class="mt-4 flex gap-2 justify-end">
          <button onclick="closeSignatureModal()" class="action-button decline-btn">Cancel</button>
          <button onclick="submitApproval()" class="action-button approve-btn" id="confirm-approval-btn">Confirm Approval</button>
        </div>
      </div>
    </div>

    <!-- Decline Modal -->
    <div id="declineModal" class="modal">
      <div class="modal-content">
        <span class="close-modal" onclick="closeDeclineModal()">&times;</span>
        <h3 class="text-xl font-bold mb-4">Decline Leave Request</h3>
        
        <div class="mb-4">
          <label for="decline-reason" class="block text-sm font-medium text-gray-700 mb-2">Reason for Decline:</label>
          <textarea id="decline-reason" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500" placeholder="Enter reason for declining this leave request..."></textarea>
        </div>

        <div class="flex gap-2 justify-end">
          <button onclick="closeDeclineModal()" class="action-button" style="background: #6b7280; color: white;">Cancel</button>
          <button onclick="submitDecline()" class="action-button decline-btn">Confirm Decline</button>
        </div>
      </div>
    </div>

    <script>
      // Global variables
      let currentLeaveId = null;
      let existingSignaturePath = null;
      let hasExistingSignature = false;
      let uploadedSignatureDataURL = null;

      // Toggle sections (Pending vs History)
      function showSection(which) {
        const pendingSec = document.getElementById('pending-section');
        const historySec = document.getElementById('history-section');
        const pendingNav = document.getElementById('pending-nav');
        const historyNav = document.getElementById('history-nav');

        if (which === 'history') {
          pendingSec.style.display = 'none';
          historySec.style.display = 'block';
          pendingNav.classList.remove('active');
          historyNav.classList.add('active');
          // Refresh history when switching tabs
          fetchApprovalHistory();
        } else {
          pendingSec.style.display = 'block';
          historySec.style.display = 'none';
          historyNav.classList.remove('active');
          pendingNav.classList.add('active');
          // Refresh pending when switching tabs
          fetchApprovedLeaves();
        }
      }

      // Handle signature file upload
      function handleSignatureUpload(event) {
        const file = event.target.files[0];
        if (!file) return;

        // Validate file type
        const validTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif'];
        if (!validTypes.includes(file.type)) {
          alert('Please upload a valid image file (PNG, JPG, or GIF)');
          return;
        }

        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
          alert('File size must be less than 5MB');
          return;
        }

        // Read file and convert to data URL
        const reader = new FileReader();
        reader.onload = function(e) {
          uploadedSignatureDataURL = e.target.result;
          
          // Show preview
          document.getElementById('upload-placeholder').style.display = 'none';
          document.getElementById('signature-preview').style.display = 'block';
          document.getElementById('signature-preview-img').src = uploadedSignatureDataURL;
        };
        reader.readAsDataURL(file);
      }

      // Clear uploaded signature
      function clearUploadedSignature() {
        uploadedSignatureDataURL = null;
        document.getElementById('signatureFileInput').value = '';
        document.getElementById('upload-placeholder').style.display = 'block';
        document.getElementById('signature-preview').style.display = 'none';
      }

      // Replace signature (show upload section)
      function replaceSignature() {
        document.getElementById('existing-signature-section').style.display = 'none';
        document.getElementById('new-signature-section').style.display = 'block';
        clearUploadedSignature();
      }

      // Load existing signature
      async function loadExistingSignature() {
        try {
          const response = await fetch('../api/get_municipal_signature.php');
          const result = await response.json();
          
          if (result.success && result.signature_path) {
            existingSignaturePath = result.signature_path;
            hasExistingSignature = true;
            document.getElementById('existing-signature-img').src = '../' + result.signature_path;
            document.getElementById('use-existing-btn').style.display = 'inline-block';
          }
        } catch (error) {
          console.error('Error loading existing signature:', error);
        }
      }

      // Load existing municipal signature
      async function loadExistingMunicipalSignature() {
        try {
          const response = await fetch("../api/get_municipal_signature.php");
          const data = await response.json();
          
          if (data.success && data.signature_path) {
            // Show existing signature
            document.getElementById('municipal_existing_signature_container').style.display = 'block';
            document.getElementById('municipal_existing_signature_img').src = '../' + data.signature_path + '?t=' + Date.now();
            hasExistingSignature = true;
          } else {
            // No existing signature
            document.getElementById('municipal_existing_signature_container').style.display = 'none';
            hasExistingSignature = false;
          }
        } catch (error) {
          console.error('Error loading municipal signature:', error);
          document.getElementById('municipal_existing_signature_container').style.display = 'none';
          hasExistingSignature = false;
        }
      }

      // Show signature modal for approval
      function openSignatureModal(leaveId) {
        currentLeaveId = leaveId;
        const modal = document.getElementById('signatureModal');
        modal.style.display = 'block';
        
        // Load existing signature when opening modal
        loadExistingMunicipalSignature();
        
        if (hasExistingSignature) {
          document.getElementById('existing-signature-section').style.display = 'block';
          document.getElementById('new-signature-section').style.display = 'none';
        } else {
          document.getElementById('existing-signature-section').style.display = 'none';
          document.getElementById('new-signature-section').style.display = 'block';
        }
        
        clearUploadedSignature();
      }

      function closeSignatureModal() {
        document.getElementById('signatureModal').style.display = 'none';
        currentLeaveId = null;
        clearUploadedSignature();
      }

      function useExistingSignature() {
        document.getElementById('new-signature-section').style.display = 'none';
        document.getElementById('existing-signature-section').style.display = 'block';
      }

      // Submit approval
      async function submitApproval() {
        if (!currentLeaveId) return;

        let signatureDataURI = null;
        
        // Check if using existing or new signature
        const usingExisting = document.getElementById('existing-signature-section').style.display !== 'none';
        
        if (usingExisting && hasExistingSignature) {
          // Convert existing signature to data URI
          const img = document.getElementById('existing-signature-img');
          const canvas = document.createElement('canvas');
          canvas.width = img.naturalWidth;
          canvas.height = img.naturalHeight;
          const ctx = canvas.getContext('2d');
          ctx.drawImage(img, 0, 0);
          signatureDataURI = canvas.toDataURL('image/png');
        } else {
          // Use newly uploaded signature
          if (!uploadedSignatureDataURL) {
            alert('Please upload your signature before approving.');
            return;
          }
          signatureDataURI = uploadedSignatureDataURL;
        }

        // Disable button to prevent double submission
        const confirmBtn = document.getElementById('confirm-approval-btn');
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Processing...';

        try {
          const response = await fetch('../api/municipal_update_leave.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              id: currentLeaveId,
              action: 'approve',
              signature_data_uri: signatureDataURI
            })
          });

          const result = await response.json();
          
          if (result.success) {
            alert('Leave request approved successfully!');
            closeSignatureModal();
            fetchApprovedLeaves(); // Refresh the table
            fetchApprovalHistory(); // Refresh history
            
            // Reload existing signature if it was updated
            if (!usingExisting) {
              await loadExistingSignature();
            }
          } else {
            // Show detailed error
            let errorMsg = 'Error: ' + (result.error || 'Failed to approve leave');
            if (result.details) {
              errorMsg += '\n\nDetails: ' + result.details;
            }
            alert(errorMsg);
            console.error('Approval error:', result);
          }
        } catch (error) {
          console.error('Error approving leave:', error);
          alert('Error approving leave request: ' + error.message);
        } finally {
          confirmBtn.disabled = false;
          confirmBtn.textContent = 'Confirm Approval';
        }
      }

      // Show decline modal
      function openDeclineModal(leaveId) {
        currentLeaveId = leaveId;
        document.getElementById('declineModal').style.display = 'block';
        document.getElementById('decline-reason').value = '';
      }

      function closeDeclineModal() {
        document.getElementById('declineModal').style.display = 'none';
        currentLeaveId = null;
      }

      // Submit decline
      async function submitDecline() {
        if (!currentLeaveId) return;

        const reason = document.getElementById('decline-reason').value.trim();
        if (!reason) {
          alert('Please enter a reason for declining this leave request.');
          return;
        }

        try {
          const response = await fetch('../api/municipal_update_leave.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              id: currentLeaveId,
              action: 'decline',
              decline_reason: reason
            })
          });

          const result = await response.json();
          
          if (result.success) {
            alert('Leave request declined successfully.');
            closeDeclineModal();
            fetchApprovedLeaves(); // Refresh the table
            fetchApprovalHistory(); // Refresh history
          } else {
            alert('Error: ' + (result.error || 'Failed to decline leave'));
          }
        } catch (error) {
          console.error('Error declining leave:', error);
          alert('Error declining leave request');
        }
      }

      // Fetch all HR-approved leave requests
      async function fetchApprovedLeaves() {
        try {
          const response = await fetch('../api/get_leave_requests.php');
          const json = await response.json();

          if (!json.success) {
            console.error('Failed to fetch leave requests');
            return;
          }

          let requests = Array.isArray(json.data) ? json.data : [];
          
          // Filter: HR approved but NOT yet municipal approved
          requests = requests.filter(r => 
            r.status === 'approved' && 
            r.approved_by_hr == 1 && 
            (r.approved_by_municipal == 0 || r.approved_by_municipal === null)
          );

          const tbody = document.querySelector('#approved-leaves-table');
          tbody.innerHTML = '';

          if (requests.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="border border-gray-300 px-4 py-2 text-center text-gray-500">No pending leave requests for municipal approval</td></tr>';
            return;
          }

          requests.forEach((request) => {
            const name = (request.firstname ? request.firstname : '') + 
                        (request.lastname ? ' ' + request.lastname : '');
            
            const row = document.createElement('tr');
            row.innerHTML = `
              <td class="border border-gray-300 px-4 py-2 text-left">${name || 'N/A'}</td>
              <td class="border border-gray-300 px-4 py-2 text-left">${request.leave_type || 'N/A'}</td>
              <td class="border border-gray-300 px-4 py-2 text-left">${request.dates || 'N/A'}</td>
              <td class="border border-gray-300 px-4 py-2 text-center">
                <button class="view-button" onclick="viewForm('${request.id}')">View</button>
              </td>
              <td class="border border-gray-300 px-4 py-2 text-center">
                <span class="status-badge" style="background-color: #fef3c7; color: #92400e;">Pending Municipal</span>
              </td>
              <td class="border border-gray-300 px-4 py-2 text-center">
                <button class="action-button approve-btn" onclick="openSignatureModal('${request.id}')">
                  <i class="fas fa-check"></i> Approve
                </button>
                <button class="action-button decline-btn" onclick="openDeclineModal('${request.id}')">
                  <i class="fas fa-times"></i> Decline
                </button>
              </td>
            `;
            tbody.appendChild(row);
          });
        } catch (error) {
          console.error('Error fetching approved leaves:', error);
        }
      }

      // Fetch municipal approval history (approved or declined)
      async function fetchApprovalHistory() {
        try {
          const response = await fetch('../api/get_leave_requests.php');
          const json = await response.json();

          if (!json.success) {
            console.error('Failed to fetch leave requests (history)');
            return;
          }

          let requests = Array.isArray(json.data) ? json.data : [];
          // Filter: processed by municipal (1=approved, 2=declined)
          requests = requests.filter(r => r.approved_by_municipal == 1 || r.approved_by_municipal == 2);

          // Sort by municipal_approval_date desc if available
          requests.sort((a, b) => {
            const da = a.municipal_approval_date ? new Date(a.municipal_approval_date) : 0;
            const db = b.municipal_approval_date ? new Date(b.municipal_approval_date) : 0;
            return db - da;
          });

          const tbody = document.querySelector('#history-leaves-table');
          tbody.innerHTML = '';

          if (requests.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="border border-gray-300 px-4 py-2 text-center text-gray-500">No municipal approvals yet</td></tr>';
            return;
          }

          requests.forEach((request) => {
            const name = (request.firstname ? request.firstname : '') + (request.lastname ? ' ' + request.lastname : '');
            const approved = request.approved_by_municipal == 1;
            const statusBadge = approved
              ? '<span class="status-badge" style="background-color:#d1fae5;color:#065f46;">Approved by Municipal</span>'
              : '<span class="status-badge" style="background-color:#fee2e2;color:#991b1b;">Declined by Municipal</span>';

            const approvedDateText = request.municipal_approval_date
              ? new Date(request.municipal_approval_date.replace(' ', 'T')).toLocaleString()
              : '—';

            const row = document.createElement('tr');
            row.innerHTML = `
              <td class="border border-gray-300 px-4 py-2 text-left">${name || 'N/A'}</td>
              <td class="border border-gray-300 px-4 py-2 text-left">${request.leave_type || 'N/A'}</td>
              <td class="border border-gray-300 px-4 py-2 text-left">${request.dates || 'N/A'}</td>
              <td class="border border-gray-300 px-4 py-2 text-center">
                <button class="view-button" onclick="viewForm('${request.id}')">View</button>
              </td>
              <td class="border border-gray-300 px-4 py-2 text-center">${statusBadge}</td>
              <td class="border border-gray-300 px-4 py-2 text-center">${approvedDateText}</td>
            `;
            tbody.appendChild(row);
          });
        } catch (error) {
          console.error('Error fetching approval history:', error);
        }
      }

      // View form function
      function viewForm(id) {
        window.open(`../dept_head/civil_form.php?id=${id}`, '_blank');
      }

      // Export table to CSV
      function exportTable(section = 'pending') {
        const tableId = section === 'history' ? 'history-leaves-table' : 'approved-leaves-table';
        const table = document.querySelector('#' + tableId);
        const rows = table.querySelectorAll('tr');
        
        let csv = '';
        if (section === 'history') {
          csv = 'NAME,DURATION,DATES,STATUS,APPROVED DATE\n';
        } else {
          csv = 'NAME,DURATION,DATES,STATUS\n';
        }

        rows.forEach(row => {
          const cols = row.querySelectorAll('td');
          if (cols.length >= 4) {
            const name = cols[0].textContent.trim();
            const duration = cols[1].textContent.trim();
            const dates = cols[2].textContent.trim();
            const status = cols[4].textContent.trim();
            
            if (section === 'history' && cols.length >= 6) {
              const approvedDate = cols[5].textContent.trim();
              csv += `"${name}","${duration}","${dates}","${status}","${approvedDate}"\n`;
            } else {
              csv += `"${name}","${duration}","${dates}","${status}"\n`;
            }
          }
        });

        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `municipal_${section}_` + new Date().toISOString().split('T')[0] + '.csv';
        a.click();
        window.URL.revokeObjectURL(url);
      }

      // Load data on page load
      document.addEventListener('DOMContentLoaded', () => {
        loadExistingSignature();
        fetchApprovedLeaves();
        fetchApprovalHistory();
      });

      // Refresh data every minute (both tabs)
      setInterval(() => {
        fetchApprovedLeaves();
        fetchApprovalHistory();
      }, 60000);
    </script>
  </body>
</html>
