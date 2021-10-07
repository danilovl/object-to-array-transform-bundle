<?php declare(strict_types=1);

namespace Danilovl\ObjectToArrayTransformBundle;

use Danilovl\ObjectToArrayTransformBundle\DependencyInjection\ObjectToArrayTransformExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ObjectToArrayTransformBundle extends Bundle
{
    public function getContainerExtension(): ObjectToArrayTransformExtension
    {
        return new ObjectToArrayTransformExtension;
    }
}
