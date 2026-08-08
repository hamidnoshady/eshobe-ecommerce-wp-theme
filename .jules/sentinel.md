## 2024-05-18 - X-Frame-Options and MIME sniffing prevention headers

**Vulnerability:** Missing `X-Frame-Options` and `X-Content-Type-Options` HTTP response headers.
**Learning:** These basic security headers were missing from the custom theme configuration, leaving the site potentially exposed to Clickjacking and MIME-type sniffing. WordPress automatically applies `X-Frame-Options: SAMEORIGIN` in the backend (`is_admin()` and login pages), but it doesn't automatically protect the frontend.
**Prevention:** Always hook into `send_headers` with `! headers_sent()` check to manually output frontend security headers in WordPress themes unless configured at the reverse proxy/web server level.
