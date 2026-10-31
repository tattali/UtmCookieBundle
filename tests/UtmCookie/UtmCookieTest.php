<?php

declare(strict_types=1);

namespace UtmCookieBundle\Tests\UtmCookie;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use UtmCookieBundle\UtmCookie\UtmCookie;

final class UtmCookieTest extends TestCase
{
    private const EMPTY_VALUES = [
        'utm_campaign' => null,
        'utm_medium' => null,
        'utm_source' => null,
        'utm_term' => null,
        'utm_content' => null,
    ];

    public function testGetReturnsNullValuesWithoutUtm(): void
    {
        $utmCookie = $this->createUtmCookie(Request::create('/'));

        self::assertSame(self::EMPTY_VALUES, $utmCookie->get());
        self::assertNull($this->sendResponse($utmCookie));
    }

    public function testQueryValuesAreReturnedAndSavedInCookie(): void
    {
        $request = Request::create('/', 'GET', ['utm_source' => 'newsletter', 'utm_medium' => 'email', 'other' => 'ignored']);
        $utmCookie = $this->createUtmCookie($request);
        $utmCookie->setLifetime(3600);

        $expected = ['utm_campaign' => null, 'utm_medium' => 'email', 'utm_source' => 'newsletter', 'utm_term' => null, 'utm_content' => null];
        self::assertSame($expected, $utmCookie->get());
        self::assertSame('newsletter', $utmCookie->get('utm_source'));
        self::assertSame('email', $utmCookie->get('medium'));

        $cookie = $this->sendResponse($utmCookie);
        self::assertInstanceOf(Cookie::class, $cookie);
        self::assertSame('utm', $cookie->getName());
        self::assertSame($expected, json_decode((string) $cookie->getValue(), true));
        self::assertEqualsWithDelta(time() + 3600, $cookie->getExpiresTime(), 5);
        self::assertSame('/', $cookie->getPath());
        self::assertSame('', $cookie->getDomain());
        self::assertStringNotContainsString('domain=', (string) $cookie);
        self::assertFalse($cookie->isSecure());
        self::assertFalse($cookie->isHttpOnly());
        self::assertFalse($cookie->isRaw(), 'The JSON value is URL-encoded in the header.');
    }

    public function testCookieOptionsAreApplied(): void
    {
        $utmCookie = $this->createUtmCookie(Request::create('/', 'GET', ['utm_source' => 'ads']));
        $utmCookie->setName('tracking');
        $utmCookie->setPath('/shop');
        $utmCookie->setDomain('example.com');
        $utmCookie->setSecure(true);
        $utmCookie->setHttpOnly(true);
        $utmCookie->init();

        $cookie = $this->sendResponse($utmCookie);
        self::assertInstanceOf(Cookie::class, $cookie);
        self::assertSame('tracking', $cookie->getName());
        self::assertSame('/shop', $cookie->getPath());
        self::assertSame('example.com', $cookie->getDomain());
        self::assertTrue($cookie->isSecure());
        self::assertTrue($cookie->isHttpOnly());
    }

    public function testDomainZeroSendsNoDomain(): void
    {
        $utmCookie = $this->createUtmCookie(Request::create('/', 'GET', ['utm_source' => 'ads']));
        $utmCookie->setDomain('0');
        $utmCookie->init();

        $cookie = $this->sendResponse($utmCookie);
        self::assertInstanceOf(Cookie::class, $cookie);
        self::assertSame('', $cookie->getDomain());
        self::assertStringNotContainsString('domain=', (string) $cookie);
    }

    public function testValuesAreReadFromCookie(): void
    {
        $request = Request::create('/', 'GET', [], ['utm' => json_encode(['utm_campaign' => 'spring', 'unknown' => 'dropped'])]);
        $utmCookie = $this->createUtmCookie($request);

        self::assertSame(array_merge(self::EMPTY_VALUES, ['utm_campaign' => 'spring']), $utmCookie->get());
        self::assertNull($this->sendResponse($utmCookie), 'The cookie is only written when the query string has utm values.');
    }

