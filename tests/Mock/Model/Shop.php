<?php declare(strict_types=1);

namespace Danilovl\ObjectToArrayTransformBundle\Tests\Mock\Model;

class Shop
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?City $city = null
    ) {}
}
