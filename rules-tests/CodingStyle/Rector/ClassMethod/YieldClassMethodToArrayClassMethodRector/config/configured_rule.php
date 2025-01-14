<?php

declare(strict_types=1);

use Rector\CodingStyle\Rector\ClassMethod\YieldClassMethodToArrayClassMethodRector;
use Rector\Tests\CodingStyle\Rector\ClassMethod\YieldClassMethodToArrayClassMethodRector\Source\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->set(YieldClassMethodToArrayClassMethodRector::class)
        ->call('configure', [[
            YieldClassMethodToArrayClassMethodRector::METHODS_BY_TYPE => [
                EventSubscriberInterface::class => ['getSubscribedEvents'],
            ],
        ]]);
};
