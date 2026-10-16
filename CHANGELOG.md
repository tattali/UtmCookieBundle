Changelog
=========

Unreleased
----------

- The cookie now gets `SameSite=Lax` by default. Use the new `samesite` option to choose
  `lax`, `strict`, `none`, or `null` to leave the attribute out
- The `secure` option now defaults to `null`: the cookie gets the Secure flag when the request
  is made over HTTPS. `true` and `false` still force it
- The cookie removed by `clear()` uses the same flags, and `get()` returns null values
  until the next request

### Upgrading from 7.0

- Browsers no longer send the cookie on cross-site subrequests (iframes, images, POST forms from
  other sites). Set `samesite: none` (with `secure: true`) if you relied on that.
- On HTTPS sites the cookie is now Secure. Behind a reverse proxy that terminates TLS, configure
  Symfony's trusted proxies, or set `secure: true`, so the request is seen as HTTPS.
- To keep the 7.0 behaviour, set `samesite: null` and `secure: false`.

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
