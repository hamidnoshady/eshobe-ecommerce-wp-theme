(function () {
  function dispatchChange(select) {
    var event;
    if (typeof Event === 'function') {
      event = new Event('change', { bubbles: true });
    } else {
      event = document.createEvent('Event');
      event.initEvent('change', true, true);
    }
    select.dispatchEvent(event);
  }

  function getVariations(form) {
    var raw = form.getAttribute('data-product_variations');
    if (!raw) {
      return [];
    }
    try {
      return JSON.parse(raw) || [];
    } catch (e) {
      return [];
    }
  }

  function getCurrentSelections(form) {
    var selections = {};
    form.querySelectorAll('.wm-variation-select-native select').forEach(function (select) {
      if (select.value) {
        selections[select.getAttribute('data-attribute_name')] = select.value;
      }
    });
    return selections;
  }

  function isValueAvailable(variations, attrKey, value, currentSelections) {
    return variations.some(function (variation) {
      var attrs = variation.attributes || {};
      if (!attrs.hasOwnProperty(attrKey)) {
        return false;
      }
      var variationValue = attrs[attrKey];
      if (variationValue && variationValue !== value) {
        return false;
      }

      for (var key in currentSelections) {
        if (key === attrKey || !attrs.hasOwnProperty(key)) {
          continue;
        }
        var otherValue = attrs[key];
        if (otherValue && otherValue !== currentSelections[key]) {
          return false;
        }
      }

      return variation.is_in_stock !== false;
    });
  }

  function syncAttribute(wrapper, form, variations) {
    var select = wrapper.querySelector('.wm-variation-select-native select');
    var swatches = wrapper.querySelectorAll('.wm-variation-swatch');
    var selectedLabel = wrapper.querySelector('[data-role="wm-selected-name"]');
    var attrKey = wrapper.getAttribute('data-attribute_name');
    var currentSelections = getCurrentSelections(form);
    var selectedName = '';

    swatches.forEach(function (swatch) {
      var value = swatch.getAttribute('data-value');
      var selected = select.value === value;

      swatch.classList.toggle('is-selected', selected);
      swatch.setAttribute('aria-selected', selected ? 'true' : 'false');

      if (selected) {
        selectedName = swatch.getAttribute('data-name') || '';
      }

      var available = isValueAvailable(variations, attrKey, value, currentSelections);
      swatch.classList.toggle('is-unavailable', !available);
    });

    if (selectedLabel) {
      selectedLabel.textContent = selectedName;
    }
  }

  function syncAllAttributes(form) {
    var variations = getVariations(form);
    form.querySelectorAll('.wm-variation-attribute').forEach(function (wrapper) {
      syncAttribute(wrapper, form, variations);
    });
  }

  document.querySelectorAll('.variations_form').forEach(function (form) {
    var wrappers = form.querySelectorAll('.wm-variation-attribute');
    if (!wrappers.length) {
      return;
    }

    wrappers.forEach(function (wrapper) {
      var select = wrapper.querySelector('.wm-variation-select-native select');
      if (!select) {
        return;
      }

      wrapper.querySelectorAll('.wm-variation-swatch').forEach(function (swatch) {
        swatch.addEventListener('click', function () {
          select.value = swatch.getAttribute('data-value');
          dispatchChange(select);
        });

        swatch.addEventListener('keydown', function (event) {
          if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            swatch.click();
          }
        });
      });

      select.addEventListener('change', function () {
        syncAllAttributes(form);
      });
    });

    syncAllAttributes(form);

    jQueryReady(form);
  });

  // WooCommerce's variation events are fired via jQuery; re-sync swatches
  // whenever core resets or matches a variation so out-of-stock greying
  // stays accurate after every selection.
  function jQueryReady(form) {
    if (typeof window.jQuery === 'undefined') {
      return;
    }
    window.jQuery(form).on('reset_data woocommerce_update_variation_values found_variation', function () {
      syncAllAttributes(form);
    });
  }
})();