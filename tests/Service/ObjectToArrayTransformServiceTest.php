<?php declare(strict_types=1);

namespace Danilovl\ParameterBundle\Tests\Service;

use Danilovl\ObjectToArrayTransformBundle\Service\ObjectToArrayTransformService;
use Danilovl\ObjectToArrayTransformBundle\Tests\Mock\Model\{
    User,
    City,
    Shop,
    IssetField
};
use Danilovl\ParameterBundle\Service\ParameterService;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
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

    #[DataProvider('dataTransform')]
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

    public static function dataTransform(): Generator
    {
        yield ['id', self::getShopModel(), ['id' => 33, 'city' => ['id' => 500]]];
        yield ['name', self::getShopModel(), ['name' => 'Apple', 'city' => ['name' => 'London']]];
        yield ['all', self::getShopModel(), ['id' => 33, 'name' => 'Apple', 'city' => ['id' => 500, 'name' => 'London', 'latitude' => 54.343_434_3, 'longitude' => 55.333_425_45]]];
        yield ['all', self::getUserModel(), ['id' => 15, 'username' => 'transformer', 'email' => 'user@gmail.com']];
        yield ['all', self::getIssetFieldModel(), ['id' => 100, 'value' => 'value']];
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
}
