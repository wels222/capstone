<?php
require_once __DIR__ . '/../auth_guard.php';
require_role('employee');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Leave History</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
      @import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap");
      body {
        font-family: "Inter", sans-serif;
        background-color: #f3f4f6;
      }
      .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
      }
    </style>
  </head>
  <body>
    <div class="container">
      <!-- Breadcrumb Navigation -->
      <main class="flex-grow p-4 overflow-y-auto mt-6">
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
          <div class="flex items-center text-gray-600">
            <a href="dashboard.php" class="cursor-pointer hover:text-blue-600"
              >Dashboard</a
            >
            <span class="mx-2">&gt;</span>
            <a
              href="apply_leave.php"
              class="cursor-pointer hover:text-blue-600"
              >Apply for Leave</a
            >
            <span class="mx-2">&gt;</span>
            <span id="current-leave-type"></span>
          </div>
        </div>
      </main>

      <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold text-gray-800">Leave History</h3>
          <div class="flex items-center space-x-4">
            <button id="btnFilter" class="flex items-center space-x-2 px-4 py-2 text-sm font-semibold text-blue-600 bg-white border border-blue-600 rounded-lg hover:bg-blue-50 transition-colors">
              <span>Filter</span>
            </button>
            <button id="btnExport" class="flex items-center space-x-2 px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
              <span>Export</span>
            </button>
          </div>
        </div>
        <!-- Filter Panel (hidden by default) -->
        <div id="filterPanel" class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200 hidden">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label class="block text-xs text-gray-600 mb-1">Status</label>
              <select id="filterStatus" class="w-full border rounded p-2 text-sm">
                <option value="">All</option>
                <option value="Pending">Pending</option>
                <option value="Approved">Approved</option>
                <option value="Declined">Declined</option>
              </select>
            </div>
            <div>
              <label class="block text-xs text-gray-600 mb-1">Leave Type</label>
              <select id="filterType" class="w-full border rounded p-2 text-sm">
                <option value="">All</option>
              </select>
            </div>
            <div>
              <label class="block text-xs text-gray-600 mb-1">Start From</label>
              <input type="date" id="filterFrom" class="w-full border rounded p-2 text-sm" />
            </div>
            <div>
              <label class="block text-xs text-gray-600 mb-1">End To</label>
              <input type="date" id="filterTo" class="w-full border rounded p-2 text-sm" />
            </div>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th
                  scope="col"
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Name
                </th>
                <th
                  scope="col"
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Duration
                </th>
                <th
                  scope="col"
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Start Date
                </th>
                <th
                  scope="col"
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  End Date
                </th>
                <th
                  scope="col"
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Form
                </th>
                <th
                  scope="col"
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Contact No.
                </th>
                <th
                  scope="col"
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Status
                </th>
              </tr>
            </thead>
            <tbody
              id="leaveHistoryBody"
              class="bg-white divide-y divide-gray-200"
            >
              <!-- Rows populated by JS from database -->
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <script>
      // --- Breadcrumb: Show current leave type ---
      document.getElementById("current-leave-type").textContent =
        localStorage.getItem("leaveType") || "Civil Service Form";

      // Realtime history with filters and export
      let API_URL = "../api/employee_leave_history.php";
      const uEmail = localStorage.getItem('userEmail');
      if (uEmail) {
        API_URL += `?email=${encodeURIComponent(uEmail)}`;
      }
      const state = { raw: [], filtered: [], poll: null };

      function applyFilters() {
        const status = document.getElementById('filterStatus').value || '';
        const type = document.getElementById('filterType').value || '';
        const from = document.getElementById('filterFrom').value || '';
        const to = document.getElementById('filterTo').value || '';

        const fromTime = from ? new Date(from + 'T00:00:00') : null;
        const toTime = to ? new Date(to + 'T23:59:59') : null;

        state.filtered = state.raw.filter(item => {
          if (status && item.status !== status) return false;
          if (type && !(item.leaveType || '').includes(type)) return false;
          // Date range filter uses startDate/endDate; fallback to appliedAt
          if (fromTime || toTime) {
            const s = item.startDate ? new Date(item.startDate) : (item.appliedAt ? new Date(item.appliedAt) : null);
            const e = item.endDate ? new Date(item.endDate) : s;
            if (fromTime && e && e < fromTime) return false;
            if (toTime && s && s > toTime) return false;
          }
          return true;
        });
        renderTable();
      }

      function renderTable() {
        const tbody = document.getElementById("leaveHistoryBody");
        tbody.innerHTML = "";
        state.filtered.forEach((item) => {
          const statusClass = item.status === "Approved" ? "text-green-600" : (item.status === 'Declined' ? 'text-red-600' : 'text-yellow-600');
          const tr = document.createElement("tr");
          tr.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${item.name || ''}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${item.duration || ''}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${item.startDate || ''}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${item.endDate || ''}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 underline"><a href="../dept_head/civil_form.php?id=${item.formId}" target="_blank">View Form</a></td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${item.contactNo || ''}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold ${statusClass}">${item.status || ''}</td>
          `;
          tbody.appendChild(tr);
        });
      }

      function populateTypeFilter() {
        const sel = document.getElementById('filterType');
        if (!sel) return;
        const current = sel.value;
        const uniqueTypes = Array.from(new Set(state.raw.map(x => x.leaveType || '').filter(Boolean)));
        sel.innerHTML = '<option value="">All</option>' + uniqueTypes.map(t => `<option value="${t.replace(/"/g,'&quot;')}">${t}</option>`).join('');
        if (current && uniqueTypes.includes(current)) sel.value = current;
      }

      async function fetchHistory() {
        try {
          const res = await fetch(API_URL);
          if (!res.ok) throw new Error('HTTP ' + res.status);
          const js = await res.json();
          if (!js || !js.success) throw new Error(js?.error || 'Failed');
          state.raw = js.data || [];
          populateTypeFilter();
          applyFilters();
        } catch (e) {
          console.error('Failed to load history', e);
        }
      }

      function startPolling() {
        if (state.poll) clearInterval(state.poll);
        state.poll = setInterval(fetchHistory, 10000);
      }

      function stopPolling() {
        if (state.poll) clearInterval(state.poll);
        state.poll = null;
      }

      function exportCSV() {
        const rows = state.filtered;
        const headers = ['Form ID','Name','Leave Type','Duration','Start Date','End Date','Contact No.','Status','Applied At'];
        const csv = [headers.join(',')].concat(
          rows.map(r => [
            r.formId,
            r.name || '',
            r.leaveType || '',
            r.duration || '',
            r.startDate || '',
            r.endDate || '',
            r.contactNo || '',
            r.status || '',
            r.appliedAt || ''
          ].map(v => '"' + String(v).replace(/"/g,'""') + '"').join(','))
        ).join('\r\n');
        const blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        const d = new Date();
        a.download = `leave_history_${d.getFullYear()}-${(d.getMonth()+1).toString().padStart(2,'0')}-${d.getDate().toString().padStart(2,'0')}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
      }

      // Wire UI
      document.getElementById('btnFilter').addEventListener('click', () => {
        const p = document.getElementById('filterPanel');
        p.classList.toggle('hidden');
      });
      document.getElementById('btnExport').addEventListener('click', exportCSV);
      ['filterStatus','filterType','filterFrom','filterTo'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', applyFilters);
      });

      window.addEventListener('beforeunload', stopPolling);

      // Initial load + poll
      fetchHistory().then(startPolling);
    </script>
  </body>
</html>
