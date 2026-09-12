/**
 * Company pages (About Us / Contact Us) front-end behavior:
 *
 * 1. AJAX submit for the wm/contact-form block (nonce comes from the rendered
 *    form; falls back to a normal admin-post.php POST when fetch fails).
 * 2. Animated counters for wm/stat-item numbers (IntersectionObserver).
 * 3. Scroll-reveal for .wm-company-section, mirroring the homepage's
 *    .is-motion-ready pattern.
 *
 * All features degrade gracefully: no JS = native form POST, static numbers,
 * fully visible sections.
 */
( function () {
	'use strict';

	var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	/* -----------------------------------------------------------------
	 * Contact form
	 * -------------------------------------------------------------- */
	function initContactForms() {
		var forms = document.querySelectorAll( '[data-wm-contact-form]' );
		if ( ! forms.length || ! window.fetch || ! window.FormData ) {
			return;
		}

		Array.prototype.forEach.call( forms, function ( form ) {
			form.addEventListener( 'submit', function ( event ) {
				event.preventDefault();

				if ( form.classList.contains( 'is-submitting' ) ) {
					return;
				}

				var notice = form.querySelector( '.wm-company-form__notice' );
				var submit = form.querySelector( '.wm-company-form__submit' );
				var data = new FormData( form );
				var ajaxUrl = ( window.wmCompanyData && window.wmCompanyData.ajaxUrl ) || '/wp-admin/admin-ajax.php';

				// Native validation first (form has novalidate for styling control).
				if ( typeof form.reportValidity === 'function' && ! form.reportValidity() ) {
					return;
				}

				form.classList.add( 'is-submitting' );
				if ( submit ) {
					submit.disabled = true;
				}
				if ( notice ) {
					notice.removeAttribute( 'data-state' );
					notice.textContent = '';
				}

				fetch( ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					body: data,
				} )
					.then( function ( response ) {
						return response.json().catch( function () {
							return { success: false };
						} );
					} )
					.then( function ( json ) {
						if ( json && json.success ) {
							form.reset();
							setNotice( notice, 'success', form.getAttribute( 'data-success-message' ) || 'پیام شما ارسال شد.' );
						} else {
							var message = json && json.data && json.data.message ? json.data.message : 'ارسال پیام ناموفق بود. لطفاً دوباره تلاش کنید.';
							setNotice( notice, 'error', message );
						}
					} )
					.catch( function () {
						// Network failure — fall back to the classic POST flow.
						// form.submit() does not re-fire the submit event.
						form.submit();
					} )
					.finally( function () {
						form.classList.remove( 'is-submitting' );
						if ( submit ) {
							submit.disabled = false;
						}
					} );
			} );
		} );
	}

	function setNotice( notice, state, text ) {
		if ( ! notice ) {
			return;
		}
		notice.setAttribute( 'data-state', state );
		notice.textContent = text;
	}

	/* -----------------------------------------------------------------
	 * Stat counters
	 * -------------------------------------------------------------- */
	function formatNumber( value ) {
		try {
			var locale = ( document.documentElement.lang || 'fa-IR' ).replace( '_', '-' );
			return new Intl.NumberFormat( locale ).format( value );
		} catch ( e ) {
			return String( value );
		}
	}

	function animateCount( el ) {
		var target = parseFloat( el.getAttribute( 'data-wm-count' ) );
		if ( isNaN( target ) || target <= 0 || reduceMotion ) {
			return; // Server-rendered final value stays.
		}

		var duration = Math.min( 1600, 600 + target );
		var start = null;

		function step( timestamp ) {
			if ( ! start ) {
				start = timestamp;
			}
			var progress = Math.min( ( timestamp - start ) / duration, 1 );
			// easeOutCubic
			var eased = 1 - Math.pow( 1 - progress, 3 );
			el.textContent = formatNumber( Math.round( target * eased ) );
			if ( progress < 1 ) {
				window.requestAnimationFrame( step );
			}
		}

		window.requestAnimationFrame( step );
	}

	function initCounters() {
		var numbers = document.querySelectorAll( '.wm-company-stat__number[data-wm-count]' );
		if ( ! numbers.length ) {
			return;
		}

		if ( ! ( 'IntersectionObserver' in window ) ) {
			return; // Keep server-rendered values.
		}

		var seen = new WeakSet();
		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting && ! seen.has( entry.target ) ) {
						seen.add( entry.target );
						animateCount( entry.target );
						observer.unobserve( entry.target );
					}
				} );
			},
			{ threshold: 0.4 }
		);

		Array.prototype.forEach.call( numbers, function ( el ) {
			observer.observe( el );
		} );
	}

	/* -----------------------------------------------------------------
	 * Scroll-reveal (mirrors home.js .is-motion-ready behavior)
	 * -------------------------------------------------------------- */
	function initReveal() {
		var sections = document.querySelectorAll( '.wm-company-section' );
		if ( ! sections.length || reduceMotion || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}

		document.body.classList.add( 'wm-company-motion-ready' );

		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						entry.target.classList.add( 'is-visible' );
						observer.unobserve( entry.target );
					}
				} );
			},
			{ rootMargin: '0px 0px -40px 0px', threshold: 0.08 }
		);

		Array.prototype.forEach.call( sections, function ( section ) {
			// Sections already in the viewport on load appear immediately.
			var rect = section.getBoundingClientRect();
			if ( rect.top < window.innerHeight * 0.9 ) {
				section.classList.add( 'is-visible' );
			} else {
				observer.observe( section );
			}
		} );
	}

	function init() {
		initContactForms();
		initCounters();
		initReveal();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
