(function() {
  document.querySelectorAll('.wm-related-products').forEach(function(section) {
    var track = section.querySelector('.wm-related-products__track');
    var arrows = section.querySelectorAll('.wm-related-products__arrow');

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

        var firstCard = track.querySelector('.wm-related-card');
        var amount = firstCard ? firstCard.getBoundingClientRect().width + 18 : track.clientWidth * 0.8;
        var direction = arrow.getAttribute('data-related-direction');
        var rtl = window.getComputedStyle(track).direction === 'rtl';
        var delta = direction === 'next' ? -amount : amount;
        var target = track.scrollLeft + (rtl ? delta : -delta);

        track.scrollTo({
          left: target,
          behavior: 'smooth'
        });
      });
    });

    updateArrows();
    window.addEventListener('resize', updateArrows);
  });
})();
