( function () {
    'use strict';

    var drawer = document.getElementById( 'wm-cart-drawer' );

    if ( ! drawer ) {
        return;
    }

    var toggles = document.querySelectorAll( '[data-wm-cart-toggle]' );
    var lastFocused = null;

    function openDrawer() {
        lastFocused = document.activeElement;
        drawer.classList.add( 'is-open' );
        document.body.classList.add( 'wm-cart-drawer-open' );

        toggles.forEach( function ( toggle ) {
            toggle.setAttribute( 'aria-expanded', 'true' );
        } );

        if ( window.wmFocusTrap ) {
            window.wmFocusTrap.trap( drawer, lastFocused );
        }

        var closeButton = drawer.querySelector( '.wm-cart-drawer__close' );
        if ( closeButton ) {
            closeButton.focus();
        }
    }

    function closeDrawer() {
        if ( window.wmFocusTrap ) {
            window.wmFocusTrap.release();
        }

        drawer.classList.remove( 'is-open' );
        document.body.classList.remove( 'wm-cart-drawer-open' );

        toggles.forEach( function ( toggle ) {
            toggle.setAttribute( 'aria-expanded', 'false' );
        } );
    }

    toggles.forEach( function ( toggle ) {
        toggle.addEventListener( 'click', function ( event ) {
            event.preventDefault();
            openDrawer();
        } );
    } );

    function parseQty( text ) {
        var match = String( text || '' ).match( /(\d+)/ );
        return match ? parseInt( match[ 1 ], 10 ) : 1;
    }

    function applyFragments( fragments ) {
        if ( ! fragments ) {
            return;
        }

        var body = drawer.querySelector( '[data-wm-cart-drawer-body]' );
        if ( fragments[ 'div.wm-cart-drawer__body' ] && body ) {
            body.innerHTML = fragments[ 'div.wm-cart-drawer__body' ];
        }

        var headerCount = document.querySelector( 'span.wm-site-header__cart-count' );
        var mobileBadge = document.querySelector( 'span.wm-mobile-nav__badge' );
        if ( fragments[ 'span.wm-site-header__cart-count' ] && headerCount ) {
            headerCount.outerHTML = fragments[ 'span.wm-site-header__cart-count' ];
        }
        if ( fragments[ 'span.wm-mobile-nav__badge' ] && mobileBadge ) {
            mobileBadge.outerHTML = fragments[ 'span.wm-mobile-nav__badge' ];
        }
    }

    function undoAdd( productId, quantity ) {
        if ( ! window.wmMiniCartData ) {
            return;
        }

        var params = new window.URLSearchParams();
        params.set( 'product_id', productId );
        params.set( 'quantity', String( quantity || 1 ) );

        window.fetch( window.wmMiniCartData.homeUrl + '?wc-ajax=add_to_cart', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        } )
            .then( function ( response ) { return response.json(); } )
            .then( function ( data ) {
                if ( data && data.fragments ) {
                    applyFragments( data.fragments );
                }
            } );
    }

    function showUndoToast( productId, quantity ) {
        var container = document.querySelector( '.wm-toast-container' );
        if ( ! container ) {
            container = document.createElement( 'div' );
            container.className = 'wm-toast-container';
            container.setAttribute( 'role', 'status' );
            container.setAttribute( 'aria-live', 'polite' );
            document.body.appendChild( container );
        }

        var toast = document.createElement( 'div' );
        toast.className = 'wm-toast wm-toast--info wm-toast--undo';

        var text = document.createElement( 'span' );
        text.textContent = 'محصول از سبد خرید حذف شد.';
        toast.appendChild( text );

        var undoButton = document.createElement( 'button' );
        undoButton.type = 'button';
        undoButton.className = 'wm-toast__action';
        undoButton.textContent = 'بازگرداندن';
        undoButton.addEventListener( 'click', function () {
            undoAdd( productId, quantity );
            dismiss();
        } );
        toast.appendChild( undoButton );

        container.appendChild( toast );
        window.requestAnimationFrame( function () {
            toast.classList.add( 'is-visible' );
        } );

        var timer = window.setTimeout( dismiss, 8000 );

        function dismiss() {
            window.clearTimeout( timer );
            toast.classList.remove( 'is-visible' );
            toast.classList.add( 'is-leaving' );
            window.setTimeout( function () {
                if ( toast.parentNode ) {
                    toast.parentNode.removeChild( toast );
                }
            }, 240 );
        }
    }

    drawer.addEventListener( 'click', function ( event ) {
        if ( event.target.closest( '[data-wm-cart-close]' ) ) {
            event.preventDefault();
            closeDrawer();
            return;
        }

        // Capture removed items for the undo toast. WooCommerce's own
        // fragment refresh keeps running; we only add the recovery action.
        var remove = event.target.closest( 'a.remove_from_cart_button' );
        if ( remove ) {
            var row = remove.closest( '.wm-cart-drawer__item' );
            var qtyEl = row ? row.querySelector( '.wm-cart-drawer__item-qty' ) : null;
            var productId = remove.getAttribute( 'data-product_id' );
            if ( productId ) {
                showUndoToast( productId, qtyEl ? parseQty( qtyEl.textContent ) : 1 );
            }
        }
    } );

    document.addEventListener( 'keydown', function ( event ) {
        if ( 'Escape' === event.key && drawer.classList.contains( 'is-open' ) ) {
            closeDrawer();
        }
    } );

    // WooCommerce's wc-cart.js fires `added_to_cart` on document.body via
    // jQuery's trigger(), which dispatches a real DOM event — a native
    // listener picks it up, so no jQuery dependency is needed here.
    document.body.addEventListener( 'added_to_cart', function () {
        openDrawer();
    } );
} )();
