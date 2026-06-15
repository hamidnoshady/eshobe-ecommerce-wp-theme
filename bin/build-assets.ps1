<#
.SYNOPSIS
  Regenerates minified .min.css / .min.js files for the theme's enqueued assets.

.DESCRIPTION
  This theme has no build step required to run the site - templates fall back to the
  unminified source files when WP_DEBUG is true, or if a .min file is missing.
  Run this script after editing any CSS/JS under assets/ to refresh the production
  (.min) copies. Requires Node.js (uses npx clean-css-cli and npx terser as one-off tools).
#>

$root = Split-Path -Parent $PSScriptRoot

$cssFiles = @(
    'assets/css/fonts.css',
    'assets/css/tokens.css',
    'assets/css/theme.css',
    'assets/css/product-components.css',
    'assets/css/components/header.css',
    'assets/css/components/footer.css',
    'assets/css/components/decorative-motifs.css',
    'assets/css/components/mobile-nav.css',
    'assets/css/components/search-modal.css',
    'assets/css/components/product-archive.css',
    'assets/css/components/cart.css',
    'assets/css/components/checkout.css',
    'assets/css/pages/home.css'
)

$jsFiles = @(
    'assets/js/navigation.js',
    'assets/js/header.js',
    'assets/js/header-search.js',
    'assets/js/mobile-nav.js',
    'assets/js/home-hero-slider.js',
    'assets/js/product-carousel.js',
    'assets/js/product-gallery.js',
    'assets/js/product-tabs.js',
    'assets/js/related-products.js',
    'assets/js/product-mobile.js',
    'assets/js/product-archive.js',
    'assets/js/checkout.js'
)

foreach ($file in $cssFiles) {
    $src = Join-Path $root $file
    $dest = $src -replace '\.css$', '.min.css'
    Write-Host "Minifying $file"
    npx --yes clean-css-cli -o $dest $src
}

foreach ($file in $jsFiles) {
    $src = Join-Path $root $file
    $dest = $src -replace '\.js$', '.min.js'
    Write-Host "Minifying $file"
    npx --yes terser $src -o $dest -c -m
}

Write-Host "Done."
