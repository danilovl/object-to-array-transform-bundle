<?php declare(strict_types=1);

namespace Danilovl\ObjectToArrayTransformBundle\Tests\Mock\Model;

class Shop
{
    public function __construct(
        private int $id,
        private string $name,
        private ?City $city = null
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCity(): ?object
    {
        return $this->city;
    }
}
