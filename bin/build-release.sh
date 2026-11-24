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
#
# Before packaging, the PHP that is about to ship is also scanned for post type
# and taxonomy registrations that borrow a core capability (manage_options,
# edit_theme_options, ...) while registering meta caps, and for registrations
# whose arguments cannot be read statically - see the guard below.

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
stray=$(git grep -n -I -- "$version" -- ':!functions.php' ':!style.css' ':!*.min.*' ':!docs' ':!CHANGELOG.md' || true)
if [ -n "$stray" ]; then
	echo "Version $version is referenced outside functions.php/style.css:" >&2
	echo "$stray" >&2
	exit 1
fi

stage=$(mktemp -d)
out=$(mktemp -d)
guard=$(mktemp -d)
trap 'rm -rf "$stage" "$out" "$guard"' EXIT

git archive HEAD | tar -x -C "$stage"

rm -rf "$stage"/docs "$stage"/tests "$stage"/payload-theme "$stage"/bin \
	"$stage"/.github "$stage"/.Jules "$stage"/.jules
rm -f "$stage"/AGENTS.md "$stage"/CLAUDE.md "$stage"/README.md \
	"$stage"/composer.json "$stage"/composer.lock "$stage"/phpunit.xml.dist \
	"$stage"/.gitattributes

# ---------------------------------------------------------------------------
# Core-capability guard.
#
# A post type or taxonomy must not borrow a core capability (manage_options,
# edit_theme_options, ...) while it also registers meta caps. With
# map_meta_cap on, WordPress copies edit_post/read_post/delete_post into the
# global $post_type_meta_caps map, so the borrowed capability is remapped for
# the whole request - that is exactly how the wm_contact_message CPT made every
# Settings API screen answer 403.
#
# The scan runs on the PHP that is about to be zipped, skips comments (so the
# rule can be explained in a docblock), follows a capabilities array that lives
# in a helper function, and exits 1 on any hijack. A borrow made while
# map_meta_cap is off is printed as a note and allowed: the mega menu CPT
# relies on that, and it cannot remap anything without meta caps.
#
# Registrations rarely pass their arguments inline. When the arguments come
# from a variable, the scan resolves a simple '$args = array( ... )' (or
# '[...]') assignment in the same scope. Anything it cannot read statically
# fails the build instead of passing silently, so an unreadable registration
# can never be the way a hijack ships.
# ---------------------------------------------------------------------------
core_caps='manage_options edit_theme_options switch_themes edit_themes install_themes update_themes delete_themes activate_plugins install_plugins edit_plugins update_plugins delete_plugins edit_users create_users delete_users promote_users remove_users list_users edit_files edit_dashboard update_core export import manage_links manage_categories moderate_comments edit_comment unfiltered_html unfiltered_upload manage_network manage_sites manage_network_options'

cat > "$guard/capabilities.awk" <<'AWK'
# Usage: awk -v core_caps="cap cap ..." -f capabilities.awk <php files...>
# Prints notes (allowed borrows) and ERROR lines to stdout; exits 1 when a
# registration would hijack a core capability.

# POSIX cores that grant site-wide admin rights. A post type or taxonomy may
# not claim one of these as its own capability while meta caps are on.
function core_cap(cap,   parts, i, n) {
	if (!core_loaded) {
		n = split(core_caps, parts, " ")
		for (i = 1; i <= n; i++) core[parts[i]] = 1
		core_loaded = 1
	}
	return (cap in core)
}

