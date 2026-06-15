(function() {
  var home = document.querySelector('.wm-home');

  if (home && 'IntersectionObserver' in window) {
    var revealItems = home.querySelectorAll('.wm-home-section, .wm-product-carousel');
    var revealObserver = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          revealObserver.unobserve(entry.target);
        }
      });
    }, { rootMargin: '0px 0px -12% 0px', threshold: 0.08 });

    home.classList.add('is-motion-ready');

    revealItems.forEach(function(item, itemIndex) {
      item.style.transitionDelay = Math.min(itemIndex * 40, 160) + 'ms';
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

    if (slides.length <= 1) {
      return;
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

      timer = window.setTimeout(function() {
        slides.forEach(function(slide) {
          slide.classList.remove('is-entering', 'is-leaving');
        });
        timer = null;
      }, 560);
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
  });
})();
