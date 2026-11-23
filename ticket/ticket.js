document.addEventListener('DOMContentLoaded', () => {
  const step1 = document.getElementById('step1');
  const step2 = document.getElementById('step2');

  if (step1 && step2) {
    step1.addEventListener('submit', (event) => {
      event.preventDefault();
      const code = document.getElementById('fallback_code').value.trim();
      if (!/^\d{4}$/.test(code)) {
        alert('Please enter your 4-digit fallback code.');
        return;
      }
      document.getElementById('hidden_fallback_code').value = code;
      step1.classList.add('hidden');
      step2.classList.remove('hidden');
      document.getElementById('first_name').focus();
    });

    step2.addEventListener('submit', () => {
      const button = step2.querySelector('button[type=submit]');
      button.disabled = true;
      button.innerHTML = '<span class="loader loader-sm"></span>Please wait…';
    });
  }

  const sendState = document.getElementById('sendState');
  if (!sendState) {
    return;
  }

  const showError = (message) => {
    sendState.replaceChildren();
    const paragraph = document.createElement('p');
    paragraph.className = 'text-red-600 text-center';
    paragraph.textContent = message;
    sendState.appendChild(paragraph);
  };

  fetch(sendState.dataset.endpoint, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'X-CSRF-Token': sendState.dataset.csrf, Accept: 'application/json' },
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        sendState.classList.add('hidden');
        document.getElementById('sendSuccess').classList.remove('hidden');
      } else {
        showError(data.message || 'The email could not be sent.');
      }
    })
    .catch(() => showError('The email could not be sent. Please try again later.'));
});
