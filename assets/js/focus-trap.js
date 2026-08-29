/**
 * Shared focus trap for theme dialogs (search, OTP, cart drawer, tablet nav).
 *
 * Exposes window.wmFocusTrap:
 *   trap(container, restoreTo) — trap Tab inside `container`, restore focus
 *     to `restoreTo` on release.
 *   release() — idempotent; removes the trap and restores focus.
 *
 * Enqueued before every dialog script; each dialog calls trap() on open and
 * release() on close.
 */
(function () {
    'use strict';

    var active = null;

    function focusableElements(container) {
        return Array.prototype.filter.call(
            container.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
            ),
            function (el) {
                return el.offsetParent !== null || el === document.activeElement;
            }
        );
    }

    function trap(container, restoreTo) {
        release();

        active = {
            container: container,
            restoreTo: restoreTo || document.activeElement,
            onKeydown: function (event) {
                if (event.key !== 'Tab' || !active) {
                    return;
                }

                var items = focusableElements(active.container);
                if (!items.length) {
                    return;
                }

                var first = items[0];
                var last = items[items.length - 1];
                var current = document.activeElement;

                if (event.shiftKey && (current === first || current === active.container)) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && (current === last || current === active.container)) {
                    event.preventDefault();
                    first.focus();
                }
            }
        };

        document.addEventListener('keydown', active.onKeydown, true);
    }

    function release() {
        if (!active) {
            return;
        }

        var restoreTo = active.restoreTo;
        document.removeEventListener('keydown', active.onKeydown, true);
        active = null;

        if (restoreTo && typeof restoreTo.focus === 'function') {
            window.setTimeout(function () {
                if (restoreTo.isConnected) {
                    restoreTo.focus();
                }
            }, 0);
        }
    }

    window.wmFocusTrap = {
        trap: trap,
        release: release
    };
})();
