Changelog
=========

6.1.0
-----

- Support Symfony 6.4 only; drop Symfony 3.4 to 6.3. Use the 5.x versions for Symfony 5.4
- Require PHP 8.1 or later
- Fix the PHP 8.4 deprecation of the implicitly nullable `$key` argument of `get()`
- Declare the `TreeBuilder` return type of `Configuration::getConfigTreeBuilder()`
- Add a test that boots a kernel with the bundle; replace Travis CI with GitHub Actions
- `clear()` also forgets the utm values kept in memory, so `get()` returns null values until the next request
- Fix the PHP 8.1 deprecation of `FILTER_SANITIZE_STRING`: the utm values are sanitized by the bundle, with the same result
- A utm value that is not UTF-8 (such as `?utm_source=a%FFb`) no longer makes the request fail with a `TypeError`: the cookie is deleted and the request keeps its utm values
- Require the Symfony 6.4 components the bundle uses (`symfony/config`, `symfony/http-kernel`), so `symfony/config` 6.1 to 6.3 or 7 no longer installs with it; also require `ext-filter`, `symfony/polyfill-ctype` and `symfony/polyfill-mbstring`

The 6.x versions get bug fixes only.
