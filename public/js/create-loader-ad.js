(function () {
  'use strict';

  const root = document.querySelector('[data-ad-preview]');
  if (!root) return;

  const buildButton = root.querySelector('[data-build]');
  const buildLabel = root.querySelector('[data-build-label]');
  const tap = root.querySelector('[data-tap]');
  const loader = root.querySelector('[data-loader]');
  const success = root.querySelector('[data-success]');
  const bar = root.querySelector('[data-progress-bar]');
  const dot = root.querySelector('[data-progress-dot]');
  const value = root.querySelector('[data-progress]');
  const time = root.querySelector('[data-time]');
  const stageText = root.querySelector('[data-stage-text]');
  const replay = root.querySelector('[data-replay]');
  const total = 7000;
  const intro = 780;
  const pressDelay = 180;
  const successTail = 420;
  const loaderDuration = total - intro - pressDelay - successTail;
  let startedAt = 0;
  let frame = 0;
  let timers = [];

  const fa = (number) => String(number).replace(/\d/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'[digit]);
  const formatTime = (milliseconds) => {
    const seconds = Math.min(99, Math.floor(milliseconds / 1000));
    return `۰۰:${fa(String(seconds).padStart(2, '0'))}`;
  };

  function clearTimers() {
    timers.forEach((timer) => window.clearTimeout(timer));
    timers = [];
    if (frame) window.cancelAnimationFrame(frame);
    frame = 0;
  }

  function reset() {
    clearTimers();
    root.dataset.phase = 'upload';
    buildButton.hidden = false;
    buildButton.classList.remove('is-pressing', 'is-hidden');
    buildLabel.textContent = 'بساز';
    tap.classList.remove('is-visible');
    loader.hidden = true;
    success.hidden = true;
    bar.style.width = '0%';
    dot.style.right = '0%';
    value.textContent = '۰٪';
    time.textContent = '۰۰:۰۰';
    loader.setAttribute('aria-valuenow', '0');
  }

  function renderProgress(progress, elapsed) {
    const rounded = Math.min(100, Math.round(progress));
    bar.style.width = `${rounded}%`;
    dot.style.right = `${rounded}%`;
    value.textContent = `${fa(rounded)}٪`;
    time.textContent = formatTime(elapsed);
    loader.setAttribute('aria-valuenow', String(rounded));
    loader.setAttribute('aria-valuetext', `${rounded} درصد`);
    if (elapsed < 900) stageText.textContent = 'در حال بررسی تصویر و ورودی‌ها';
    else if (elapsed < 2200) stageText.textContent = 'در حال ساخت ترکیب اصلی تصویر';
    else if (elapsed < 3000) stageText.textContent = 'در حال پرداخت جزئیات نهایی';
    else stageText.textContent = 'در حال آماده‌سازی خروجی شما';
  }

  function showLoader() {
    root.dataset.phase = 'loading';
    buildButton.classList.add('is-hidden');
    timer(() => { buildButton.hidden = true; }, 230);
    loader.hidden = false;
    startedAt = performance.now();
    renderProgress(0, 0);
    const tick = (now) => {
      const elapsed = Math.min(total - intro, Math.max(0, now - startedAt));
      const progress = Math.min(100, (elapsed / (total - intro)) * 100);
      renderProgress(progress, elapsed);
      if (progress < 100) frame = window.requestAnimationFrame(tick);
    };
    frame = window.requestAnimationFrame(tick);
    timer(showSuccess, loaderDuration);
  }

  function showSuccess() {
    if (frame) window.cancelAnimationFrame(frame);
    frame = 0;
    renderProgress(100, total - intro);
    root.dataset.phase = 'success';
    loader.hidden = true;
    success.hidden = false;
  }

  function timer(callback, delay) {
    timers.push(window.setTimeout(callback, delay));
  }

  function start() {
    reset();
    timer(() => {
      tap.classList.add('is-visible');
      buildButton.classList.add('is-pressing');
      buildLabel.textContent = 'در حال ساخت';
      timer(showLoader, pressDelay);
    }, intro);
  }

  replay.addEventListener('click', start);
  buildButton.addEventListener('click', () => {
    if (root.dataset.phase !== 'upload') return;
    clearTimers();
    tap.classList.add('is-visible');
    buildButton.classList.add('is-pressing');
    buildLabel.textContent = 'در حال ساخت';
    timer(showLoader, pressDelay);
  });

  start();
}());
