<?php

declare(strict_types=1);

namespace UtmCookieBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use UtmCookieBundle\UtmCookie\UtmCookie;

class UtmCookieExtension extends Extension
{
    public function load(array $config, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $this->processConfiguration($configuration, $config);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $definition = $container->getDefinition(UtmCookie::class);
        $definition->addMethodCall('setName', [$config['name']]);
        $definition->addMethodCall('setLifetime', [$config['lifetime']]);
        $definition->addMethodCall('setPath', [$config['path']]);
        $definition->addMethodCall('setDomain', [$config['domain']]);
        $definition->addMethodCall('setOverwrite', [$config['overwrite']]);
        $definition->addMethodCall('setSecure', [$config['secure']]);
        $definition->addMethodCall('setHttponly', [$config['httponly']]);
        if ($config['auto_init']) {
            $definition->addTag(
                'kernel.event_listener',
                ['event' => 'kernel.request', 'method' => 'onKernelRequest']
            );
        }
    }
}
