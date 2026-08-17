(function () {
  var toggle = document.querySelector('.wm-site-header__search-toggle');
  var modal = document.getElementById('wm-search-modal');

  if (!toggle || !modal) {
    return;
  }

  var field = modal.querySelector('.wm-search-modal__field');
  var form = modal.querySelector('form.wm-search-modal__form, form[role="search"]');
  var suggestions = modal.querySelector('[data-search-suggestions]');
  var recentWrap = modal.querySelector('[data-search-recent]');
  var recentChips = modal.querySelector('[data-search-recent-chips]');
  var resultsWrap = modal.querySelector('[data-search-results]');
  var resultsList = modal.querySelector('[data-search-results-list]');
  var viewAll = modal.querySelector('[data-search-view-all]');
  var empty = modal.querySelector('[data-search-empty]');
  var closers = modal.querySelectorAll('[data-search-modal-close]');
  var debounceTimer = 0;
  var currentRequest = null;
  var closeTimer = 0;
  var activeIndex = -1;
  var CLOSE_ANIMATION_MS = 240;
  var RECENT_KEY = 'wmRecentSearches';
  var RECENT_MAX = 8;

  /* ── recent searches (localStorage) ── */

  function readRecent() {
    try {
      return JSON.parse(window.localStorage.getItem(RECENT_KEY)) || [];
    } catch (e) {
      return [];
    }
  }

  function saveRecent(term) {
    var list = readRecent().filter(function (t) { return t !== term; });
    list.unshift(term);
    list = list.slice(0, RECENT_MAX);
    try {
      window.localStorage.setItem(RECENT_KEY, JSON.stringify(list));
    } catch (e) {}
  }

  function renderRecent() {
    if (!recentWrap || !recentChips) {
      return;
    }
    var list = readRecent();
    recentChips.innerHTML = '';
    if (!list.length) {
      recentWrap.hidden = true;
      return;
    }
    list.forEach(function (term) {
      var chip = document.createElement('button');
      chip.type = 'button';
      chip.className = 'wm-search-modal__recent-chip';
      chip.textContent = term;
      chip.addEventListener('click', function () {
        field.value = term;
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.focus();
      });
      recentChips.appendChild(chip);
    });
    recentWrap.hidden = false;
  }

  function showSuggestions() {
    activeIndex = -1;
    renderRecent();
    if (suggestions) {
      suggestions.hidden = suggestions.children.length === 0;
    }
    resultsWrap.hidden = true;
    empty.hidden = true;
  }

  /* ── helpers ── */

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function highlight(text, term) {
    var escaped = escapeHtml(text);
    var needle = term.toLowerCase();
    var idx = escaped.toLowerCase().indexOf(needle);
    if (idx === -1) {
      return escaped;
    }
    return escaped.slice(0, idx)
      + '<mark>' + escaped.slice(idx, idx + needle.length) + '</mark>'
      + escaped.slice(idx + needle.length);
  }

  function setActive(links, index) {
    activeIndex = index;
    links.forEach(function (link, i) {
      link.classList.toggle('is-active', i === index);
    });
    if (links[index] && links[index].scrollIntoView) {
      links[index].scrollIntoView({ block: 'nearest' });
    }
  }

  function renderResults(results, term) {
    activeIndex = -1;
    resultsList.innerHTML = '';

    if (!results.length) {
      resultsWrap.hidden = true;
      empty.hidden = false;
      if (suggestions) {
        suggestions.hidden = true;
      }
      if (recentWrap) {
        recentWrap.hidden = true;
      }
      return;
    }

    empty.hidden = true;
    if (suggestions) {
      suggestions.hidden = true;
    }
    if (recentWrap) {
      recentWrap.hidden = true;
    }

    results.forEach(function (item) {
      var li = document.createElement('li');
      li.className = 'wm-search-result';

      var link = document.createElement('a');
      link.className = 'wm-search-result__link';
      link.href = item.permalink;

      var media = document.createElement('span');
      media.className = 'wm-search-result__media';
      var img = document.createElement('img');
      img.className = 'wm-search-result__image';
      img.src = item.image;
      img.alt = item.title;
      img.loading = 'lazy';
      media.appendChild(img);

      var info = document.createElement('span');
      info.className = 'wm-search-result__info';
      var title = document.createElement('span');
      title.className = 'wm-search-result__title';
      title.innerHTML = highlight(item.title, term);
      info.appendChild(title);

      if (item.brand) {
        var brand = document.createElement('span');
        brand.className = 'wm-search-result__brand';
        brand.textContent = item.brand;
        info.appendChild(brand);
      }

      var price = document.createElement('span');
      price.className = 'wm-search-result__price';
      price.innerHTML = item.price;

      link.appendChild(media);
      link.appendChild(info);
      link.appendChild(price);
      li.appendChild(link);
      resultsList.appendChild(li);
    });

    resultsWrap.hidden = false;

    if (viewAll) {
      var url = window.wmSearchData.searchUrl + '?s=' + encodeURIComponent(term) + '&post_type=product';
      viewAll.href = url;
      viewAll.hidden = false;
    }
  }

  function fetchResults(term) {
    if (currentRequest) {
      currentRequest.abort();
    }

    var controller = new AbortController();
    currentRequest = controller;

    var url = window.wmSearchData.ajaxUrl
      + '?action=wm_search_products'
      + '&nonce=' + encodeURIComponent(window.wmSearchData.nonce)
      + '&term=' + encodeURIComponent(term);

    fetch(url, { signal: controller.signal })
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        if (data && data.success) {
          renderResults(data.data.results || [], term);
        }
      })
      .catch(function (error) {
        if (error.name !== 'AbortError') {
          renderResults([], term);
        }
      });
  }

  /* ── modal open/close ── */

  function openModal() {
    window.clearTimeout(closeTimer);
    modal.hidden = false;
    document.body.classList.add('wm-search-modal-open');
    toggle.setAttribute('aria-expanded', 'true');
    if (window.wmFocusTrap) {
      window.wmFocusTrap.trap(modal, toggle);
    }
    window.requestAnimationFrame(function () {
      modal.classList.add('is-open');
    });
    window.setTimeout(function () {
      field.focus();
      renderRecent();
    }, 30);
  }

  function closeModal() {
    if (modal.hidden) {
      return;
    }

    if (window.wmFocusTrap) {
      window.wmFocusTrap.release();
    }
    modal.classList.remove('is-open');
    document.body.classList.remove('wm-search-modal-open');
    toggle.setAttribute('aria-expanded', 'false');

    window.clearTimeout(closeTimer);
    closeTimer = window.setTimeout(function () {
      modal.hidden = true;
    }, CLOSE_ANIMATION_MS);
  }

  /* ── input + keyboard navigation ── */

  field.addEventListener('input', function () {
    var term = field.value.trim();

    window.clearTimeout(debounceTimer);

    if (term.length < 2) {
      showSuggestions();
      return;
    }

    debounceTimer = window.setTimeout(function () {
      fetchResults(term);
    }, 300);
  });

  field.addEventListener('keydown', function (event) {
    if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp' && event.key !== 'Enter') {
      return;
    }

    var links = Array.prototype.slice.call(resultsList.querySelectorAll('a.wm-search-result__link'));
    if (!links.length) {
      return;
    }

    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
      event.preventDefault();
      var next = event.key === 'ArrowDown'
        ? Math.min(activeIndex + 1, links.length - 1)
        : Math.max(activeIndex - 1, 0);
      setActive(links, next);
    } else if (event.key === 'Enter' && activeIndex >= 0) {
      event.preventDefault();
      links[activeIndex].click();
    }
  });

  // Remember the term when the modal form submits (full search page).
  if (form) {
    form.addEventListener('submit', function () {
      var term = field.value.trim();
      if (term) {
        saveRecent(term);
      }
    });
  }

  toggle.addEventListener('click', function (event) {
    event.preventDefault();
    openModal();
  });

  closers.forEach(function (el) {
    el.addEventListener('click', closeModal);
  });

  // Global shortcuts: Ctrl/Cmd+K or "/" open the modal.
  document.addEventListener('keydown', function (event) {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
      event.preventDefault();
      openModal();
      return;
    }

    if (event.key === '/' && !event.ctrlKey && !event.metaKey && !event.altKey) {
      var target = event.target;
      if (target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable)) {
        return;
      }
      event.preventDefault();
      openModal();
    }

    if (event.key === 'Escape' && !modal.hidden) {
      closeModal();
    }
  });
})();