# Blank out comments (keeping the byte offsets and newlines intact) so that
# prose about core capabilities never trips the scan.
function strip_comments(s,   i, n, c, nx, mode, out) {
	n = length(s)
	out = ""
	mode = "code"
	for (i = 1; i <= n; i++) {
		c = substr(s, i, 1)
		nx = substr(s, i + 1, 1)
		if (mode == "line") {
			if (c == "\n") { mode = "code"; out = out c } else out = out " "
			continue
		}
		if (mode == "block") {
			if (c == "*" && nx == "/") { out = out "  "; i++; mode = "code"; continue }
			if (c == "\n") out = out c; else out = out " "
			continue
		}
		if (mode == "sq") {
			out = out c
			if (c == "\\") { i++; out = out substr(s, i, 1) }
			else if (c == "'") mode = "code"
			continue
		}
		if (mode == "dq") {
			out = out c
			if (c == "\\") { i++; out = out substr(s, i, 1) }
			else if (c == "\"") mode = "code"
			continue
		}
		if (c == "'") { mode = "sq"; out = out c; continue }
		if (c == "\"") { mode = "dq"; out = out c; continue }
		if (c == "#") { mode = "line"; out = out " "; continue }
		if (c == "/" && nx == "/") { mode = "line"; out = out "  "; i++; continue }
		if (c == "/" && nx == "*") { mode = "block"; out = out "  "; i++; continue }
		out = out c
	}
	return out
}

function find_close(s, start,   i, n, c, depth, mode) {
	n = length(s)
	depth = 0
	mode = "code"
	for (i = start; i <= n; i++) {
		c = substr(s, i, 1)
		if (mode == "sq") { if (c == "\\") i++; else if (c == "'") mode = "code"; continue }
		if (mode == "dq") { if (c == "\\") i++; else if (c == "\"") mode = "code"; continue }
		if (c == "'") { mode = "sq"; continue }
		if (c == "\"") { mode = "dq"; continue }
		if (c == "(" || c == "[") depth++
		else if (c == ")" || c == "]") { depth--; if (depth <= 0) return i }
	}
	return 0
}

function find_close_brace(s, start,   i, n, c, depth, mode) {
	n = length(s)
	depth = 0
	mode = "code"
	for (i = start; i <= n; i++) {
		c = substr(s, i, 1)
		if (mode == "sq") { if (c == "\\") i++; else if (c == "'") mode = "code"; continue }
		if (mode == "dq") { if (c == "\\") i++; else if (c == "\"") mode = "code"; continue }
		if (c == "'") { mode = "sq"; continue }
		if (c == "\"") { mode = "dq"; continue }
		if (c == "{") depth++
		else if (c == "}") { depth--; if (depth <= 0) return i }
	}
	return 0
}

# Quoted string immediately before pos (the '<key>' of a '<key> => ...' pair).
function prev_string(s, pos,   i, q, end) {
	i = pos - 1
	while (i >= 1 && substr(s, i, 1) ~ /[[:space:]]/) i--
	if (i < 1) return ""
	q = substr(s, i, 1)
	if (q != "'" && q != "\"") return ""
	end = i - 1
	while (end >= 1) {
		if (substr(s, end, 1) == q && substr(s, end - 1, 1) != "\\") break
		end--
	}
	if (end < 1) return ""
	return substr(s, end + 1, i - end - 1)
}

# Quoted string immediately at/after pos (the value of a pair).
function next_string(s, pos,   i, n, q, i0) {
	n = length(s)
	i = pos
	while (i <= n && substr(s, i, 1) ~ /[[:space:]]/) i++
	if (i > n) return ""
	q = substr(s, i, 1)
	if (q != "'" && q != "\"") return ""
	i0 = i
	i++
	while (i <= n) {
		if (substr(s, i, 1) == "\\") { i += 2; continue }
		if (substr(s, i, 1) == q) return substr(s, i0 + 1, i - i0 - 1)
		i++
	}
	return ""
}

function line_of(f, pos,   i) {
	for (i = 1; i <= nlines[f]; i++)
		if (lstart[f, i] <= pos && (i == nlines[f] || lstart[f, i + 1] > pos)) return i
	return 1
}

# '<key> => <value>' pairs inside a capabilities array whose value is a core
# capability. One hit per distinct core capability (first key wins).
function collect_hits(region,   p, k, key, val, x) {
	for (x in hitkey) delete hitkey[x]
	for (x in hitval) delete hitval[x]
	for (x in seenval) delete seenval[x]
	nhits = 0
	p = 1
	while ((k = index(substr(region, p), "=>")) > 0) {
		k += p - 1
		key = prev_string(region, k)
		val = next_string(region, k + 2)
		if (key != "" && val != "" && key != val && core_cap(val) && !(val in seenval)) {
			seenval[val] = 1
			nhits++
			hitkey[nhits] = key
			hitval[nhits] = val
		}
		p = k + 2
	}
}

