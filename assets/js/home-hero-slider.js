(function() {
  var home = document.querySelector('.wm-home');

  if (home && 'IntersectionObserver' in window) {
    var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var revealItems = home.querySelectorAll('.wm-home-section, .wm-product-carousel');
    var revealObserver = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          revealObserver.unobserve(entry.target);
        }
      });
    }, { rootMargin: '0px 0px -12% 0px', threshold: 0.08 });

    // Skip the staggered reveal animations for reduced-motion users;
    // sections still fade in but without the per-item delay.
    if (!reducedMotion) {
      home.classList.add('is-motion-ready');
    }

    revealItems.forEach(function(item, itemIndex) {
      if (!reducedMotion) {
        item.style.transitionDelay = Math.min(itemIndex * 40, 160) + 'ms';
      }
      revealObserver.observe(item);
    });
  }

  document.querySelectorAll('[data-home-hero]').forEach(function(hero) {
    var slides = hero.querySelectorAll('[data-home-hero-slide]');
    var dots = hero.querySelectorAll('[data-home-hero-dot]');
    var prev = hero.querySelector('[data-home-hero-prev]');
    var next = hero.querySelector('[data-home-hero-next]');
    var index = 0;
    var timer = null;
    var autoplayTimer = null;
    var autoplayPaused = false;
    var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function syncVideos() {
      slides.forEach(function(slide) {
        var video = slide.querySelector('video');
        if (!video) {
          return;
        }

        if (slide.classList.contains('is-active')) {
          if (reducedMotion) {
            video.pause();
            return;
          }

          var playPromise = video.play();
          if (playPromise && playPromise.catch) {
            playPromise.catch(function() {});
          }
        } else {
          video.pause();
          if (video.readyState > 0 && !isNaN(video.duration)) {
            video.currentTime = 0;
          }
        }
      });
    }

    syncVideos();

    if (slides.length <= 1) {
      return;
    }

    var autoplayInterval = parseInt(hero.getAttribute('data-autoplay') || '0', 10);
    var pauseOnHover = hero.getAttribute('data-autoplay-pause-hover') !== '0';
    var progress = hero.querySelector('[data-home-hero-progress]');
    var progressBar = progress ? progress.querySelector('.wm-home-hero__progress-bar') : null;

    if (!autoplayInterval || autoplayInterval <= 0) {
      autoplayInterval = 0;
    }

    function clearAutoplay() {
      if (autoplayTimer) {
        window.clearTimeout(autoplayTimer);
        autoplayTimer = null;
      }
    }

    function resetProgress() {
      if (!progressBar) {
        return;
      }

      progressBar.classList.remove('is-running');
    }

    function restartProgress() {
      if (!progressBar || !autoplayInterval) {
        return;
      }

      progressBar.classList.remove('is-running');
      void progressBar.offsetWidth; // force reflow so the fill restarts from 0
      progressBar.style.transitionDuration = autoplayInterval + 'ms';
      progressBar.classList.add('is-running');
    }

    function scheduleAutoplay() {
      if (!autoplayInterval || autoplayPaused) {
        return;
      }

      clearAutoplay();
      restartProgress();

      autoplayTimer = window.setTimeout(function() {
        autoplayTimer = null;
        show(index + 1);
      }, autoplayInterval);
    }

    function show(target) {
      var nextIndex = (target + slides.length) % slides.length;
      var currentSlide = slides[index];
      var nextSlide = slides[nextIndex];

      if (!nextSlide || nextIndex === index) {
        return;
      }

      if (timer) {
        window.clearTimeout(timer);
      }

      slides.forEach(function(slide) {
        slide.classList.remove('is-entering', 'is-leaving');
      });

      if (currentSlide) {
        currentSlide.classList.add('is-leaving');
        currentSlide.classList.remove('is-active');
      }

      nextSlide.classList.add('is-active');

      window.requestAnimationFrame(function() {
        nextSlide.classList.add('is-entering');
      });

      dots.forEach(function(dot, itemIndex) {
        dot.classList.toggle('is-active', itemIndex === nextIndex);
      });

      index = nextIndex;

      syncVideos();

      timer = window.setTimeout(function() {
        slides.forEach(function(slide) {
          slide.classList.remove('is-entering', 'is-leaving');
        });
        timer = null;
      }, 560);

      scheduleAutoplay();
    }

    if (prev) {
      prev.addEventListener('click', function() {
        show(index - 1);
      });
    }

    if (next) {
      next.addEventListener('click', function() {
        show(index + 1);
      });
    }

    dots.forEach(function(dot) {
      dot.addEventListener('click', function() {
        show(parseInt(dot.getAttribute('data-home-hero-dot'), 10) || 0);
      });
    });

    if (autoplayInterval) {
      if (pauseOnHover) {
        hero.addEventListener('mouseenter', function() {
          autoplayPaused = true;
          clearAutoplay();
          resetProgress();
        });

        hero.addEventListener('mouseleave', function() {
          autoplayPaused = false;
          scheduleAutoplay();
        });
      }

      document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
          autoplayPaused = true;
          clearAutoplay();
          resetProgress();
        } else {
          autoplayPaused = false;
          scheduleAutoplay();
        }
      });

      scheduleAutoplay();
    }
  });
})();