    public function testQueryValuesOverwriteCookieValuesByDefault(): void
    {
        $request = Request::create('/', 'GET', ['utm_source' => 'ads'], ['utm' => json_encode(['utm_campaign' => 'spring', 'utm_source' => 'newsletter'])]);
        $utmCookie = $this->createUtmCookie($request);

        self::assertSame(array_merge(self::EMPTY_VALUES, ['utm_source' => 'ads']), $utmCookie->get());
    }

    public function testQueryValuesAreMergedWithCookieValuesWithoutOverwrite(): void
    {
        $request = Request::create('/', 'GET', ['utm_source' => 'ads'], ['utm' => json_encode(['utm_campaign' => 'spring', 'utm_source' => 'newsletter'])]);
        $utmCookie = $this->createUtmCookie($request);
        $utmCookie->setOverwrite(false);

        self::assertSame(array_merge(self::EMPTY_VALUES, ['utm_campaign' => 'spring', 'utm_source' => 'ads']), $utmCookie->get());
    }

    public function testValuesAreSanitized(): void
    {
        $request = Request::create('/', 'GET', ['utm_source' => '<script>alert(1)</script>"ads\'', 'utm_term' => ['an', 'array']]);
        $utmCookie = $this->createUtmCookie($request);

        self::assertSame('alert(1)&#34;ads&#39;', $utmCookie->get('source'));
        self::assertNull($utmCookie->get('term'));
    }

    /**
     * Outputs of the FILTER_SANITIZE_STRING filter that strip_tags() alone does not give: a "<"
     * followed by a space starts a tag, and quotes are encoded before the tags are stripped.
     */
    #[DataProvider('provideValuesTheRemovedFilterSanitizedDifferentlyFromStripTags')]
    public function testValuesAreSanitizedAsTheRemovedFilterDid(string $value, string $expected): void
    {
        $utmCookie = $this->createUtmCookie(Request::create('/', 'GET', ['utm_source' => $value]));

        self::assertSame($expected, $utmCookie->get('source'));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideValuesTheRemovedFilterSanitizedDifferentlyFromStripTags(): iterable
    {
        yield 'less-than sign followed by a space' => ['1 < 2', '1 '];
        yield 'tag opened with a space' => ['< b>x', 'x'];
        yield 'quoted ">" in an attribute' => ['<a title=">">x', '&#34;>x'];
    }

    /**
     * The values are sanitized as FILTER_SANITIZE_STRING did before PHP 8.1 deprecated it:
     * compare both on many generated strings, wherever PHP still has the filter.
     */
    public function testSanitizeGivesTheSameResultAsTheDeprecatedFilter(): void
    {
        if (!\defined('FILTER_SANITIZE_STRING')) {
            self::markTestSkipped('This PHP version no longer has FILTER_SANITIZE_STRING.');
        }
        // silenced: the constant is deprecated since PHP 8.1
        $filter = @\constant('FILTER_SANITIZE_STRING');
        $sanitize = \Closure::bind(static fn (string $value): string => UtmCookie::sanitize($value), null, UtmCookie::class);

        $pieces = ['<', '>', ' ', "\t", "\n", "\x0B", "\f", "\r", "\0", "\xA0", '"', "'", '!', '?', '-', '/', '=', '&', '\\', 'a', 'é', '<!--', '-->', '<?', '?>', '<b>', '</b>', '<script>'];
        // seeded, so every run checks the same strings
        $randomizer = new \Random\Randomizer(new \Random\Engine\Mt19937(42));
        for ($i = 0; $i < 20000; ++$i) {
            $value = '';
            for ($length = $randomizer->getInt(0, 12); $length > 0; --$length) {
                $value .= $pieces[$randomizer->getInt(0, \count($pieces) - 1)];
            }

            self::assertSame(@filter_var($value, $filter), $sanitize($value), 'Input: '.json_encode(bin2hex($value)));
        }
    }

    public function testInvalidCookieIsIgnored(): void
    {
        $utmCookie = $this->createUtmCookie(Request::create('/', 'GET', [], ['utm' => 'not json']));

        self::assertSame(self::EMPTY_VALUES, $utmCookie->get());
    }

    public function testGetUnknownKeyThrows(): void
    {
        $utmCookie = $this->createUtmCookie(Request::create('/'));

        $this->expectException(\UnexpectedValueException::class);
        $utmCookie->get('unknown');
    }

    public function testClearExpiresCookie(): void
    {
        $utmCookie = $this->createUtmCookie(Request::create('/'));
        $utmCookie->clear();

        $cookie = $this->sendResponse($utmCookie);
        self::assertInstanceOf(Cookie::class, $cookie);
        self::assertSame('utm', $cookie->getName());
        self::assertTrue($cookie->isCleared());
        self::assertSame(1, $cookie->getExpiresTime(), 'Same expiry as Response::headers->clearCookie().');
        self::assertFalse($cookie->isRaw());
        self::assertSame('', $cookie->getDomain());
    }

    public function testGetReturnsNullValuesAfterClear(): void
    {
        $request = Request::create('/', 'GET', ['utm_source' => 'ads'], ['utm' => json_encode(['utm_campaign' => 'spring'])]);
        $utmCookie = $this->createUtmCookie($request);
        self::assertSame('ads', $utmCookie->get('source'));

        $utmCookie->clear();

        self::assertSame(self::EMPTY_VALUES, $utmCookie->get());
        $utmCookie->init();
        $cookie = $this->sendResponse($utmCookie);
        self::assertInstanceOf(Cookie::class, $cookie);
        self::assertTrue($cookie->isCleared(), 'A later get() or init() must not write the values back.');
    }

    public function testGetReturnsNullValuesWhenClearedBeforeInit(): void
    {
        $request = Request::create('/', 'GET', ['utm_source' => 'ads'], ['utm' => json_encode(['utm_campaign' => 'spring'])]);
        $utmCookie = $this->createUtmCookie($request);

        $utmCookie->clear();

        self::assertSame(self::EMPTY_VALUES, $utmCookie->get());
        $cookie = $this->sendResponse($utmCookie);
        self::assertInstanceOf(Cookie::class, $cookie);
        self::assertTrue($cookie->isCleared());
    }

    public function testOnKernelRequestInitializesOnMainRequestOnly(): void
    {
        $request = Request::create('/', 'GET', ['utm_source' => 'ads']);
        $utmCookie = $this->createUtmCookie($request);
        $kernel = self::createStub(HttpKernelInterface::class);

        $utmCookie->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST));
        self::assertNull($this->sendResponse($utmCookie));

