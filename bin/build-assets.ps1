<#
.SYNOPSIS
  Regenerates minified .min.css / .min.js files for every asset under assets/,
  and builds the always-on CSS bundle (assets/css/theme-bundle.css + .min).

.DESCRIPTION
  This theme has no build step required to run the site - templates fall back to the
  unminified source files when WP_DEBUG is true, or if a .min file is missing.
  Run this script after editing any CSS/JS under assets/ to refresh the production
  (.min) copies. Requires Node.js (uses npx clean-css-cli and npx terser as one-off tools).

  The bundle concatenates the stylesheets loaded on every page (fonts, tokens,
  theme, product-components, header, footer, decorative-motifs, mobile-nav,
  notifications) into assets/css/theme-bundle.css; functions.php enqueues the
  bundle when present and falls back to the individual files otherwise.
#>

$root = Split-Path -Parent $PSScriptRoot

function Minify-Css($src, $dest) {
    Write-Host "Minifying CSS: $src"
    npx --yes clean-css-cli -o $dest $src
    if ($LASTEXITCODE -ne 0) { throw "clean-css failed on $src" }
}

function Minify-Js($src, $dest) {
    Write-Host "Minifying JS: $src"
    npx --yes terser $src -o $dest -c -m
    if ($LASTEXITCODE -ne 0) { throw "terser failed on $src" }
}

# 1) Per-file min builds for every source asset (recursive, auto-covers new files).
$cssFiles = Get-ChildItem -Path (Join-Path $root 'assets') -Recurse -File -Filter *.css |
    Where-Object { $_.Name -notlike '*.min.css' }
foreach ($file in $cssFiles) {
    $dest = $file.FullName -replace '\.css$', '.min.css'
    Minify-Css $file.FullName $dest
}

$jsFiles = Get-ChildItem -Path (Join-Path $root 'assets') -Recurse -File -Filter *.js |
    Where-Object { $_.Name -notlike '*.min.js' }
foreach ($file in $jsFiles) {
    $dest = $file.FullName -replace '\.js$', '.min.js'
    Minify-Js $file.FullName $dest
}

# 2) Always-on CSS bundle (must stay in sync with functions.php's fallback list).
$bundleParts = @(
    'assets/css/fonts.css',
    'assets/css/tokens.css',
    'assets/css/theme.css',
    'assets/css/components/product-components.css',
    'assets/css/components/header.css',
    'assets/css/components/footer.css',
    'assets/css/components/decorative-motifs.css',
    'assets/css/components/mobile-nav.css',
    'assets/css/components/notifications.css',
    'assets/css/components/product-archive-critical.css'
)

$bundlePath = Join-Path $root 'assets/css/theme-bundle.css'

# Read/write with explicit UTF-8 (no BOM) via .NET so Windows PowerShell 5.1
# and pwsh 7 produce byte-identical output. Get-Content -Encoding UTF8 on
# PS 5.1 defaults to ANSI for BOM-less files (mojibake) and Set-Content UTF8
# writes a BOM, which breaks the CI drift check on Linux runners.
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

$parts = foreach ($partName in $bundleParts) {
    $part = Join-Path $root $partName
    if (Test-Path $part) {
        [System.IO.File]::ReadAllText($part, [System.Text.Encoding]::UTF8)
    } else {
        Write-Warning "Missing bundle part: $partName"
    }
}

$bundleContent = ($parts -join "`n")
[System.IO.File]::WriteAllText($bundlePath, $bundleContent, $utf8NoBom)
Write-Host "Built bundle: assets/css/theme-bundle.css"
Minify-Css $bundlePath ($bundlePath -replace '\.css$', '.min.css')

Write-Host "Done."
