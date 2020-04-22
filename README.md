UtmCookieBundle
===============

This bundle allow you to save utm parameters from url into a cookie when they exists. It's also provide a bunch of tools to easily retrive all or each utm.

* Symfony 3.4+ or Symfony 4.0+ or Symfony 5.0+
* PHP v7.1+

Documentation
-------------

Install the bundle:

```
$ composer require tattali/utm-cookie-bundle
```

### Basic Usage

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
        $this->utmCookie->get('utm_source'); // get utm_source
        $this->utmCookie->get('source'); // get utm_source
    }
}
```

```php
$this->utmCookie->init(); // just init - read utm params and cookie and save new values. (optionnal if auto_init config is TRUE or automatically called when call get() method)
$this->utmCookie->get(); // get all utm cookies as array
$this->utmCookie->get('utm_source'); // get utm_source
$this->utmCookie->get('source'); // get utm_source
```

### Parameters:

```yaml
utm_cookie:
    name: 'utm' #The Name of cookie (default value "utm")
    lifetime: 604800 #The lifetime of cookie in seconds (default 604800 => 7 days)
    path: '/' #The path on the server in which the cookie will be available on (default '/')
    domain: '' #The (sub)domain that the cookie is available to (default '' so use current domain)
    overwrite: true|false #If overwrite all utm values when even one is set in get (default true)
    secure: true|false #Indicates that the cookie should only be transmitted over a secure HTTPS connection from the client (default false)
    httponly: true|false #When TRUE the cookie will be made accessible only through the HTTP protocol (default false)
    auto_init: true|false #If true, run init and create cookie automatically. If false you have to call init manually (default true)
```

## License

This bundle is under the MIT license.
