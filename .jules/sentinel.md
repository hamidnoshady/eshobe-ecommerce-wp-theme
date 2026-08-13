## 2024-05-18 - X-Frame-Options and MIME sniffing prevention headers

**Vulnerability:** Missing `X-Frame-Options` and `X-Content-Type-Options` HTTP response headers.
**Learning:** These basic security headers were missing from the custom theme configuration, leaving the site potentially exposed to Clickjacking and MIME-type sniffing. WordPress automatically applies `X-Frame-Options: SAMEORIGIN` in the backend (`is_admin()` and login pages), but it doesn't automatically protect the frontend.
**Prevention:** Always hook into `send_headers` with `! headers_sent()` check to manually output frontend security headers in WordPress themes unless configured at the reverse proxy/web server level.

## 2025-02-20 - Missing Rate Limiting on Password Login
**Vulnerability:** The AJAX endpoint `wm_ajax_otp_password_login` (used for password-based login in the OTP modal) did not have any rate limiting. While the OTP generation endpoint correctly throttled requests by IP and phone number, the password login endpoint allowed unbounded password guessing attempts for any known phone number.
**Learning:** Even when a system is primarily OTP-based, any fallback password authentication endpoints must have the same or stricter rate limiting and brute force protections applied to them. Relying only on the OTP issue flow being rate-limited creates a weak link for accounts that have set a password.
**Prevention:** Apply consistent rate limiting logic across *all* authentication endpoints, utilizing `set_transient` to track attempts per IP and per username/phone number.
