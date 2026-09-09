(function () {
  'use strict';

  var dialog = document.getElementById('bulk-user-token-dialog');
  var submit = document.getElementById('bulk-user-token-submit');
  if (!dialog || !submit) return;

  var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  var selectedUserId = null;

  function faNum(value) {
    return Number(value || 0).toLocaleString('fa-IR');
  }

  function setState(message) {
    var state = document.getElementById('bulk-user-token-state');
    if (!state) return;
    state.textContent = message || '';
    state.classList.toggle('hidden', !message);
  }

  function closeDialog() {
    if (typeof dialog.close === 'function' && dialog.open) dialog.close();
    else dialog.removeAttribute('open');
  }

  function updateUserRows(balances) {
    Object.keys(balances || {}).forEach(function (userId) {
      var balance = balances[userId] || {};
      var tokenNode = document.querySelector('[data-user-token-balance="' + userId + '"]');
      var purchasedNode = document.querySelector('[data-user-token-purchased="' + userId + '"]');
      if (tokenNode && balance.tokens !== undefined) tokenNode.textContent = faNum(balance.tokens);
      if (purchasedNode && balance.tokens_purchased !== undefined) purchasedNode.textContent = faNum(balance.tokens_purchased);
    });
  }

  function openDialog(button) {
    selectedUserId = Number(button.getAttribute('data-user-id'));
    var name = button.getAttribute('data-user-name') || 'کاربر';
    var token = Number(button.getAttribute('data-user-token') || 0);
    if (!Number.isInteger(selectedUserId) || selectedUserId < 1) return;

    dialog.dataset.creditMode = 'individual';
    submit.removeAttribute('onclick');
    document.getElementById('bulk-user-token-dialog-subtitle').textContent = name + ' · موجودی فعلی: ' + faNum(token) + ' اعتبار';
    document.getElementById('bulk-user-token-ids').value = String(selectedUserId);
    document.getElementById('bulk-user-token-action').value = 'add';
    document.getElementById('bulk-user-token-kind').value = 'gift';
    document.getElementById('bulk-user-token-amount').value = '';
    document.getElementById('bulk-user-token-expiry-unit').value = 'none';
    document.getElementById('bulk-user-token-send-sms').checked = false;
    document.getElementById('bulk-user-token-note').value = '';
    setState('');
    if (typeof window.updateBulkTokenExpiryVisibility === 'function') window.updateBulkTokenExpiryVisibility();
    if (typeof dialog.showModal === 'function') dialog.showModal();
    else dialog.setAttribute('open', '');
  }

  async function submitIndividual() {
    var action = document.getElementById('bulk-user-token-action').value;
    var amount = Number(document.getElementById('bulk-user-token-amount').value);
    var kind = document.getElementById('bulk-user-token-kind').value;
    var unit = document.getElementById('bulk-user-token-expiry-unit').value;
    var note = document.getElementById('bulk-user-token-note').value || null;
    if (!selectedUserId || !Number.isInteger(amount) || amount < 0 || (action !== 'set' && amount < 1)) {
      setState('مقدار اعتبار را به‌درستی وارد کنید.');
      return;
    }

    var expiresAt = null;
    if (action === 'add' && kind !== 'paid_adjustment' && unit !== 'none') {
      if (unit === 'date') expiresAt = document.getElementById('bulk-user-token-expiry-date').value || null;
      else {
        var n = Number(document.getElementById('bulk-user-token-expiry-value').value);
        if (!Number.isInteger(n) || n < 1) { setState('مهلت اعتبار را وارد کنید.'); return; }
        var date = new Date();
        if (unit === 'days') date.setDate(date.getDate() + n);
        if (unit === 'weeks') date.setDate(date.getDate() + n * 7);
        if (unit === 'months') date.setMonth(date.getMonth() + n);
        expiresAt = date.toISOString();
      }
    }

    submit.disabled = true;
    submit.classList.add('opacity-60', 'cursor-wait');
    setState('');
    try {
      var response = await fetch('/api/v1/admin/users/bulk-token', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ user_ids: [selectedUserId], action: action, amount: amount, credit_kind: kind, expires_at: expiresAt, send_sms: document.getElementById('bulk-user-token-send-sms').checked, note: note })
      });
      var data = await response.json().catch(function () { return {}; });
      if (!response.ok || data.status !== 'success') throw new Error(data.message || 'اعمال اعتبار انجام نشد.');
      updateUserRows(data.balances || {});
      closeDialog();
      if (typeof window.showAdminToast === 'function') window.showAdminToast(data.message, 'success');
      else alert(data.message);
    } catch (error) {
      setState(error.message || 'اعمال اعتبار انجام نشد.');
    } finally {
      submit.disabled = false;
      submit.classList.remove('opacity-60', 'cursor-wait');
    }
  }

  document.querySelectorAll('[data-user-token-dialog]').forEach(function (button) {
    button.addEventListener('click', function () { openDialog(button); });
  });

  submit.addEventListener('click', function (event) {
    if (dialog.dataset.creditMode !== 'individual') return;
    event.preventDefault();
    event.stopImmediatePropagation();
    submitIndividual();
  });
})();
