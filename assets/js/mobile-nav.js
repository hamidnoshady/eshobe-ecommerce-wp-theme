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
    resetShopStage();
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

  // Shop drill-down: each non-leaf menu item renders as a button with
  // data-mobile-shop-open="<view>"; clicking it swaps which sub-view stack
  // panel is visible inside the same modal sheet.
  function resetShopStage() {
    const stages = root.querySelectorAll('[data-mobile-shop-stage]');
    stages.forEach(function(stage) {
      const panes = stage.querySelectorAll('[data-mobile-shop-view]');
      panes.forEach(function(pane) {
        pane.hidden = true;
      });
      const rootPane = stage.querySelector('[data-mobile-shop-view="root"]');
      if (rootPane) {
        rootPane.hidden = false;
        stage.setAttribute('data-mobile-shop-stage', 'root');
      }
      const openButtons = stage.querySelectorAll('[data-mobile-shop-open]');
      openButtons.forEach(function(btn) {
        btn.setAttribute('aria-expanded', 'false');
      });
    });
  }

  function findShopStage(button) {
    let node = button.parentElement;
    while (node && !node.hasAttribute('data-mobile-shop-stage')) {
      node = node.parentElement;
    }
    return node;
  }

  function openShopView(stage, viewKey, triggerButton) {
    const target = stage.querySelector('[data-mobile-shop-view="' + viewKey + '"]');
    if (!target) {
      return;
    }

    const panes = stage.querySelectorAll('[data-mobile-shop-view]');
    panes.forEach(function(pane) {
      pane.hidden = true;
    });
    target.hidden = false;
    stage.setAttribute('data-mobile-shop-stage', viewKey);

    if (triggerButton) {
      triggerButton.setAttribute('aria-expanded', 'true');
    }

    // Reset and re-collapse open buttons beneath the new view so the indicator
    // state is consistent regardless of how deep the user has drilled.
    const nestedOpenButtons = stage.querySelectorAll('[data-mobile-shop-open]');
    nestedOpenButtons.forEach(function(btn) {
      if (btn === triggerButton) {
        return;
      }
      btn.setAttribute('aria-expanded', 'false');
    });

    if (typeof target.scrollIntoView === 'function') {
      const sheet = stage.closest('.wm-mobile-sheet');
      const body = sheet ? sheet.querySelector('.wm-mobile-sheet__body') : null;
      if (body) {
        body.scrollTop = 0;
      }
    }
  }

  function backShopView(stage) {
    const current = stage.getAttribute('data-mobile-shop-stage') || 'root';
    if (current === 'root') {
      return;
    }
    const currentPane = stage.querySelector('[data-mobile-shop-view="' + current + '"]');
    const parentKey = currentPane ? currentPane.getAttribute('data-mobile-shop-parent') : '';
    const targetKey = parentKey || 'root';

    const target = stage.querySelector('[data-mobile-shop-view="' + targetKey + '"]');
    if (!target) {
      return;
    }
    const panes = stage.querySelectorAll('[data-mobile-shop-view]');
    panes.forEach(function(pane) {
      pane.hidden = true;
    });
    target.hidden = false;
    stage.setAttribute('data-mobile-shop-stage', targetKey);

    const openButtons = stage.querySelectorAll('[data-mobile-shop-open]');
    openButtons.forEach(function(btn) {
      btn.setAttribute('aria-expanded', 'false');
    });
    // Restore `aria-expanded="true"` for the trigger that opened this view
    // chain leading up to currentKey.
    const openKey = targetKey === 'root' ? null : targetKey;
    if (openKey) {
      const rootTrigger = stage.querySelector('[data-mobile-shop-open="' + openKey + '"]');
      if (rootTrigger) {
        rootTrigger.setAttribute('aria-expanded', 'true');
      }
    }

    const sheet = stage.closest('.wm-mobile-sheet');
    const body = sheet ? sheet.querySelector('.wm-mobile-sheet__body') : null;
    if (body) {
      body.scrollTop = 0;
    }
  }

  resetShopStage();

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

  // Delegated handlers for the drill-down sub-views.
  document.addEventListener('click', function(event) {
    const openBtn = event.target.closest('[data-mobile-shop-open]');
    if (openBtn) {
      const stage = findShopStage(openBtn);
      if (!stage) {
        return;
      }
      event.preventDefault();
      const viewKey = openBtn.getAttribute('data-mobile-shop-open');
      openShopView(stage, viewKey, openBtn);
      return;
    }

    const backBtn = event.target.closest('[data-mobile-shop-back]');
    if (backBtn) {
      const stage = findShopStage(backBtn);
      if (!stage) {
        return;
      }
      event.preventDefault();
      backShopView(stage);
    }
  });
})();
