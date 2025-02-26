<?php

declare(strict_types=1);

namespace UtmCookieBundle;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use UtmCookieBundle\DependencyInjection\UtmCookieExtension;

class UtmCookieBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return dirname(__DIR__);
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new UtmCookieExtension();
    }
}
