<?php declare(strict_types=1);

namespace Danilovl\ObjectToArrayTransformBundle\Interfaces;

interface ObjectToArrayTransformServiceInterface
{
    public function transform(string $source, string|object $object, array $objectFields = null): array;
}