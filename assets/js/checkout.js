(function () {
  var root = document.querySelector('.wm-checkout');

  if (!root) {
    return;
  }

  var stepButtons = Array.prototype.slice.call(root.querySelectorAll('[data-checkout-step-target]'));
  var panels = Array.prototype.slice.call(root.querySelectorAll('[data-checkout-step]'));

  function setStep(step) {
    panels.forEach(function (panel) {
      panel.classList.toggle('is-active', panel.getAttribute('data-checkout-step') === step);
    });

    stepButtons.forEach(function (button) {
      button.classList.toggle('is-active', button.getAttribute('data-checkout-step-target') === step);
    });

    root.setAttribute('data-current-step', step);
  }

  root.addEventListener('click', function (event) {
    var target = event.target.closest('[data-checkout-step-target], [data-checkout-next], [data-checkout-prev]');

    if (!target) {
      return;
    }

    event.preventDefault();
    setStep(target.getAttribute('data-checkout-step-target') || target.getAttribute('data-checkout-next') || target.getAttribute('data-checkout-prev'));
  });

  setStep('address');
})();
