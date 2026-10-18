<?php

declare(strict_types=1);

namespace UtmCookieBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use UtmCookieBundle\UtmCookie\UtmCookie;

final class UtmCookieBundleTest extends TestCase
{
    /**
     * @var TestKernel|null
     */
    private $kernel;

    protected function tearDown(): void
    {
        if (null !== $this->kernel) {
            $this->kernel->shutdown();
            $this->kernel->removeCacheDir();
            $this->kernel = null;
        }
    }

    public function testTheKernelBootsWithTheBundle(): void
    {
        $this->kernel = $kernel = new TestKernel();
        $kernel->boot();

        $utmCookie = $kernel->getContainer()->get(UtmCookie::class);

        self::assertInstanceOf(UtmCookie::class, $utmCookie);
        self::assertTrue($this->listensToRequests($kernel, $utmCookie));
    }

    public function testTheServiceDoesNotListenToRequestsWithoutAutoInit(): void
    {
        $this->kernel = $kernel = new TestKernel(['auto_init' => false]);
        $kernel->boot();

        $utmCookie = $kernel->getContainer()->get(UtmCookie::class);

        self::assertInstanceOf(UtmCookie::class, $utmCookie);
        self::assertFalse($this->listensToRequests($kernel, $utmCookie));
    }

    private function listensToRequests(TestKernel $kernel, UtmCookie $utmCookie): bool
    {
        $dispatcher = $kernel->getContainer()->get('event_dispatcher');
        self::assertInstanceOf(EventDispatcherInterface::class, $dispatcher);

        foreach ($dispatcher->getListeners('kernel.request') as $listener) {
            if (\is_array($listener) && $listener[0] === $utmCookie && 'onKernelRequest' === $listener[1]) {
                return true;
            }
        }

        return false;
    }
}
