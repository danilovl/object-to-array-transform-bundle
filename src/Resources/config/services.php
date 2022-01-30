<?php declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Danilovl\ObjectToArrayTransformBundle\Service\ObjectToArrayTransformService;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('danilovl.object_to_array_transform', ObjectToArrayTransformService::class)
        ->args([
            service('danilovl.parameter'),
        ])
        ->public()
        ->alias(ObjectToArrayTransformService::class, 'danilovl.object_to_array_transform');
};
