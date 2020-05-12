<?php

namespace SCode\AmqpExtraBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class AmqpExtraExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);
        
        $this->configureRouting($config, $container);
        $this->configureSharedTransport($config, $container);
    }

    private function configureRouting(array $config, ContainerBuilder $container): void
    {
        if (empty($config['routing'])) {
            return;
        }

        foreach ($config['routing'] as $name => $item) {
            $middlewareName = $name . '_routing_middleware';
            $routingContextParameter = $this->getRoutingContextParameter($name);

            $middleware = (new ChildDefinition('amqp_extra.dynamic_routing_middleware'))
                ->setBindings([
                    '$strategy' => new Reference($item['strategy']),
                    '$routingContext' => new Parameter($routingContextParameter)
                ]);

            $container->setDefinition($middlewareName, $middleware);
            $container->setParameter($routingContextParameter, ['class_map' => $item['class_map']]);
        }
    }

    private function configureSharedTransport(array $config, ContainerBuilder $container): void
    {
        if (empty($config['shared_transport'])) {
            return;
        }

        foreach ($config['shared_transport'] as $name => $item) {
            $serializerName = $name . '_shared_transport_serializer';
            $headersConverterName = $name . '_shared_transport_headers_converter';
            $headersMapParameter = $name . '_shared_transport_headers_map';
            $routingContextParameter = $this->getRoutingContextParameter($item['routing']);
            $routingStrategy = $config['routing'][$item['routing']]['strategy'];

            $headersConverter = (new ChildDefinition('amqp_extra.headers_converter'))
                ->setBindings([
                    '$routingStrategy' => new Reference($routingStrategy),
                    '$routingContext' => new Parameter($routingContextParameter),
                    '$headersMap' => new Parameter($headersMapParameter),
                ]);

            $serializer = (new ChildDefinition('amqp_extra.shared_transport_serializer'))
                ->setBindings([
                    '$busName' => $item['default_bus'],
                    '$originalSerializer' => new Reference($item['original_serializer']),
                    '$headersConverter' => new Reference($headersConverterName)
                ]);

            $container->setDefinition($serializerName, $serializer);
            $container->setDefinition($headersConverterName, $headersConverter);
            $container->setParameter($headersMapParameter, $item['headers_map']);
        }
    }
    
    private function getRoutingContextParameter(string $routingName): string
    {
        return $routingName . '_routing_context';
    }
}