# Map every named function to its body, so 'capabilities' => some_helper() can
# be checked too (the contact form CPT uses exactly that shape).
function find_functions(   f, s, pos, k, after, name, brace, closeb) {
	for (f = 1; f <= nfiles; f++) {
		s = code[f]
		pos = 1
		while ((k = index(substr(s, pos), "function")) > 0) {
			k += pos - 1
			pos = k + 8
			if (k > 1 && substr(s, k - 1, 1) ~ /[A-Za-z0-9_$]/) continue
			after = k + 8
			if (!match(substr(s, after), /^[[:space:]]*&?[[:space:]]*[A-Za-z_][A-Za-z0-9_]*/)) continue
			name = substr(s, after + RSTART - 1, RLENGTH)
			sub(/^[[:space:]]*&?[[:space:]]*/, "", name)
			brace = index(substr(s, after + RSTART + RLENGTH - 1), "{")
			if (!brace) continue
			brace = after + RSTART + RLENGTH - 1 + brace - 1
			closeb = find_close_brace(s, brace)
			if (closeb) {
				funcbody[name] = substr(s, brace, closeb - brace + 1)
				nfunc[f]++
				fstart[f, nfunc[f]] = brace
				fend[f, nfunc[f]] = closeb
			}
		}
	}
}

# Innermost function body (recorded by find_functions) that contains pos, or 0
# at top level. A variable and the registration using it must live in the same
# scope before the resolver trusts the assignment.
function scope_start_of(f, pos,   i, best) {
	best = 0
	for (i = 1; i <= nfunc[f]; i++)
		if (fstart[f, i] <= pos && pos <= fend[f, i] && fstart[f, i] > best) best = fstart[f, i]
	return best
}

# Split the top-level arguments of a call string such as "( 'slug', $args )"
# into args[1..nargs], ignoring commas inside strings, arrays and closures.
function split_args(call,   i, n, c, depth, mode, start, x) {
	for (x in args) delete args[x]
	n = length(call)
	nargs = 0
	depth = 0
	mode = ""
	start = 2
	for (i = 2; i <= n; i++) {
		c = substr(call, i, 1)
		if (mode == "sq") { if (c == "\\") i++; else if (c == "'") mode = ""; continue }
		if (mode == "dq") { if (c == "\\") i++; else if (c == "\"") mode = ""; continue }
		if (c == "'") { mode = "sq"; continue }
		if (c == "\"") { mode = "dq"; continue }
		if (c == "(" || c == "[" || c == "{") { depth++; continue }
		if (c == ")" || c == "]" || c == "}") {
			if (depth == 0) { args[++nargs] = substr(call, start, i - start); return }
			depth--
			continue
		}
		if (c == "," && depth == 0) { args[++nargs] = substr(call, start, i - start); start = i + 1 }
	}
}

