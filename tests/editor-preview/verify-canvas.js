/**
 * Editor-canvas verification (AGENTS.md remote workflow).
 *
 * Simulates the WordPress 6.2+/7.1 iframed block editor:
 *  - canvas-buggy.html : canvas WITHOUT theme styles (old enqueue_block_editor_assets behavior)
 *  - canvas.html       : canvas WITH the theme styles the new enqueue_block_assets
 *                        hook gets collected into the iframe by core
 *                        (_wp_get_iframed_editor_assets()).
 *
 * Asserts computed styles on the real ServerSideRender markup so the check is
 * about actual rendering, not markup presence.
 */
const { chromium } = require( 'playwright' );
const path = require( 'path' );

const ROOT = path.resolve( __dirname, '..', '..' );
const CANVAS_FIXED = 'file:///' + path.join( __dirname, 'canvas.html' ).replace( /\\/g, '/' );
const CANVAS_BUGGY = 'file:///' + path.join( __dirname, 'canvas-buggy.html' ).replace( /\\/g, '/' );

function assert( name, condition, detail ) {
	if ( condition ) {
		console.log( '  PASS  ' + name + ( detail ? '  (' + detail + ')' : '' ) );
		return true;
	}
	console.log( '  FAIL  ' + name + ( detail ? '  (' + detail + ')' : '' ) );
	process.exitCode = 1;
	return false;
}

async function inspect( url ) {
	const browser = await chromium.launch( {
		executablePath: 'C:/Users/hamid/AppData/Local/ms-playwright/chromium-1217/chrome-win64/chrome.exe',
	} );
	const page = await browser.newPage( { viewport: { width: 1280, height: 900 } } );
	await page.goto( url, { waitUntil: 'networkidle' } );
	return { browser, page };
}

async function measure( page ) {
	return page.evaluate( () => {
		const gs = ( el, prop ) => getComputedStyle( el ).getPropertyValue( prop );
		const hero = document.querySelector( '.wm-home-hero' );
		const slide = document.querySelector( '.wm-home-hero__slide.is-active' );
		const btn = document.querySelector( '.wm-home-button--primary' );
		const title = document.querySelector( '.wm-home-hero__title' );
		const card = document.querySelector( '.wm-product-card' );
		const track = document.querySelector( '.wm-product-carousel__track' );
		const section = document.querySelector( '.wm-product-carousel' );
		return {
			heroBorderRadius: hero ? gs( hero, 'border-top-left-radius' ) : null,
			heroShadow: hero ? gs( hero, 'box-shadow' ) : null,
			slideDisplay: slide ? gs( slide, 'display' ) : null,
			slideMinHeight: slide ? gs( slide, 'min-height' ) : null,
			btnBorderRadius: btn ? gs( btn, 'border-top-left-radius' ) : null,
			btnFontWeight: btn ? gs( btn, 'font-weight' ) : null,
			bodyFont: gs( document.body, 'font-family' ),
			titleFontSize: title ? gs( title, 'font-size' ) : null,
			cardRadius: card ? gs( card, 'border-top-left-radius' ) : null,
			cardShadow: card ? gs( card, 'box-shadow' ) : null,
			trackDisplay: track ? gs( track, 'display' ) : null,
			sectionWidth: section ? gs( section, 'width' ) : null,
			sectionMarginTop: section ? gs( section, 'margin-top' ) : null,
		};
	} );
}

( async () => {
	console.log( '\n== Buggy canvas (old behavior: enqueue_block_editor_assets styles never reached the iframe) ==' );
	{
		const { browser, page } = await inspect( CANVAS_BUGGY );
		const m = await measure( page );
		assert( 'hero has no border-radius (unstyled)', m.heroBorderRadius === '0px', m.heroBorderRadius );
		assert( 'hero button has no border-radius (unstyled)', m.btnBorderRadius === '0px', m.btnBorderRadius );
		assert( 'body does not use Vazirmatn', ! /vazirmatn/i.test( m.bodyFont ), m.bodyFont );
		await page.screenshot( { path: path.join( __dirname, 'canvas-buggy.png' ), fullPage: true } );
		await browser.close();
	}

	console.log( '\n== Fixed canvas (enqueue_block_assets styles collected into the iframe) ==' );
	{
		const { browser, page } = await inspect( CANVAS_FIXED );
		const m = await measure( page );

		assert( 'body uses Vazirmatn', /vazirmatn/i.test( m.bodyFont ), m.bodyFont.slice( 0, 40 ) );
		assert( 'hero slide visible as grid', m.slideDisplay === 'grid', m.slideDisplay );
		assert( 'hero slide min-height 500px', m.slideMinHeight === '500px', m.slideMinHeight );
		assert( 'hero rounded card (28px)', m.heroBorderRadius === '28px', m.heroBorderRadius );
		assert( 'hero has the accent-tinted shadow', /rgba\(17, ?24, ?39, ?0\.08\)/.test( m.heroShadow ), m.heroShadow.slice( 0, 60 ) );
		assert( 'hero button pill radius', parseFloat( m.btnBorderRadius ) >= 10, m.btnBorderRadius );
		assert( 'hero title clamp() resolves near 4vw/52px at 1280w (51.2px)', parseFloat( m.titleFontSize ) > 48, m.titleFontSize );
		assert( 'product card rounded (22px)', m.cardRadius === '22px', m.cardRadius );
		assert( 'product card uses --wm-home-shadow-sm from canvas shim', /rgba\(17, ?24, ?39, ?0\.04/.test( m.cardShadow ), m.cardShadow.slice( 0, 70 ) );
		assert( 'carousel track is a column grid scroller', m.trackDisplay === 'grid', m.trackDisplay );
		assert( 'section width capped at --wm-content-width (1200px)', m.sectionWidth === '1200px', m.sectionWidth );
		assert( 'section margin-top 54px (block CSS active)', m.sectionMarginTop === '54px', m.sectionMarginTop );

		await page.screenshot( { path: path.join( __dirname, 'canvas-fixed-desktop.png' ), fullPage: true } );

		// Mobile viewport sanity (theme is heavily RTL/mobile).
		await page.setViewportSize( { width: 390, height: 844 } );
		await page.waitForTimeout( 150 );
		const mob = await page.evaluate( () => {
			const slide = document.querySelector( '.wm-home-hero__slide.is-active' );
			return {
				display: getComputedStyle( slide ).display,
				gridCols: getComputedStyle( slide ).gridTemplateColumns,
			};
		} );
		assert( 'mobile: hero slide stacks to single column', mob.display === 'grid' && ! mob.gridCols.includes( ' ' ), mob.gridCols );
		await page.screenshot( { path: path.join( __dirname, 'canvas-fixed-mobile.png' ), fullPage: true } );

		await browser.close();
	}

	console.log( '' );
	if ( process.exitCode ) {
		console.log( 'RESULT: FAIL' );
	} else {
		console.log( 'RESULT: PASS — fixed canvas renders the theme styles inside the editor canvas' );
	}
} )();
