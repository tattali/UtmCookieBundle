UtmCookieBundle
===============

This bundle allow you to save utm parameters from url into a cookie when they exists. It's also provide a bunch of tools to easily retrive all or each utm.

* Symfony 6.4, 7.x or 8.x
* PHP 8.1+ (Symfony 7 needs PHP 8.2+, Symfony 8 needs PHP 8.4+)

For older Symfony versions, use `tattali/utm-cookie-bundle:^6.0`. See the [changelog](CHANGELOG.md) when upgrading.

Documentation
-------------

### Installation

```
$ composer require tattali/utm-cookie-bundle
```

### Usage

```php
<?php

use UtmCookieBundle\UtmCookie\UtmCookie;

class ExampleHandler
{
    public function __construct(
        private readonly UtmCookie $utmCookie,
    ) {
    }

    public function example()
    {
        $this->utmCookie->get(); // get all utm cookies as array
    }
}
```

#### Get all values as array
```php
$this->utmCookie->get();
```

#### Get each values
```php
$this->utmCookie->get('utm_campaign'); // or whithout 'utm_' prefix
$this->utmCookie->get('utm_content');
$this->utmCookie->get('utm_medium');
$this->utmCookie->get('utm_source');
$this->utmCookie->get('utm_term');
```

#### Initialize
Only if auto_init parameter is FALSE else it will be automatically initialized
```php
$this->utmCookie->init(); // Init and read utm params and cookie and save new values.
```

The cookie is added to the response of the current request.

### Parameters (optional):

```yaml
utm_cookie:
    auto_init: true   # Automaticaly run init when get method is called
    domain: ''        # The (sub)domain that the cookie is available to, or '' to use current domain
    httponly: false   # When TRUE the cookie will be made accessible only through the HTTP protocol
    lifetime: 604800  # The lifetime of the cookie in seconds (default 604800 => 7 days)
    name: 'utm'       # The name of the cookie (default value "utm")
    overwrite: true   # If overwrite all utm values when even one is set in get
    path: '/'         # The path on the server in which the cookie will be available on
    samesite: 'lax'   # The SameSite attribute: 'lax', 'strict', 'none', or null to leave it out
    secure: null      # null: secure when the request is made over HTTPS; true or false to force it
```

#### Cookie flags

- `samesite` defaults to `lax`, so the cookie is sent when a visitor follows a link to your site but not on cross-site subrequests. Browsers ignore `samesite: 'none'` unless the cookie is also secure.
- `secure` defaults to `null`: the cookie gets the Secure flag when the current request is made over HTTPS. Behind a reverse proxy that terminates TLS, configure Symfony's [trusted proxies](https://symfony.com/doc/current/deployment/proxies.html) so the request is seen as HTTPS. Set `true` or `false` to force the flag.
- `httponly` defaults to `false` so that front-end JavaScript can read the utm values. Set it to `true` if only the server reads them.

## License

This bundle is under the MIT license.
