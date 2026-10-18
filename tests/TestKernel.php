<?php

declare(strict_types=1);

namespace UtmCookieBundle\Tests;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use UtmCookieBundle\UtmCookieBundle;

/**
 * Smallest application that uses the bundle, to check that it boots.
 */
class TestKernel extends Kernel
{
    /**
     * @var array<string, mixed>
     */
    private $utmCookieConfig;

    /**
     * @param array<string, mixed> $utmCookieConfig
     */
    public function __construct(array $utmCookieConfig = [])
    {
        parent::__construct('test', true);
        $this->utmCookieConfig = $utmCookieConfig;
    }

    public function registerBundles(): iterable
    {
        return [new FrameworkBundle(), new UtmCookieBundle()];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container): void {
            $container->loadFromExtension('framework', [
                'secret' => 'test',
                'test' => true,
            ]);
            $container->loadFromExtension('utm_cookie', $this->utmCookieConfig);
        });
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/utm-cookie-bundle/'.md5(__DIR__.self::VERSION.json_encode($this->utmCookieConfig)).'/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/utm-cookie-bundle/logs';
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }
}
