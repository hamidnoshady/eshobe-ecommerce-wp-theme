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

  // Numeric amount out of a WooCommerce price fragment (currency symbol,
  // thousands separators, Latin or Persian/Arabic digits).
  function priceAmount(html) {
    if (!html) {
      return 0;
    }
    var text = String(html).replace(/<[^>]*>/g, '');
    var latin = text
      .replace(/[\u06F0-\u06F9]/g, function (digit) {
        return String('0123456789'.charAt('\u06F0\u06F1\u06F2\u06F3\u06F4\u06F5\u06F6\u06F7\u06F8\u06F9'.indexOf(digit)));
      })
      .replace(/[\u0660-\u0669]/g, function (digit) {
        return String('0123456789'.charAt('\u0660\u0661\u0662\u0663\u0664\u0665\u0666\u0667\u0668\u0669'.indexOf(digit)));
      });
    var digits = latin.replace(/[^0-9]/g, '');
    return digits ? parseFloat(digits) : 0;
  }

  // Formats an amount the way the variation's own price markup does, so the
  // digits keep the store's numerals and thousands separator.
  function formatAmountLike(amount, sample) {
    var text = String(sample).replace(/<[^>]*>/g, '');
    var separator = (text.match(/[\u066C\u060C,]/) || [','])[0];
    var digits = String(Math.round(amount)).replace(/\B(?=(\d{3})+(?!\d))/g, separator);

    if (/[\u06F0-\u06F9]/.test(text)) {
      digits = digits.replace(/[0-9]/g, function (digit) {
        return '۰۱۲۳۴۵۶۷۸۹'.charAt(Number(digit));
      });
    }

    return digits;
  }

  // Swaps the first amount printed in the markup for another one, leaving the
  // currency symbol and WooCommerce's wrappers in place.
  function replaceAmount(html, amount) {
    var replaced = false;
    return String(html).replace(/(<[^>]*>)|([^<]+)/g, function (match, tag, text) {
      if (tag || replaced || !/[0-9\u06F0-\u06F9]/.test(text)) {
        return match;
      }
      replaced = true;
      return text.replace(
        /[0-9\u06F0-\u06F9](?:[0-9\u06F0-\u06F9,\u066C\u060C.]*[0-9\u06F0-\u06F9])?/,
        formatAmountLike(amount, text)
      );
    });
  }

  // The variation's price markup without WooCommerce's struck regular price:
  // the payable amount, wrappers and currency intact.
  function currentAmountHtml(priceHtml) {
    var html = String(priceHtml);
    var ins = html.match(/<ins[^>]*>([\s\S]*?)<\/ins>/i);

    if (ins) {
      return ins[1];
    }

    return html.replace(/<del[\s\S]*?<\/del>/i, '').trim() || html;
  }

  // Same breakdown the server prints for the page's price block, rebuilt for
  // the variation the customer just picked. The purchase box keeps the saving
  // line, the compact bar leaves it out. Returns '' when that variation has no
  // discount, so the caller can fall back to WooCommerce's own price markup.
  function variationHeroHtml(variation, compact) {
    var regular = priceAmount(variation.display_regular_price);
    var current = priceAmount(variation.display_price);
    if (!regular || !current || current >= regular) {
      return '';
    }

    var amountHtml = currentAmountHtml(variation.price_html || current);
    var percent = Math.max(1, Math.min(99, Math.round(((regular - current) / regular) * 100)));
    var percentText = String(percent).replace(/[0-9]/g, function (digit) {
      return '۰۱۲۳۴۵۶۷۸۹'.charAt(Number(digit));
    });

    return (
      '<div class="wm-price-hero wm-price-hero--discounted' +
      (compact ? ' wm-price-hero--bar' : '') +
      '">' +
      '<div class="wm-price-hero__row">' +
      '<span class="wm-price-hero__current">' + amountHtml + '</span>' +
      '<span class="wm-discount-badge wm-price-hero__badge">' + percentText + '٪ تخفیف</span>' +
      '</div>' +
      '<div class="wm-price-hero__meta">' +
      '<span class="wm-price-hero__regular-label">قیمت قبلی</span>' +
      '<del class="wm-price-hero__regular">' + replaceAmount(amountHtml, regular) + '</del>' +
      (compact
        ? ''
        : '<span class="wm-price-hero__saved">' + replaceAmount(amountHtml, regular - current) + ' سود شما</span>') +
      '</div>' +
      '</div>'
    );
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
      // Native `disabled` makes unavailable swatches unselectable (no click,
      // no keyboard focus) without any extra guard logic in the handlers below.
      swatch.disabled = !available;
      swatch.setAttribute('aria-disabled', available ? 'false' : 'true');
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

  // Variable products only: replace the default WooCommerce variation
  // price/availability box with a live update of the page's own price
  // element, and turn the plain quantity input into a second box (status +
  // -/+ stepper) below it. Simple products have no .single_variation_wrap
  // and are left untouched.
  function initVariationPriceAndStock(form) {
    var wrap = form.querySelector('.single_variation_wrap');
    if (!wrap) {
      return;
    }

    var container = form.closest('.wm-product-purchase, .wm-mobile-bottom-bar');
    var priceEl = container
      ? container.querySelector('.wm-product-purchase__price, .wm-mobile-bottom-bar__price')
      : null;
    var buttonRow = wrap.querySelector('.woocommerce-variation-add-to-cart');
    var quantity = buttonRow ? buttonRow.querySelector('.quantity') : null;

    if (priceEl) {
      priceEl.dataset.wmDefaultPrice = priceEl.innerHTML;
    }

    // The mobile buy bar prints the compact form of the theme's price block
    // (no saving line); the purchase box has room for the full one.
    var compactPrice = !!priceEl && priceEl.dataset.wmDefaultPrice.indexOf('wm-price-hero--bar') !== -1;

    var stockBox = null;
    var status = null;
    var minus = null;
    var plus = null;

    if (buttonRow && quantity) {
      stockBox = document.createElement('div');
      stockBox.className = 'wm-variation-stock-box';
      stockBox.hidden = true;

      status = document.createElement('div');
      status.className = 'wm-variation-stock-box__status';
      stockBox.appendChild(status);

      var qtyRow = document.createElement('div');
      qtyRow.className = 'wm-variation-stock-box__qty';

      var qtyLabel = document.createElement('span');
      qtyLabel.className = 'wm-variation-stock-box__qty-label';
      qtyLabel.textContent = 'تعداد:';
      qtyRow.appendChild(qtyLabel);

      minus = document.createElement('button');
      minus.type = 'button';
      minus.className = 'wm-variation-stock-box__btn wm-variation-stock-box__btn--minus';
      minus.setAttribute('aria-label', 'کاهش تعداد');
      minus.textContent = '−';

      plus = document.createElement('button');
      plus.type = 'button';
      plus.className = 'wm-variation-stock-box__btn wm-variation-stock-box__btn--plus';
      plus.setAttribute('aria-label', 'افزایش تعداد');
      plus.textContent = '+';

      qtyRow.appendChild(minus);
      qtyRow.appendChild(quantity); // re-parents the real <input name="quantity">, nothing is cloned
      qtyRow.appendChild(plus);
      stockBox.appendChild(qtyRow);

      buttonRow.parentNode.insertBefore(stockBox, buttonRow);

      var updateButtons = function () {
        var input = quantity.querySelector('input.qty');
        if (!input) {
          return;
        }
        var min = parseFloat(input.min);
        var max = parseFloat(input.max);
        var current = parseFloat(input.value);
        if (isNaN(min)) {
          min = 1;
        }
        if (isNaN(current)) {
          current = min;
        }
        minus.disabled = current <= min;
        plus.disabled = !isNaN(max) && current >= max;
      };

      var step = function (dir) {
        var input = quantity.querySelector('input.qty');
        if (!input || input.disabled) {
          return;
        }
        var min = parseFloat(input.min);
        var max = parseFloat(input.max);
        var stepVal = parseFloat(input.step) || 1;
        var current = parseFloat(input.value);
        if (isNaN(min)) {
          min = 1;
        }
        if (isNaN(current)) {
          current = min;
        }
        var next = dir > 0 ? current + stepVal : current - stepVal;
        next = Math.max(min, next);
        if (!isNaN(max)) {
          next = Math.min(max, next);
        }
        if (next !== current) {
          input.value = next;
          input.dispatchEvent(new Event('change', { bubbles: true }));
        }
        updateButtons();
      };

      minus.addEventListener('click', function () {
        step(-1);
      });
      plus.addEventListener('click', function () {
        step(1);
      });
      quantity.addEventListener('change', updateButtons);
      quantity.addEventListener('input', updateButtons);
    }

    if (typeof window.jQuery === 'undefined') {
      return;
    }

    window.jQuery(form).on('found_variation', function (event, variation) {
      // The purchase box and the bar start on the product's price block; once
      // a swatch is picked, keep the same badge/regular-price breakdown for
      // that variation instead of dropping to WooCommerce's bare price markup.
      var heroHtml = priceEl ? variationHeroHtml(variation, compactPrice) : '';

      if (priceEl && heroHtml) {
        priceEl.innerHTML = heroHtml;
      } else if (priceEl && variation.price_html) {
        priceEl.innerHTML = variation.price_html;
      }
      if (stockBox) {
        status.innerHTML = variation.availability_html || '';
        stockBox.hidden = false;
        stockBox.classList.toggle('is-out-of-stock', !variation.is_in_stock);
        if (minus && plus) {
          minus.disabled = !variation.is_in_stock;
          plus.disabled = !variation.is_in_stock;
          setTimeout(function () {
            if (quantity) {
              quantity.dispatchEvent(new Event('change'));
            }
          }, 0);
        }
      }
    });

    window.jQuery(form).on('reset_data woocommerce_update_variation_values', function () {
      if (priceEl && priceEl.dataset.wmDefaultPrice !== undefined) {
        priceEl.innerHTML = priceEl.dataset.wmDefaultPrice;
      }
      if (stockBox) {
        stockBox.hidden = true;
      }
    });
  }

  document.querySelectorAll('.variations_form').forEach(initVariationPriceAndStock);

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