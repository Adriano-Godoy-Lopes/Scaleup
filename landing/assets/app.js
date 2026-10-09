(function () {
  const market = document.body.dataset.market;

  function send(type, label) {
    const body = new FormData();
    body.append('type', type);
    body.append('label', label || '');
    body.append('market', market);
    if (navigator.sendBeacon) navigator.sendBeacon('api.php?action=event', body);
    else fetch('api.php?action=event', { method: 'POST', body, keepalive: true });
  }

  document.querySelectorAll('[data-cta]').forEach((el) => {
    if (el.dataset.cta === 'form_submit') return;
    el.addEventListener('click', () => send('cta_click', el.dataset.cta));
  });

  document.querySelectorAll('.menu a').forEach((a) =>
    a.addEventListener('click', () => document.getElementById('menu').classList.remove('open'))
  );

  const form = document.getElementById('lead-form');
  if (!form) return;
  const msg = document.getElementById('form-msg');
  let started = false;
  form.addEventListener('focusin', () => {
    if (!started) { started = true; send('form_start', 'lead_form'); }
  });

  form.addEventListener('submit', async (ev) => {
    ev.preventDefault();
    msg.className = 'form-msg';
    form.querySelectorAll('.invalid').forEach((el) => el.classList.remove('invalid'));
    const btn = form.querySelector('button[type=submit]');
    btn.disabled = true;
    try {
      const res = await fetch(form.action, { method: 'POST', body: new FormData(form) });
      const data = await res.json();
      if (data.ok) {
        msg.textContent = msg.dataset.ok;
        msg.classList.add('ok');
        form.reset();
      } else {
        (data.errors || []).forEach((n) => form.querySelector(`[name="${n}"]`)?.classList.add('invalid'));
        msg.textContent = msg.dataset.err;
        msg.classList.add('err');
      }
    } catch (e) {
      msg.textContent = msg.dataset.err;
      msg.classList.add('err');
    } finally {
      btn.disabled = false;
    }
  });
})();
