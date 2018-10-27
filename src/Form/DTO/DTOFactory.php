<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\DTO;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigManager;

final class DTOFactory
{
    private $configManager;

    /**
     * @var ObjectFactoryInterface[]
     */
    private $factories;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    public function addFactory(ObjectFactoryInterface $objectFactory): void
    {
        $factoryName = $objectFactory->getName();

        if (isset($this->factories[$factoryName])) {
            throw new \InvalidArgumentException(sprintf(
                'Object factory with name "%s" already exists. You cannot set two object factories with the same name.',
                $factoryName
            ));
        }

        $this->factories[$factoryName] = $objectFactory;
    }

    public function hasFactory(string $name): bool
    {
        return \array_key_exists($name, $this->factories);
    }

    public function createEntityDTO(string $entityName, string $view, $entityObject = null)
    {
        $entityConfig = $this->configManager->getEntityConfig($entityName);

        $dtoClass = $entityConfig[$view]['dto_class'];
        $factory = $entityConfig[$view]['dto_factory'];

        $serviceFactory = $this->factories[$factory] ?? null;

        if (null !== $serviceFactory) {
            return $serviceFactory->createDTO($dtoClass, $view, $entityObject);
        }

        if (null === $factory || '__construct' === $factory) {
            return new $dtoClass($entityObject);
        }

        if (false === \strpos($factory, '::')) {
            $callable = $dtoClass.'::'.$factory;

            return $callable($entityObject);
        }

        if (\is_callable($factory)) {
            // Static factories
            return $factory($entityObject);
        }

        throw new \InvalidArgumentException(sprintf(
        'Could not find a way to create a DTO for entity %s with configured factory %s.',
            $entityName, $factory
        ));
    }
}
