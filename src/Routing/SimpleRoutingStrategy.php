<?php

namespace SCode\AmqpExtraBundle\Routing;

use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class SimpleRoutingStrategy implements RoutingStrategyInterface
{
    /**
     * @var NameConverterInterface
     */
    private $nameConverter;

    public function __construct(NameConverterInterface $nameConverter)
    {
        $this->nameConverter = $nameConverter;
    }

    public function getClass(string $routingKey, array $context): ?string
    {
        if (empty($context['class_map'])) {
            return null;
        }

        preg_match('/(.*)\.([^.]*)$/', $routingKey, $matches);

        if (empty($matches[2])) {
            return null;
        }

        $namespace = array_search($matches[1], $context['class_map']);

        if ($namespace === false) {
            return null;
        }

        return $namespace . '\\' . $this->nameConverter->denormalize($matches[2]);
    }

    public function getRoutingKey(string $class, array $context): ?string
    {
        if (empty($context['class_map'])) {
            return null;
        }

        $reflection = new \ReflectionClass($class);

        $routingKeyPath = $context['class_map'][$reflection->getNamespaceName()] ?? null;

        if ($routingKeyPath === null) {
            return null;
        }

        $messageName = $this->nameConverter->normalize($reflection->getShortName());

        return $routingKeyPath . '.' . $messageName;
    }
}