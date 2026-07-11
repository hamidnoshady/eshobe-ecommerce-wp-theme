/**
 * Converts WooCommerce's inline notices (success/error/info banners that
 * otherwise sit in the page until reload) into auto-dismissing toasts, and
 * keeps a short session history rendered into the account dropdown / mobile
 * account sheet. Moves the real notice node (not a clone) so links inside it
 * — e.g. the cart's "Undo" link — keep working via WooCommerce's own
 * delegated event handlers.
 */
( function() {
	'use strict';

	var AUTO_DISMISS_MS = 10000;
	var STORAGE_KEY = 'wmNotifications';
	var MAX_HISTORY = 8;

	function getToastContainer() {
		var container = document.querySelector( '.wm-toast-container' );
		if ( ! container ) {
			container = document.createElement( 'div' );
			container.className = 'wm-toast-container';
			container.setAttribute( 'role', 'status' );
			container.setAttribute( 'aria-live', 'polite' );
			document.body.appendChild( container );
		}
		return container;
	}

	function noticeType( el ) {
		if ( el.classList.contains( 'woocommerce-error' ) ) {
			return 'error';
		}
		if ( el.classList.contains( 'woocommerce-info' ) ) {
			return 'info';
		}
		return 'success';
	}

	function saveToHistory( text, type ) {
		var list = [];
		try {
			list = JSON.parse( sessionStorage.getItem( STORAGE_KEY ) || '[]' );
		} catch ( e ) {}
		list.unshift( { text: text, type: type } );
		list = list.slice( 0, MAX_HISTORY );
		try {
			sessionStorage.setItem( STORAGE_KEY, JSON.stringify( list ) );
		} catch ( e ) {}
		renderNotificationLists();
	}

	function dismissToast( toast ) {
		toast.classList.remove( 'is-visible' );
		toast.classList.add( 'is-leaving' );
		window.setTimeout( function() {
			if ( toast.parentNode ) {
				toast.parentNode.removeChild( toast );
			}
		}, 240 );
	}

	function toastify( notice ) {
		if ( ! notice || notice.dataset.wmToasted ) {
			return;
		}
		notice.dataset.wmToasted = '1';

		var type = noticeType( notice );
		var text = notice.textContent.trim();

		notice.classList.add( 'wm-toast', 'wm-toast--' + type );

		var closeButton = document.createElement( 'button' );
		closeButton.type = 'button';
		closeButton.className = 'wm-toast__close';
		closeButton.setAttribute( 'aria-label', 'بستن اعلان' );
		closeButton.innerHTML = '&times;';
		closeButton.addEventListener( 'click', function() {
			dismissToast( notice );
		} );
		notice.appendChild( closeButton );

		getToastContainer().appendChild( notice );
		window.requestAnimationFrame( function() {
			notice.classList.add( 'is-visible' );
		} );

		if ( text ) {
			saveToHistory( text, type );
		}

		window.setTimeout( function() {
			if ( notice.isConnected ) {
				dismissToast( notice );
			}
		}, AUTO_DISMISS_MS );
	}

	function toastifyWrapper( wrapper ) {
		var notices = wrapper.querySelectorAll( '.woocommerce-message, .woocommerce-error, .woocommerce-info' );
		notices.forEach( toastify );
	}

	function scanExisting() {
		document.querySelectorAll( '.woocommerce-notices-wrapper' ).forEach( toastifyWrapper );
		document.querySelectorAll( 'body > ul.woocommerce-error, body > ul.woocommerce-message, body > div.woocommerce-info' ).forEach( toastify );
	}

	function watchForNewNotices() {
		var observer = new MutationObserver( function( mutations ) {
			mutations.forEach( function( mutation ) {
				mutation.addedNodes.forEach( function( node ) {
					if ( node.nodeType !== 1 ) {
						return;
					}
					if ( node.classList && node.classList.contains( 'woocommerce-notices-wrapper' ) ) {
						toastifyWrapper( node );
						return;
					}
					if ( node.matches && node.matches( '.woocommerce-message, .woocommerce-error, .woocommerce-info' ) ) {
						toastify( node );
						return;
					}
					if ( node.querySelectorAll ) {
						node.querySelectorAll( '.woocommerce-notices-wrapper' ).forEach( toastifyWrapper );
						node.querySelectorAll( '.woocommerce-message, .woocommerce-error, .woocommerce-info' ).forEach( toastify );
					}
				} );
			} );
		} );

		observer.observe( document.body, { childList: true, subtree: true } );
	}

	function renderNotificationLists() {
		var list = [];
		try {
			list = JSON.parse( sessionStorage.getItem( STORAGE_KEY ) || '[]' );
		} catch ( e ) {}

		document.querySelectorAll( '[data-wm-notification-list]' ).forEach( function( target ) {
			target.innerHTML = '';

			if ( ! list.length ) {
				var empty = document.createElement( 'p' );
				empty.className = 'wm-notification-empty';
				empty.textContent = 'اعلان جدیدی ندارید.';
				target.appendChild( empty );
				return;
			}

			list.forEach( function( item ) {
				var entry = document.createElement( 'div' );
				entry.className = 'wm-notification-item wm-notification-item--' + item.type;
				entry.textContent = item.text;
				target.appendChild( entry );
			} );
		} );
	}

	function setupAccountDropdown() {
		var trigger = document.querySelector( '[data-wm-account-toggle]' );
		var dropdown = document.querySelector( '[data-wm-account-dropdown]' );

		if ( ! trigger || ! dropdown ) {
			return;
		}

		function close() {
			dropdown.classList.remove( 'is-open' );
			trigger.setAttribute( 'aria-expanded', 'false' );
		}

		function open() {
			dropdown.classList.add( 'is-open' );
			trigger.setAttribute( 'aria-expanded', 'true' );
		}

		trigger.addEventListener( 'click', function( event ) {
			event.preventDefault();
			if ( dropdown.classList.contains( 'is-open' ) ) {
				close();
			} else {
				open();
			}
		} );

		document.addEventListener( 'click', function( event ) {
			if ( ! dropdown.contains( event.target ) && ! trigger.contains( event.target ) ) {
				close();
			}
		} );

		document.addEventListener( 'keydown', function( event ) {
			if ( event.key === 'Escape' ) {
				close();
			}
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function() {
		scanExisting();
		renderNotificationLists();
		watchForNewNotices();
		setupAccountDropdown();
	} );
} )();
