<?php

declare(strict_types=1);

namespace UtmCookieBundle\UtmCookie;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Contracts\Service\ResetInterface;

class UtmCookie implements ResetInterface
{
    public const UTM_KEYS = [
        'utm_campaign',
        'utm_medium',
        'utm_source',
        'utm_term',
        'utm_content',
    ];

    /**
     * Name of cookie where will be saved utm params.
     */
    private string $utmCookieName = 'utm';

    /**
     * @var array<string, string|null>|null
     */
    private ?array $utmCookie = null;

    /**
     * Lifetime of utmCookie in seconds.
     */
    private int $lifetime = 604800;

    /**
     * If overwrite all utm values when even one is set in get. Default true.
     */
    private bool $overwrite = true;

    /**
     * Path for cookie. Default "/" so not empty like in setcookie PHP function!
     */
    private string $path = '/';

    /**
     * Domain for cookie.
     */
    private string $domain = '';

    /**
     * If cookie should be secured (same as $secure parameter in setcookie PHP function).
     */
    private bool $secure = false;

    /**
     * If cookie should be http only (same as $httponly parameter in setcookie PHP function).
     */
    private bool $httpOnly = false;

    /**
     * Cookie to add to the response of the current request.
     */
    private ?Cookie $pendingCookie = null;

    /**
     * Without a request stack (or outside of a request), values are read from the
     * $_GET and $_COOKIE superglobals and the cookie is sent with setcookie().
     */
    public function __construct(
        private readonly ?RequestStack $requestStack = null,
    ) {
    }

    /**
     * Remove utmCookie.
     */
    public function clear(): void
    {
        $this->sendCookie(new Cookie($this->utmCookieName, null, 1, $this->path, $this->domain ?: null, $this->secure, $this->httpOnly, false, null));
    }

    /**
     * Get all utm values or just value of utm with specific key.
     *
     * @param string|null $key default null (return all values as array)
     *
     * @return array<string, string|null>|string|null return string value, array or null if not set
     */
    public function get(?string $key = null): array|string|null
    {
        $this->init();
        \assert(null !== $this->utmCookie);

        if (null === $key) {
            return $this->utmCookie;
        }
        if (!str_starts_with($key, 'utm_')) {
            $key = 'utm_'.$key;
        }
        if (!\array_key_exists($key, $this->utmCookie)) {
            throw new \UnexpectedValueException(\sprintf('Argument $key has unexpected value "%s". Utm value with key "%s" does not exists.', $key, $key));
        }

        return $this->utmCookie[$key];
    }

    /**
     * Initialize. Get values from the query string and the cookie and save to UtmCookie. Init $this->utmCookie value.
     */
    public function init(): void
    {
        // if initialized, just return
        if (null !== $this->utmCookie) {
            return;
        }

        $request = $this->requestStack?->getMainRequest();

        // utm from the cookie
        $cookieValue = $request instanceof Request ? $request->cookies->get($this->utmCookieName) : ($_COOKIE[$this->utmCookieName] ?? null);
        $decoded = \is_string($cookieValue) ? json_decode($cookieValue, true) : null;
        $utmCookie = $this->filterUtmValues(\is_array($decoded) ? $decoded : []);

        // utm from the query string
        $utmGet = $this->filterUtmValues($request instanceof Request ? $request->query->all() : $_GET);

        $defaults = array_fill_keys(self::UTM_KEYS, null);
        if ([] !== $utmGet && $this->overwrite) {
            $utmCookieSave = array_merge($defaults, $utmGet);
        } else {
            $utmCookieSave = array_merge($defaults, $utmCookie, $utmGet);
        }

        $this->utmCookie = $utmCookieSave;

        if ([] !== $utmGet) {
            $this->sendCookie(new Cookie($this->utmCookieName, (string) json_encode($utmCookieSave), time() + $this->lifetime, $this->path, $this->domain ?: null, $this->secure, $this->httpOnly, false, null));
        }
    }

    /**
     * onKernelRequest called if autoInit is true.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->init();
    }

    /**
     * Adds the utm cookie set or cleared during the request to the response.
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest() || null === $this->pendingCookie) {
            return;
        }

        $event->getResponse()->headers->setCookie($this->pendingCookie);
        $this->pendingCookie = null;
    }

    public function reset(): void
    {
        $this->utmCookie = null;
        $this->pendingCookie = null;
    }

    /**
     * Set domain for cookie.
     */
    public function setDomain(string $domain): void
    {
        $this->domain = $domain;
    }

    /**
     * Set httponly for cookie.
     */
    public function setHttpOnly(bool $httpOnly): void
    {
        $this->httpOnly = $httpOnly;
    }

    /**
     * Set lifetime of utm cookie in seconds.
     */
    public function setLifetime(int $lifetime): void
    {
        if ($lifetime <= 0) {
            throw new \UnexpectedValueException(\sprintf('Lifetime has unexpected value "%s". Value must be positive.', $lifetime));
        }
        $this->lifetime = $lifetime;
    }

    /**
     * Set name of cookie where will be saved utm params.
     */
    public function setName(string $utmCookieName): void
    {
        if ('' === trim($utmCookieName)) {
            throw new \UnexpectedValueException(\sprintf('Name has unexpected value "%s". Value can\'t be empty.', $utmCookieName));
        }

        $this->utmCookieName = $utmCookieName;
        // cancel previous init
        $this->utmCookie = null;
    }

    /**
     * Set if even one utm value in the query string will overwrite all utm values or not.
     */
    public function setOverwrite(bool $overwrite): void
    {
        $this->overwrite = $overwrite;
        // cancel previous init
        $this->utmCookie = null;
    }

    /**
     * Set path for cookie.
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * Set secure for cookie.
     */
    public function setSecure(bool $secure): void
    {
        $this->secure = $secure;
    }

    /**
     * Keep the known utm keys with a scalar value, sanitized.
     *
     * @param array<mixed> $values
     *
     * @return array<string, string>
     */
    private function filterUtmValues(array $values): array
    {
        $filtered = [];
        foreach (self::UTM_KEYS as $key) {
            if (isset($values[$key]) && \is_scalar($values[$key])) {
                $filtered[$key] = self::sanitize((string) $values[$key]);
            }
        }

        return $filtered;
    }

    /**
     * Strip tags and encode quotes, as the removed FILTER_SANITIZE_STRING filter did.
     */
    private static function sanitize(string $value): string
    {
        return str_replace(["'", '"'], ['&#39;', '&#34;'], strip_tags($value));
    }

    private function sendCookie(Cookie $cookie): void
    {
        if ($this->requestStack?->getMainRequest() instanceof Request) {
            $this->pendingCookie = $cookie;

            return;
        }

        if (!headers_sent()) {
            header('Set-Cookie: '.$cookie, false);
        }
    }
}
