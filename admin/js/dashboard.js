document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-open]').forEach((button) => {
    button.addEventListener('click', () => {
      document.getElementById(button.dataset.open).classList.remove('hidden');
    });
  });

  document.querySelectorAll('[data-close]').forEach((button) => {
    button.addEventListener('click', () => {
      button.closest('.overlay').classList.add('hidden');
    });
  });

  const form = document.getElementById('guestAddForm');
  if (!form) {
    return;
  }

  const result = document.getElementById('guestAddResult');
  const preview = document.getElementById('ticketPreview');
  const image = document.getElementById('ticketImage');
  const fallbackCode = document.getElementById('fallbackCode');
  const download = document.getElementById('ticketDownload');

  const showResult = (message, ok) => {
    result.textContent = message;
    result.className = 'mt-4 text-center ' + (ok ? 'text-green-600' : 'text-red-600');
  };

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    preview.classList.add('hidden');

    try {
      const response = await fetch('add_guest.php', {
        method: 'POST',
        body: new FormData(form),
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
      });
      const data = await response.json();

      if (!data.success) {
        showResult(data.message || 'The guest could not be added.', false);
        return;
      }

      showResult('Guest added.', true);
      image.src = data.ticket_image;
      fallbackCode.textContent = data.fallback_code;
      download.href = data.ticket_image;
      download.download = `ticket_${data.fallback_code}.png`;
      preview.classList.remove('hidden');
      form.querySelectorAll('input[type=text], select').forEach((field) => { field.value = ''; });
    } catch (error) {
      showResult('Server error. Please try again.', false);
    }
  });
});
