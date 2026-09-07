(function () {
  const slider = document.querySelector('[data-products-slider]');
  if (!slider) return;

  const track = slider.querySelector('.create-products-track');
  const prev = document.querySelector('[data-products-prev]');
  const next = document.querySelector('[data-products-next]');
  const step = () => Math.max(280, Math.round(slider.clientWidth * (window.innerWidth <= 900 ? 0.92 : 0.25)));

  prev?.addEventListener('click', () => slider.scrollBy({ left: step(), behavior: 'smooth' }));
  next?.addEventListener('click', () => slider.scrollBy({ left: -step(), behavior: 'smooth' }));

  slider.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowLeft') {
      event.preventDefault();
      slider.scrollBy({ left: -step(), behavior: 'smooth' });
    }
    if (event.key === 'ArrowRight') {
      event.preventDefault();
      slider.scrollBy({ left: step(), behavior: 'smooth' });
    }
  });

  let pointerStart = null;
  slider.addEventListener('pointerdown', (event) => {
    pointerStart = { x: event.clientX, scrollLeft: slider.scrollLeft };
  });
  slider.addEventListener('pointermove', (event) => {
    if (!pointerStart || event.pointerType === 'touch') return;
    slider.scrollLeft = pointerStart.scrollLeft - (event.clientX - pointerStart.x);
  });
  slider.addEventListener('pointerup', () => { pointerStart = null; });
  slider.addEventListener('pointercancel', () => { pointerStart = null; });

  if (track?.children.length < 5) {
    document.querySelector('.create-products-controls')?.setAttribute('hidden', 'hidden');
  }
}());
