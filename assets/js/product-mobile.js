(function() {
  document.querySelectorAll('.wm-product-specs[data-mobile-ready], .wm-product-specs--mobile-collapsed, .wm-product-specs--mobile-expanded').forEach(function(specs) {
    var toggles = specs.querySelectorAll('[data-specs-toggle]');

    if (!toggles.length) {
      return;
    }

    toggles.forEach(function(toggle) {
      toggle.addEventListener('click', function() {
        var expanded = specs.classList.contains('wm-product-specs--mobile-expanded');
        specs.classList.toggle('wm-product-specs--mobile-expanded', !expanded);
        specs.classList.toggle('wm-product-specs--mobile-collapsed', expanded);

        toggles.forEach(function(item) {
          item.setAttribute('aria-expanded', !expanded ? 'true' : 'false');
        });
      });
    });
  });

  document.querySelectorAll('.wm-mobile-bottom-bar').forEach(function(bar) {
    var handle = bar.querySelector('.wm-mobile-bottom-bar__handle');
    var content = bar.querySelector('.wm-mobile-bottom-bar__content');

    if (!handle || !content) {
      return;
    }

    content.hidden = false;
    content.setAttribute('aria-hidden', 'true');

    handle.addEventListener('click', function() {
      var expanded = bar.classList.contains('wm-mobile-bottom-bar--expanded');
      bar.classList.toggle('wm-mobile-bottom-bar--expanded', !expanded);
      bar.classList.toggle('wm-mobile-bottom-bar--collapsed', expanded);
      handle.setAttribute('aria-expanded', !expanded ? 'true' : 'false');
      content.setAttribute('aria-hidden', !expanded ? 'false' : 'true');
    });
  });

  document.querySelectorAll('[data-mobile-description]').forEach(function(description) {
    var toggles = description.querySelectorAll('[data-description-toggle]');

    if (!toggles.length) {
      return;
    }

    toggles.forEach(function(toggle) {
      toggle.addEventListener('click', function() {
        var expanded = description.classList.contains('wm-product-description--expanded');
        description.classList.toggle('wm-product-description--expanded', !expanded);
        description.classList.toggle('wm-product-description--collapsed', expanded);

        toggles.forEach(function(item) {
          item.setAttribute('aria-expanded', !expanded ? 'true' : 'false');
        });
      });
    });
  });
})();
