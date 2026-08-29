(function () {
  'use strict';

  var STORAGE_KEY = 'wmWishlist';
  var page = document.querySelector('[data-wm-wishlist-page]');
  if (!page || typeof window.wmWishlistData === 'undefined') {
    return;
  }

  var grid = page.querySelector('[data-wm-wishlist-grid]');
  var empty = page.querySelector('[data-wm-wishlist-empty]');

  function readIds() {
    try {
      return JSON.parse(window.localStorage.getItem(STORAGE_KEY)) || [];
    } catch (e) {
      return [];
    }
  }

  function writeIds(ids) {
    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(ids));
    } catch (e) {}
  }

  function render() {
    var ids = readIds();

    if (!ids.length) {
      grid.innerHTML = '';
      if (empty) {
        empty.hidden = false;
      }
      return;
    }

    if (empty) {
      empty.hidden = true;
    }

    var params = new window.URLSearchParams();
    params.set('action', 'wm_wishlist_products');
    params.set('nonce', window.wmWishlistData.nonce);
    params.set('ids', JSON.stringify(ids));

    window.fetch(window.wmWishlistData.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: params.toString()
    })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        var cards = (data && data.success && data.data && data.data.cards) || [];
        grid.innerHTML = cards.map(function (card) {
          return '<div class="wm-wishlist-page__item">'
            + card.html
            + '<button type="button" class="wm-wishlist-page__remove" data-wm-wishlist-remove data-product-id="' + card.id + '" aria-label="حذف از علاقه\u200cمندی\u200cها">×</button>'
            + '</div>';
        }).join('');

        if (!cards.length && empty) {
          empty.hidden = false;
        }
      })
      .catch(function () {
        grid.innerHTML = '';
        if (empty) {
          empty.hidden = false;
        }
      });
  }

  grid.addEventListener('click', function (event) {
    var remove = event.target.closest('[data-wm-wishlist-remove]');
    if (!remove) {
      return;
    }
    event.preventDefault();
    var id = remove.getAttribute('data-product-id');
    writeIds(readIds().filter(function (x) { return x !== id; }));
    window.dispatchEvent(new window.CustomEvent('wm:wishlist', { detail: { count: readIds().length } }));
    render();
  });

  render();
})();
