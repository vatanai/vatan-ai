(function () {
  'use strict';

  const root = document.querySelector('[data-create-ui]');
  if (!root) return;

  const creator = root.querySelector('[data-creator-panel]');
  const preview = root.querySelector('[data-preview-panel]');
  const chat = root.querySelector('[data-chat-panel]');
  const prompt = root.querySelector('[data-prompt-input]');
  const promptCount = root.querySelector('[data-prompt-count]');
  const emptyPreview = root.querySelector('[data-empty-preview]');
  const generating = root.querySelector('[data-generating]');
  const output = root.querySelector('[data-output]');
  const submit = root.querySelector('[data-create-submit]');
  const submitLabel = root.querySelector('[data-submit-label]');
  const cost = root.querySelector('[data-cost]');
  const videoOnly = root.querySelector('[data-video-only]');
  const modeTabs = root.querySelectorAll('[data-mode]');
  const toFa = (value) => String(value).replace(/\d/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'[digit]);

  root.querySelectorAll('[data-create-theme]').forEach((themeButton) => themeButton.addEventListener('click', () => {
    if (typeof window.vatanToggleTheme === 'function') window.vatanToggleTheme();
  }));

  function setMode(mode) {
    modeTabs.forEach((tab) => {
      const active = tab.dataset.mode === mode;
      tab.classList.toggle('is-active', active);
      tab.setAttribute('aria-selected', active ? 'true' : 'false');
    });

    const isChat = mode === 'chat';
    creator.hidden = isChat;
    preview.hidden = isChat;
    chat.hidden = !isChat;
    if (isChat) {
      root.querySelector('[data-chat-input]')?.focus();
      return;
    }

    const title = root.querySelector('[data-creator-title]');
    const subtitle = root.querySelector('[data-creator-subtitle]');
    const emptyTitle = root.querySelector('[data-empty-title]');
    const emptyCopy = root.querySelector('[data-empty-copy]');
    const isVideo = mode === 'video';
    title.textContent = isVideo ? 'چه حرکتی می‌خواهی بسازی؟' : 'چی می‌خوای برات انجام بدم؟';
    subtitle.textContent = isVideo ? 'صحنه و حرکت را با چند کلمه توضیح بده.' : 'ایده‌ات را بنویس و با وطن شروع کن.';
    prompt.placeholder = isVideo ? 'مثلاً: دوربین آرام به سمت یک کافه‌ی روشن حرکت کند، باران روی شیشه و نورهای گرم...' : 'مثلاً: یک پرتره‌ی سینمایی از یک زن ایرانی در کوچه‌های بارانی تهران...';
    emptyTitle.textContent = isVideo ? 'حرکت ایده‌ات اینجا جان می‌گیرد' : 'ایده‌ات اینجا جان می‌گیرد';
    emptyCopy.textContent = isVideo ? 'صحنه و حرکت را بنویس و روی «بساز» بزن تا پیش‌نمایش ویدیو را ببینی.' : 'توضیحت را بنویس و روی «بساز» بزن تا اولین خروجی‌ات را ببینی.';
    videoOnly.hidden = !isVideo;
    cost.textContent = isVideo ? '۱۴ توکن' : '۸ توکن';
    submitLabel.textContent = 'بساز';
    submit.disabled = false;
  }

  modeTabs.forEach((tab) => tab.addEventListener('click', () => setMode(tab.dataset.mode)));

  prompt.addEventListener('input', () => {
    promptCount.textContent = toFa(Math.min(prompt.value.length, 1000));
  });

  root.querySelectorAll('[data-suggestion]').forEach((button) => {
    button.addEventListener('click', () => {
      prompt.value = button.dataset.suggestion;
      prompt.dispatchEvent(new Event('input'));
      prompt.focus();
    });
  });

  root.querySelector('[data-prompt-improve]')?.addEventListener('click', () => {
    const current = prompt.value.trim();
    prompt.value = current ? `${current}، ترکیب‌بندی حرفه‌ای، نورپردازی طبیعی، جزئیات دقیق و کیفیت سینمایی` : 'یک صحنه‌ی خلاقانه و چشم‌نواز با نورپردازی حرفه‌ای، ترکیب‌بندی دقیق و جزئیات سینمایی';
    prompt.dispatchEvent(new Event('input'));
  });

  const uploadZone = root.querySelector('[data-upload-zone]');
  const uploadInput = root.querySelector('[data-upload-input]');
  const uploadFile = root.querySelector('[data-upload-file]');
  uploadZone.addEventListener('click', () => uploadInput.click());
  uploadZone.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' || event.key === ' ') uploadInput.click();
  });
  uploadInput.addEventListener('change', () => {
    const file = uploadInput.files?.[0];
    if (!file) return;
    uploadFile.hidden = false;
    uploadFile.innerHTML = `<i class="fa-regular fa-file-image"></i> ${file.name}`;
  });

  function showPreview() {
    if (!prompt.value.trim()) {
      prompt.focus();
      prompt.closest('.create-prompt-box').classList.add('create-shake');
      window.setTimeout(() => prompt.closest('.create-prompt-box').classList.remove('create-shake'), 450);
      return;
    }
    emptyPreview.hidden = true;
    preview.hidden = false;
    output.hidden = true;
    generating.hidden = false;
    submit.disabled = true;
    submitLabel.textContent = 'در حال ساخت';
    window.setTimeout(() => {
      generating.hidden = true;
      output.hidden = false;
      submit.disabled = false;
      submitLabel.textContent = 'دوباره بساز';
    }, 1200);
  }

  submit.addEventListener('click', showPreview);
  root.querySelector('[data-regenerate]')?.addEventListener('click', showPreview);
  root.querySelector('[data-reset-form]')?.addEventListener('click', () => {
    prompt.value = '';
    prompt.dispatchEvent(new Event('input'));
    emptyPreview.hidden = false;
    preview.hidden = true;
    generating.hidden = true;
    output.hidden = true;
    submitLabel.textContent = 'بساز';
    submit.disabled = false;
  });

  const chatForm = root.querySelector('[data-chat-form]');
  const chatInput = root.querySelector('[data-chat-input]');
  const messages = root.querySelector('[data-chat-messages]');
  const chatHeading = root.querySelector('[data-chat-heading]');
  const appendMessage = (text, type) => {
    const message = document.createElement('div');
    message.className = `chat-message chat-message-${type}`;
    const body = document.createElement('span');
    body.textContent = text;
    const time = document.createElement('small');
    time.textContent = 'همین حالا';
    message.append(body, time);
    messages.appendChild(message);
    messages.scrollTop = messages.scrollHeight;
  };

  chatForm.addEventListener('submit', (event) => {
    event.preventDefault();
    const text = chatInput.value.trim();
    if (!text) return;
    appendMessage(text, 'user');
    chatInput.value = '';
    window.setTimeout(() => appendMessage('ایده‌ات را گرفتم. می‌توانم آن را به یک پرامپت دقیق، سناریوی کوتاه یا مسیر تصویری تبدیل کنم. از کدام شروع کنیم؟', 'agent'), 550);
  });

  root.querySelectorAll('.chat-list-item').forEach((item) => {
    item.addEventListener('click', () => {
      root.querySelectorAll('.chat-list-item').forEach((entry) => entry.classList.remove('is-active'));
      item.classList.add('is-active');
      chatHeading.textContent = item.dataset.chatTitle;
    });
  });

  root.querySelector('[data-new-chat]')?.addEventListener('click', () => {
    root.querySelectorAll('.chat-list-item').forEach((entry) => entry.classList.remove('is-active'));
    const item = document.createElement('button');
    item.type = 'button';
    item.className = 'chat-list-item is-active';
    item.dataset.chatTitle = 'گفت‌وگوی جدید';
    item.innerHTML = '<span class="chat-list-icon"><i class="fa-regular fa-comments"></i></span><span><strong>گفت‌وگوی جدید</strong><small>همین حالا</small></span><i class="fa-solid fa-ellipsis"></i>';
    root.querySelector('[data-chat-list]').prepend(item);
    chatHeading.textContent = 'گفت‌وگوی جدید';
    item.addEventListener('click', () => {
      root.querySelectorAll('.chat-list-item').forEach((entry) => entry.classList.remove('is-active'));
      item.classList.add('is-active');
      chatHeading.textContent = item.dataset.chatTitle;
    });
    chatInput.focus();
  });
}());
