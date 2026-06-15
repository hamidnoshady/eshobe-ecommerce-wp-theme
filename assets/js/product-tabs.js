(function() {
  document.querySelectorAll('.wm-product-tabs').forEach(function(tabs) {
    var buttons = tabs.querySelectorAll('.wm-product-tabs__button');
    var panels = tabs.querySelectorAll('.wm-product-tabs__panel');

    if (!buttons.length || !panels.length) {
      return;
    }

    buttons.forEach(function(button) {
      button.addEventListener('click', function() {
        var target = button.getAttribute('data-tab');

        buttons.forEach(function(item) {
          var active = item === button;
          item.classList.toggle('is-active', active);
          item.setAttribute('aria-selected', active ? 'true' : 'false');
        });

        panels.forEach(function(panel) {
          var active = panel.getAttribute('data-panel') === target;
          panel.classList.toggle('is-active', active);
          panel.hidden = !active;
        });
      });
    });
  });
})();
