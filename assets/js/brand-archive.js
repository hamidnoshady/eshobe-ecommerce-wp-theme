/**
 * Brand directory: highlight the active letter in the alphabet index
 * as the corresponding group scrolls into view.
 */
( function () {
	var nav = document.querySelector( '[data-brand-index]' );
	if ( ! nav ) {
		return;
	}

	var links  = Array.prototype.slice.call( nav.querySelectorAll( '.wm-brand-index__link' ) );
	var groups = Array.prototype.slice.call( document.querySelectorAll( '[data-brand-group]' ) );

	if ( ! links.length || ! groups.length ) {
		return;
	}

	var linkByHash = {};
	links.forEach( function ( link ) {
		linkByHash[ link.getAttribute( 'href' ) ] = link;
	} );

	function setActive( link ) {
		links.forEach( function ( item ) {
			item.classList.toggle( 'is-active', item === link );
		} );
	}

	if ( 'IntersectionObserver' in window ) {
		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						var link = linkByHash[ '#' + entry.target.id ];
						if ( link ) {
							setActive( link );
						}
					}
				} );
			},
			{ rootMargin: '-20% 0px -70% 0px' }
		);

		groups.forEach( function ( group ) {
			observer.observe( group );
		} );
	}
} )();
