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

  var stepOrder = ['address', 'shipping', 'payment'];

  function markDone(step) {
    var index = stepOrder.indexOf(step);
    stepButtons.forEach(function (button) {
      var buttonIndex = stepOrder.indexOf(button.getAttribute('data-checkout-step-target'));
      if (buttonIndex < index) {
        button.classList.add('is-done');
      }
    });
  }

  function normalizeDigits(value) {
    return String(value || '')
      .replace(/[۰-۹]/g, function (d) { return String('۰۱۲۳۴۵۶۷۸۹'.indexOf(d)); })
      .replace(/[٠-٩]/g, function (d) { return String('٠١٢٣٤٥٦٧٨٩'.indexOf(d)); });
  }

  // Light client-side gate before advancing: required fields, email and
  // phone formats for the *current* step. WooCommerce's own validation on
  // submit stays the source of truth.
  function validateCurrentStep() {
    var activePanel = null;
    panels.forEach(function (panel) {
      if (panel.classList.contains('is-active')) {
        activePanel = panel;
      }
    });
    if (!activePanel) {
      return true;
    }

    var fields = activePanel.querySelectorAll('input, select, textarea');
    var firstInvalid = null;

    fields.forEach(function (field) {
      var ok = true;
      if (field.disabled) {
        return;
      }
      if (field.required && !String(field.value || '').trim()) {
        ok = false;
      }
      if (ok && field.type === 'email' && field.value && !/^\S+@\S+\.\S+$/.test(field.value)) {
        ok = false;
      }
      if (ok && field.type === 'tel' && field.value && field.value.replace(/\D/g, '').length < 10) {
        ok = false;
      }

      field.classList.toggle('is-invalid', !ok);
      if (!ok && !firstInvalid) {
        firstInvalid = field;
      }
    });

    if (firstInvalid) {
      firstInvalid.focus();
      firstInvalid.addEventListener('input', function clearInvalid() {
        firstInvalid.classList.remove('is-invalid');
        firstInvalid.removeEventListener('input', clearInvalid);
      });
      return false;
    }

    return true;
  }

  root.addEventListener('click', function (event) {
    var target = event.target.closest('[data-checkout-step-target], [data-checkout-next], [data-checkout-prev]');

    if (!target) {
      return;
    }

    event.preventDefault();
    var step = target.getAttribute('data-checkout-step-target') || target.getAttribute('data-checkout-next') || target.getAttribute('data-checkout-prev');

    var nextIndex = stepOrder.indexOf(step);
    var currentIndex = stepOrder.indexOf(root.getAttribute('data-current-step') || 'address');

    // Only gate forward navigation; going back is always allowed.
    if (nextIndex > currentIndex && !validateCurrentStep()) {
      return;
    }

    setStep(step);
    markDone(step);
  });

  setStep('address');

  // === Coupon: move into sidebar, wire up toggle + apply ===
  var couponForm = document.querySelector('.checkout_coupon.woocommerce-form-coupon');
  var reviewBox = document.querySelector('.wm-checkout-review-box');

  if (couponForm && reviewBox) {
    var couponBlock = document.createElement('div');
    couponBlock.className = 'wm-coupon-block';
    couponBlock.innerHTML =
      '<button type="button" class="wm-coupon-toggle-btn" aria-expanded="false" aria-controls="wm_coupon_input_row">کد تخفیف دارید؟</button>' +
      '<div class="wm-coupon-input-row" id="wm_coupon_input_row" style="display:none;">' +
      '<input type="text" id="wm_coupon_code" aria-label="کد تخفیف" placeholder="کد تخفیف را وارد کنید" dir="rtl" />' +
      '<button type="button" id="wm_apply_coupon">اعمال</button>' +
      '</div>' +
      '<div class="wm-coupon-success" id="wm_coupon_success" style="display:none;"></div>';
    reviewBox.appendChild(couponBlock);

    var toggleBtn = couponBlock.querySelector('.wm-coupon-toggle-btn');
    var inputRow = couponBlock.querySelector('.wm-coupon-input-row');

    toggleBtn.addEventListener('click', function () {
      var isOpen = inputRow.style.display !== 'none';
      inputRow.style.display = isOpen ? 'none' : 'flex';
      toggleBtn.setAttribute('aria-expanded', String(!isOpen));
    });

    var couponInput = couponBlock.querySelector('#wm_coupon_code');
    couponInput.addEventListener('input', function () {
      couponInput.value = normalizeDigits(couponInput.value);
    });

    couponBlock.querySelector('#wm_apply_coupon').addEventListener('click', function () {
      var code = couponBlock.querySelector('#wm_coupon_code').value.trim();
      if (!code) {
        return;
      }
      var wcInput = couponForm.querySelector('[name="coupon_code"]');
      var wcBtn = couponForm.querySelector('[name="apply_coupon"]');
      if (wcInput && wcBtn) {
        wcInput.value = code;
        wcBtn.click();
      }
    });

    if (window.jQuery) {
      window.jQuery(document.body).on('applied_coupon', function (e, couponCode) {
        var successEl = couponBlock.querySelector('#wm_coupon_success');
        successEl.textContent = '✓ کد تخفیف ' + couponCode + ' با موفقیت اعمال شد';
        successEl.style.display = 'flex';
        inputRow.style.display = 'none';
      });
    }
  }

  // === Wallet: hide payment option when balance is zero ===
  var walletLabel = root.querySelector('.payment_method_pa_wallet > label');
  if (walletLabel) {
    var balanceMatch = (walletLabel.textContent || '').match(/[\d,،]+/);
    var balance = balanceMatch ? parseInt(balanceMatch[0].replace(/[,،]/g, ''), 10) : 0;
    if (!balance) {
      root.querySelector('.payment_method_pa_wallet').classList.add('wm-wallet-hidden');
    }
  }
})();
