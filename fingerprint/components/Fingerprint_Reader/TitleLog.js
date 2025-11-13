export function TitleLog(text) {
    const container = document.createElement('div');
    container.className = 'title-log';
    container.innerText = text;
    container.style.fontFamily = 'Poppins, sans-serif';
    container.style.fontSize = '1.2rem';
    container.style.marginTop = '20px';
    container.style.textAlign = 'center';
    return container;
}
