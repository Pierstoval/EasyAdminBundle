<?php

namespace EasyCorp\Bundle\EasyAdminBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class EasyAdminDTOFactoriesPass implements CompilerPassInterface
{
    public const OBJECT_FACTORY_TAG = 'easyadmin.data_object_factory';

    public function process(ContainerBuilder $container)
    {
        $dtoFactoryDefinition = $container->getDefinition('easyadmin.dto_factory');

        foreach ($container->findTaggedServiceIds(self::OBJECT_FACTORY_TAG) as $id => $tags) {
            $dtoFactoryDefinition->addMethodCall('addFactory', [new Reference($id)]);
        }
    }
}
