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
            ->validate()
                ->ifTrue(function (array $config) {
                    foreach ($config['shared_transport'] as $item) {
                        if (!isset($config['routing'][$item['routing']])) {
                            return true;
                        }
                    }

                    return false;
                })
                ->thenInvalid('Shared transports configuration contain undefined roting names')
            ->end()  
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
                            ->scalarNode('default_bus')
                                ->defaultValue('messenger.default_bus')
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('routing')
                                ->isRequired()
                                ->cannotBeEmpty()
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