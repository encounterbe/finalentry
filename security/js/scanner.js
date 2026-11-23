document.addEventListener('DOMContentLoaded', () => {
  let scanner = null;
  let cameras = [];
  let cameraId = null;
  let flashOn = false;

  const overlay = document.getElementById('scannerOverlay');
  const qrForm = document.getElementById('qrForm');
  const qrInput = document.getElementById('qrCodeInput');

  async function stopScanner() {
    if (scanner && scanner.isScanning) {
      await scanner.stop().catch(() => {});
    }
  }

  async function startScanner() {
    try {
      cameras = await Html5Qrcode.getCameras();
    } catch (error) {
      alert('Camera access was denied.');
      return;
    }
    if (cameras.length === 0) {
      alert('No camera found.');
      return;
    }
    if (!cameraId) {
      const back = cameras.find((camera) => /back|rear|environment/i.test(camera.label));
      cameraId = (back || cameras[0]).id;
    }

    scanner = scanner || new Html5Qrcode('reader');
    scanner.start(
      { deviceId: { exact: cameraId } },
      { fps: 10, qrbox: 250 },
      async (decodedText) => {
        await stopScanner();
        overlay.classList.add('hidden');
        qrInput.value = decodedText;
        qrForm.submit();
      },
      () => {}
    ).catch((error) => alert('Could not start the camera: ' + error));
  }

  document.getElementById('openScannerBtn').addEventListener('click', () => {
    overlay.classList.remove('hidden');
    startScanner();
  });

  document.getElementById('closeScannerBtn').addEventListener('click', async () => {
    await stopScanner();
    overlay.classList.add('hidden');
  });

  document.getElementById('switchCameraBtn').addEventListener('click', async () => {
    if (cameras.length < 2) {
      alert('Only one camera available.');
      return;
    }
    const index = cameras.findIndex((camera) => camera.id === cameraId);
    cameraId = cameras[(index + 1) % cameras.length].id;
    await stopScanner();
    startScanner();
  });

  document.getElementById('toggleFlashBtn').addEventListener('click', async () => {
    if (!scanner || !scanner.isScanning) {
      return;
    }
    try {
      flashOn = !flashOn;
      await scanner.applyVideoConstraints({ advanced: [{ torch: flashOn }] });
    } catch (error) {
      flashOn = !flashOn;
      alert('Flash is not available on this device.');
    }
  });

  const result = document.getElementById('resultContainer');
  if (result && result.textContent.trim() !== '') {
    result.scrollIntoView({ behavior: 'smooth' });
  }
});
