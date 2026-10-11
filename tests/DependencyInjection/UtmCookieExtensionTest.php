<?php

declare(strict_types=1);

namespace UtmCookieBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use UtmCookieBundle\DependencyInjection\UtmCookieExtension;
use UtmCookieBundle\UtmCookie\UtmCookie;
use UtmCookieBundle\UtmCookieBundle;

final class UtmCookieExtensionTest extends TestCase
{
    public function testDefaultConfigRegistersListeners(): void
    {
        $container = $this->load([]);

        $definition = $container->getDefinition(UtmCookie::class);
        self::assertTrue($definition->isPublic());
        self::assertSame(
            [
                ['event' => 'kernel.response', 'method' => 'onKernelResponse'],
                ['event' => 'kernel.request', 'method' => 'onKernelRequest'],
            ],
            $definition->getTag('kernel.event_listener'),
        );
        self::assertSame([['method' => 'reset']], $definition->getTag('kernel.reset'));
    }

    public function testAutoInitCanBeDisabled(): void
    {
        $container = $this->load([['auto_init' => false]]);

        self::assertSame(
            [['event' => 'kernel.response', 'method' => 'onKernelResponse']],
            $container->getDefinition(UtmCookie::class)->getTag('kernel.event_listener'),
        );
    }

    public function testConfigIsAppliedToTheService(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('/', 'GET', ['utm_campaign' => 'launch']));

        $container = $this->load([[
            'name' => 'tracking',
            'lifetime' => 60,
            'path' => '/app',
            'domain' => 'example.com',
            'secure' => true,
            'httponly' => true,
        ]]);
        $container->register('request_stack', RequestStack::class)->setSynthetic(true);
        $container->compile();
        $container->set('request_stack', $requestStack);

        $utmCookie = $container->get(UtmCookie::class);
        self::assertInstanceOf(UtmCookie::class, $utmCookie);
        self::assertSame('launch', $utmCookie->get('campaign'));

        $response = new Response();
        $utmCookie->onKernelResponse(new ResponseEvent($this->createMock(HttpKernelInterface::class), Request::create('/'), HttpKernelInterface::MAIN_REQUEST, $response));
        $cookie = $response->headers->getCookies()[0] ?? null;

        self::assertInstanceOf(Cookie::class, $cookie);
        self::assertSame('tracking', $cookie->getName());
        self::assertSame('/app', $cookie->getPath());
        self::assertSame('example.com', $cookie->getDomain());
        self::assertTrue($cookie->isSecure());
        self::assertTrue($cookie->isHttpOnly());
        self::assertEqualsWithDelta(time() + 60, $cookie->getExpiresTime(), 5);
    }

    public function testBundleProvidesTheExtension(): void
    {
        $extension = (new UtmCookieBundle())->getContainerExtension();

        self::assertInstanceOf(UtmCookieExtension::class, $extension);
        self::assertSame('utm_cookie', $extension->getAlias());
    }

    /**
     * @param list<array<string, mixed>> $configs
     */
    private function load(array $configs): ContainerBuilder
    {
        $container = new ContainerBuilder();
        (new UtmCookieExtension())->load($configs, $container);

        return $container;
    }
}
