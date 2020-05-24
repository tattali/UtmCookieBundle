UtmCookieBundle
===============

This bundle allow you to save utm parameters from url into a cookie when they exists. It's also provide a bunch of tools to easily retrive all or each utm.

* Symfony 3.4+ or Symfony 4.0+ or Symfony 5.0+
* PHP v7.1+

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
    private $utmCookie;

    public function __construct(UtmCookie $utmCookie) {
        $this->utmCookie = $utmCookie;
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
    secure: false     # Indicates that the cookie should only be transmitted over a secure HTTPS connection from the client
```

## License

This bundle is under the MIT license.
