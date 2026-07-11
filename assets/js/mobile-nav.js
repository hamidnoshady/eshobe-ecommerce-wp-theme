(function() {
  const root = document.querySelector('[data-mobile-nav-root]');
  if (!root) {
    return;
  }

  // Queried document-wide (not scoped to `root`) so triggers living outside
  // the shell — e.g. the product page's mobile purchase bar, which replaces
  // the bottom nav bar but still needs a way to open the shop sheet — work too.
  const sheetButtons = Array.from(document.querySelectorAll('[data-mobile-sheet-target]'));
  const sheets = Array.from(root.querySelectorAll('[data-mobile-sheet]'));
  const backdrop = root.querySelector('.wm-mobile-sheet__backdrop');
  const closeButtons = root.querySelectorAll('[data-mobile-sheet-close]');

  function closeSheets() {
    root.classList.remove('is-sheet-open');
    sheets.forEach(function(sheet) {
      sheet.classList.remove('is-open');
      sheet.setAttribute('aria-hidden', 'true');
    });
    sheetButtons.forEach(function(button) {
      button.setAttribute('aria-expanded', 'false');
      button.classList.remove('is-active');
    });
    if (backdrop) {
      backdrop.classList.remove('is-active');
      window.setTimeout(function() {
        if (!root.classList.contains('is-sheet-open')) {
          backdrop.hidden = true;
        }
      }, 280);
    }
  }

  function openSheet(target) {
    const sheet = sheets.find(function(item) {
      return item.getAttribute('data-mobile-sheet') === target;
    });
    const button = sheetButtons.find(function(item) {
      return item.getAttribute('data-mobile-sheet-target') === target;
    });

    if (!sheet) {
      return;
    }
    closeSheets();
    root.classList.add('is-sheet-open');
    sheet.classList.add('is-open');
    sheet.setAttribute('aria-hidden', 'false');
    if (button) {
      button.setAttribute('aria-expanded', 'true');
      button.classList.add('is-active');
    }
    if (backdrop) {
      backdrop.hidden = false;
      window.requestAnimationFrame(function() {
        backdrop.classList.add('is-active');
      });
    }
  }

  sheetButtons.forEach(function(button) {
    button.addEventListener('click', function() {
      const target = button.getAttribute('data-mobile-sheet-target');
      const isOpen = button.getAttribute('aria-expanded') === 'true';
      if (isOpen) {
        closeSheets();
      } else {
        openSheet(target);
      }
    });
  });

  closeButtons.forEach(function(button) {
    button.addEventListener('click', closeSheets);
  });

  sheets.forEach(function(sheet) {
    sheet.addEventListener('click', function(event) {
      if (event.target === sheet) {
        closeSheets();
      }
    });
  });

  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
      closeSheets();
    }
  });
})();
