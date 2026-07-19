# Cégértékelő

Symfony review-aggregator mini-app (Trustindex take-home assignment). Full project description,
setup instructions, and work-time log are added in a later pass — see `PROGRESS.md` for current
build status in the meantime.

## Security considerations

This app includes a couple of deliberate OWASP-aligned hardening measures as the "extra" bonus
(task 2.6), on top of what Symfony provides by default.

**Built specifically for this app:**

- **Rate limiting on review submissions** ([OWASP A04:2021 – Insecure Design][a04] /
  [API4:2023 – Unrestricted Resource Consumption][api4]) — `/reviews/new` allows at most 5
  submission attempts per 10 minutes per IP address (sliding window), via Symfony's
  `rate-limiter` component (`config/packages/rate_limiter.yaml`,
  `src/Controller/ReviewController.php`). This limits spam/bulk-fake-review abuse, which is a
  real concern for a review-aggregator platform. The check runs on every submit attempt —
  valid or not — so it also throttles garbage-data floods, not just successful ones.
- **Security response headers** ([OWASP A05:2021 – Security Misconfiguration][a05]) —
  `src/EventSubscriber/SecurityHeadersSubscriber.php` adds `X-Content-Type-Options: nosniff`,
  `X-Frame-Options: DENY`, `Referrer-Policy: strict-origin-when-cross-origin`, and a
  `Content-Security-Policy` to every response. The CSP is intentionally strict: no
  `unsafe-inline` anywhere (not even for styles — the one place that used to need it, the
  review-detail page, was refactored to use a CSS class instead), scripts are `'self'`-only,
  and only the Bootstrap CDN origin is allowed for styles/fonts.

**Already provided by the framework (worth naming explicitly, not just assumed):**

- **CSRF protection** ([OWASP A01:2021 – Broken Access Control][a01] category, cross-site
  request forgery) — every Symfony Form, including `ReviewType`, includes a CSRF token
  automatically; no extra code needed, but it's genuinely on and enforced.
- **XSS prevention** ([OWASP A03:2021 – Injection][a03]) — Twig auto-escapes all output by
  default. No template in this app uses the `|raw` filter or otherwise opts out of escaping,
  so user-submitted review text/company names can't inject HTML/script into pages.
- **SQL injection prevention** ([OWASP A03:2021 – Injection][a03]) — `ReviewRepository` uses
  Doctrine's query builder with bound parameters (`setParameter(...)`) everywhere, including the
  company-name search (`findLatest()`); no raw SQL string concatenation exists anywhere in the app.

[a01]: https://owasp.org/Top10/A01_2021-Broken_Access_Control/
[a03]: https://owasp.org/Top10/A03_2021-Injection/
[a04]: https://owasp.org/Top10/A04_2021-Insecure_Design/
[a05]: https://owasp.org/Top10/A05_2021-Security_Misconfiguration/
[api4]: https://owasp.org/API-Security/editions/2023/en/0xa4-unrestricted-resource-consumption/