        $utmCookie->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));
        self::assertInstanceOf(Cookie::class, $this->sendResponse($utmCookie));
    }

    public function testResetForgetsThePreviousRequest(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('/', 'GET', ['utm_source' => 'ads']));
        $utmCookie = new UtmCookie($requestStack);
        self::assertSame('ads', $utmCookie->get('source'));

        $utmCookie->reset();
        $requestStack->pop();
        $requestStack->push(Request::create('/'));

        self::assertNull($utmCookie->get('source'));
        self::assertNull($this->sendResponse($utmCookie));
    }

    public function testWithoutRequestStackValuesAreReadFromSuperglobals(): void
    {
        $get = $_GET;
        $cookies = $_COOKIE;
        $_GET = [];
        $_COOKIE = ['utm' => json_encode(['utm_medium' => 'email'])];

        try {
            self::assertSame(array_merge(self::EMPTY_VALUES, ['utm_medium' => 'email']), (new UtmCookie())->get());
        } finally {
            $_GET = $get;
            $_COOKIE = $cookies;
        }
    }

    public function testInvalidLifetimeThrows(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        (new UtmCookie())->setLifetime(0);
    }

    public function testEmptyNameThrows(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        (new UtmCookie())->setName(' ');
    }

    private function createUtmCookie(Request $request): UtmCookie
    {
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new UtmCookie($requestStack);
    }

    private function sendResponse(UtmCookie $utmCookie): ?Cookie
    {
        $response = new Response();
        $utmCookie->onKernelResponse(new ResponseEvent(self::createStub(HttpKernelInterface::class), Request::create('/'), HttpKernelInterface::MAIN_REQUEST, $response));

        $cookies = $response->headers->getCookies();

        return $cookies[0] ?? null;
    }
}
