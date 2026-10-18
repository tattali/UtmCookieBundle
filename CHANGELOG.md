Changelog
=========

5.0.0
-----

- Support Symfony 5.4 only; drop Symfony 3.4 to 5.3 and Symfony 6
- Require PHP 7.2.5 or later
- The bundle class extends `Bundle` instead of `AbstractBundle`, which Symfony 5.4 does not have
- Fix the PHP 8.4 deprecation of the implicitly nullable `$key` argument of `get()`
- Declare the `TreeBuilder` return type of `Configuration::getConfigTreeBuilder()`
- Add a test that boots a kernel with the bundle; replace Travis CI with GitHub Actions
- Fix the PHP 8.1 deprecation of `FILTER_SANITIZE_STRING`: the utm values are sanitized by the bundle, with the same result
- A utm value that is not UTF-8 (such as `?utm_source=a%FFb`) no longer makes the request fail with a `TypeError`: the cookie is deleted and the request keeps its utm values
- Require the Symfony 5.4 components the bundle uses (`symfony/config`, `symfony/http-kernel`), so Symfony 6 components no longer install with it; also require `ext-filter`, `symfony/polyfill-ctype` and `symfony/polyfill-mbstring`

The 5.x versions get security fixes only.
