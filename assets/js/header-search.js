(function () {
  var toggle = document.querySelector('.wm-site-header__search-toggle');
  var modal = document.getElementById('wm-search-modal');

  if (!toggle || !modal) {
    return;
  }

  var field = modal.querySelector('.wm-search-modal__field');
  var suggestions = modal.querySelector('[data-search-suggestions]');
  var resultsWrap = modal.querySelector('[data-search-results]');
  var resultsList = modal.querySelector('[data-search-results-list]');
  var viewAll = modal.querySelector('[data-search-view-all]');
  var empty = modal.querySelector('[data-search-empty]');
  var closers = modal.querySelectorAll('[data-search-modal-close]');
  var debounceTimer = 0;
  var currentRequest = null;

  function openModal() {
    modal.hidden = false;
    document.body.classList.add('wm-search-modal-open');
    toggle.setAttribute('aria-expanded', 'true');
    window.setTimeout(function () {
      field.focus();
    }, 30);
  }

  function closeModal() {
    modal.hidden = true;
    document.body.classList.remove('wm-search-modal-open');
    toggle.setAttribute('aria-expanded', 'false');
  }

  function showSuggestions() {
    if (suggestions) {
      suggestions.hidden = suggestions.children.length === 0;
    }
    resultsWrap.hidden = true;
    empty.hidden = true;
  }

  function renderResults(results, term) {
    resultsList.innerHTML = '';

    if (!results.length) {
      resultsWrap.hidden = true;
      empty.hidden = false;
      if (suggestions) {
        suggestions.hidden = true;
      }
      return;
    }

    empty.hidden = true;
    if (suggestions) {
      suggestions.hidden = true;
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
      title.textContent = item.title;
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

  toggle.addEventListener('click', function (event) {
    event.preventDefault();
    openModal();
  });

  closers.forEach(function (el) {
    el.addEventListener('click', closeModal);
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !modal.hidden) {
      closeModal();
    }
  });
})();
