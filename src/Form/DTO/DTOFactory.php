<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\DTO;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DTOFactory
{
    private $configManager;
    private $container;

    public function __construct(ConfigManager $configManager, ContainerInterface $container)
    {
        $this->configManager = $configManager;
        $this->container = $container;
    }

    public function createEntityDTO(string $entityName, string $view, $entityObject = null)
    {
        $entityConfig = $this->configManager->getEntityConfig($entityName);

        $dtoClass = $entityConfig[$view]['dto_class'];
        $factory = $entityConfig[$view]['dto_factory'];

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

        // Check if the service exists in the container
        [$class, $method] = \explode('::', $factory);

        if ($this->container->has($class)) {
            return $this->container->get($class)->$method($entityObject);
        }

        throw new \InvalidArgumentException(sprintf(
        'Could not find a way to create a DTO for entity %s with configured factory %s.',
            $entityName, $factory
        ));
    }
}
