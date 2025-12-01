import { FindEmployeeModal } from "./FindEmployeeModal.js";
import { RegisterResponseModal } from "./RegisterResponseModal.js";

export async function Register_Forms(isFingerReady, onRegister) {
    let isReady = isFingerReady;
    // ✅ Store references to value holders
    const fields = {};
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

    const mainContainer = document.createElement('div');
    mainContainer.style.cssText = `
        width: max(50vw, 600px);
        min-height: 38vh;
        background-color: white;
        border-radius: 10px;
        padding: 15px;
        margin: 20px;
        box-shadow: 0 3px 5px rgba(0,0,0,0.15);
        display: flex;
        flex-direction: column;
        font-family: 'Poppins', sans-serif;
        position: relative;
    `;

    // CONTENT WRAPPER
    const contentContainer = document.createElement('div');
    contentContainer.style.cssText = `
        display: flex;
        flex: 1;
        gap: 12px;
        overflow: hidden;
    `;

    // FORM COLUMN
    const formColumn = document.createElement('div');
    formColumn.style.cssText = `
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 6px;
    `;

    // ✅ Smaller holder boxes
    function createLabeledBox(label, value = 'N/A') {
        const container = document.createElement('div');
        container.style.cssText = `
            position: relative;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 10px 8px 6px 8px;
            margin-bottom: 6px;
        `;

        const labelEl = document.createElement('div');
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

        const valueEl = document.createElement('div');
        valueEl.textContent = value;
        valueEl.style.cssText = `
            font-size: 12px;
            color: #333;
            margin-top: 2px;
        `;

        container.appendChild(labelEl);
        container.appendChild(valueEl);

        return { container, valueEl };
    }

    // ✅ Create all rows and fields
    const row1 = document.createElement('div');
    row1.style.cssText = `display: flex; gap: 8px; margin-top: 10px;`;

    fields.lastname = createLabeledBox('Last Name');
    fields.lastname.container.style.width = '45%';
    fields.firstname = createLabeledBox('First Name');
    fields.firstname.container.style.width = '45%';
    fields.mi = createLabeledBox('M.I.');
    fields.mi.container.style.width = '10%';

    row1.appendChild(fields.lastname.container);
    row1.appendChild(fields.firstname.container);
    row1.appendChild(fields.mi.container);

    const row2 = document.createElement('div');
    row2.style.cssText = `display: flex; gap: 8px;`;

    fields.contact_no = createLabeledBox('Contact');
    fields.contact_no.container.style.width = '40%';
    fields.email = createLabeledBox('Email');
    fields.email.container.style.width = '60%';

    row2.appendChild(fields.contact_no.container);
    row2.appendChild(fields.email.container);

    const row3 = document.createElement('div');
    row3.style.cssText = `display: flex; gap: 8px;`;

    const row4 = document.createElement('div');
    row4.style.cssText = `display: flex; gap: 8px;`;

    fields.employee_id = createLabeledBox('Employee ID');
    fields.employee_id.container.style.width = '50%';
    fields.department = createLabeledBox('Department');
    fields.department.container.style.width = '50%';

    row4.appendChild(fields.employee_id.container);
    row4.appendChild(fields.department.container);

    const row5 = document.createElement('div');
    row5.style.cssText = `display: flex; gap: 8px;`;

    fields.position = createLabeledBox('Position');
    fields.position.container.style.width = '50%';
    fields.role = createLabeledBox('Role');
    fields.role.container.style.width = '50%';

    row5.appendChild(fields.position.container);
    row5.appendChild(fields.role.container);

    formColumn.appendChild(row1);
    formColumn.appendChild(row2);
    formColumn.appendChild(row3);
    formColumn.appendChild(row4);
    formColumn.appendChild(row5);

    contentContainer.appendChild(formColumn);

    // ✅ BUTTON ROW
    const buttonRow = document.createElement('div');
    buttonRow.style.cssText = `
        width: 100%;
        display: flex;
        margin-top: 12px;
    `;

    const fetchDetailsButton = document.createElement('button');
    const fetchDetailsIcon = document.createElement('i');
    fetchDetailsIcon.className = "fa-solid fa-file-arrow-down";
    fetchDetailsButton.textContent = "FETCH DETAILS";
    fetchDetailsButton.prepend(fetchDetailsIcon);
    fetchDetailsButton.style.cssText = `
        background-color: #f5f5f5;
        border: 1px solid #ccc;
        border-radius: 12px;
        padding: 10px 25px;
        font-size: 13px;
        cursor: pointer;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
        justify-content: flex-start;
    `;

    buttonRow.appendChild(fetchDetailsButton);

    // ✅ REGISTER ROW
    const registerRow = document.createElement('div');
    registerRow.style.cssText = `
        position: absolute;
        left: 50%;
        transform: translate(-50%, -50%);
        display: flex;
        justify-content: center;
        gap: 12px;
        bottom: -80px;
        width: auto;
    `;

    const registerButton = document.createElement('button');
    registerButton.textContent = "Register Details";

    // Base style
    registerButton.style.cssText = `
        border-radius: 12px;
        padding: 12px 32px;
        font-size: 16px;
        font-weight: 600;
        border: 1px solid ${isReady ? '#1e40af' : '#6b7280'};
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        transition: 0.2s ease;
        cursor: ${isReady ? 'pointer' : 'not-allowed'};
        background-color: ${isReady ? '#1d4ed8' : '#3c59aaad'};
        color: ${isReady ? 'white' : '#e5e7eb'};
    `;

    // Hover effect only if enabled
    if (isReady) {
        registerButton.addEventListener("mouseover", () => registerButton.style.backgroundColor = "#1e40af");
        registerButton.addEventListener("mouseout", () => registerButton.style.backgroundColor = "#1d4ed8");
    }

    // Click only if enabled
    registerButton.addEventListener("click", async () => {
    if (!mainContainer.isReady) return;  // ignore click if disabled

    // Collect employee_id from the form
    const employee_id = fields.employee_id.valueEl.textContent;
    if (!employee_id || employee_id === "N/A") {
        alert("Please select an employee first.");
        return;
    }

    try {
        // Use your callAPI helper to call the PHP script
        const result = await callAPI(
            "http://ec2-54-153-182-130.ap-southeast-2.compute.amazonaws.com/doel/fingerprint/services/register_user_data.php",
            { employee_id }  // POST data
        );

        if (result.success) {
            console.log("Fingerprint registered:", result);
           
            RegisterResponseModal(true, "Fingerprint successfully registered!");
            setTimeout(() => {
                resetForm();
            }, 2200)
           resetForm()


        } else {
            console.error("Registration failed:", result.message);
            
            
// Show failure modal
RegisterResponseModal(false, "Failed to register fingerprint.");
        }
    } catch (err) {
        console.error("Error calling register_user_data.php:", err);
       
        
// Show failure modal
RegisterResponseModal(false, "Failed to register fingerprint.");
    }

    // Disable button after click
    isReady = false;
    registerButton.style.backgroundColor = '#9ca3af';
    registerButton.style.color = '#e5e7eb';
    registerButton.style.cursor = 'not-allowed';

    // Optional callback
    if (onRegister) onRegister();
});


    registerRow.appendChild(registerButton);

    // ✅ Assemble main container
    mainContainer.appendChild(contentContainer);
    mainContainer.appendChild(buttonRow);
    mainContainer.appendChild(registerRow);

    // ✅ Function to fill the form dynamically
 mainContainer.isReady = isFingerReady;

// function updateRegisterButton() {
//     const employeeId = fields.employee_id.valueEl.textContent;
//     if (mainContainer.isReady && employeeId && employeeId !== "N/A") {
//         registerButton.style.cursor = 'pointer';
//         registerButton.style.backgroundColor = '#1d4ed8';
//         registerButton.style.color = 'white';
//         registerButton.disabled = false;
//     } else {
//         registerButton.style.cursor = 'not-allowed';
//         registerButton.style.backgroundColor = '#9ca3af';
//         registerButton.style.color = '#e5e7eb';
//         registerButton.disabled = true;
//     }
// }
function updateRegisterButton() {
    const employeeId = fields.employee_id?.valueEl?.textContent || null;
    console.log("Updating register button:", mainContainer.isReady, employeeId);

    // Only enable if form is ready and an employee is selected
    if (mainContainer.isReady && employeeId && employeeId !== "N/A") {
        registerButton.style.cursor = 'pointer';
        registerButton.style.backgroundColor = '#1d4ed8';
        registerButton.style.color = 'white';
        registerButton.disabled = false;
    } else {
        registerButton.style.cursor = 'not-allowed';
        registerButton.style.backgroundColor = '#9ca3af';
        registerButton.style.color = '#e5e7eb';
        registerButton.disabled = true;
    }
}

function fillForm(employeeDetails) {
    Object.keys(fields).forEach(key => {
        if (employeeDetails[key] !== undefined && employeeDetails[key] !== null) {
            fields[key].valueEl.textContent = employeeDetails[key];
        } else {
            fields[key].valueEl.textContent = 'N/A';
        }
    });

    updateRegisterButton(); // ✅ enable button if possible
}


    function resetForm() {
        // Reset all fields to "N/A"
      
            mainContainer.employeeDetails = null;
        
    Object.keys(fields).forEach(key => {
        fields[key].valueEl.textContent = 'N/A';
    });
    }
    // Reset stored employee object (if needed)
   
    // ✅ Add event listener for fetch button
    fetchDetailsButton.addEventListener("click", async () => {
        const modal = await FindEmployeeModal();
        document.body.appendChild(modal);

        const employeeDetails = await modal.onClose;
        if (!employeeDetails) {
            console.log("Modal closed without selecting a user.");
            return;
        }

        console.log("Employee details returned:", employeeDetails);

        mainContainer.employeeDetails = employeeDetails;
        fillForm(employeeDetails);
    });
mainContainer.fillForm = fillForm;

// Add data-field attributes for manual restoration
Object.keys(fields).forEach(key => {
    fields[key].valueEl.dataset.field = key;
});
    return mainContainer;
}

// ✅ Add fonts dynamically
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
