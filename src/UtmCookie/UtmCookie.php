<?php

declare(strict_types=1);

namespace UtmCookieBundle\UtmCookie;

use DateTime;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use UnexpectedValueException;

class UtmCookie
{
    /**
     * Name of cookie where will be saved utm params.
     *
     * @var string
     */
    private $utmCookieName;

    /**
     * @var array
     */
    private $utmCookie;

    /**
     * Lifetime of utmCookie.
     *
     * @var int
     */
    private $lifetime;

    /**
     * If overwrite all utm values when even one is set in get. Default true.
     *
     * @var bool
     */
    private $overwrite;

    /**
     * Path for cookie. Default "/" so not empty like in setcookie PHP function!
     *
     * @var string
     */
    private $path = '/';

    /**
     * Domain for cookie.
     *
     * @var string
     */
    private $domain = '';

    /**
     * If cookie should be secured (same as $secure parameter in setcookie PHP function).
     *
     * @var bool
     */
    private $secure = false;

    /**
     * If cookie should be http only (same as $httponly parameter in setcookie PHP function).
     *
     * @var bool
     */
    private $httpOnly = false;

    /**
     * Remove utmCookie.
     */
    public function clear()
    {
        setcookie($this->utmCookieName, '', -1, $this->path, $this->domain, $this->secure, $this->httpOnly);
    }

    /**
     * Get all utm values or just value of utm with specific key.
     *
     * @param string|null $key default null (return all values as array)
     *
     * @return string|array|null return string value, array or null if not set
     */
    public function get(string $key = null)
    {
        $this->init();

        if (null === $key) {
            return $this->utmCookie;
        }
        if (0 !== mb_strpos($key, 'utm_')) {
            $key = 'utm_'.$key;
        }
        if (!\array_key_exists($key, $this->utmCookie)) {
            throw new UnexpectedValueException(sprintf('Argument $key has unexpected value "%s". Utm value with key "%s" does not exists.', $key, $key));
        }

        return $this->utmCookie[$key];
    }

    /**
     * Initialize. Get values from _GET and _COOKIES and save to UtmCookie. Init $this->utmCookie value.
     */
    public function init()
    {
        // if initialized, just return
        if (null !== $this->utmCookie) {
            return;
        }

        $this->initStaticValues();
        // utm from _COOKIE
        $utmCookieFilter = filter_var(
            json_decode((string) filter_input(INPUT_COOKIE, $this->utmCookieName), true),
            FILTER_SANITIZE_STRING,
            FILTER_REQUIRE_ARRAY
        );
        if (!\is_array($utmCookieFilter)) {
            $utmCookieFilter = [];
        }
        $utmCookie = $this->removeNullValues($utmCookieFilter);
        // utm from _GET
        $utmGetFilter = filter_input_array(
            INPUT_GET,
            [
                'utm_campaign' => FILTER_SANITIZE_STRING,
                'utm_medium' => FILTER_SANITIZE_STRING,
                'utm_source' => FILTER_SANITIZE_STRING,
                'utm_term' => FILTER_SANITIZE_STRING,
                'utm_content' => FILTER_SANITIZE_STRING,
            ]
        );
        if (!\is_array($utmGetFilter)) {
            $utmGetFilter = [];
        }
        $utmGet = $this->removeNullValues($utmGetFilter);

        if (0 !== \count($utmGet) && $this->overwrite) {
            $utmCookieSave = array_merge($this->utmCookie, $utmGet);
        } else {
            $utmCookieSave = array_merge($this->utmCookie, $utmCookie, $utmGet);
        }
        if (0 !== \count($utmGet)) {
            $this->save($utmCookieSave);
        } else {
            $this->utmCookie = $utmCookieSave;
        }
    }

    /**
     * onKernelRequest called if autoInit is true.
     *
     * @param GetResponseEvent|RequestEvent $event
     */
    public function onKernelRequest($event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->init();
    }

    /**
     * Set domain for cookie.
     */
    public function setDomain(string $domain)
    {
        $this->domain = $domain;
    }

    /**
     * Set httponly for cookie.
     */
    public function setHttpOnly(bool $httpOnly)
    {
        $this->httpOnly = $httpOnly;
    }

    /**
     * Set lifetime of utm cookie in seconds.
     */
    public function setLifetime(int $lifetime)
    {
        if ($lifetime <= 0) {
            throw new UnexpectedValueException(sprintf('Lifetime has unexpected value "%s". Value must be positive.', $lifetime));
        }
        $this->lifetime = $lifetime;
    }

    /**
     * Set name of cookie where will be saved utm params.
     */
    public function setName(string $utmCookieName)
    {
        if ('' === trim($utmCookieName)) {
            throw new UnexpectedValueException(sprintf('Name has unexpected value "%s". Value can\'t be empty.', $utmCookieName));
        }

        $this->utmCookieName = $utmCookieName;
        // cancel previous init
        $this->utmCookie = null;
    }

    /**
     * Set if even one utm value in _GET will overwrite all utm values or not.
     */
    public function setOverwrite(bool $overwrite)
    {
        $this->overwrite = $overwrite;
        // cancel previous init
        $this->utmCookie = null;
    }

    /**
     * Set path for cookie.
     */
    public function setPath(string $path)
    {
        $this->path = $path;
    }

    /**
     * Set secure for cookie.
     */
    public function setSecure(bool $secure)
    {
        $this->secure = $secure;
    }

    /**
     * Initialize static values to default (or empty) values.
     */
    private function initStaticValues()
    {
        $this->utmCookie = [
            'utm_campaign' => null,
            'utm_medium' => null,
            'utm_source' => null,
            'utm_term' => null,
            'utm_content' => null,
        ];
    }

    /**
     * Remove elements with null values from array.
     *
     * @return array
     */
    private static function removeNullValues(array $array = null)
    {
        // null (undefined) or false (filter failed)
        if (null === $array || false === $array) {
            return [];
        }

        return array_filter(
            $array,
            function ($value) {
                return null !== $value;
            }
        );
    }

    /**
     * Save utmCookie value into _COOKIE and set actual $this->utmCookie value (call only from init).
     */
    private function save(array $utmCookieSave)
    {
        $expire = (new DateTime())->getTimestamp() + $this->lifetime;

        setcookie($this->utmCookieName, json_encode($utmCookieSave), $expire, $this->path, $this->domain, $this->secure, $this->httpOnly);

        $this->utmCookie = $utmCookieSave;
    }
}
