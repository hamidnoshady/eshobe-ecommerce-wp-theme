#!/usr/bin/env bash
#
# Builds the distributable runtime zip for the current HEAD commit.
#
# POSIX counterpart of bin/build-release.ps1 for CI (the PowerShell version is
# still the one for local Windows dev). Both follow the same rules; keep them
# in sync when the exclusion list changes.
#
#   ./bin/build-release.sh
#
# Produces eshobe-ecommerce-wp-theme-<version>.zip in the repo root, ready for
# Appearance > Themes > Add New > Upload Theme and for a release asset:
#   - exactly the files tracked by git at HEAD (no local junk, no uncommitted edits)
#   - runtime-only: docs, tests, payload-theme, bin, .github, .jules, AGENTS/
#     CLAUDE/README, composer and phpunit files are pruned
#   - wrapped in a top-level eshobe-ecommerce-wp-theme/ folder
#
# The version is read from functions.php (cross-checked against style.css) so
# the zip can never drift from the code it contains.

set -euo pipefail

repo=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
cd "$repo"

version=$(sed -n "s/.*define( *'ESHOBE_ECOMMERCE_VERSION', *'\([^']*\)' *).*/\1/p" functions.php | head -n1)
if [ -z "$version" ]; then
	echo "Could not find ESHOBE_ECOMMERCE_VERSION in functions.php" >&2
	exit 1
fi

style_version=$(sed -n 's/^Version:[[:space:]]*//p' style.css | head -n1)
if [ "$version" != "$style_version" ]; then
	echo "Version mismatch: functions.php has $version but style.css has $style_version. Bump both files together, commit, then re-run." >&2
	exit 1
fi

# Tracked files must be committed: the zip is built from HEAD.
if [ -n "$(git status --porcelain --untracked-files=no)" ]; then
	echo "Tracked files are modified - the zip is built from HEAD. Commit or stash first:" >&2
	git status --short --untracked-files=no >&2
	exit 1
fi

# The version should live only in the files that declare it.
stray=$(git grep -n -I -- "$version" -- ':!functions.php' ':!style.css' ':!*.min.*' ':!docs' || true)
if [ -n "$stray" ]; then
	echo "Version $version is referenced outside functions.php/style.css:" >&2
	echo "$stray" >&2
	exit 1
fi

stage=$(mktemp -d)
out=$(mktemp -d)
trap 'rm -rf "$stage" "$out"' EXIT

git archive HEAD | tar -x -C "$stage"

rm -rf "$stage"/docs "$stage"/tests "$stage"/payload-theme "$stage"/bin \
	"$stage"/.github "$stage"/.Jules "$stage"/.jules
rm -f "$stage"/AGENTS.md "$stage"/CLAUDE.md "$stage"/README.md \
	"$stage"/composer.json "$stage"/composer.lock "$stage"/phpunit.xml.dist \
	"$stage"/.gitattributes

root_folder='eshobe-ecommerce-wp-theme'
mkdir -p "$out/$root_folder"
cp -a "$stage"/. "$out/$root_folder"/

# Fixed timestamps + sorted entry order make the same commit produce the same bytes.
find "$out" -exec touch -t 202601010000.00 {} +

zip_path="$repo/eshobe-ecommerce-wp-theme-$version.zip"
rm -f "$zip_path"
(cd "$out" && find "$root_folder" -type f -print | LC_ALL=C sort | zip -X -q "$zip_path" -@)

# Self-checks: no dev-only paths, version present, theme root intact.
entries=$(unzip -Z1 "$zip_path")
if echo "$entries" | grep -Eq '(^|/)(docs|tests|payload-theme|bin|\.github|\.Jules|\.jules)/|(^|/)(AGENTS|CLAUDE|README)\.md$|(^|/)composer\.(json|lock)$|(^|/)phpunit\.xml\.dist$'; then
	echo "Dev-only files leaked into the zip:" >&2
	echo "$entries" | grep -E '(^|/)(docs|tests|payload-theme|bin|\.github|\.Jules|\.jules)/|(^|/)(AGENTS|CLAUDE|README)\.md$|(^|/)composer\.(json|lock)$|(^|/)phpunit\.xml\.dist$' >&2
	exit 1
fi
for required in "$root_folder/functions.php" "$root_folder/style.css" "$root_folder/index.php"; do
	if ! echo "$entries" | grep -qx "$required"; then
		echo "$required is missing from the zip" >&2
		exit 1
	fi
done
if ! unzip -p "$zip_path" "$root_folder/functions.php" | grep -q "$version"; then
	echo "Zip contains functions.php without version $version" >&2
	exit 1
fi

printf 'OK: %s (%s files, %s) built from HEAD (%s)\n' \
	"$(basename "$zip_path")" \
	"$(echo "$entries" | wc -l | tr -d ' ')" \
	"$(du -h "$zip_path" | cut -f1)" \
	"$(git rev-parse --short HEAD)"
