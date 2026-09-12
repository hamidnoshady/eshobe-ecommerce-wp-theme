<#
.SYNOPSIS
  Builds the distributable runtime zip for the current HEAD commit.

.DESCRIPTION
  Produces eshobe-ecommerce-wp-theme-<version>.zip in the repo root, ready to
  install via WordPress (Appearance > Themes > Add New > Upload Theme) or to
  publish as a release asset.

  What goes in:
    - Exactly the files tracked by git at HEAD (no local junk, no uncommitted edits)
    - Runtime-only: dev-only paths (docs, tests, payload-theme, bin, .github,
      composer files, phpunit config, AGENTS/CLAUDE/README) are pruned
    - Wrapped in a top-level eshobe-ecommerce-wp-theme/ folder, as WordPress expects

  What is verified before packing:
    - ESHOBE_ECOMMERCE_VERSION in functions.php matches Version: in style.css
    - The declared version appears in no other tracked file

.EXAMPLE
  pwsh -NoProfile -File bin/build-release.ps1          # zip for current HEAD
  pwsh -NoProfile -File bin/build-release.ps1 -Clean   # also delete older zips first

.NOTES
  Version comes from functions.php, not from a parameter, so the zip can never
  drift from the code it contains. Bump the version (functions.php + style.css)
  and commit BEFORE running this script; the zip is always built from HEAD.
#>

[CmdletBinding()]
param(
    # Delete older eshobe-ecommerce-wp-theme-*.zip files in the repo root first.
    [switch]$Clean
)

$ErrorActionPreference = 'Stop'

$repo = Split-Path -Parent $PSScriptRoot

# ---------------------------------------------------------------------------
# 1) Read and cross-check the version from the two files that must agree.
# ---------------------------------------------------------------------------
$functionsPhp = Get-Content -Raw (Join-Path $repo 'functions.php')
if ( $functionsPhp -notmatch "define\(\s*'ESHOBE_ECOMMERCE_VERSION',\s*'([^']+)'\s*\)" ) {
    throw "Could not find ESHOBE_ECOMMERCE_VERSION in functions.php"
}
$version = $Matches[1]

$styleCss = Get-Content -Raw (Join-Path $repo 'style.css')
if ( $styleCss -notmatch '(?m)^Version:\s*(\S+)' ) {
    throw "Could not find the Version: header in style.css"
}
$styleVersion = $Matches[1]

if ( $version -ne $styleVersion ) {
    throw "Version mismatch: functions.php has $version but style.css has $styleVersion. Bump both files together, commit, then re-run."
}

# ---------------------------------------------------------------------------
# 2) Sanity checks: clean working tree, no stray version references.
# ---------------------------------------------------------------------------
# Untracked files (e.g. local zips, editor dirs) are fine - HEAD is what ships.
$dirty = git -C $repo status --porcelain | Where-Object { $_ -notmatch '^\?\?' }
if ( $dirty ) {
    throw "Tracked files are modified - the zip is built from HEAD. Commit or stash first:`n$dirty"
}

$stray = git -C $repo grep -n -I $version -- ':!functions.php' ':!style.css' ':!*.min.*' ':!docs' 2>$null
if ( $stray ) {
    throw "Version $version is referenced outside functions.php/style.css:`n$stray"
}

if ( $Clean ) {
    Get-ChildItem -Path $repo -Filter 'eshobe-ecommerce-wp-theme-*.zip' -File |
        Remove-Item -Force
}

# ---------------------------------------------------------------------------
# 3) Stage tracked files at HEAD, prune dev-only paths.
# ---------------------------------------------------------------------------
$stage = Join-Path ([System.IO.Path]::GetTempPath()) ("eshobe-release-" + [System.IO.Path]::GetRandomFileName())
New-Item -ItemType Directory -Path $stage | Out-Null

$pkgDir = Join-Path ([System.IO.Path]::GetTempPath()) ("eshobe-pkg-" + [System.IO.Path]::GetRandomFileName())
New-Item -ItemType Directory -Path $pkgDir | Out-Null

