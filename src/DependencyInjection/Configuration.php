<?php

declare(strict_types=1);

namespace UtmCookieBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('utm_cookie');
        $rootNode = method_exists($treeBuilder, 'getRootNode') ? $treeBuilder->getRootNode() : $treeBuilder->root('utm_cookie');

        $rootNode
            ->children()
            ->scalarNode('name')->defaultValue('utm')->end()
            ->integerNode('lifetime')->defaultValue(604800)->min(0)->end()
            ->scalarNode('path')->defaultValue('/')->end()
            ->scalarNode('domain')->defaultValue('')->end()
            ->booleanNode('overwrite')->defaultTrue()->end()
            ->booleanNode('secure')->defaultFalse()->end()
            ->booleanNode('httponly')->defaultFalse()->end()
            ->booleanNode('auto_init')->defaultTrue()->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
