<?php

declare(strict_types=1);

namespace UtmCookieBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;
use UtmCookieBundle\UtmCookie\UtmCookie;

class UtmCookieExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $definition = $container->register(UtmCookie::class, UtmCookie::class)
            ->setPublic(true)
            ->setArguments([new Reference('request_stack', ContainerInterface::NULL_ON_INVALID_REFERENCE)])
            ->addMethodCall('setName', [$config['name']])
            ->addMethodCall('setLifetime', [$config['lifetime']])
            ->addMethodCall('setPath', [$config['path']])
            ->addMethodCall('setDomain', [$config['domain']])
            ->addMethodCall('setOverwrite', [$config['overwrite']])
            ->addMethodCall('setSecure', [$config['secure']])
            ->addMethodCall('setHttpOnly', [$config['httponly']])
            ->addMethodCall('setSameSite', [$config['samesite']])
            ->addTag('kernel.event_listener', ['event' => 'kernel.response', 'method' => 'onKernelResponse'])
            ->addTag('kernel.reset', ['method' => 'reset'])
        ;

        if ($config['auto_init']) {
            $definition->addTag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'onKernelRequest']);
        }
    }
}
