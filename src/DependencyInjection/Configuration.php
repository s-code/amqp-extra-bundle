<?php

namespace SCode\AmqpExtraBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('amqp_extra');

        if (method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $rootNode = $treeBuilder->root('maker');
        }

        $rootNode
            ->children()
                ->arrayNode('routing')
                    ->children()
                        ->arrayNode('map')
                            ->useAttributeAsKey('name')
                            ->prototype('scalar')
                        ->end()
                        ->scalarNode('name_converter')
                            ->defaultValue('amqp_extra.routing_map_name_converter')
                            ->cannotBeEmpty()
                            ->beforeNormalization()
                            ->ifTrue(function ($v) {
                                return strpos($v, '@') === 0;
                            })
                            ->then(function ($v) {
                                return substr($v, 1);
                            })
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}