<?php declare(strict_types=1);

namespace Danilovl\ObjectToArrayTransformBundle\Tests\Mock\Model;

use DateTimeInterface;

class Product
{
    public ?Category $category = null;

    /**
     * @param iterable<string> $tags
     */
    public function __construct(
        public readonly string $name,
        public readonly DateTimeInterface $createdAt,
        public readonly iterable $tags = []
    ) {}
}
