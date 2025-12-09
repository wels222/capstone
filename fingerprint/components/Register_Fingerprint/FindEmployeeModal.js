export async function FindEmployeeModal() {

    function getSQLTimestamp() {
        const now = new Date();
        return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')} ${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}:${String(now.getSeconds()).padStart(2,'0')}`;
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

     function updateLoadButtonStyle(btn) {
        if (btn.disabled) {
            btn.style.opacity = "0.8";
            btn.style.cursor = "not-allowed";
            btn.style.backgroundColor = "#7ba7d9";
        } else {
            btn.style.opacity = "1";
            btn.style.cursor = "pointer";
            btn.style.backgroundColor = "#007bff";
        }
    }

    let resolveOnClose;
    let searchLog = '';
    let searchStatus = '';
    let searchValue = '';
    let enableButton = false;

    const overlay = document.createElement('div');
    overlay.onClose = new Promise((resolve) => { resolveOnClose = resolve; });
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

    const modal = document.createElement('div');
    modal.style.cssText = `
        width: 50vw;
        height: 25vh;
        background-color: white;
        border-radius: 6px;
        padding: 10px;
        box-shadow: 0 3px 5px rgba(0,0,0,0.15);
        display: flex;
        flex-direction: column;
    `;

    const contentContainer = document.createElement('div');
    contentContainer.style.cssText = `
        display: flex;
        flex: 1;
        gap: 12px;
        overflow: hidden;
        flex-direction: column;
    `;

    const formColumn = document.createElement('div');
    formColumn.style.cssText = `
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 6px;
    `;

    function createFormBox(hint = '', value = '') {
        const container = document.createElement('div');
        container.style.cssText = `
            position: relative;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 10px 8px 6px 8px;
            margin-bottom: 6px;
            width: 100%;
            box-sizing: border-box;
            height: 50px
        `;

        const input = document.createElement('input');
        input.type = 'text';
        input.value = value;
        input.placeholder = hint;
        input.style.cssText = `
            width: 100%;
            border: none;
            outline: none;
            font-size: 12px;
            color: #333;
            background-color: transparent;
        `;

        input.addEventListener('focus', () => container.style.borderColor = '#007bff');
        input.addEventListener('blur', () => container.style.borderColor = '#ddd');

        container.appendChild(input);

        return {
            element: container,
            getValue: () => input.value.trim()
        };
    }
    
    const row1 = document.createElement('div');
    row1.style.cssText = `
        display: flex;
        gap: 8px;
        margin-top: 10px;
    `;

    const employeeBox = createFormBox("EMPLOYEE_ID / EMAIL ADDRESS");
    employeeBox.element.style.width = '70%';


    // --------------------------------------------------------------------
    // ✅ ✅ SEARCH LOGIC & UPDATE RESULTS (FIXED)
    // --------------------------------------------------------------------

    const searchButton = document.createElement('button');
    const searchButtonIcon = document.createElement('i');
    searchButtonIcon.className = "fa-solid fa-magnifying-glass";
    searchButton.textContent = " Search";
    searchButton.prepend(searchButtonIcon);

    searchButtonIcon.style.cssText = `margin-right: 8px`;
    searchButton.style.cssText = `
        display: block;
        margin: 0 auto 0 auto;
        padding: 0px 35px;
        font-size: 14px;
        font-weight: 500;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        background-color: #007bff;
        color: white;
        width:30%;
        height: 50px;  
    `;

    // ✅ ✅ FIXED FEEDBACK SYSTEM (Option A)
    function buildSearchFeedback(searchValue) {
        if (searchValue === "user_exists") { 
            return "User Already Registered!"; 
        }
        // Duplicate employee_id
        if (searchValue === "duplicate_id") {
            return "Invalid Duplicate Users";
        }

        // Empty, null, undefined
        if (!searchValue) {
            return "0 / User does not exist";
        }

        // Multiple matches (array)
        if (Array.isArray(searchValue)) {
            return `${searchValue.length} / ${searchValue.join(", ")}`;
        }

        // Only 1 result
        return `1 / ${searchValue}`;
    }

    const row2 = document.createElement('div');
    row2.style.cssText = `display: flex; gap: 8px;`;

    function createResultsContainer() {
        const resultsContainer = document.createElement('div');
        resultsContainer.style.cssText = `
            width: 55%;
            display: flex;
            flex-direction: column;
            font-size: 18px;
            color: #333;
            margin-top: 6px;
        `;
        resultsContainer.textContent = "Results: ";
        return resultsContainer;
    }

    const resultsContainer = createResultsContainer();


    searchButton.addEventListener("click", async () => {
        const search_string = employeeBox.getValue();
        if (!search_string) {
            alert("Please enter Employee ID or Email.");
            return;
        }

        const searchResult = await callAPI(
            `http://ec2-54-153-182-130.ap-southeast-2.compute.amazonaws.com/doel/fingerprint/services/register_search_user.php?search_string=${encodeURIComponent(search_string)}`
        );

        if (!searchResult) return;

        // Interpretation of server result
        searchLog = searchResult.log;
        searchStatus = searchResult.status;
        searchValue = searchResult.value;

        // ✅ Update the resultsContainer LIVE
        resultsContainer.textContent = "Results: " + buildSearchFeedback(searchValue);

        if (searchLog === "user_found" && searchStatus === "success" && searchValue && !Array.isArray(searchValue)) {
            loadButton.disabled = false;
        } else {
            loadButton.disabled = true;
        }
        updateLoadButtonStyle(loadButton);
    });


    row1.appendChild(employeeBox.element);
    row1.appendChild(searchButton);

    row2.appendChild(resultsContainer);


    // --------------------------------------------------------------------
    // LOAD BUTTON LOGIC (unchanged except small fix)
    // --------------------------------------------------------------------
    const buttonRow = document.createElement('div');
    buttonRow.style.cssText = `
        display: flex;
        align-items: center;
        position: relative;
        width: 100%;
        height: 40px;
    `;

    const loadButton = document.createElement('button');
    const loadIcon = document.createElement('i');
    loadIcon.className = "fa-solid fa-arrow-down-to-line";
    loadButton.textContent = "LOAD DATA";
    loadButton.append(loadIcon);
    
    loadButton.style.cssText = `
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
        
        loadButton.disabled = true;
        updateLoadButtonStyle(loadButton);
   


    loadButton.addEventListener("click", async () => {
        // Validation
        if (!(searchLog === "user_found" && searchStatus === "success" && searchValue && !Array.isArray(searchValue))) {
            alert("Invalid search state. Cannot load data.");
            return;
        }

        const employeeDetails = await callAPI(
            `http://ec2-54-153-182-130.ap-southeast-2.compute.amazonaws.com/doel/fingerprint/services/reader_get_details.php?employee_id=${encodeURIComponent(searchValue)}`
        );

        if (employeeDetails.success) {
            resolveOnClose(employeeDetails.data);
            overlay.remove();
            return;
        }
    });


    // Cancel Button
    if (!document.getElementById('attendance-modal-styles')) {
        const style = document.createElement('style');
        style.id = 'attendance-modal-styles';
        style.textContent = `
            .cancel-btn {
                background: none;
                border: none;
                color: red;
                cursor: pointer;
                font-size: 13px;
                position: absolute;
                right: 4px;
                top: 5px;
                transition: color 0.2s;
            }
            .cancel-btn:hover {
                color: darkred;
                text-decoration: underline;
            }
        `;
        document.head.appendChild(style);
    }

    const cancelButton = document.createElement('button');
    cancelButton.textContent = "CANCEL";
    cancelButton.className = 'cancel-btn';
    cancelButton.addEventListener("click", () => {
        overlay.remove();
        resolveOnClose();
    });

    buttonRow.appendChild(loadButton);
    buttonRow.appendChild(cancelButton);
    contentContainer.appendChild(row1);
    contentContainer.appendChild(row2);
    modal.appendChild(contentContainer);
    modal.appendChild(buttonRow);
    overlay.appendChild(modal);

    return overlay;
}


// Load CSS
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
