export async function RegisterResponseModal(isSuccess, String_Response) {
    // 1️⃣ Ensure Font Awesome is loaded
    if (!document.querySelector('link[href*="font-awesome"]')) {
        const faLink = document.createElement('link');
        faLink.rel = "stylesheet";
        faLink.href = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css";
        document.head.appendChild(faLink);

        await new Promise(resolve => {
            faLink.onload = resolve;
            faLink.onerror = resolve; // fallback
        });

        // Small delay to ensure styles applied
        await new Promise(resolve => setTimeout(resolve, 50));
    }

    // 2️⃣ Create overlay
    const overlay = document.createElement('div');
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        backdrop-filter: blur(4px);
        background-color: rgba(0,0,0,0.4);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    `;

    // 3️⃣ Modal container
    const modal = document.createElement('div');
    modal.style.cssText = `
        background-color: white;
        padding: 30px 40px;
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        gap: 15px;
        min-width: 300px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.2);
    `;

    // 4️⃣ Icon
    const icon = document.createElement('i');
    // Use solid icons for both success and failure
    icon.className = isSuccess ? "fa-solid fa-user-check" : "fa-solid fa-user-xmark";
    icon.style.cssText = `
        font-size: 64px;
        color: ${isSuccess ? '#22C55E' : '#EF4444'};
        margin-bottom: 10px;
    `;

    // 5️⃣ Message
    const message = document.createElement('div');
    message.textContent = String_Response;
    message.style.cssText = `
        font-size: 16px;
        color: #333;
        font-weight: 500;
    `;

    modal.appendChild(icon);
    modal.appendChild(message);
    overlay.appendChild(modal);
    document.body.appendChild(overlay);

    // 6️⃣ Auto-close
    setTimeout(() => overlay.remove(), 2000);

    return overlay;
}
