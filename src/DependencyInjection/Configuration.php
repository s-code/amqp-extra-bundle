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
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('class_map')
                                ->scalarPrototype()->end()
                            ->end()
                            ->scalarNode('strategy')
                                ->defaultValue('amqp_extra.default_routing_strategy')
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
                ->end()
                ->arrayNode('shared_transport')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('default_bus')
                                ->defaultValue('messenger.default_bus')
                                ->cannotBeEmpty()
                                ->scalarPrototype()->end()
                            ->end()
                            ->arrayNode('routing')
                                ->cannotBeEmpty()
                                ->scalarPrototype()->end()
                            ->end()
                            ->scalarNode('original_serializer')
                                ->defaultValue('messenger.transport.symfony_serializer')
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
                            ->arrayNode('headers_map')
                                ->scalarPrototype()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}