Changelog
=========

6.1.0
-----

- Support Symfony 6.4 only; drop Symfony 3.4 to 6.3. Use the 5.x versions for Symfony 5.4
- Require PHP 8.1 or later
- Fix the PHP 8.4 deprecation of the implicitly nullable `$key` argument of `get()`
- Declare the `TreeBuilder` return type of `Configuration::getConfigTreeBuilder()`
- Add a test that boots a kernel with the bundle; replace Travis CI with GitHub Actions

The 6.x versions get bug fixes only.
