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
  // data-mobile-shop-open="<view>"; clicking it crossfades to that item's
  // own pane (a flat sibling in the DOM, see wm_render_mobile_shop_sheet_panes())
  // inside the same modal sheet.
  const SHOP_ANIM_MS = 200;

  // The header's "مشاهده همه" or "تمام مدل های [نام دسته]" link points at
  // whatever is currently shown, and the back button shows when drilled down.
  function updateShopHeader(sheet, activeKey) {
    if (!sheet) {
      return;
    }
    const stage = sheet.querySelector('[data-mobile-shop-stage]');
    const header = sheet.querySelector('[data-mobile-shop-header]');
    if (!stage || !header) {
      return;
    }
    const pane = stage.querySelector('[data-mobile-shop-view="' + activeKey + '"]');
    if (!pane) {
      return;
    }
    const title = header.querySelector('[data-mobile-shop-title]');
    const viewAll = header.querySelector('[data-mobile-shop-view-all]');
    const backBtn = header.querySelector('[data-mobile-shop-back]');

    const isRoot = activeKey === 'root';

    if (backBtn) {
      backBtn.hidden = isRoot;
    }

    if (title) {
      const label = pane.getAttribute('data-mobile-shop-label');
      if (label) {
        title.textContent = isRoot ? 'فروشگاه' : label;
      }
    }
    if (viewAll) {
      const url = pane.getAttribute('data-mobile-shop-url');
      if (url) {
        viewAll.setAttribute('href', url);
      }
      const label = pane.getAttribute('data-mobile-shop-label');
      if (isRoot) {
        viewAll.textContent = 'مشاهده همه';
      } else if (label) {
        viewAll.textContent = 'تمام مدل های ' + label;
      }
    }
  }

  // Crossfades from whichever pane is currently visible to `targetKey`
  // (drilling deeper is the only direction — there's no in-modal way back,
  // the header's "مشاهده همه" link is how you leave a drilled-into category).
  function setActiveShopPane(stage, targetKey) {
    const target = stage.querySelector('[data-mobile-shop-view="' + targetKey + '"]');
    if (!target) {
      return;
    }
    const current = Array.prototype.find.call(
      stage.querySelectorAll('[data-mobile-shop-view]'),
      function(pane) { return pane !== target && !pane.hidden; }
    );

    if (!current && stage.getAttribute('data-mobile-shop-stage') === targetKey) {
      return;
    }

    target.hidden = false;
    target.classList.add('is-entering', 'is-from-right');
    // Force a reflow so the class above applies before we transition away from it.
    void target.offsetWidth; // eslint-disable-line no-void

    window.requestAnimationFrame(function() {
      target.classList.remove('is-entering', 'is-from-right');
      if (current) {
        current.classList.add('is-leaving', 'is-to-left');
      }
    });

    if (current) {
      window.setTimeout(function() {
        current.hidden = true;
        current.classList.remove('is-leaving', 'is-to-left');
      }, SHOP_ANIM_MS);
    }

    stage.setAttribute('data-mobile-shop-stage', targetKey);
    updateShopHeader(stage.closest('.wm-mobile-sheet'), targetKey);
  }

  function resetShopStage() {
    const stages = root.querySelectorAll('[data-mobile-shop-stage]');
    stages.forEach(function(stage) {
      const panes = stage.querySelectorAll('[data-mobile-shop-view]');
      panes.forEach(function(pane) {
        pane.hidden = true;
        pane.classList.remove('is-entering', 'is-leaving', 'is-from-right', 'is-to-left');
      });
      const rootPane = stage.querySelector('[data-mobile-shop-view="root"]');
      if (rootPane) {
        rootPane.hidden = false;
      }
      stage.setAttribute('data-mobile-shop-stage', 'root');
      const openButtons = stage.querySelectorAll('[data-mobile-shop-open]');
      openButtons.forEach(function(btn) {
        btn.setAttribute('aria-expanded', 'false');
      });
      updateShopHeader(stage.closest('.wm-mobile-sheet'), 'root');
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
    setActiveShopPane(stage, viewKey);

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

  // Delegated handlers for the drill-down sub-views and back button navigation.
  document.addEventListener('click', function(event) {
    const backBtn = event.target.closest('[data-mobile-shop-back]');
    if (backBtn) {
      event.preventDefault();
      const sheet = backBtn.closest('.wm-mobile-sheet');
      const stage = sheet ? sheet.querySelector('[data-mobile-shop-stage]') : null;
      if (!stage) {
        return;
      }
      const activeKey = stage.getAttribute('data-mobile-shop-stage');
      const activePane = stage.querySelector('[data-mobile-shop-view="' + activeKey + '"]');
      const parentKey = activePane ? activePane.getAttribute('data-mobile-shop-parent') : 'root';

      openShopView(stage, parentKey || 'root', null);
      return;
    }

    const openBtn = event.target.closest('[data-mobile-shop-open]');
    if (openBtn) {
      const stage = findShopStage(openBtn);
      if (!stage) {
        return;
      }
      event.preventDefault();
      const viewKey = openBtn.getAttribute('data-mobile-shop-open');
      openShopView(stage, viewKey, openBtn);
    }
  });
})();
