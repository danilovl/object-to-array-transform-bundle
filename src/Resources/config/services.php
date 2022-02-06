<?php declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Danilovl\ObjectToArrayTransformBundle\Interfaces\ObjectToArrayTransformServiceInterface;
use Danilovl\ObjectToArrayTransformBundle\Service\ObjectToArrayTransformService;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set(ObjectToArrayTransformService::class, ObjectToArrayTransformService::class)
        ->autowire()
        ->public()
        ->alias(ObjectToArrayTransformServiceInterface::class, ObjectToArrayTransformService::class);
};
