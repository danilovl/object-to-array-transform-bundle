<?php declare(strict_types=1);

namespace Danilovl\ObjectToArrayTransformBundle\Tests\Mock\Model;

class User
{
    public function __construct(
        public int $id,
        public string $username,
        public string $email
    ) {
    }
}
