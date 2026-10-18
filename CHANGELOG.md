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

The 5.x versions get security fixes only.
