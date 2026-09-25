## 2024-05-18 - X-Frame-Options and MIME sniffing prevention headers

**Vulnerability:** Missing `X-Frame-Options` and `X-Content-Type-Options` HTTP response headers.
**Learning:** These basic security headers were missing from the custom theme configuration, leaving the site potentially exposed to Clickjacking and MIME-type sniffing. WordPress automatically applies `X-Frame-Options: SAMEORIGIN` in the backend (`is_admin()` and login pages), but it doesn't automatically protect the frontend.
**Prevention:** Always hook into `send_headers` with `! headers_sent()` check to manually output frontend security headers in WordPress themes unless configured at the reverse proxy/web server level.

## 2024-05-24 - [OTP Brute-Force Vulnerability]
**Vulnerability:** The OTP verification endpoint (`wm_ajax_otp_verify_code`) lacked rate limiting for failed attempts. An attacker could rapidly guess all 90,000 possible 5-digit codes within the 2-minute validity window.
**Learning:** While the endpoint properly limited the *sending* of OTPs via SMS, it failed to limit the *verification* attempts of a generated code. Security checks must exist at both generation and verification stages.
**Prevention:** Implement a transient-based counter to track failed verification attempts, explicitly deleting the OTP transient when the failure limit (e.g., 5 attempts) is reached.

## 2025-02-20 - Missing Rate Limiting on Password Login
**Vulnerability:** The AJAX endpoint `wm_ajax_otp_password_login` (used for password-based login in the OTP modal) did not have any rate limiting. While the OTP generation endpoint correctly throttled requests by IP and phone number, the password login endpoint allowed unbounded password guessing attempts for any known phone number.
**Learning:** Even when a system is primarily OTP-based, any fallback password authentication endpoints must have the same or stricter rate limiting and brute force protections applied to them. Relying only on the OTP issue flow being rate-limited creates a weak link for accounts that have set a password.
**Prevention:** Apply consistent rate limiting logic across *all* authentication endpoints, utilizing `set_transient` to track attempts per IP and per username/phone number.

## 2025-02-24 - Missing Rate Limiting on Set Password Endpoint
**Vulnerability:** The AJAX endpoint `wm_ajax_otp_set_password` lacked rate limiting. Similar to the password login endpoint, this fallback endpoint allowed an unbounded number of attempts to set a password either via brute-forcing the passwordToken or continuously sending password setting requests when logged in.
**Learning:** Any endpoint that sets or changes credentials must be rate limited to prevent abuse, brute force, and credential stuffing.
**Prevention:** Apply consistent rate limiting logic across all authentication and credential-setting endpoints, utilizing transients to track attempts per IP and per user ID or token.

## 2024-05-24 - Authorization Bypass in Block Regions Setup
**Vulnerability:** The block regions setup handlers (`wm_blocks_admin_handle_setup_home` and `wm_blocks_admin_handle_setup_region`) only checked for the `edit_posts` capability. This allowed low-privileged users (like Contributors) to create pages and change site-wide options (`show_on_front` and `page_on_front`), effectively changing the site's homepage. The menus were also registered with `edit_posts`.
**Learning:** Admin action handlers (like `admin_post_*` hooks) that modify site-wide settings or theme structures must enforce a strict capability, typically `edit_theme_options` or `manage_options`, rather than a lower-level post-editing capability like `edit_posts`.
**Prevention:** Always use `edit_theme_options` for functionality that manages theme appearance, blocks, or layout settings, unless explicitly intended for lower-privileged content creators (in which case, do not allow changing global options).

## 2025-02-25 - Authorization Bypass in Mega Menu Management
**Vulnerability:** The custom post type `wm_mega_menu` was registered with `'capability_type' => 'post'`, which allowed lower-privileged users (like Contributors) to view, edit, and create Mega Menus, even though these are structural theme navigation elements.
**Learning:** Structural layout and theme-related settings registered as Custom Post Types (such as Mega Menus or Block Regions) should not use the default post capability. Doing so can expose critical site architecture to low-privileged users.
**Prevention:** Always restrict theme configuration and structure-related CPTs by passing an explicit `capabilities` array mapping standard operations (e.g., `edit_post`, `edit_posts`) to `edit_theme_options` (or another appropriate admin capability), unless non-admins explicitly need to modify them.

## 2025-02-26 - Open Redirect in OTP Auth Redirect
**Vulnerability:** The function `wm_otp_resolve_redirect_url` in `inc/ajax/otp-auth.php` used a custom URL validation logic relying on `filter_var` and `wp_parse_url`. This allowed attackers to craft URLs like `https://evil.com%5C@example.com` that passed `FILTER_VALIDATE_URL` but bypassed the host comparison check (`wp_parse_url` extracted `example.com` as the host instead of `evil.com`), resulting in an Open Redirect.
**Learning:** Custom URL host extraction and validation using built-in PHP tools is notoriously prone to edge cases and parsing discrepancies, often leading to Open Redirect or SSRF vulnerabilities.
**Prevention:** Always use WordPress core's `wp_validate_redirect()` function for safe redirect validation instead of writing custom URL validation logic.

## 2025-02-27 - Denial of Service via Array Input in Wishlist AJAX
**Vulnerability:** The `wm_ajax_wishlist_products` endpoint accepted an array of product IDs via POST, iterated over them, and loaded full product objects/HTML without rate-limiting. A malicious actor could send massive arrays in repeated requests, exhausting server memory and database connections.
**Learning:** Any endpoint that processes an unbounded list of inputs provided by the client (even simple IDs) must enforce strict limits, especially if the processing loop executes database queries or complex template rendering.
**Prevention:** Implement IP-based rate limiting on endpoints that process bulk data, and consider enforcing a hard upper limit on the number of items processed per request.
## 2025-02-28 - Database DoS via Search Throttling
**Vulnerability:** The AJAX live search endpoint (`wm_ajax_search_products`) executed its IP-based rate limiting check (`wm_search_is_throttled`) before the term cache check. This caused `set_transient` to be called on every single keystroke, creating a severe Database Denial of Service (DoS) risk by hammering the `wp_options` table even for cheap, cached requests or invalid search terms.
**Learning:** When implementing rate limiting in WordPress themes without guaranteed object caching, you must avoid setting a new transient (e.g., using `set_transient()`) on every request.
**Prevention:** Move rate limiting checks after caching layers so they only execute when an expensive operation (like a `wc_get_products` LIKE query) is actually about to occur.

## 2025-02-29 - PII Exposure via Default CPT Capabilities
**Vulnerability:** The `wm_contact_message` custom post type (which stores user contact messages, potentially containing Personally Identifiable Information like emails and phone numbers) was registered with `'capability_type' => 'post'`. This allowed lower-privileged users (like Authors or Editors) to view, edit, and potentially expose sensitive user data.
**Learning:** Custom Post Types that store sensitive user data or PII should not inherit default `post` capabilities, as these are designed for public content management, not administrative data logging.
**Prevention:** Always restrict data storage CPTs by explicitly passing a `capabilities` array mapping standard operations (e.g., `read_post`, `edit_posts`) to `manage_options` or a similarly restrictive admin capability.
