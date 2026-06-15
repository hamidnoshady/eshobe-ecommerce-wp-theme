(function() {
  document.querySelectorAll('[data-product-carousel]').forEach(function(section) {
    var track = section.querySelector('.wm-product-carousel__track');
    var arrows = section.querySelectorAll('.wm-product-carousel__arrow');

    if (!track || !arrows.length) {
      return;
    }

    function updateArrows() {
      var canScroll = track.scrollWidth > track.clientWidth + 2;

      arrows.forEach(function(arrow) {
        arrow.disabled = !canScroll;
        arrow.setAttribute('aria-disabled', canScroll ? 'false' : 'true');
      });
    }

    arrows.forEach(function(arrow) {
      arrow.addEventListener('click', function() {
        if (arrow.disabled) {
          return;
        }

        var card = track.querySelector('.wm-product-card');
        var amount = card ? card.getBoundingClientRect().width + 18 : track.clientWidth * 0.8;
        var direction = arrow.getAttribute('data-carousel-direction');
        var rtl = window.getComputedStyle(track).direction === 'rtl';
        var delta = direction === 'next' ? amount : -amount;

        var start = track.scrollLeft;
        var firstDelta = rtl ? -delta : delta;

        track.scrollBy({
          left: firstDelta,
          behavior: 'smooth'
        });

        window.setTimeout(function() {
          if (track.scrollLeft !== start) {
            return;
          }

          track.scrollBy({
            left: -firstDelta,
            behavior: 'smooth'
          });
        }, 140);
      });
    });

    updateArrows();
    window.addEventListener('resize', updateArrows);
  });
})();
