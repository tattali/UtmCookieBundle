<?php

declare(strict_types=1);

namespace UtmCookieBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('utm_cookie');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('name')->defaultValue('utm')->end()
                ->integerNode('lifetime')->defaultValue(604800)->min(0)->end()
                ->scalarNode('path')->defaultValue('/')->end()
                ->scalarNode('domain')->defaultValue('')->end()
                ->booleanNode('overwrite')->defaultTrue()->end()
                ->enumNode('secure')->values([null, true, false])->defaultNull()->info('Null secures the cookie when the request is made over HTTPS')->end()
                ->enumNode('samesite')->values(['lax', 'strict', 'none', null])->defaultValue('lax')->end()
                ->booleanNode('httponly')->defaultFalse()->end()
                ->booleanNode('auto_init')->defaultTrue()->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
