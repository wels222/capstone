<?php
require_once '../db.php';
// Get leave request ID from URL
$leave_id = $_GET['id'] ?? null;
$leave = null;
$user = null;
if ($leave_id) {
    // Fetch leave request
    $stmt = $pdo->prepare('SELECT * FROM leave_requests WHERE id = ?');
    $stmt->execute([$leave_id]);
    $leave = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($leave) {
        // Fetch user info by employee_email
        $stmt2 = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt2->execute([$leave['employee_email']]);
        $user = $stmt2->fetch(PDO::FETCH_ASSOC);
    // Try to fetch salary from employees table if available
    try {
      $stmt3 = $pdo->prepare('SELECT salary FROM employees WHERE email = ?');
      $stmt3->execute([$leave['employee_email']]);
      $empSalaryRow = $stmt3->fetch(PDO::FETCH_ASSOC);
      if ($empSalaryRow && isset($empSalaryRow['salary'])) {
        $user['salary'] = $empSalaryRow['salary'];
      }
    } catch (PDOException $e) { /* ignore */ }
    }
}

function field($arr, $key) {
    return isset($arr[$key]) ? $arr[$key] : '';
}
// Decode structured details JSON
$details = [];
if (!empty($leave['details'])) {
  $d = json_decode($leave['details'], true);
  if (is_array($d)) $details = $d;
}
// Prefer the salary the employee actually entered on the form
$displaySalary = '';
// Prefer exactly what the employee typed.
if (!empty($details['snapshot']['salary']['value'])) {
  $displaySalary = $details['snapshot']['salary']['value'];
} elseif (!empty($details['salary'])) {
  // Legacy path (submitted from Apply Leave page): salary saved directly in details
  $displaySalary = $details['salary'];
} elseif (!empty($user['salary'])) {
  // Fallback to DB salary if nothing was supplied in the form
  $displaySalary = $user['salary'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>CS Form No. 6 - Application for Leave</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap");
    body { font-family: "Inter", sans-serif; background-color: #f3f4f6; }
    .form-container { max-width: 800px; margin: 2rem auto; background-color: white; padding: 1rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    .form-box { border: 1px solid #000; }
    .form-input { width: 100%; border: none; outline: none; padding: 0 4px; line-height: 1.25rem; }
    .form-line { border-bottom: 1px solid #000; }
    .form-underline { border-bottom: 1px solid #000; padding-bottom: 2px; }
    .text-xxs { font-size: 0.65rem; }
    .checkbox-label { display: flex; align-items: flex-start; line-height: 1.1; }
    .checkbox-label input[type="checkbox"], .checkbox-label input[type="radio"] { margin-top: 2px; margin-right: 0.25rem; min-width: 1rem; min-height: 1rem; accent-color: #1f2937; }
    @media print {
      @page { size: A4 portrait; margin: 12mm; }
      html, body { width: 210mm; background: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      .form-container { width: 186mm !important; max-width: 186mm !important; margin: 0 auto !important; box-shadow: none !important; background: #fff !important; padding: 6mm; min-height: auto !important; }
      .no-print { display: none !important; }
    }
  </style>
</head>
<body>
<div class="form-container">
      <!-- Header Section (FIXED to match reference image) -->
      <header class="text-xs mb-2">
        <div class="flex justify-between items-start mb-1">
          <div class="text-left leading-tight">
            <p>Civil Service Form No. 6</p>
            <p>Revised 2020</p>
          </div>
          <div class="text-right leading-tight font-bold">
            <p>ANNEX A</p>
          </div>
        </div>

        <div class="flex justify-between items-end mb-4">
          <!-- AGENCY LOGO -->
          <div class="flex items-start">
            <img
              src="../assets/logo.png"
              alt="Agency Logo"
              class="h-10 w-20 object-contain"
              onerror="this.onerror=null; this.src='https://placehold.co/40x40/DDDDDD/000?text=AGENCY%20LOGO';"
            />
          </div>

          <!-- REPUBLIC OF THE PHILIPPINES / AGENCY INFO -->
          <div class="text-center leading-tight">
            <p class="font-bold">Republic of the Philippines</p>
            <p class="font-bold text-gray-900">MUNICIPAL OFFICE OF MABINI</p>
            <p class="text-xs font-normal">Poblacion, Mabini Batangas</p>
          </div>

          <!-- Stamp of Date of Receipt (UPDATED: Box is now blank with text above/below) -->
          <div class="text-right text-xxs pt-1 leading-snug w-20">
            <div class="border border-gray-400 border-dashed w-full h-8"></div>
          </div>
        </div>

        <h1 class="text-center text-lg font-extrabold text-gray-900 mt-2">
          APPLICATION FOR LEAVE
        </h1>
      </header>

      <!-- 1-5. Employee Information Section (PERFECT ALIGNMENT FIXED HERE) -->
      <div class="text-xs form-box">
        <!-- Row 1: 1. OFFICE/DEPARTMENT and 2. NAME (Last, First, Middle) -->
        <div class="flex form-line">
          <!-- 1. OFFICE/DEPARTMENT -->
          <div class="w-2/5 flex flex-col p-0 h-full">
            <div class="flex items-center px-1 pt-1 h-6">
              <span class="font-bold">1. OFFICE/DEPARTMENT</span>
            </div>
            <div class="flex-grow flex items-end px-1 pb-1 pt-0">
              <input type="text" id="office_dept" class="form-input h-4 p-0" value="<?= htmlspecialchars($user['department'] ?? '') ?>" readonly />
            </div>
          </div>
          <!-- 2. NAME -->
          <div class="w-3/5 flex border-l border-white">
            <div class="w-1/5 flex items-start pt-1 pl-1 pr-0 font-bold">2. NAME:</div>
            <div class="flex-grow flex justify-between">
              <div class="w-1/3 flex flex-col border-l border-white">
                <div class="h-6 flex items-center justify-center pt-1">
                  <span class="text-center text-xxs">(Last)</span>
                </div>
                <div class="flex-grow flex items-end px-1 pb-1 pt-0">
                  <input type="text" id="name_last" class="form-input text-center h-4 p-0" value="<?= htmlspecialchars($user['lastname'] ?? '') ?>" readonly />
                </div>
              </div>
              <div class="w-1/3 flex flex-col border-l border-white">
                <div class="h-6 flex items-center justify-center pt-1">
                  <span class="text-center text-xxs">(First)</span>
                </div>
                <div class="flex-grow flex items-end px-1 pb-1 pt-0">
                  <input type="text" id="name_first" class="form-input text-center h-4 p-0" value="<?= htmlspecialchars($user['firstname'] ?? '') ?>" readonly />
                </div>
              </div>
              <div class="w-1/3 flex flex-col border-l border-white">
                <div class="h-6 flex items-center justify-center pt-1">
                  <span class="text-center text-xxs">(Middle)</span>
                </div>
                <div class="flex-grow flex items-end px-1 pb-1 pt-0">
                  <input type="text" id="name_middle" class="form-input text-center h-4 p-0" value="<?= htmlspecialchars($user['mi'] ?? '') ?>" readonly />
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- Row 2: 3. DATE OF FILING, 4. POSITION, 5. SALARY -->
        <div class="flex p-1">
          <div class="w-1/3 flex items-center">
            <span class="font-bold">3. DATE OF FILING</span>
            <input type="date" id="date_filing" class="form-underline inline-block w-auto h-4 p-0 ml-1 flex-grow text-xs" value="<?= htmlspecialchars(substr($leave['applied_at'] ?? '', 0, 10)) ?>" readonly />
          </div>
          <div class="w-1/3 flex items-center pl-2 border-l border-white">
            <span class="font-bold">4. POSITION</span>
            <input type="text" id="position" class="form-underline text-center w-auto ml-1 p-0 flex-grow text-xs" value="<?= htmlspecialchars($user['position'] ?? '') ?>" readonly />
          </div>
          <div class="w-1/3 flex items-center pl-2 border-l border-white">
            <span class="font-bold">5. SALARY</span>
            <input type="text" id="salary" class="form-underline text-center inline-block w-auto ml-1 p-0 flex-grow text-xs" value="<?= htmlspecialchars($displaySalary) ?>" readonly />
          </div>
        </div>
      </div>

      <!-- 6. DETAILS OF APPLICATION -->
      <div class="text-xs form-box mt-3">
        <div class="text-center font-bold form-line p-1">
          <span class="text-lg font-bold">6. DETAILS OF APPLICATION</span>
        </div>

        <div class="flex">
          <!-- 6.A TYPE OF LEAVE -->
          <div class="w-1/2 p-2 form-line border-r border-black">
            <div class="font-bold mb-1">6.A TYPE OF LEAVE TO BE AVAILED OF</div>
            <div class="space-y-1">
              <label class="checkbox-label" for="vl">
                <input type="checkbox" id="vl" name="leave_type" <?= (strpos($leave['leave_type'] ?? '', 'Vacation Leave') !== false ? 'checked' : '') ?> disabled />
                <span class="ml-1 text-xxs">Vacation Leave (Sec. 51, Rule XVI, Omnibus Rules Implementing E.O. No. 292)</span>
              </label>
              <label class="checkbox-label" for="mfl">
                <input type="checkbox" id="mfl" name="leave_type" <?= (strpos($leave['leave_type'] ?? '', 'Mandatory/Forced Leave') !== false ? 'checked' : '') ?> disabled />
                <span class="ml-1 text-xxs">Mandatory/Forced Leave (Sec. 25, Rule XVI, Omnibus Rules Implementing E.O. No. 292)</span>
              </label>
              <label class="checkbox-label" for="sl">
                <input type="checkbox" id="sl" name="leave_type" <?= (strpos($leave['leave_type'] ?? '', 'Sick Leave') !== false ? 'checked' : '') ?> disabled />
                <span class="ml-1 text-xxs">Sick Leave (Sec. 43, Rule XVI, Omnibus Rules Implementing E.O. No. 292)</span>
              </label>
              <label class="checkbox-label" for="ml">
                <input type="checkbox" id="ml" name="leave_type" <?= (strpos($leave['leave_type'] ?? '', 'Maternity Leave') !== false ? 'checked' : '') ?> disabled />
                <span class="ml-1 text-xxs">Maternity Leave (R.A. No. 11210 / IRR issued by CSC, DOLE and SSS)</span>
              </label>
              <label class="checkbox-label" for="pl">
                <input type="checkbox" id="pl" name="leave_type" <?= (strpos($leave['leave_type'] ?? '', 'Paternity Leave') !== false ? 'checked' : '') ?> disabled />
                <span class="ml-1 text-xxs">Paternity Leave (R.A. No. 8187 / CSC MC No. 71, s. 1998, as amended)</span>
              </label>
              <label class="checkbox-label" for="spl">
                <input type="checkbox" id="spl" name="leave_type" <?= (strpos($leave['leave_type'] ?? '', 'Special Privilege Leave') !== false ? 'checked' : '') ?> disabled />
                <span class="ml-1 text-xxs">Special Privilege Leave (Sec. 21, Rule XVI, Omnibus Rules Implementing E.O. No. 292)</span>
              </label>
              <label class="checkbox-label" for="solopl">
                <input type="checkbox" id="solopl" name="leave_type" <?= (strpos($leave['leave_type'] ?? '', 'Solo Parent Leave') !== false ? 'checked' : '') ?> disabled />
                <span class="ml-1 text-xxs">Solo Parent Leave (RA No. 8972 / CSC MC No. 8, s. 2004)</span>
              </label>
              <label class="checkbox-label" for="studyl">
                <input type="checkbox" id="studyl" name="leave_type" <?= (strpos($leave['leave_type'] ?? '', 'Study Leave') !== false ? 'checked' : '') ?> disabled />
                <span class="ml-1 text-xxs">Study Leave (Sec. 68, Rule XVI, Omnibus Rules Implementing E.O. No. 292)</span>
              </label>
              <label class="checkbox-label" for="vawc">
                <input type="checkbox" id="vawc" name="leave_type" <?= (strpos($leave['leave_type'] ?? '', '10-Day VAWC Leave') !== false ? 'checked' : '') ?> disabled />
                <span class="ml-1 text-xxs">10-Day VAWC Leave (RA No. 9262 / CSC MC No. 15, s. 2005)</span>
              </label>
              <label class="checkbox-label" for="rehab">
                <input type="checkbox" id="rehab" name="leave_type" <?= (strpos($leave['leave_type'] ?? '', 'Rehabilitation Privilege') !== false ? 'checked' : '') ?> disabled />
                <span class="ml-1 text-xxs">Rehabilitation Privilege (Sec. 55, Rule XVI, Omnibus Rules Implementing E.O. No. 292)</span>
              </label>
              <label class="checkbox-label" for="splwomen">
                <input type="checkbox" id="splwomen" name="leave_type" <?= (strpos($leave['leave_type'] ?? '', 'Special Leave Benefits for Women') !== false ? 'checked' : '') ?> disabled />
                <span class="ml-1 text-xxs">Special Leave Benefits for Women (RA No. 9710 / CSC MC No. 25, s. 2010)</span>
              </label>
              <label class="checkbox-label" for="secl">
                <input type="checkbox" id="secl" name="leave_type" <?= (strpos($leave['leave_type'] ?? '', 'Special Emergency (Calamity) Leave') !== false ? 'checked' : '') ?> disabled />
                <span class="ml-1 text-xxs">Special Emergency (Calamity) Leave (CSC MC No. 2, s. 2012, as amended)</span>
              </label>
              <label class="checkbox-label" for="adoptl">
                <input type="checkbox" id="adoptl" name="leave_type" <?= (strpos($leave['leave_type'] ?? '', 'Adoption Leave') !== false ? 'checked' : '') ?> disabled />
                <span class="ml-1 text-xxs">Adoption Leave (R.A. No. 8552)</span>
              </label>
              <div class="mt-2 text-xxs flex items-center">
                Others:
                <input type="text" id="other_leave_specify" class="form-underline w-2/3 ml-1 p-0 text-xs" value="<?= htmlspecialchars($details['snapshot']['other_leave_specify']['value'] ?? '') ?>" readonly />
              </div>
            </div>
          </div>

          <!-- 6.B DETAILS OF LEAVE -->
          <div class="w-1/2 p-2 form-line">
            <div class="font-bold mb-1">6.B DETAILS OF LEAVE</div>

            <!-- Vacation/Special Privilege Leave -->
            <div class="mb-3">
              <p class="text-xxs font-semibold mb-1">
                In case of Vacation/Special Privilege Leave:
              </p>
              <div class="ml-3 space-y-1">
                <label class="checkbox-label" for="loc_ph">
                  <input type="checkbox" id="loc_ph" name="vl_loc" <?= (!empty($details['section6b']['vl']['withinPH']) ? 'checked' : '') ?> disabled /><span
                    class="ml-1 text-xxs w-full flex items-center"
                    >Within the Philippines
                    <input
                      type="text"
                      id="vl_loc_within_specify"
                      class="form-underline w-3/5 ml-1 p-0 text-xs"
                      value="<?= htmlspecialchars($details['section6b']['vl']['withinSpecify'] ?? '') ?>"
                    /></span>
                </label>
                <label class="checkbox-label" for="loc_ab">
                  <input type="checkbox" id="loc_ab" name="vl_loc" <?= (!empty($details['section6b']['vl']['abroad']) ? 'checked' : '') ?> disabled /><span
                    class="ml-1 text-xxs w-full flex items-center"
                    >Abroad (Specify)
                    <input
                      type="text"
                      id="vl_loc_abroad_specify"
                      class="form-underline w-3/5 ml-1 p-0 text-xs"
                      value="<?= htmlspecialchars($details['section6b']['vl']['abroadSpecify'] ?? '') ?>"
                    /></span>
                </label>
              </div>
            </div>

            <!-- Sick Leave -->
            <div class="mb-3">
              <p class="text-xxs font-semibold mb-1">In case of Sick Leave:</p>
              <div class="ml-3 space-y-1">
                <label class="checkbox-label" for="sl_hosp">
                  <input type="checkbox" id="sl_hosp" name="sl_type" <?= (!empty($details['section6b']['sl']['inHospital']) ? 'checked' : '') ?> disabled /><span
                    class="ml-1 text-xxs w-full flex flex-col"
                    >In Hospital (Specify Illness)
                    <input
                      type="text"
                      id="sl_hosp_illness"
                      class="form-underline w-full p-0 text-xs mt-1"
                      value="<?= htmlspecialchars($details['section6b']['sl']['hospitalIllness'] ?? '') ?>"
                    /></span>
                </label>
                <label class="checkbox-label" for="sl_out">
                  <input type="checkbox" id="sl_out" name="sl_type" <?= (!empty($details['section6b']['sl']['outPatient']) ? 'checked' : '') ?> disabled /><span
                    class="ml-1 text-xxs w-full flex flex-col"
                    >Out Patient (Specify Illness)
                    <input
                      type="text"
                      id="sl_out_illness"
                      class="form-underline w-full p-0 text-xs mt-1"
                      value="<?= htmlspecialchars($details['section6b']['sl']['outIllness'] ?? '') ?>"
                    /></span>
                </label>
              </div>
            </div>

            <!-- Special Leave Benefits for Women -->
            <div class="mb-3">
              <p class="text-xxs font-semibold mb-1">
                In case of Special Leave Benefits for Women:
              </p>
              <div class="ml-3">
                <span class="text-xxs w-full flex flex-col"
                  >(Specify Illness)
                  <input
                    type="text"
                    id="splwomen_illness_input"
                    class="form-underline w-full ml-1 p-0 text-xs mt-1"
                    value="<?= htmlspecialchars($details['section6b']['splwomen']['illness'] ?? '') ?>"
                /></span>
              </div>
            </div>

            <!-- Study Leave -->
            <div class="mb-3">
              <p class="text-xxs font-semibold mb-1">In case of Study Leave:</p>
              <div class="ml-3 space-y-1">
                <label class="checkbox-label" for="sl_md">
                  <input type="checkbox" id="sl_md" name="study_type" <?= (!empty($details['section6b']['study']['masters']) ? 'checked' : '') ?> disabled /><span
                    class="ml-1 text-xxs"
                    >Completion of Master's Degree</span
                  >
                </label>
                <label class="checkbox-label" for="sl_bar">
                  <input type="checkbox" id="sl_bar" name="study_type" <?= (!empty($details['section6b']['study']['bar']) ? 'checked' : '') ?> disabled /><span
                    class="ml-1 text-xxs"
                    >BAR/Board Examination Review</span
                  >
                </label>
                <div class="text-xxs">
                  Other purpose:
                  <input
                    type="text"
                    id="study_other_input"
                    class="form-underline w-3/4 ml-1 p-0 text-xs"
                    value="<?= htmlspecialchars($details['section6b']['study']['other'] ?? '') ?>"
                  />
                </div>
              </div>
            </div>

            <!-- Others (Monetization/Terminal Leave) -->
            <div>
              <p class="text-xxs font-semibold mb-1">Others:</p>
              <div class="ml-3 space-y-1">
                <label class="checkbox-label" for="mlc">
                  <input type="checkbox" id="mlc" name="other_type" <?= (!empty($details['section6b']['others']['mlc']) ? 'checked' : '') ?> disabled /><span
                    class="ml-1 text-xxs"
                    >Monetization of Leave Credits</span
                  >
                </label>
                <label class="checkbox-label" for="tl">
                  <input type="checkbox" id="tl" name="other_type" <?= (!empty($details['section6b']['others']['tl']) ? 'checked' : '') ?> disabled /><span
                    class="ml-1 text-xxs"
                    >Terminal Leave</span
                  >
                </label>
              </div>
            </div>
          </div>
        </div>

          <div class="flex">
          <!-- 6.C NUMBER OF WORKING DAYS APPLIED FOR -->
          <div
            class="w-2/4 p-2 form-line border-t border-black border-r border-black"
          >
          <!-- (HR section moved to the dedicated Section 7 block below) -->
            <div class="font-bold mb-1">6.C NUMBER OF WORKING DAYS APPLIED FOR</div>
            <!-- Line for number of days -->
            <input
              type="text"
              id="num_working_days"
              class="form-underline w-3/4 p-0 text-center text-sm mb-2"
              value="<?= htmlspecialchars($details['num_working_days'] ?? '') ?>"
              readonly
            />
            <div class="font-bold mb-1">INCLUSIVE DATES</div>
            <!-- Line for inclusive dates -->
            <input
              type="text"
              id="inclusive_dates"
              class="form-underline w-3/4 p-0 text-center text-sm"
              value="<?= htmlspecialchars($leave['dates'] ?? ($details['inclusive_dates'] ?? '')) ?>"
              readonly
            />
          </div>

          <!-- 6.D COMMUTATION -->
          <div class="w-2/4 p-2 form-line border-t border-black">
            <div class="font-bold mb-3">6.D COMMUTATION</div>
            <div class="space-y-2 text-sm">
              <label class="checkbox-label" for="comm_not_req">
                <input
                  type="radio"
                  id="comm_not_req"
                  name="commutation"
                  value="Not Requested"
                  class="mt-0.5"
                  <?= (($details['commutation'] ?? '') === 'Not Requested' ? 'checked' : '') ?> disabled
                /><span class="ml-1">Not Requested</span>
              </label>
              <label class="checkbox-label" for="comm_req">
                <input
                  type="radio"
                  id="comm_req"
                  name="commutation"
                  value="Requested"
                  class="mt-0.5"
                  <?= (($details['commutation'] ?? '') === 'Requested' ? 'checked' : '') ?> disabled
                /><span class="ml-1">Requested</span>
              </label>
            </div>
            <!-- Signature of Applicant -->
            <div class="mt-6 text-center pt-2 text-xs">
              <div id="signature_container" class="w-full" style="text-align:center;">
                <?php if (!empty($leave['signature_path'])): ?>
                  <img src="/capstone/<?= ltrim($leave['signature_path'], '/') ?>" alt="Signature" style="max-height:60px; object-fit:contain; display:block; margin:0 auto;" />
                <?php else: ?>
                  <input type="text" id="signature_text" class="form-underline w-full text-center text-sm" value="" readonly />
                <?php endif; ?>
              </div>
              <p class="mt-0.5 font-normal">(Signature of Applicant)</p>
            </div>
          </div>
        </div>
      </div>

      <!-- 7. DETAILS OF ACTION ON APPLICATION (FIXED to match reference image) -->
      <div class="text-xs form-box mt-3">
        <div class="text-center font-bold form-line p-1">
          7. DETAILS OF ACTION ON APPLICATION
        </div>

        <div class="flex form-line">
          <!-- 7.A Certification of Leave Credits -->
          <div class="w-1/2 p-2 border-r border-black">
            <div class="font-bold mb-1">7.A CERTIFICATION OF LEAVE CREDITS</div>
            <p class="text-sm mb-2">
              &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;As
              of
              <input
                type="text"
                class="form-underline inline-block w-2/5 text-center p-0 h-4 ml-1"
                value="<?= htmlspecialchars($leave['certification_date'] ?? '') ?>"
              />
            </p>

            <!-- Leave Credit Table (Fixed with explicit borders) -->
            <div class="border border-black mt-2">
              <!-- Header Row -->
              <div class="flex font-bold text-xxs">
                <div class="w-1/3 p-1"></div>
                <div class="w-1/3 p-1 text-center border-l border-black">
                  Vacation Leave
                </div>
                <div class="w-1/3 p-1 text-center border-l border-black">
                  Sick Leave
                </div>
              </div>
              <!-- Total Earned Row -->
              <div class="flex text-xxs border-t border-black">
                <div
                  class="w-1/3 p-1 font-semibold text-right border-r border-black"
                >
                  Total Earned
                </div>
                <input
                  type="text"
                  class="w-1/3 p-1 text-center border-r border-black"
                  value="<?= htmlspecialchars($leave['vl_total_earned'] ?? '') ?>"
                />
                <input
                  type="text"
                  class="w-1/3 p-1 text-center"
                  style="border: none; outline: none; padding: 1px"
                  value="<?= htmlspecialchars($leave['sl_total_earned'] ?? '') ?>"
                />
              </div>
              <!-- Less this application Row -->
              <div class="flex text-xxs border-t border-black">
                <div
                  class="w-1/3 p-1 font-semibold text-right border-r border-black"
                >
                  Less this application
                </div>
                <input
                  type="text"
                  class="w-1/3 p-1 text-center border-r border-black"
                  value="<?= htmlspecialchars($leave['vl_less_this_application'] ?? '') ?>"
                />
                <input
                  type="text"
                  class="w-1/3 p-1 text-center"
                  style="border: none; outline: none; padding: 1px"
                  value="<?= htmlspecialchars($leave['sl_less_this_application'] ?? '') ?>"
                />
              </div>
              <!-- Balance Row -->
              <div class="flex text-xxs border-t border-black">
                <div
                  class="w-1/3 p-1 font-semibold text-right border-r border-black"
                >
                  Balance
                </div>
                <input
                  type="text"
                  class="w-1/3 p-1 text-center border-r border-black"
                  value="<?= htmlspecialchars($leave['vl_balance'] ?? '') ?>"
                />
                <input
                  type="text"
                  class="w-1/3 p-1 text-center"
                  style="border: none; outline: none; padding: 1px"
                  value="<?= htmlspecialchars($leave['sl_balance'] ?? '') ?>"
                />
              </div>
            </div>

            <?php $hr = $details['hr'] ?? null; $s = $hr['section7'] ?? []; $hsigs = $hr['signatures'] ?? []; ?>
            <div class="mt-8 text-center text-xxs font-semibold pt-2">
              <?php $certSig = $hsigs['certifier'] ?? ($hsigs['7a'] ?? null); ?>
                <div style="position:relative; min-height:56px;">
                  <?php if (!empty($certSig)): ?>
                    <img src="/capstone/<?= ltrim($certSig, '/') ?>" alt="Certifier sig" style="position:absolute; left:50%; transform:translateX(-50%); bottom:28px; max-height:40px; pointer-events:none; z-index:2;" />
                  <?php endif; ?>
                  <input type="text" class="form-underline w-3/4 text-center text-sm" style="padding-top:28px;" value="<?= htmlspecialchars($s['certifier_name'] ?? $s['authorized_officer'] ?? $s['authorized_officer_7a'] ?? $leave['authorized_officer'] ?? '') ?>" readonly />
                </div>
              <p class="mt-0.5 font-normal">(Authorized Officer)</p>
            </div>
          </div>

          <!-- 7.B RECOMMENDATION -->
          <div class="w-1/2 p-2">
            <div class="font-bold mb-3">7.B RECOMMENDATION</div>
            <div class="space-y-2 text-sm">
              <label class="checkbox-label" for="rec_appr">
                <input
                  type="checkbox"
                  id="rec_appr"
                  name="recommendation"
                  <?= (field($leave, 'recommendation') == 'For approval' ? 'checked' : '') ?> disabled
                /><span class="ml-1">For approval</span>
              </label>
              <label class="checkbox-label" for="rec_disappr">
                <input
                  type="checkbox"
                  id="rec_disappr"
                  name="recommendation"
                  class="mt-0.5"
                  <?= (field($leave, 'recommendation') == 'For disapproval' ? 'checked' : '') ?> disabled
                /><span class="ml-1">For disapproval due to</span>
              </label>
            </div>
            <!-- Multiple lines for disapproval reason -->
            <div class="ml-5 mt-1 space-y-1">
              <input
                type="text"
                class="form-underline w-full p-0 text-xs"
                placeholder=""
                value="<?= htmlspecialchars($leave['disapproval_reason1'] ?? '') ?>"
              />
              <input
                type="text"
                class="form-underline w-full p-0 text-xs"
                placeholder=""
                value="<?= htmlspecialchars($leave['disapproval_reason2'] ?? '') ?>"
              />
              <input
                type="text"
                class="form-underline w-full p-0 text-xs"
                placeholder=""
                value="<?= htmlspecialchars($leave['disapproval_reason3'] ?? '') ?>"
              />
            </div>

            <div class="mt-12 text-center text-xs font-semibold pt-2">
              <div style="position:relative; min-height:56px;">
                <?php if (!empty($hsigs['7b'])): ?>
                  <img src="/capstone/<?= ltrim($hsigs['7b'], '/') ?>" alt="7B sig" style="position:absolute; left:50%; transform:translateX(-50%); bottom:28px; max-height:40px; pointer-events:none; z-index:2;" />
                <?php endif; ?>
                <input type="text" class="form-underline w-3/4 text-center text-sm" style="padding-top:28px;" value="<?= htmlspecialchars($s['authorized_officer_7b'] ?? $leave['authorized_officer_recommendation'] ?? '') ?>" readonly />
              </div>
              <p class="mt-0.5 font-normal">(Authorized Officer)</p>
            </div>
          </div>
        </div>

        <div class="flex form-line border-t border-white">
          <!-- 7.C APPROVED FOR -->
          <div class="w-1/2 p-2 border-r border-white">
            <div class="font-bold mb-3">7.C APPROVED FOR:</div>
            <div class="space-y-2 text-sm">
              <p class="mb-1 flex items-center">
                <input
                  type="text"
                  class="form-underline w-8 text-center mr-1"
                  placeholder="___"
                  value="<?= htmlspecialchars($leave['approved_days_with_pay'] ?? '') ?>"
                />
                days with pay
              </p>
              <p class="mb-1 flex items-center">
                <input
                  type="text"
                  class="form-underline w-8 text-center mr-1"
                  placeholder="___"
                  value="<?= htmlspecialchars($leave['approved_days_without_pay'] ?? '') ?>"
                />
                days without pay
              </p>
              <p class="mb-1 flex items-center">
                <input
                  type="text"
                  class="form-underline w-8 text-center mr-1"
                  placeholder="___"
                  value="<?= htmlspecialchars($leave['approved_others'] ?? '') ?>"
                />
                others (Specify)
              </p>
            </div>
          </div>

          <!-- 7.D DISAPPROVED DUE TO -->
          <div class="w-1/2 p-2">
            <div class="font-bold mb-3">7.D DISAPPROVED DUE TO:</div>
            <input
              type="text"
              class="form-underline w-full p-0 text-sm mb-2"
              placeholder=""
              value="<?= htmlspecialchars($leave['disapproved_reason'] ?? '') ?>"
            />
            <input
              type="text"
              class="form-underline w-full p-0 text-sm mb-2"
              placeholder=""
            />
            <input
              type="text"
              class="form-underline w-full p-0 text-sm"
              placeholder=""
            />
          </div>
        </div>
        <div class="mt-4 text-center text-xs font-semibold p-2">
          <?php $finalSig = $hsigs['final'] ?? ($hsigs['authorized'] ?? null); ?>
          <div style="position:relative; min-height:56px;">
            <?php if (!empty($finalSig)): ?>
              <img src="/capstone/<?= ltrim($finalSig, '/') ?>" alt="final sig" style="position:absolute; left:50%; transform:translateX(-50%); bottom:28px; max-height:40px; pointer-events:none; z-index:2;" />
            <?php endif; ?>
            <input type="text" class="form-underline w-1/4 text-center text-sm" style="padding-top:28px;" value="<?= htmlspecialchars($s['final_official'] ?? $leave['authorized_official'] ?? 'Mayor Noel Bitrics Luistro') ?>" readonly />
          </div>
          <p class="mt-0.5 font-normal">(Authorized Official)</p>
        </div>
      </div>

  <!-- Continue with the rest of the form: 6. DETAILS OF APPLICATION, all checkboxes, radios, grouped fields, etc. -->
  <!-- For each checkbox/radio, use: <input type="checkbox" <?= ($request['leave_type'] == 'Vacation Leave' ? 'checked' : '') ?> disabled /> -->
  <!-- For each text field, use: <input type="text" value="<?= htmlspecialchars($request['fieldname'] ?? '') ?>" readonly /> -->
  <!-- Repeat the structure and layout from employee/civil form.html for all sections. -->

  <!-- Print Button -->
  <div class="mt-4 text-center">
    <button onclick="window.print()" class="no-print bg-blue-500 text-white px-6 py-2 rounded-lg shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-opacity-75">Print Form</button>
    <?php if (isset($_GET['live']) && $_GET['live'] === '1'): ?>
      <button onclick="doneAction()" class="no-print bg-gray-600 text-white px-4 py-2 rounded-lg shadow-md ml-3 hover:bg-gray-700">Done</button>
    <?php endif; ?>
  </div>
</div>
</body>
<script>
  // Live-refresh when opened with ?live=1 — polls leave requests and reloads if details changed
  const initialDetails = <?= json_encode($details) ?>;
  const leaveId = <?= json_encode($leave_id) ?>;
  (function(){
    try{
      const params = new URLSearchParams(window.location.search);
      if (params.get('live') === '1'){
        setInterval(async ()=>{
          try{
            const res = await fetch('/capstone/api/get_leave_requests.php');
            const js = await res.json();
            if(js && js.success && Array.isArray(js.data)){
              const row = js.data.find(r => String(r.id) === String(leaveId));
              if(row){
                let newDetails = row.details;
                if (typeof newDetails === 'string'){
                  try{ newDetails = JSON.parse(newDetails); }catch(e){}
                }
                if (JSON.stringify(newDetails) !== JSON.stringify(initialDetails)){
                  location.reload();
                }
              }
            }
          }catch(e){}
        }, 2500);
      }
    }catch(e){}
  })();

  function doneAction(){
    try{
      // If this window was opened from the leave-request page, close it and focus the opener
      if (window.opener && !window.opener.closed){
        try{ window.opener.focus(); }catch(e){}
        window.close();
        return;
      }
    }catch(e){}
    // Otherwise navigate back to the dept head leave-request listing
    window.location.href = '/capstone/dept_head/leave-request.html';
  }
</script>
</html>