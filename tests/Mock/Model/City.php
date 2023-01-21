<?php declare(strict_types=1);

namespace Danilovl\ObjectToArrayTransformBundle\Tests\Mock\Model;

class City
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly float $latitude,
        public readonly float $longitude
    ) {}
}
