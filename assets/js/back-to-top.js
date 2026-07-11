(function () {
  var button = document.querySelector('[data-wm-back-to-top]');
  if (!button) {
    return;
  }

  var toggle = function () {
    button.classList.toggle('is-visible', window.scrollY > 400);
  };

  toggle();
  window.addEventListener('scroll', toggle, { passive: true });

  button.addEventListener('click', function () {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
})();