# Text of the '$name = array( ... )' (or '[...]') that supplies a registration's
# arguments, or "" when the guard cannot check them: the assignment is missing,
# lives in another scope, is not a static array, or the variable is changed
# afterwards.
function resolve_args(f, name, pos,   s, k, p, eq, rhs, op, cl, arr, at, scope, seen, mutated) {
	s = code[f]
	scope = scope_start_of(f, pos)
	arr = ""
	at = 0
	seen = 0
	mutated = 0
	argline = 0
	argreason = ""
	p = 1
	while ((k = index(substr(s, p), "$" name)) > 0) {
		k += p - 1
		p = k + 1 + length(name)
		if (p <= length(s) && substr(s, p, 1) ~ /[A-Za-z0-9_]/) continue
		eq = p
		while (eq <= length(s) && substr(s, eq, 1) ~ /[[:space:]]/) eq++
		if (substr(s, eq, 1) != "=") continue
		if (substr(s, eq + 1, 1) == "=" || substr(s, eq + 1, 1) == ">") continue
		if (k >= pos) continue
		if (scope_start_of(f, k) != scope) continue
		seen = 1
		rhs = substr(s, eq + 1)
		op = 0
		if (match(rhs, /^[[:space:]]*[Aa][Rr][Rr][Aa][Yy][[:space:]]*\(/)) op = eq + RSTART + RLENGTH - 1
		else if (match(rhs, /^[[:space:]]*\[/)) op = eq + RSTART + RLENGTH - 1
		cl = op ? find_close(s, op) : 0
		arr = cl ? substr(s, op, cl - op + 1) : ""
		at = cl
		if (arr != "") argline = line_of(f, k)
	}
	if (arr != "" && pos > at && match(substr(s, at + 1, pos - at - 1), "\\$" name "[[:space:]]*\\[[^]]*\\][[:space:]]*=")) {
		arr = ""
		mutated = 1
	}
	if (arr == "") {
		if (mutated) argreason = "it is changed after that assignment"
		else if (seen) argreason = "that assignment is not a static array"
		else argreason = "there is no '$" name " = array( ... )' assignment in the same function before the call"
	}
	return arr
}

function check_file(f,   s, pos, k1, k2, best, kind, after, tail, openp, closep, call, mmc, cpos, caps, sig, op, cl, region, origin, target, label, h, argi, arg, vname, arrtext, argorigin) {
	s = code[f]
	pos = 1
	while (1) {
		best = 0
		kind = ""
		k1 = index(substr(s, pos), "register_post_type")
		if (k1) { best = k1 + pos - 1; kind = "register_post_type" }
		k2 = index(substr(s, pos), "register_taxonomy")
		if (k2) { k2 += pos - 1; if (!best || k2 < best) { best = k2; kind = "register_taxonomy" } }
		if (!best) return
		pos = best + length(kind)
		if (best > 1 && substr(s, best - 1, 1) ~ /[A-Za-z0-9_]/) continue
		after = best + length(kind)
		tail = substr(s, after)
		if (!match(tail, /^[[:space:]]*\(/)) continue
		# The match ends on the '(' - anything else cuts the call short at the
		# first nested ')' and hides the arguments after it.
		openp = after + RSTART + RLENGTH - 2
		closep = find_close(s, openp)
		if (!closep) return
		pos = closep + 1
		call = substr(s, openp, closep - openp + 1)
		target = next_string(call, 2)

		# The arguments array is usually inline, but WordPress also accepts one
		# held in a variable. Resolve a simple '$args = array( ... )' assignment
		# in the same scope; anything the scan cannot read fails the build
		# instead of passing silently.
		argorigin = ""
		argi = (kind == "register_taxonomy") ? 3 : 2
		split_args(call)
		arg = args[argi]
		sub(/^[[:space:]]+/, "", arg)
		sub(/[[:space:]]+$/, "", arg)
		if (arg ~ /^\$[A-Za-z_][A-Za-z0-9_]*$/) {
			vname = substr(arg, 2)
			arrtext = resolve_args(f, vname, openp)
			if (arrtext == "") {
				errors++
				msg[++nmsg] = sprintf("ERROR: %s:%d: %s() takes its arguments from $%s, which cannot be checked statically: %s. Pass the array inline or assign a plain '$%s = array( ... )' in the same function.", fname[f], line_of(f, openp), kind, vname, argreason, vname)
				continue
			}
			call = "(" arrtext ")"
			argorigin = sprintf(" (arguments from $%s defined at line %d)", vname, argline)
		} else if (arg != "" && arg !~ /^([Aa][Rr][Rr][Aa][Yy][[:space:]]*\(|\[)/) {
			errors++
			msg[++nmsg] = sprintf("ERROR: %s:%d: %s() arguments cannot be checked statically. Pass an inline array or a simple $args = array( ... ) variable.", fname[f], line_of(f, openp), kind)
			continue
		}

		mmc = 0
		if (match(call, /["']map_meta_cap["'][[:space:]]*=>[[:space:]]*(true|TRUE|True)([^A-Za-z0-9_]|$)/)) mmc = 1
		else if (match(call, /["']map_meta_cap["'][[:space:]]*=>[[:space:]]*(false|FALSE|False|0)([^A-Za-z0-9_]|$)/)) mmc = 0
		else if (match(call, /["']map_meta_cap["'][[:space:]]*=>/)) mmc = -1

		cpos = 0
		if (match(call, /["']capabilities["'][[:space:]]*=>[[:space:]]*/)) cpos = RSTART + RLENGTH
		region = ""
		origin = ""
		if (cpos) {
			caps = substr(call, cpos)
			if (match(caps, /^[Aa][Rr][Rr][Aa][Yy][[:space:]]*\(/)) {
				op = cpos + RLENGTH - 1
				cl = find_close(call, op)
				if (cl) region = substr(call, op, cl - op + 1)
			} else if (substr(caps, 1, 1) == "[") {
				cl = find_close(call, cpos)
				if (cl) region = substr(call, cpos, cl - cpos + 1)
			} else if (match(caps, /^[A-Za-z_][A-Za-z0-9_]*[[:space:]]*\(/)) {
				sig = substr(caps, RSTART, RLENGTH)
				gsub(/[[:space:](]/, "", sig)
				if (sig in funcbody) { region = funcbody[sig]; origin = " (capabilities built in " sig "())" }
			}
		}

		if (region == "") {
			if (mmc != 0) {
				errors++
				msg[++nmsg] = sprintf("ERROR: %s:%d: %s() sets map_meta_cap but its capability array cannot be checked statically - inline the array or return it from a plain function%s.", fname[f], line_of(f, openp), kind, argorigin)
			}
			continue
		}

		origin = origin argorigin
		collect_hits(region)
		if (!nhits) continue

		label = sprintf("%s:%d: %s(%s)", fname[f], line_of(f, openp), kind, target != "" ? "'" target "'" : "")
		if (mmc == 0) {
			for (h = 1; h <= nhits; h++)
				msg[++nmsg] = sprintf("note: %s maps '%s' to the core capability '%s'; allowed because map_meta_cap is off%s.", label, hitkey[h], hitval[h], origin)
		} else {
			for (h = 1; h <= nhits; h++) {
				errors++
				msg[++nmsg] = sprintf("ERROR: %s maps '%s' to the core capability '%s' while map_meta_cap is on%s.", label, hitkey[h], hitval[h], origin)
			}
			if (kind == "register_taxonomy")
				msg[++nmsg] = "       A taxonomy that borrows a core capability takes its edit/delete permission from that site-wide admin capability. Keep taxonomy capabilities theme-owned, or leave map_meta_cap off."
			else
				msg[++nmsg] = "       With map_meta_cap on, WordPress records edit_post/read_post/delete_post in the global post_type_meta_caps map, so a core capability listed there is remapped for the whole request - that is how the wm_contact_message CPT made every Settings API form answer 403. Give this post type its own capability names, or leave map_meta_cap off."
		}
	}
}

{
	if (FNR == 1) {
		nfiles++
		fname[nfiles] = FILENAME
		nlines[nfiles] = 0
		raw[nfiles] = ""
	}
	nlines[nfiles]++
	lstart[nfiles, nlines[nfiles]] = length(raw[nfiles]) + 1
	raw[nfiles] = raw[nfiles] $0 "\n"
}

END {
	for (f = 1; f <= nfiles; f++) code[f] = strip_comments(raw[f])
	find_functions()
	for (f = 1; f <= nfiles; f++) check_file(f)
	for (i = 1; i <= nmsg; i++) print msg[i]
	exit (errors ? 1 : 0)
}
AWK

php_files=()
while IFS= read -r php; do
	php_files+=("$php")
done < <(cd "$stage" && find . -type f -name '*.php' -print | sed 's|^\./||' | LC_ALL=C sort)

if [ "${#php_files[@]}" -eq 0 ]; then
	echo "No PHP files found to scan - refusing to build." >&2
	exit 1
fi

if ! guard_output=$(cd "$stage" && awk -v core_caps="$core_caps" -f "$guard/capabilities.awk" "${php_files[@]}"); then
	echo "Core-capability guard failed: a post type or taxonomy borrows a core capability while registering meta caps. Refusing to build." >&2
	printf '%s\n' "$guard_output" >&2
	exit 1
fi

if [ -n "$guard_output" ]; then
	printf '%s\n' "$guard_output" >&2
fi

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
