<?php declare(strict_types=1);

namespace Danilovl\ObjectToArrayTransformBundle\Tests\Mock\Model;

class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly string $email
    ) {}
}
