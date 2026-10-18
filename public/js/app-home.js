(function () {
  var home = document.querySelector('.home-page');
  if (home && 'IntersectionObserver' in window) {
    var imageObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.setAttribute('data-hb-loaded', '');
        imageObserver.unobserve(entry.target);
      });
    }, { rootMargin: '400px' });
    home.querySelectorAll('[data-hb-background]').forEach(function (element) {
      imageObserver.observe(element);
    });
    clearTimeout(window.homeImageFallback);
  }

  // ویدیوهای هوم فقط وقتی نزدیک viewport هستند منبع می‌گیرند و پخش می‌شوند.
  // به این ترتیب تعداد زیاد محصولات باعث دانلود هم‌زمان فایل‌های MP4 نمی‌شود.
  var lazyVideos = document.querySelectorAll('[data-hb-video-src]');
  function activateVideo(video) {
    if (!video.dataset.hbVideoLoaded) {
      video.src = video.dataset.hbVideoSrc;
      video.dataset.hbVideoLoaded = '1';
      video.load();
    }
    video.play().catch(function () {});
  }
  if (lazyVideos.length) {
    if ('IntersectionObserver' in window) {
      var videoObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting && entry.intersectionRatio >= 0.25) {
            activateVideo(entry.target);
          } else if (entry.target.dataset.hbVideoLoaded) {
            entry.target.pause();
          }
        });
      }, { rootMargin: '160px 0px', threshold: [0, 0.25] });
      Array.prototype.forEach.call(lazyVideos, function (video) { videoObserver.observe(video); });
    } else {
      Array.prototype.slice.call(lazyVideos, 0, 2).forEach(activateVideo);
    }
  }

})();
