<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\DTO;

interface ObjectFactoryInterface
{
    /**
     * This is used to identify the factory name in entities configuration.
     *
     * For example, if this method has "return 'my_factory';", it must be
     * referenced in the config as:
     *
     *     easy_admin:
     *         entities:
     *             User:
     *                 edit:
     *                     dto_factory: "my_factory"
     */
    public function getName(): string;

    public function createDTO(string $class, string $view, $defaultData = null);
}
