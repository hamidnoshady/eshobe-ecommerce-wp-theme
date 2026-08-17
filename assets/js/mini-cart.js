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

        var closeButton = drawer.querySelector( '.wm-cart-drawer__close' );
        if ( closeButton ) {
            closeButton.focus();
        }
    }

    function closeDrawer() {
        drawer.classList.remove( 'is-open' );
        document.body.classList.remove( 'wm-cart-drawer-open' );

        toggles.forEach( function ( toggle ) {
            toggle.setAttribute( 'aria-expanded', 'false' );
        } );

        if ( lastFocused && typeof lastFocused.focus === 'function' ) {
            lastFocused.focus();
        }
    }

    toggles.forEach( function ( toggle ) {
        toggle.addEventListener( 'click', function ( event ) {
            event.preventDefault();
            openDrawer();
        } );
    } );

    drawer.addEventListener( 'click', function ( event ) {
        if ( event.target.closest( '[data-wm-cart-close]' ) ) {
            event.preventDefault();
            closeDrawer();
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
