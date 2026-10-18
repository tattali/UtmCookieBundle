<?php

declare(strict_types=1);

namespace UtmCookieBundle\UtmCookie;

use DateTime;
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
     * The utm values, null until init() reads them.
     *
     * @var array<array-key, array<mixed>|string|false|null>|null
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
     * Remove utmCookie. Until the next request, get() returns null values.
     *
     * @return void
     */
    public function clear()
    {
        $this->utmCookie = self::defaultValues();
        setcookie($this->utmCookieName, '', -1, $this->path, $this->domain, $this->secure, $this->httpOnly);
    }

    /**
     * Get all utm values or just value of utm with specific key.
     *
     * @param string|null $key default null (return all values as array)
     *
     * @return array<mixed>|string|false|null return string value, array, null if not set or false if not a string
     */
    public function get(?string $key = null)
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
     *
     * @return void
     *
     * @phpstan-assert !null $this->utmCookie
     */
    public function init()
    {
        // if initialized, just return
        if (null !== $this->utmCookie) {
            return;
        }

        $defaultValues = self::defaultValues();
        // utm from _COOKIE
        $utmCookieFilter = filter_var(
            json_decode((string) filter_input(INPUT_COOKIE, $this->utmCookieName), true),
            FILTER_UNSAFE_RAW,
            FILTER_REQUIRE_ARRAY
        );
        if (!\is_array($utmCookieFilter)) {
            $utmCookieFilter = [];
        }
        // the filter turns every value into a string
        array_walk_recursive($utmCookieFilter, function (string &$value) {
            $value = self::sanitize($value);
        });
        $utmCookie = self::removeNullValues($utmCookieFilter);
        // utm from _GET
        $utmGetFilter = [];
        foreach (['utm_campaign', 'utm_medium', 'utm_source', 'utm_term', 'utm_content'] as $key) {
            // null: not in the query string, false: not a string
            $value = filter_input(INPUT_GET, $key, FILTER_UNSAFE_RAW);
            $utmGetFilter[$key] = \is_string($value) ? self::sanitize($value) : $value;
        }
        $utmGet = self::removeNullValues($utmGetFilter);

        if (0 !== \count($utmGet) && $this->overwrite) {
            $utmCookieSave = array_merge($defaultValues, $utmGet);
        } else {
            $utmCookieSave = array_merge($defaultValues, $utmCookie, $utmGet);
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
     * @param RequestEvent $event
     *
     * @return void
     */
    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->init();
    }

    /**
     * Set domain for cookie.
     *
     * @return void
     */
    public function setDomain(string $domain)
    {
        $this->domain = $domain;
    }

    /**
     * Set httponly for cookie.
     *
     * @return void
     */
    public function setHttpOnly(bool $httpOnly)
    {
        $this->httpOnly = $httpOnly;
    }

    /**
     * Set lifetime of utm cookie in seconds.
     *
     * @return void
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
     *
     * @return void
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
     *
     * @return void
     */
    public function setOverwrite(bool $overwrite)
    {
        $this->overwrite = $overwrite;
        // cancel previous init
        $this->utmCookie = null;
    }

    /**
     * Set path for cookie.
     *
     * @return void
     */
    public function setPath(string $path)
    {
        $this->path = $path;
    }

    /**
     * Set secure for cookie.
     *
     * @return void
     */
    public function setSecure(bool $secure)
    {
        $this->secure = $secure;
    }

    /**
     * Default (empty) utm values.
     *
     * @return array<string, null>
     */
    private static function defaultValues(): array
    {
        return [
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
     * @template T
     *
     * @param array<T|null>|null $array
     *
     * @return array<T>
     */
    private static function removeNullValues(?array $array = null): array
    {
        // null (undefined)
        if (null === $array) {
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
     * Strip tags and encode quotes, as the removed FILTER_SANITIZE_STRING filter did.
     *
     * The quotes are encoded first, and a "<" followed by a space also starts a tag, so the result
     * is the same as the filter's for any string.
     */
    private static function sanitize(string $value): string
    {
        $value = str_replace(["'", '"'], ['&#39;', '&#34;'], $value);
        // strip_tags() keeps a "<" followed by a space as text, where the filter started a tag:
        // a letter after the "<" makes strip_tags() start the tag too
        $value = preg_replace_callback('/<(?=(.))/s', function (array $match) {
            return ctype_space($match[1]) ? '<x' : '<';
        }, $value);

        return strip_tags((string) $value);
    }

    /**
     * Save utmCookie value into _COOKIE and set actual $this->utmCookie value (call only from init).
     *
     * @param array<array-key, array<mixed>|string|false|null> $utmCookieSave
     */
    private function save(array $utmCookieSave): void
    {
        $expire = (new DateTime())->getTimestamp() + $this->lifetime;

        // json_encode() fails on a value that is not UTF-8: an empty value deletes the cookie
        setcookie($this->utmCookieName, (string) json_encode($utmCookieSave), $expire, $this->path, $this->domain, $this->secure, $this->httpOnly);

        $this->utmCookie = $utmCookieSave;
    }
}
