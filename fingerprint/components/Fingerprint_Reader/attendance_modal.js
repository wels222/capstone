export async function AttendanceModal(employeeDetails) {
  const data = employeeDetails.data;

  function getSQLTimestamp() {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(
      2,
      "0"
    )}-${String(now.getDate()).padStart(2, "0")} ${String(
      now.getHours()
    ).padStart(2, "0")}:${String(now.getMinutes()).padStart(2, "0")}:${String(
      now.getSeconds()
    ).padStart(2, "0")}`;
  }

  let resolveOnClose;

  // OVERLAY
  const overlay = document.createElement("div");
  overlay.onClose = new Promise((resolve) => {
    resolveOnClose = resolve;
  });
  overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background-color: rgba(0, 0, 0, 0.45);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9997;
        font-family: 'Poppins', sans-serif;
    `;

  // MODAL
  const modal = document.createElement("div");
  modal.style.cssText = `
        width: 50vw;
        height: 40vh;
        background-color: white;
        border-radius: 6px;
        padding: 10px;
        box-shadow: 0 3px 5px rgba(0,0,0,0.15);
        display: flex;
        flex-direction: column;
    `;

  // CONTENT WRAPPER
  const contentContainer = document.createElement("div");
  contentContainer.style.cssText = `
        display: flex;
        flex: 1;
        gap: 12px;
        overflow: hidden;
    `;

  // LEFT IMAGE COLUMN
  const imageColumn = document.createElement("div");
  imageColumn.style.cssText = `
        flex: 0 0 40%;
        display: flex;
        flex-direction: column;
        align-items: center;
    `;

  const imageContainer = document.createElement("div");
  imageContainer.style.cssText = `
    width: 100%;
    height: 87%;
    border: 1px solid #ccc;
    border-radius: 6px;
    background-color: #f3f3f3;
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 5px;
    position: relative;   /* needed for left to work */
    left: 5px;
    top: 10px;           /* moves container 5px right */
`;

  if (data.profile_picture) {
    const img = document.createElement("img");
    img.src = data.profile_picture;
    img.style.cssText = `
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 6px;
        `;
    imageContainer.appendChild(img);
  } else {
    const icon = document.createElement("i");
    icon.className = "fa-solid fa-image";
    icon.style.cssText = `
            font-size: 32px;
            color: #bbb;
        `;
    imageContainer.appendChild(icon);
  }

  // ✅ NO LABEL NOW
  imageColumn.appendChild(imageContainer);

  // RIGHT FORM COLUMN
  const formColumn = document.createElement("div");
  formColumn.style.cssText = `
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 6px;
    `;

  // ✅ Smaller holder boxes except image
  function createLabeledBox(label, value) {
    const container = document.createElement("div");
    container.style.cssText = `
            position: relative;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 10px 8px 6px 8px;   /* ✅ smaller height */
            margin-bottom: 6px;
        `;

    const labelEl = document.createElement("div");
    labelEl.textContent = label;
    labelEl.style.cssText = `
            position: absolute;
            top: -9px;
            left: 8px;
            background-color: white;
            padding: 0 4px;
            font-size: 10px;
            color: #666;
        `;

    const valueEl = document.createElement("div");
    valueEl.textContent = value || "N/A";
    valueEl.style.cssText = `
            font-size: 12px;
            color: #333;
            margin-top: 2px;
        `;

    container.appendChild(labelEl);
    container.appendChild(valueEl);
    return container;
  }

  // ROWS
  const row1 = document.createElement("div");
  row1.style.cssText = `
    display: flex;
    gap: 8px;
    margin-top: 10px;  /* added top offset */
`;

  const lastnameBox = createLabeledBox("Last Name", data.lastname);
  lastnameBox.style.width = "45%";

  const firstnameBox = createLabeledBox("First Name", data.firstname);
  firstnameBox.style.width = "45%";

  const miBox = createLabeledBox("M.I.", data.mi);
  miBox.style.width = "10%";

  row1.appendChild(lastnameBox);
  row1.appendChild(firstnameBox);
  row1.appendChild(miBox);

  const row2 = document.createElement("div");
  row2.style.cssText = `display: flex; gap: 8px;`;

  const contactBox = createLabeledBox("Contact", data.contact_no);
  contactBox.style.width = "40%";

  const emailBox = createLabeledBox("Email", data.email);
  emailBox.style.width = "60%";

  row2.appendChild(contactBox);
  row2.appendChild(emailBox);

  const row3 = document.createElement("div");
  row3.style.cssText = `display: flex; gap: 8px; align-items: center;`;

  // const statusBox = createLabeledBox('Status', '<status_value>');
  async function callAPI(url, postData = null) {
    try {
      const response = await fetch(url, {
        method: postData ? "POST" : "GET",
        headers: { "Content-Type": "application/json" },
        body: postData ? JSON.stringify(postData) : null,
      });
      return await response.json();
    } catch (err) {
      return { success: false, message: "Fetch error: " + err.message };
    }
  }

  // Fetch current attendance status ("in" or "out")
  // let statusValue = "loading...";
  // try {
  //     const attendanceResult = await callAPI(
  //         `http://localhost/capstone/fingerprint/services/attendance_get_status.php?employee_id=${encodeURIComponent(data.employee_id)}`
  //     );

  //     if (attendanceResult.success && attendanceResult.status) {
  //         statusValue = attendanceResult.status; // "in" or "out"
  //     } else {
  //         statusValue = "unknown";
  //         console.warn("Failed to fetch attendance status:", attendanceResult.message);
  //     }
  // } catch (err) {
  //     console.error("Error fetching attendance status:", err);
  //     statusValue = "error";
  // }
  let statusValue = "loading...";
  let enumValue;

  try {
    const attendanceResult = await callAPI(
      `http://localhost/capstone/fingerprint/services/attendance_get_status.php?employee_id=${encodeURIComponent(
        data.employee_id
      )}`
    );

    if (attendanceResult.success && attendanceResult.status) {
      statusValue = attendanceResult.status;
      console.log("fresh fetch: " + attendanceResult.status);
      // Assume `attendanceResult` is the API response
    } else {
      statusValue = "unknown";
      console.warn(
        "Failed to fetch attendance status:",
        attendanceResult.message
      );
    }
  } catch (err) {
    console.error("Error fetching attendance status:", err);
    statusValue = "error";
  }

  if (statusValue === "already") {
    // Standalone “Already Completed Attendance” modal
    const alreadyContainer = document.createElement("div");
    alreadyContainer.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 50vw;
        height: 40vh;
        background-color: rgba(255, 255, 255, 0.95);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        font-family: 'Poppins', sans-serif;
        text-align: center;
        padding: 10px;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    `;

    const icon = document.createElement("i");
    icon.className = "fa-solid fa-person-circle-check";
    icon.style.cssText = `
        font-size: 64px;
        color: #007bff;
        margin-bottom: 10px;
    `;

    const msg = document.createElement("div");
    msg.textContent = "Already completed attendance for this day!";
    msg.style.cssText = `
        font-size: 16px;
        font-weight: 500;
        color: #333;
    `;

    alreadyContainer.appendChild(icon);
    alreadyContainer.appendChild(msg);

    if (!overlay) {
      console.error("overlay not defined!");
    } else {
      overlay.appendChild(alreadyContainer);

      // Automatically remove modal after 1 second
      setTimeout(() => {
        overlay.remove();
        resolveOnClose();
      }, 3000);
    }
  }

  // Continue with normal attendance logic here if not "already"
  // e.g., render resultContainer like clock button logic

  //     const capitalizedStatus = statusValue.charAt(0).toUpperCase() + statusValue.slice(1);
  //     const statusBox = createLabeledBox('Status', timestampValue);
  //     statusBox.style.width = '45%';

  //     const timestampContainer = document.createElement('div');
  //     timestampContainer.style.cssText = `
  //         width: 55%;
  //         display: flex;
  //         flex-direction: column;
  //     `;

  //     const timestampValue = document.createElement('div');
  //     // timestampValue.textContent = timestamp;
  //     timestampValue.style.cssText = `
  //         font-size: 14px;
  //         color: #333;
  //         font-weight: bold;
  //     `;
  //     function getCurrentTimestamp() {
  //     const now = new Date();
  //     return `${String(now.getMonth() + 1).padStart(2, '0')}/${String(now.getDate()).padStart(2, '0')}/${now.getFullYear()} ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')}`;
  // }

  //     // timestampContainer.appendChild(timestampLabel);
  //     timestampContainer.appendChild(timestampValue);
  //     row3.appendChild(statusBox);
  //     row3.appendChild(timestampContainer);

  // // Async function to update timestamp continuously
  // async function updateTimestamp() {
  //     timestampValue.textContent = getCurrentTimestamp();
  //     // Update every second
  //     setTimeout(updateTimestamp, 1000);
  // }

  // // Start updating
  // updateTimestamp();
  const capitalizedStatus =
    statusValue.charAt(0).toUpperCase() + statusValue.slice(1);

  // Create the status box (initially empty)
  const statusBox = createLabeledBox("Status", "");
  statusBox.style.width = "45%";
  row3.appendChild(statusBox);

  // Timestamp container
  const timestampContainer = document.createElement("div");
  timestampContainer.style.cssText = `
    width: 55%;
    display: flex;
    flex-direction: column;
`;

  // Timestamp value div
  const timestampValue = document.createElement("div");
  timestampValue.style.cssText = `
    font-size: 14px;
    color: #333;
    font-weight: bold;
`;
  timestampContainer.appendChild(timestampValue);
  row3.appendChild(timestampContainer);

  // Function to get current timestamp string
  function getCurrentTimestamp() {
    const now = new Date();
    return `${String(now.getMonth() + 1).padStart(2, "0")}/${String(
      now.getDate()
    ).padStart(2, "0")}/${now.getFullYear()} ${String(now.getHours()).padStart(
      2,
      "0"
    )}:${String(now.getMinutes()).padStart(2, "0")}:${String(
      now.getSeconds()
    ).padStart(2, "0")}`;
  }

  // Function to calculate attendance enum status
  function calculateAttendanceStatus(statusValue, now) {
    const hours = now.getHours();
    const minutes = now.getMinutes();
    const time = hours * 60 + minutes; // total minutes

    let result = "";
    let color = "";

    if (statusValue === "in") {
      // Time In Status Ranges (minutes from midnight)
      // Present: <= 6:00 AM (360) treated as Present; 6:00 AM - 8:00 AM => 360 - 480
      // Late: 8:01 AM - 12:00 PM => 481 - 720
      // Undertime: 12:01 PM - 5:00 PM => 721 - 1020
      // Absent: after 5:00 PM => 1021+
      if (time < 360) {
        result = "Present";
        color = "green";
      } else if (time <= 480) {
        result = "Present";
        color = "green";
      } else if (time <= 720) {
        result = "Late";
        color = "orange";
      } else if (time <= 1020) {
        result = "Undertime";
        color = "orange";
      } else {
        result = "Absent";
        color = "red";
      }
    } else if (statusValue === "out") {
      // Time Out Status Ranges
      // Undertime: up to 4:59 PM => <= 1019
      // Out: 5:00 PM - 5:05 PM => 1020 - 1025 (and up to 5:59 PM considered Out)
      // Overtime: 6:00 PM onwards => >= 1080
      if (time <= 1019) {
        result = "Undertime";
        color = "orange";
      } else if (time >= 1020 && time <= 1025) {
        result = "Out";
        color = "green";
      } else if (time >= 1080) {
        result = "Overtime";
        color = "red";
      } else {
        result = "Out";
        color = "green";
      }
    } else {
      result = capitalizedStatus; // fallback
      color = "#333";
    }
    enumValue = result;
    return { text: result, color };
  }

  // Async function to update timestamp and status continuously
  async function updateTimestamp() {
    const now = new Date();
    timestampValue.textContent = getCurrentTimestamp();

    const { text, color } = calculateAttendanceStatus(statusValue, now);
    statusBox.querySelector("div:last-child").textContent = text; // update value inside labeled box
    statusBox.querySelector("div:last-child").style.color = color;

    // update every second
    setTimeout(updateTimestamp, 1000);
  }

  // Start updating
  updateTimestamp();
  const row4 = document.createElement("div");
  row4.style.cssText = `display: flex; gap: 8px;`;

  const employeeIdBox = createLabeledBox("Employee ID", data.employee_id);
  employeeIdBox.style.width = "50%";

  const departmentBox = createLabeledBox("Department", data.department);
  departmentBox.style.width = "50%";

  row4.appendChild(employeeIdBox);
  row4.appendChild(departmentBox);

  const row5 = document.createElement("div");
  row5.style.cssText = `display: flex; gap: 8px;`;

  const positionBox = createLabeledBox("Position", data.position);
  positionBox.style.width = "50%";

  const roleBox = createLabeledBox("Role", data.role);
  roleBox.style.width = "50%";

  row5.appendChild(positionBox);
  row5.appendChild(roleBox);

  formColumn.appendChild(row1);
  formColumn.appendChild(row2);
  formColumn.appendChild(row3);
  formColumn.appendChild(row4);
  formColumn.appendChild(row5);

  contentContainer.appendChild(imageColumn);
  contentContainer.appendChild(formColumn);

  // BUTTON ROW
  const buttonRow = document.createElement("div");
  buttonRow.style.cssText = `
        display: flex;
        align-items: center;
        margin-top: 0;
        position: relative;
        width: 100%;
        height: 40px;
    `;

  // CLOCK BUTTON WIDER & TALLER - positioned higher
  const clockButton = document.createElement("button");
  // clockButton.innerHTML = `<i class="fa-solid fa-clock"></i> ('Status', "Time-" + capitalizedStatus); `;

  // Create the icon
  const clockIcon = document.createElement("i");
  clockIcon.className = "fa-solid fa-clock";

  // Set the text after the icon
  clockButton.textContent = "Time-" + capitalizedStatus;

  // Prepend the icon
  clockButton.prepend(clockIcon);

  clockButton.style.cssText = `
        background-color: #007bff;
        color: white;
        padding: 10px 30px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 13px;
        position: absolute;
        left: 50%;
        top: -10px;
        transform: translateX(-50%);
        font-weight: 500;
    `;

  clockButton.addEventListener("click", async () => {
    const full_timestamp = getSQLTimestamp(); // Use ISO string for SQL timestamp

    // Create result container
    const resultContainer = document.createElement("div");
    resultContainer.style.cssText = `
        position: fixed;  /* fixed to screen */
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);  /* truly center it */
        width: 50vw;
        height: 40vh;
        background-color: rgba(255, 255, 255, 0.95);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        font-family: 'Poppins', sans-serif;
        text-align: center;
        padding: 10px;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    `;

    overlay.appendChild(resultContainer);

    try {
      const response = await fetch(
        `http://localhost/capstone/fingerprint/services/attendance_post_status.php?employee_id=${encodeURIComponent(
          data.employee_id
        )}&status=${encodeURIComponent(
          statusValue
        )}&timestamp=${encodeURIComponent(full_timestamp)}`
      );

      const result = await response.json();
      console.log("Attendance saved:", result);
      console.log("Clock button pressed!");

      // Create icon
      const request_status_icon = document.createElement("i");
      request_status_icon.className = result.success
        ? "fa-solid fa-circle-check"
        : "fa-solid fa-circle-xmark";
      request_status_icon.style.cssText = `
            font-size: 64px;
            color: ${result.success ? "green" : "red"};
            margin-bottom: 10px;
        `;
      // Create message
      const msg = document.createElement("div");
      msg.innerHTML = result.success
        ? `Successful Check - ${capitalizedStatus}<br>at: <b>${full_timestamp}</b><br>status: ${enumValue}</b>`
        : `Error Check - ${capitalizedStatus}<br>Please Try Again`;
      console.log("enum value :" + enumValue + "status: " + statusValue);
      // Append to container
      resultContainer.appendChild(request_status_icon);
      resultContainer.appendChild(msg);
    } catch (err) {
      console.error("Request failed:", err);

      const request_status_icon = document.createElement("i");
      request_status_icon.className = "fa-solid fa-circle-xmark";
      request_status_icon.style.cssText = `
            font-size: 64px;
            color: red;
            margin-bottom: 10px;
        `;

      const msg = document.createElement("div");
      msg.innerHTML = `Error Check - ${capitalizedStatus}<br>Please Try Again`;

      resultContainer.appendChild(request_status_icon);
      resultContainer.appendChild(msg);
    }
    setTimeout(() => {
      overlay.remove();
      resolveOnClose(); // ✅ resolves modal close
    }, 2000);
  });

  // CANCEL BIGGER WITH UNDERLINE - positioned higher
  // Add CSS for hover effect if not already added
  if (!document.getElementById("attendance-modal-styles")) {
    const style = document.createElement("style");
    style.id = "attendance-modal-styles";
    style.textContent = `
        .cancel-btn {
            background: none;
            border: none;
            color: red;                  /* default red */
            text-decoration: none;       /* not underlined by default */
            cursor: pointer;
            font-size: 13px;
            position: absolute;
            right: 4px;
            top: 5px;
            transition: color 0.2s, text-decoration 0.2s;
        }
        .cancel-btn:hover {
            color: darkred;              /* darker red on hover */
            text-decoration: underline 1px;
        }
    `;
    document.head.appendChild(style);
  }

  // CREATE CANCEL BUTTON
  const cancelButton = document.createElement("button");
  cancelButton.textContent = "CANCEL";
  cancelButton.className = "cancel-btn";
  cancelButton.addEventListener("click", () => {
    overlay.remove();
    resolveOnClose(); // ✅ resolves modal close
  });

  // Append buttons to buttonRow
  buttonRow.appendChild(clockButton);
  buttonRow.appendChild(cancelButton);

  modal.appendChild(contentContainer);
  modal.appendChild(buttonRow);
  overlay.appendChild(modal);

  return overlay;
}

// FONT AWESOME
if (!document.querySelector('link[href*="font-awesome"]')) {
  const faLink = document.createElement("link");
  faLink.rel = "stylesheet";
  faLink.href =
    "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css";
  document.head.appendChild(faLink);
}

// POPPINS FONT
if (!document.querySelector('link[href*="poppins"]')) {
  const fontLink = document.createElement("link");
  fontLink.rel = "stylesheet";
  fontLink.href =
    "https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap";
  document.head.appendChild(fontLink);
}
