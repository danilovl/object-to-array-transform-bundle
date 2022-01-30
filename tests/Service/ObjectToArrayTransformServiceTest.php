<?php declare(strict_types=1);

namespace Danilovl\ParameterBundle\Tests\Service;

use Danilovl\ObjectToArrayTransformBundle\Service\ObjectToArrayTransformService;
use Danilovl\ObjectToArrayTransformBundle\Tests\Mock\Model\{
    City,
    IssetField,
    Shop,
    User
};
use Danilovl\ParameterBundle\Services\ParameterService;
use Generator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class ObjectToArrayTransformServiceTest extends TestCase
{
    private ObjectToArrayTransformService $objectToArrayTransformService;

    public function setUp(): void
    {
        $parameterBug = new ParameterBag($this->getParameterBagData());
        $parameterService = new ParameterService($parameterBug);
        $this->objectToArrayTransformService = new ObjectToArrayTransformService($parameterService);
    }

    /**
     * @dataProvider dataTransform
     */
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

    public function dataTransform(): Generator
    {
        yield ['id', $this->getShopModel(), ['id' => 33, 'city' => ['id' => 500]]];
        yield ['name', $this->getShopModel(), ['name' => 'Apple', 'city' => ['name' => 'London']]];
        yield ['all', $this->getShopModel(), ['id' => 33, 'name' => 'Apple', 'city' => ['id' => 500, 'name' => 'London', 'latitude' => 54.3434343, 'longitude' => 55.33342545]]];
        yield ['all', $this->getUserModel(), ['id' => 15, 'username' => 'transformer', 'email' => 'user@gmail.com']];
        yield ['all', $this->getIssetFieldModel(), ['id' => 100, 'value' => 'value']];
    }

    private function getParameterBagData(): array
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

    private function getShopModel(): object
    {
        return new Shop(33, 'Apple', $this->getCityModel());
    }

    private function getCityModel(): City
    {
        return new City(500, 'London', 54.3434343, 55.33342545);
    }

    private function getUserModel(): User
    {
        return new User(15, 'transformer', 'user@gmail.com');
    }

    private function getIssetFieldModel(): IssetField
    {
        return new IssetField(100, 'value');
    }
}
