<?php

use INSYS\Bundle\MaintenanceBundle\Drivers\DatabaseDriver;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('insys_maintenance.driver.database', DatabaseDriver::class)
        ->arg(0, service('doctrine')->ignoreOnInvalid());
};
