(function() {
  const header = document.querySelector('.wm-site-header');
  const desktopQuery = window.matchMedia('(min-width: 1024px)');
  const megaRoot = document.querySelector('.wm-mega-menu');
  const megaPanels = megaRoot ? Array.from(megaRoot.querySelectorAll('.wm-mega-menu__panel[data-mega-key]')) : [];
  const megaTriggers = Array.from(document.querySelectorAll('.wm-mega-trigger'));
  let activeMegaKey = '';
  let closeMegaTimer = 0;

  if (header && header.classList.contains('is-sticky')) {
    const updateScrolledState = function() {
      header.classList.toggle('is-scrolled', window.scrollY > 16);
    };

    updateScrolledState();
    window.addEventListener('scroll', updateScrolledState, { passive: true });
  }

  function getMegaKeyFromTrigger(trigger) {
    if (!trigger) {
      return '';
    }

    const link = getMegaTriggerLink(trigger);

    return link && link.dataset ? (link.dataset.megaKey || '') : '';
  }

  function getMegaTrigger(key) {
    if (!key) {
      return null;
    }

    const link = document.querySelector('[data-mega-key="' + window.CSS.escape(key) + '"]');

    return link ? link.closest('.wm-mega-trigger') : null;
  }

  function getMegaTriggerLink(trigger) {
    if (!trigger) {
      return null;
    }

    return trigger.matches && trigger.matches('a') ? trigger : trigger.querySelector('a');
  }

  function setMegaTriggerState(key, expanded) {
    const triggers = megaTriggers.filter(function(item) {
      return getMegaKeyFromTrigger(item) === key;
    });

    triggers.forEach(function(trigger) {
      trigger.classList.toggle('wm-mega-trigger-active', expanded);
      trigger.classList.toggle('is-active', expanded);
      const link = getMegaTriggerLink(trigger);
      if (link) {
        link.setAttribute('aria-expanded', expanded ? 'true' : 'false');
      }
    });
  }

  function closeMegaMenu() {
    if (!megaPanels.length) {
      return;
    }

    window.clearTimeout(closeMegaTimer);
    megaPanels.forEach(function(panel) {
      panel.classList.remove('is-active');
      panel.classList.remove('is-open');
      panel.classList.remove('hidden');
      panel.removeAttribute('hidden');
      panel.setAttribute('aria-hidden', 'true');
    });

    megaTriggers.forEach(function(trigger) {
      trigger.classList.remove('wm-mega-trigger-active');
      trigger.classList.remove('is-active');
      const link = getMegaTriggerLink(trigger);
      if (link) {
        link.setAttribute('aria-expanded', 'false');
      }
    });

    activeMegaKey = '';
  }

  function openMegaMenu(key) {
    if (!desktopQuery.matches || !key || !megaPanels.length) {
      return;
    }

    const panel = megaPanels.find(function(item) {
      return item.getAttribute('data-mega-key') === key;
    });

    if (!panel) {
      closeMegaMenu();
      return;
    }

    window.clearTimeout(closeMegaTimer);
    if (activeMegaKey && activeMegaKey !== key) {
      setMegaTriggerState(activeMegaKey, false);
    }

    megaPanels.forEach(function(item) {
      const isActive = item === panel;
      item.classList.remove('hidden');
      item.removeAttribute('hidden');
      item.classList.toggle('is-active', isActive);
      item.classList.toggle('is-open', isActive);
      item.setAttribute('aria-hidden', isActive ? 'false' : 'true');
    });

    activeMegaKey = key;
    setMegaTriggerState(key, true);
  }

  function scheduleMegaClose() {
    window.clearTimeout(closeMegaTimer);
    closeMegaTimer = window.setTimeout(closeMegaMenu, 140);
  }

  function cancelMegaClose() {
    window.clearTimeout(closeMegaTimer);
  }

  if (megaPanels.length) {
    megaTriggers.forEach(function(trigger) {
      const key = getMegaKeyFromTrigger(trigger);
      const panel = megaPanels.find(function(item) {
        return item.getAttribute('data-mega-key') === key;
      });
      const link = getMegaTriggerLink(trigger);

      if (!panel || !link) {
        return;
      }

      link.setAttribute('aria-haspopup', 'true');
      link.setAttribute('aria-expanded', 'false');

      trigger.addEventListener('mouseenter', function() {
        openMegaMenu(key);
      });

      trigger.addEventListener('focusin', function() {
        openMegaMenu(key);
      });

      trigger.addEventListener('mouseleave', scheduleMegaClose);
      trigger.addEventListener('focusout', function(event) {
        if (!trigger.contains(event.relatedTarget) && !panel.contains(event.relatedTarget)) {
          scheduleMegaClose();
        }
      });
    });

    megaPanels.forEach(function(panel) {
      panel.addEventListener('mouseenter', cancelMegaClose);
      panel.addEventListener('mouseleave', scheduleMegaClose);
      panel.addEventListener('focusin', cancelMegaClose);
      panel.addEventListener('focusout', function(event) {
        const trigger = getMegaTrigger(panel.getAttribute('data-mega-key'));
        if (!panel.contains(event.relatedTarget) && (!trigger || !trigger.contains(event.relatedTarget))) {
          scheduleMegaClose();
        }
      });
    });

    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape') {
        closeMegaMenu();
      }
    });

    document.addEventListener('click', function(event) {
      if (!activeMegaKey) {
        return;
      }

      const activeTrigger = getMegaTrigger(activeMegaKey);
      if ((megaRoot && megaRoot.contains(event.target)) || (activeTrigger && activeTrigger.contains(event.target))) {
        return;
      }

      closeMegaMenu();
    });

    desktopQuery.addEventListener('change', function(event) {
      if (!event.matches) {
        closeMegaMenu();
      }
    });
  }

  const tabletToggle = document.querySelector('.wm-site-header__tablet-toggle');
  const tabletDrawer = document.getElementById('wm-tablet-nav-drawer');
  const tabletBackdrop = document.querySelector('.wm-tablet-nav-drawer__backdrop');

  if (tabletToggle && tabletDrawer && tabletBackdrop) {
    const closeTabletDrawer = function() {
      if (window.wmFocusTrap) {
        window.wmFocusTrap.release();
      }
      tabletDrawer.classList.remove('is-open');
      tabletDrawer.hidden = true;
      tabletBackdrop.hidden = true;
      tabletToggle.setAttribute('aria-expanded', 'false');
    };

    const openTabletDrawer = function() {
      tabletDrawer.hidden = false;
      tabletBackdrop.hidden = false;
      if (window.wmFocusTrap) {
        window.wmFocusTrap.trap(tabletDrawer, tabletToggle);
      }
      requestAnimationFrame(function() {
        tabletDrawer.classList.add('is-open');
      });
      tabletToggle.setAttribute('aria-expanded', 'true');
    };

    tabletToggle.addEventListener('click', function() {
      if (tabletDrawer.classList.contains('is-open')) {
        closeTabletDrawer();
      } else {
        openTabletDrawer();
      }
    });

    tabletBackdrop.addEventListener('click', closeTabletDrawer);
    tabletDrawer.querySelectorAll('[data-tablet-nav-close]').forEach(function(el) {
      el.addEventListener('click', closeTabletDrawer);
    });

    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape') {
        closeTabletDrawer();
      }
    });

    window.matchMedia('(max-width: 1023px)').addEventListener('change', function(event) {
      if (!event.matches) {
        closeTabletDrawer();
      }
    });
  }
})();
