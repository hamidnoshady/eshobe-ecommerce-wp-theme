// ponytail: no wishlist plugin/backend exists in this theme, so state is
// stored client-side only (localStorage). Swap for a real endpoint if
// cross-device persistence is ever needed.
(function () {
  var STORAGE_KEY = 'wmWishlist';

  function readWishlist() {
    try {
      return JSON.parse(window.localStorage.getItem(STORAGE_KEY)) || [];
    } catch (e) {
      return [];
    }
  }

  function writeWishlist(ids) {
    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(ids));
    } catch (e) {
      // Storage unavailable (private mode, quota) — state just won't persist.
    }
  }

  function updateBadge(count) {
    document.querySelectorAll('[data-wm-wishlist-count]').forEach(function (el) {
      el.textContent = String(count);
      el.hidden = count === 0;
    });
  }

  // Keep every badge on the page (and across tabs) in sync.
  updateBadge(readWishlist().length);
  window.addEventListener('storage', function (event) {
    if (event.key === STORAGE_KEY) {
      updateBadge(readWishlist().length);
    }
  });
  window.addEventListener('wm:wishlist', function (event) {
    if (event.detail && typeof event.detail.count === 'number') {
      updateBadge(event.detail.count);
    } else {
      updateBadge(readWishlist().length);
    }
  });

  document.querySelectorAll('[data-wm-wishlist-toggle]').forEach(function (button) {
    var id = button.getAttribute('data-product-id');
    var isSaved = readWishlist().indexOf(id) !== -1;
    button.classList.toggle('is-active', isSaved);
    button.setAttribute('aria-pressed', isSaved ? 'true' : 'false');

    button.addEventListener('click', function () {
      var ids = readWishlist();
      var index = ids.indexOf(id);
      var nowSaved = index === -1;

      if (nowSaved) {
        ids.push(id);
      } else {
        ids.splice(index, 1);
      }

      writeWishlist(ids);
      button.classList.toggle('is-active', nowSaved);
      button.setAttribute('aria-pressed', nowSaved ? 'true' : 'false');

      window.dispatchEvent(new window.CustomEvent('wm:wishlist', { detail: { count: ids.length } }));
    });
  });
})();
