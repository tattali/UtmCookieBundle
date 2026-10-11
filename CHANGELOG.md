Changelog
=========

7.0.0
-----

- Support Symfony 6.4, 7.x and 8.x; drop Symfony 3.4 to 6.3
- Require PHP 8.1 or later
- Read the utm values from the current request and add the cookie to the response
  (`kernel.response`) instead of reading the raw PHP input and calling `setcookie()`.
  This also works with long-running runtimes, and the service is reset between requests
- Replace the deprecated `FILTER_SANITIZE_STRING` filter: values are still stripped of
  tags and quotes are still encoded
- Only the five `utm_*` keys are read from the cookie; other keys are ignored
- Register the service in PHP instead of XML (XML service configuration is removed in Symfony 8)
- Add native types to `UtmCookie` and a test suite; replace Travis CI with GitHub Actions

### Upgrading from 6.x

- Nothing to change in the bundle configuration.
- If you extend `UtmCookie`, add the new return types (`void` on setters, `init()`, `clear()`,
  and `array|string|null` on `get()`).
- When `UtmCookie` is created by hand without a `RequestStack`, it still reads `$_GET`/`$_COOKIE`
  and sends the cookie header itself.
