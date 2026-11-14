<?php declare(strict_types=1);

namespace Danilovl\ParameterBundle\Tests\Service;

use Danilovl\ObjectToArrayTransformBundle\Service\ObjectToArrayTransformService;
use ArrayIterator;
use Danilovl\ObjectToArrayTransformBundle\Tests\Mock\Model\{
    Category,
    Node,
    Product,
    User,
    City,
    Shop,
    IssetField
};
use Danilovl\ParameterBundle\Service\ParameterService;
use DateTimeImmutable;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class ObjectToArrayTransformServiceTest extends TestCase
{
    private ObjectToArrayTransformService $objectToArrayTransformService;

    protected function setUp(): void
    {
        $parameterBug = new ParameterBag($this->getParameterBagData());
        $parameterService = new ParameterService($parameterBug);
        $this->objectToArrayTransformService = new ObjectToArrayTransformService($parameterService);
    }

    #[DataProvider('provideTransformCases')]
    public function testTransform(
        string $source,
        string|object $object,
        array $expectedValue,
    ): void {
        $value = $this->objectToArrayTransformService->transform(
            $source,
            $object
        );

        $this->assertEquals($expectedValue, $value);
    }

    public static function provideTransformCases(): Generator
    {
        yield ['id', self::getShopModel(), ['id' => 33, 'city' => ['id' => 500]]];
        yield ['name', self::getShopModel(), ['name' => 'Apple', 'city' => ['name' => 'London']]];
        yield ['all', self::getShopModel(), ['id' => 33, 'name' => 'Apple', 'city' => ['id' => 500, 'name' => 'London', 'latitude' => 54.343_434_3, 'longitude' => 55.333_425_45]]];
        yield ['all', self::getUserModel(), ['id' => 15, 'username' => 'transformer', 'email' => 'user@gmail.com']];
        yield ['all', self::getIssetFieldModel(), ['id' => 100, 'value' => 'value']];
    }

    #[DataProvider('provideDateFormatPriorityCases')]
    public function testDateFormatPriority(string $source, string $expectedDate): void
    {
        $value = $this->objectToArrayTransformService->transform($source, self::getProductModel());

        $expected = ['createdAt' => $expectedDate];

        $this->assertSame($expected, $value);
    }

    public static function provideDateFormatPriorityCases(): Generator
    {
        yield 'default format' => ['date_default', '2024-01-02 03:04:05'];
        yield 'source format' => ['date_source', '02.01.2024'];
        yield 'field format overrides source format' => ['date_field', '2024'];
    }

    public function testEmptyCollection(): void
    {
        $category = new Category('Empty');

        $value = $this->objectToArrayTransformService->transform('collection', $category);

        $expected = [
            'title' => 'Empty',
            'products' => []
        ];

        $this->assertSame($expected, $value);
    }

    public function testScalarCollection(): void
    {
        $product = new Product('Phone', self::getDate(), new ArrayIterator(['new', 'sale']));

        $value = $this->objectToArrayTransformService->transform('collection', $product);

        $expected = [
            'name' => 'Phone',
            'tags' => ['new', 'sale']
        ];

        $this->assertSame($expected, $value);
    }

    public function testObjectCollection(): void
    {
        $category = new Category('Phones');
        $category->addProduct(new Product('Phone', self::getDate(), new ArrayIterator(['new'])));
        $category->addProduct(new Product('Tablet', self::getDate()));

        $value = $this->objectToArrayTransformService->transform('collection', $category);

        $expected = [
            'title' => 'Phones',
            'products' => [
                ['name' => 'Phone', 'tags' => ['new']],
                ['name' => 'Tablet', 'tags' => []]
            ]
        ];

        $this->assertSame($expected, $value);
    }

    public function testSameObjectInSiblingBranchesIsNotCircular(): void
    {
        $product = self::getProductModel();
        $category = new Category('Phones');
        $category->addProduct($product);
        $category->addProduct($product);

        $value = $this->objectToArrayTransformService->transform('collection', $category);

        $this->assertCount(2, $value['products']);
    }

    public function testCircularReference(): void
    {
        $product = self::getProductModel();
        $category = new Category('Phones');
        $category->addProduct($product);
        $product->category = $category;

        $message = sprintf('Circular reference detected for class "%s".', Product::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        $this->objectToArrayTransformService->transform('cycle', $product);
    }

    public function testMaxDepthNotExceeded(): void
    {
        $value = $this->objectToArrayTransformService->transform('depth', self::getNodeChain(32));

        $this->assertSame(0, $value['id']);
    }

    public function testMaxDepthExceeded(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Maximum transformation depth 32 exceeded.');

        $this->objectToArrayTransformService->transform('depth', self::getNodeChain(33));
    }

    public function testClassNotExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Class "NotExistClass" does not exist.');

        $this->objectToArrayTransformService->transform('all', 'NotExistClass');
    }

    private static function getParameterBagData(): array
    {
        return [
            'id' => [
                'Shop' => [
                    'fields' => ['id', 'city']
                ],
                'City' => [
                    'fields' => ['id']
                ]
            ],
            'name' => [
                'Shop' => [
                    'fields' => ['name', 'city']
                ],
                'City' => [
                    'fields' => ['name']
                ]
            ],
            'all' => [
                'Shop' => [
                    'fields' => ['id', 'name', 'city']
                ],
                'City' => [
                    'fields' => ['id', 'name', 'latitude', 'longitude']
                ],
                'User' => [
                    'fields' => ['id', 'username', 'email']
                ],
                'IssetField' => [
                    'fields' => ['id', 'value']
                ]
            ],
            'date_default' => [
                'Product' => [
                    'fields' => ['createdAt']
                ]
            ],
            'date_source' => [
                'parameters' => [
                    'date_format' => 'd.m.Y'
                ],
                'Product' => [
                    'fields' => ['createdAt']
                ]
            ],
            'date_field' => [
                'parameters' => [
                    'date_format' => 'd.m.Y'
                ],
                'Product' => [
                    'fields' => [
                        ['createdAt' => ['parameters' => ['format' => 'Y']]]
                    ]
                ]
            ],
            'collection' => [
                'Category' => [
                    'fields' => ['title', 'products']
                ],
                'Product' => [
                    'fields' => ['name', 'tags']
                ]
            ],
            'cycle' => [
                'Category' => [
                    'fields' => ['title', 'products']
                ],
                'Product' => [
                    'fields' => ['name', 'category']
                ]
            ],
            'depth' => [
                'Node' => [
                    'fields' => ['id', 'child']
                ]
            ]
        ];
    }

    private static function getShopModel(): object
    {
        return new Shop(33, 'Apple', self::getCityModel());
    }

    private static function getCityModel(): City
    {
        return new City(500, 'London', 54.343_434_3, 55.333_425_45);
    }

    private static function getUserModel(): User
    {
        return new User(15, 'transformer', 'user@gmail.com');
    }

    private static function getIssetFieldModel(): IssetField
    {
        return new IssetField(100, 'value');
    }

    private static function getDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2024-01-02 03:04:05');
    }

    private static function getProductModel(): Product
    {
        return new Product('Phone', self::getDate());
    }

    private static function getNodeChain(int $length): Node
    {
        $node = new Node($length - 1);
        for ($id = $length - 2; $id >= 0; $id--) {
            $node = new Node($id, $node);
        }

        return $node;
    }
}
