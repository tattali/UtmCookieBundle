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
     * @var string
     */
    private $cacheDir;

    /**
     * @param array<string, mixed> $utmCookieConfig
     */
    public function __construct(array $utmCookieConfig = [])
    {
        parent::__construct('test', true);
        $this->utmCookieConfig = $utmCookieConfig;
        // a new container for each kernel: a cached one would hide changes to the bundle's code,
        // such as the mutants of mutation tests
        $this->cacheDir = sys_get_temp_dir().'/utm-cookie-bundle/'.uniqid('', true);
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
        return $this->cacheDir;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/utm-cookie-bundle/logs';
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    public function removeCacheDir(): void
    {
        if (!is_dir($this->cacheDir)) {
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->cacheDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            \assert($file instanceof \SplFileInfo);
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->cacheDir);
    }
}
