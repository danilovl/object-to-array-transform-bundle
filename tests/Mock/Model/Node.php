<?php declare(strict_types=1);

namespace Danilovl\ObjectToArrayTransformBundle\Tests\Mock\Model;

class Node
{
    public function __construct(
        public readonly int $id,
        public readonly ?self $child = null
    ) {}
}
