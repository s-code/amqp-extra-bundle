<?php

namespace SCode\AmqpExtraBundle\Middleware;

use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class RoutingMap
{
    /**
     * @var NameConverterInterface
     */
    private $nameConverter;

    /**
     * @var array
     */
    private $routingMap;

    /**
     * @var string[]
     */
    private $rotingKeyCache;

    /**
     * @var string[]
     */
    private $classCache;

    /**
     * @param NameConverterInterface $nameConverter
     * @param string[] $routingMap
     */
    public function __construct(NameConverterInterface $nameConverter, array $routingMap)
    {
        $this->nameConverter = $nameConverter;
        $this->routingMap = $routingMap;
        $this->rotingKeyCache = [];
        $this->classCache = [];
    }

    public function getClass(string $routingKey): ?string
    {
        if (!isset($this->classCache[$routingKey])) {
            preg_match('/(.*)\.([^.]*)$/', $routingKey, $matches);

            if (empty($matches[2])) {
                throw new \InvalidArgumentException('Roting key "%s" has unsupported format');
            }

            $namespace = array_search($matches[1], $this->routingMap);

            if ($namespace === false) {
                return null;
            }

            $className = $namespace . '\\' . $this->nameConverter->denormalize($matches[2]);

            $this->rotingKeyCache[$className] = $routingKey;
            $this->classCache[$routingKey] = $className;
        }

        return $this->classCache[$routingKey];
    }

    public function getRoutingKey(string $className): ?string
    {
        if (!isset($this->rotingKeyCache[$className])) {
            $class = new \ReflectionClass($className);

            $routingKeyPath = $this->routingMap[$class->getNamespaceName()] ?? null;

            if ($routingKeyPath === null) {
                return null;
            }

            $messageName = $this->nameConverter->normalize($class->getShortName());
            $routingKey = $routingKeyPath . '.' . $messageName;

            $this->rotingKeyCache[$className] = $routingKey;
            $this->classCache[$routingKey] = $className;
        }

        return $this->rotingKeyCache[$className];
    }
}