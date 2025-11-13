export function ScannerReader(containerId, status, fingerprintFile = '../server/fingerprint.bmp') {
   const container = document.getElementById(containerId);
if (!container) return;

container.textContent = '';

container.style.position = 'relative';
container.style.overflow = 'hidden';
container.style.borderRadius = '8px';
container.style.backgroundSize = 'cover';
container.style.backgroundPosition = 'center';

function createLineScan() {
    const layer1Gap = 150;  // yellow bounding margin
    const layer2Gap = 50;   // blue bounding margin
    const lineHeight = 4;
    const speed = 2;

    // Base fingerprint
    const fpBase = document.createElement('img');
    fpBase.src = './icon_svg/fingerprint_dummy.svg';
    fpBase.style.position = 'absolute';
    fpBase.style.top = '0';
    fpBase.style.left = '0';
    fpBase.style.width = '100%';
    fpBase.style.height = '100%';
    fpBase.style.opacity = '0.4';
    fpBase.style.zIndex = '100';
    container.appendChild(fpBase);

    // Layer 1 (semi-green overlay)
    const fpLayer1 = document.createElement('img');
    fpLayer1.src = './icon_svg/fingerprint_dummy_green.svg';
    fpLayer1.style.position = 'absolute';
    fpLayer1.style.top = '0';
    fpLayer1.style.left = '0';
    fpLayer1.style.width = '100%';
    fpLayer1.style.height = '100%';
    fpLayer1.style.opacity = '0.2';
    fpLayer1.style.zIndex = '3';
    container.appendChild(fpLayer1);

    // Layer 2 (full opacity overlay)
    const fpLayer2 = document.createElement('img');
    fpLayer2.src = './icon_svg/fingerprint_dummy_green.svg';
    fpLayer2.style.position = 'absolute';
    fpLayer2.style.top = '0';
    fpLayer2.style.left = '0';
    fpLayer2.style.width = '100%';
    fpLayer2.style.height = '100%';
    fpLayer2.style.opacity = '0.9';
    fpLayer2.style.zIndex = '1';
    container.appendChild(fpLayer2);

    // Layer 1 (yellow) bounding boxes
    const layer1Upper = document.createElement('div');
    layer1Upper.style.position = 'absolute';
    layer1Upper.style.top = '0';
    layer1Upper.style.left = '0';
    layer1Upper.style.width = '100%';
    layer1Upper.style.height = '0';
    layer1Upper.style.background = 'white';
    layer1Upper.style.zIndex = '4';
    container.appendChild(layer1Upper);

    const layer1Lower = document.createElement('div');
    layer1Lower.style.position = 'absolute';
    layer1Lower.style.left = '0';
    layer1Lower.style.width = '100%';
    layer1Lower.style.height = '0';
    layer1Lower.style.background = 'white';
    layer1Lower.style.zIndex = '4';
    container.appendChild(layer1Lower);

    // Layer 2 (blue) bounding boxes
    const layer2Upper = document.createElement('div');
    layer2Upper.style.position = 'absolute';
    layer2Upper.style.top = '0';
    layer2Upper.style.left = '0';
    layer2Upper.style.width = '100%';
    layer2Upper.style.height = '0';
    layer2Upper.style.background = 'white';
    layer2Upper.style.zIndex = '2';
    container.appendChild(layer2Upper);

    const layer2Lower = document.createElement('div');
    layer2Lower.style.position = 'absolute';
    layer2Lower.style.left = '0';
    layer2Lower.style.width = '100%';
    layer2Lower.style.height = '0';
    layer2Lower.style.background = 'white';
    layer2Lower.style.zIndex = '2';
    container.appendChild(layer2Lower);

    // Moving reference lines
    const movingLine1 = document.createElement('div');
    movingLine1.style.position = 'absolute';
    movingLine1.style.left = '0';
    movingLine1.style.width = '100%';
    movingLine1.style.height = `${lineHeight}px`;
    movingLine1.style.background = 'limegreen';
    movingLine1.style.zIndex = '5';
    container.appendChild(movingLine1);

    const movingLine2 = document.createElement('div');
    movingLine2.style.position = 'absolute';
    movingLine2.style.left = '0';
    movingLine2.style.width = '100%';
    movingLine2.style.height = `${lineHeight}px`;
    movingLine2.style.background = 'limegreen';
    movingLine2.style.zIndex = '5';
    container.appendChild(movingLine2);

    let pos1 = 0;
    let pos2 = 0; 
    let dir1 = 1;
    let dir2 = 1;

    const frame = () => {
        // Update pos1
        pos1 += speed * dir1;
        if (pos1 >= container.offsetHeight) dir1 = -1;
        if (pos1 <= 0) dir1 = 1;

        // Update pos2
        pos2 += speed * dir2;
        if (pos2 >= container.offsetHeight) dir2 = -1;
        if (pos2 <= 0) dir2 = 1;

        // Move visual lines
        movingLine1.style.top = `${pos1}px`;
        movingLine2.style.top = `${pos2}px`;

        // Layer1 bounding (yellow)
        layer1Upper.style.height = `${pos1 - layer1Gap}px`;
        layer1Lower.style.top = `${pos1 + layer1Gap}px`;
        layer1Lower.style.height =
            `${container.offsetHeight - (pos1 + layer1Gap)}px`;

        // Layer2 bounding (blue)
        layer2Upper.style.height = `${pos2 - layer2Gap}px`;
        layer2Lower.style.top = `${pos2 + layer2Gap}px`;
        layer2Lower.style.height =
            `${container.offsetHeight - (pos2 + layer2Gap)}px`;

        requestAnimationFrame(frame);
    };

    frame();
}

function activateAnimation(container) {
    const duration = 1000;
    const phaseTime = duration / 5;

    // ✅ Remove old layers
    container.querySelectorAll('.fp-layer').forEach(el => el.remove());

    // ✅ Create fingerprint layers
    const fp50  = document.createElement('img');
    const fp75  = document.createElement('img');
    const fp100 = document.createElement('img');

    fp50.src  = './icon_svg/fingerprint_50.svg';
    fp75.src  = './icon_svg/fingerprint_75.svg';
    fp100.src = './icon_svg/fingerprint_100.svg';

    // ✅ Bottom → top
    const layers = [
        { img: fp50,  z: 1 },  // bottom
        { img: fp75,  z: 2 },  // middle
        { img: fp100, z: 3 }   // top
    ];

    // ✅ Apply image properties
    layers.forEach(({ img, z }) => {
        img.classList.add('fp-layer');
        img.style.position = 'absolute';
        img.style.top = '0';
        img.style.left = '0';
        img.style.width = '100%';
        img.style.height = '100%';
        img.style.opacity = '0';
        img.style.pointerEvents = 'none';
        img.style.transition = 'opacity 0.8s linear';

        img.style.zIndex = z;  // ✅ correct stacking order

        container.appendChild(img);
    });

    // ✅ Ensure images load before animation
    Promise.all(layers.map(o => o.img.decode())).then(() => {

        void container.offsetHeight; // reflow

        // ✅ Phase 1
        setTimeout(() => {
            fp50.style.opacity  = '0.50';
            fp75.style.opacity  = '0.25';
            fp100.style.opacity = '0.10';
        }, 10);

        // ✅ Phase 2
        setTimeout(() => {
            fp50.style.opacity  = '0.75';
            fp75.style.opacity  = '0.50';
            fp100.style.opacity = '0.25';
        }, phaseTime * 1);

        // ✅ Phase 3
        setTimeout(() => {
            fp50.style.opacity  = '1.00';
            fp75.style.opacity  = '0.75';
            fp100.style.opacity = '0.50';
        }, phaseTime * 2);

        // ✅ Phase 4
        setTimeout(() => {
            fp50.style.opacity  = '1.00';
            fp75.style.opacity  = '1.00';
            fp100.style.opacity = '0.75';
        }, phaseTime * 3);

        // ✅ Phase 5
        setTimeout(() => {
            fp50.style.opacity  = '1.00';
            fp75.style.opacity  = '1.00';
            fp100.style.opacity = '1.00';
        }, phaseTime * 4);
    });
}
    // State logic
    switch (status.toLowerCase()) {
        case 'idle':
            container.style.backgroundImage = "";
            container.style.backgroundColor = 'white';
            container.style.backgroundRepeat = 'no-repeat';
            container.style.backgroundSize = 'contain';
            container.style.backgroundPosition = 'center';
            createLineScan();
            break;

        case 'activated':
            activateAnimation(container);
            break;

        case 'display':
            container.style.backgroundImage = "url('../../server/fingerprint.bmp')";
            container.style.backgroundRepeat = 'no-repeat';
            container.style.backgroundSize = 'contain';
            container.style.backgroundPosition = 'center';
            container.style.opacity = '1';
            break;

        case 'disconnected':
        container.style.backgroundImage = '';
        container.style.backgroundColor = 'white'; 
        container.innerHTML = ''; 
        break;

        default:
            container.style.backgroundImage = '';
            container.style.backgroundColor = 'white'; 
            container.innerHTML = ''; 
            break;
    }
}
