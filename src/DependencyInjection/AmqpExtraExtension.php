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

        foreach ($config as $busName => $item) {
            if ($item['dynamic_routing']['enabled']) {
                $this->configureDymamicRoutingBus($busName, $item, $container);
            }

            if ($item['shared_transport']['enabled']) {
                $this->configureSharedBus($busName, $item, $container);
            }
        }
    }

    private function configureDymamicRoutingBus(string $busName, array $config, ContainerBuilder $container): void
    {
        $middlewareName = $busName . '.dynamic_routing_middleware';
        $routingContextParameter = $this->getRoutingContextParameter($busName);

        $middleware = (new ChildDefinition('amqp_extra.dynamic_routing_middleware'))
            ->setBindings([
                '$strategy' => new Reference($config['dynamic_routing']['strategy']),
                '$routingContext' => new Parameter($routingContextParameter)
            ]);

        $container->setDefinition($middlewareName, $middleware);
        $container->setParameter($routingContextParameter, ['class_map' => $config['dynamic_routing']['class_map']]);
    }

    private function configureSharedBus(string $busName, array $config, ContainerBuilder $container): void
    {
        $serializerName = $busName . '.shared_serializer';
        $headersConverterName = $busName . '.shared_headers_converter';
        $headersMapParameter = $busName . '.shared_headers_map';
        $routingContextParametr = $this->getRoutingContextParameter($busName);

        $headersConverter = (new ChildDefinition('amqp_extra.headers_converter'))
            ->setBindings([
                '$routingStrategy' => new Reference($config['dynamic_routing']['strategy']),
                '$routingContext' => new Parameter($routingContextParametr),
                '$headersMap' => new Parameter($headersMapParameter),
            ]);

        $serializer = (new ChildDefinition('amqp_extra.shared_transport_serializer'))
            ->setBindings([
                '$busName' => $busName,
                '$originalSerializer' => new Reference($config['shared_transport']['original_serializer']),
                '$headersConverter' => new Reference($headersConverterName)
            ]);

        $container->setDefinition($serializerName, $serializer);
        $container->setDefinition($headersConverterName, $headersConverter);
        $container->setParameter($headersMapParameter, $config['shared_transport']['headers_map']);
    }
    
    private function getRoutingContextParameter(string $busName): string
    {
        return $busName . '.dynamic_routing_context';
    }
}
