(function () {
  'use strict';

  var modal = document.getElementById('wm-quick-view-modal');
  if (!modal || typeof window.wmQuickViewData === 'undefined') {
    return;
  }

  var body = modal.querySelector('[data-wm-quick-view-body]');
  var lastTrigger = null;

  function open(productId, trigger) {
    lastTrigger = trigger;
    modal.hidden = false;
    document.body.classList.add('wm-quick-view-open');
    if (window.wmFocusTrap) {
      window.wmFocusTrap.trap(modal, trigger);
    }
    window.requestAnimationFrame(function () {
      modal.classList.add('is-open');
    });

    body.innerHTML = '<div class="wm-quick-view__loading">' + (window.wmQuickViewData.loadingText || 'در حال بارگذاری…') + '</div>';

    var params = new window.URLSearchParams();
    params.set('action', 'wm_quick_view');
    params.set('nonce', window.wmQuickViewData.nonce);
    params.set('product_id', productId);

    window.fetch(window.wmQuickViewData.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: params.toString()
    })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (data && data.success) {
          body.innerHTML = data.data.html;
          var closeButton = modal.querySelector('.wm-quick-view-modal__close');
          if (closeButton) {
            closeButton.focus();
          }
        } else {
          body.innerHTML = '<p class="wm-quick-view__error">' + (window.wmQuickViewData.errorText || 'محصول یافت نشد.') + '</p>';
        }
      })
      .catch(function () {
        body.innerHTML = '<p class="wm-quick-view__error">' + (window.wmQuickViewData.networkErrorText || 'خطا در ارتباط با سرور.') + '</p>';
      });
  }

  function close() {
    if (window.wmFocusTrap) {
      window.wmFocusTrap.release();
    }
    modal.classList.remove('is-open');
    document.body.classList.remove('wm-quick-view-open');
    window.setTimeout(function () {
      modal.hidden = true;
      body.innerHTML = '';
    }, 240);
  }

  document.addEventListener('click', function (event) {
    var trigger = event.target.closest('[data-wm-quick-view]');
    if (trigger) {
      event.preventDefault();
      open(trigger.getAttribute('data-product-id'), trigger);
      return;
    }

    if (!modal.hidden && event.target.closest('[data-wm-quick-view-close]')) {
      close();
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !modal.hidden) {
      close();
    }
  });
})();