try {
    # PowerShell 5.1 mangles binary data piped between native commands, so the
    # tar stream goes through a temp file instead of `git archive | tar -x`.
    $tarPath = Join-Path $pkgDir 'head.tar'
    git -C $repo archive -o $tarPath HEAD
    # Use Windows' own bsdtar: GNU tar (e.g. from Git Bash on PATH) treats
    # drive-letter paths like D:\... as remote "host:path" targets.
    $tarExe = Join-Path $env:SystemRoot 'System32\tar.exe'
    if ( -not (Test-Path $tarExe) ) { throw "tar.exe not found at $tarExe" }
    & $tarExe -x -f $tarPath -C $stage
    if ( $LASTEXITCODE -ne 0 ) { throw "tar extraction failed (exit $LASTEXITCODE)" }

    $devPaths = @(
        'docs', 'tests', 'payload-theme', 'bin', '.github', '.Jules', '.jules'
    )
    $devFiles = @(
        'AGENTS.md', 'CLAUDE.md', 'README.md', 'composer.json',
        'composer.lock', 'phpunit.xml.dist'
    )

    foreach ( $p in $devPaths ) {
        $target = Join-Path $stage $p
        if ( Test-Path $target ) { Remove-Item -Recurse -Force $target }
    }
    foreach ( $f in $devFiles ) {
        $target = Join-Path $stage $f
        if ( Test-Path $target ) { Remove-Item -Force $target }
    }

    # Pack deterministically: fixed order, fixed timestamps, no per-machine data.
    $zipPath = Join-Path $pkgDir "eshobe-ecommerce-wp-theme-$version.zip"
    $rootFolder = 'eshobe-ecommerce-wp-theme'

    Add-Type -AssemblyName System.IO.Compression
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    $zip = [System.IO.Compression.ZipFile]::Open(
        $zipPath, [System.IO.Compression.ZipArchiveMode]::Create
    )
    try {
        $zipComment = 'eshobe-ecommerce-wp-theme release ' + $version
        $base = (Get-Item $stage).FullName
        # Windows paths are case-insensitive; enforce a stable lowercase sort.
        $files = Get-ChildItem -Path $stage -Recurse -File |
            ForEach-Object { [PSCustomObject]@{
                Path     = $_.FullName
                RelPath  = $rootFolder + '/' + $_.FullName.Substring($base.Length + 1).Replace('\', '/')
                SortKey  = $_.FullName.ToLowerInvariant()
            } } |
            Sort-Object -Property SortKey
        foreach ( $file in $files ) {
            $entry = $zip.CreateEntryFromFile(
                $file.Path, $file.RelPath,
                [System.IO.Compression.CompressionLevel]::Optimal
            )
            # Fixed timestamp so the same commit always yields the same bytes.
            $entry.LastWriteTime = [DateTime]::new(2026, 1, 1, 0, 0, 0, [DateTimeKind]::Utc)
        }
    }
    finally {
        $zip.Dispose()
    }

    $outPath = Join-Path $repo "eshobe-ecommerce-wp-theme-$version.zip"
    Move-Item -Force $zipPath $outPath

    # ---------------------------------------------------------------------------
    # 4) Self-check: entry count, no dev paths inside, correct version inside.
    # ---------------------------------------------------------------------------
    $check = [System.IO.Compression.ZipFile]::OpenRead($outPath)
    try {
        $names = $check.Entries | ForEach-Object { $_.FullName }
        $leaks = $names | Where-Object {
            $_ -match '(^|/)(docs|tests|payload-theme|bin|\.github|\.Jules)/' -or
            $_ -match '(AGENTS|CLAUDE)\.md$' -or
            $_ -match '(^|/)(README\.md|composer\.(json|lock)|phpunit\.xml\.dist)$'
        }
        if ( $leaks ) {
            throw "Dev-only files leaked into the zip: $($leaks -join ', ')"
        }
        $functionsEntry = $check.Entries | Where-Object { $_.FullName -eq "$rootFolder/functions.php" }
        if ( -not $functionsEntry ) { throw "functions.php missing from the zip" }
        $reader = New-Object System.IO.StreamReader($functionsEntry.Open())
        $inZipVersion = $reader.ReadToEnd()
        $reader.Close()
        if ( $inZipVersion -notmatch [regex]::Escape($version) ) {
            throw "Zip contains functions.php without version $version"
        }
        Write-Host ("OK: {0} ({1} files, {2:N0} KB) built from HEAD ({3})" -f `
            (Split-Path -Leaf $outPath), $names.Count, ((Get-Item $outPath).Length / 1KB), `
            (git -C $repo rev-parse --short HEAD))
    }
    finally {
        $check.Dispose()
    }
}
finally {
    Remove-Item -Recurse -Force $stage, $pkgDir -ErrorAction SilentlyContinue
}
