<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Form\DTO;

use AppTestBundle\Entity\UnitTests\Product;
use AppTestBundle\Form\DTO\EditProductDTO;
use AppTestBundle\Form\DTO\NewProductDTO;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigManager;
use EasyCorp\Bundle\EasyAdminBundle\Form\DTO\DTOFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DTOFactoryTest extends TestCase
{
    /**
     * @dataProvider provideTestDataToCreateDTO
     */
    public function testDTOCreationWithProvidedData(string $action, string $dtoClassToCheck, array $configParams = [], $defaultData = null)
    {
        $container = $this->createMock(ContainerInterface::class);

        $factory = new DTOFactory($this->getConfigManager($configParams), $container);

        $dto = $factory->createEntityDTO('Product', $action, $defaultData);

        static::assertInstanceOf($dtoClassToCheck, $dto);
    }

    public function provideTestDataToCreateDTO()
    {
        yield 'create_new_DTO_from_default_factory' => [
            'new',
            NewProductDTO::class,
        ];

        yield 'create_edit_DTO_from_default_factory' => [
            'edit',
            EditProductDTO::class,
            [],
            new Product(),
        ];

        yield 'create_new_DTO_from_constructor' => [
            'new',
            NewProductDTO::class,
            ['new' => ['dto_factory' => '__construct']],
        ];

        yield 'create_edit_DTO_from_constructor' => [
            'edit',
            EditProductDTO::class,
            ['edit' => ['dto_factory' => '__construct']],
            new Product(),
        ];

        yield 'create_new_DTO_from_static_factory' => [
            'new',
            NewProductDTO::class,
            ['new' => ['dto_factory' => StaticDTOFactoryTest::class.'::createNewDTO']],
        ];

        yield 'create_edit_DTO_from_static_factory' => [
            'edit',
            EditProductDTO::class,
            ['edit' => ['dto_factory' => StaticDTOFactoryTest::class.'::createEditDTO']],
            new Product(),
        ];
    }

    public function testNewDTOCreationFromContainer()
    {
        $serviceFactory = $this->createMock(TestFactoryInterface::class);
        $serviceFactory
            ->expects($this->once())
            ->method('factory')
            ->with()
            ->willReturn(new NewProductDTO())
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with('test_service_factory')
            ->willReturn(true)
        ;
        $container
            ->expects($this->once())
            ->method('get')
            ->with('test_service_factory')
            ->willReturn($serviceFactory)
        ;

        $configParams = ['new' => ['dto_class' => NewProductDTO::class, 'dto_factory' => 'test_service_factory::factory']];

        $factory = new DTOFactory($this->getConfigManager($configParams), $container);

        $dto = $factory->createEntityDTO('Product', 'new');

        static::assertInstanceOf(NewProductDTO::class, $dto);
    }

    public function testEditDTOCreationFromContainer()
    {
        $defaultData = new Product();

        $serviceFactory = $this->createMock(TestFactoryInterface::class);
        $serviceFactory
            ->expects($this->once())
            ->method('factory')
            ->with($defaultData)
            ->willReturn(new EditProductDTO($defaultData))
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with('test_service_factory')
            ->willReturn(true)
        ;
        $container
            ->expects($this->once())
            ->method('get')
            ->with('test_service_factory')
            ->willReturn($serviceFactory)
        ;

        $configParams = ['edit' => ['dto_class' => EditProductDTO::class, 'dto_factory' => 'test_service_factory::factory']];

        $factory = new DTOFactory($this->getConfigManager($configParams), $container);

        $dto = $factory->createEntityDTO('Product', 'edit', $defaultData);

        static::assertInstanceOf(EditProductDTO::class, $dto);
    }

    public function testNoFactoryAvailableToCreateDTO()
    {
        $container = $this->createMock(ContainerInterface::class);

        $factory = new DTOFactory($this->getConfigManager([]), $container);

        $factory->createEntityDTO('Product', 'new');
    }

    private function getConfigManager(array $entityConfig = []): ConfigManager
    {
        $configManager = $this->createMock(ConfigManager::class);

        $configManager
            ->expects($this->once())
            ->method('getEntityConfig')
            ->with('Product')
            ->willReturn(array_replace_recursive(
                [
                    'class' => Product::class,
                    'new' => [
                        'fields' => [],
                        'dto_class' => NewProductDTO::class,
                        'dto_factory' => null,
                        'dto_entity_method' => null,
                    ],
                    'edit' => [
                        'dto_class' => EditProductDTO::class,
                        'dto_factory' => null,
                        'dto_entity_method' => null,
                    ],
                ], $entityConfig
            ));

        return $configManager;
    }
}

interface TestFactoryInterface
{
    public function factory();
}

class StaticDTOFactoryTest
{
    public static function createNewDTO()
    {
        return new NewProductDTO();
    }

    public static function createEditDTO(Product $product)
    {
        return new EditProductDTO($product);
    }
}
