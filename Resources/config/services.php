<?php

use INSYS\Bundle\MaintenanceBundle\Command\DriverLockCommand;
use INSYS\Bundle\MaintenanceBundle\Command\DriverUnlockCommand;
use INSYS\Bundle\MaintenanceBundle\Drivers\DriverFactory;
use INSYS\Bundle\MaintenanceBundle\Listener\MaintenanceListener;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services
        ->set('insys_maintenance.driver.factory', DriverFactory::class)
        ->public()
        ->arg(0, service('insys_maintenance.driver.database'))
        ->arg(1, service('translator'))
        ->arg(2, param('insys_maintenance.driver'));

    $services
        ->set('insys_maintenance.listener', MaintenanceListener::class)
        ->tag('kernel.event_listener', array('event' => 'kernel.request', 'method' => 'onKernelRequest'))
        ->tag('kernel.event_listener', array('event' => 'kernel.response', 'method' => 'onKernelResponse'))
        ->arg(0, service('insys_maintenance.driver.factory'))
        ->arg(1, param('insys_maintenance.authorized.path'))
        ->arg(2, param('insys_maintenance.authorized.host'))
        ->arg(3, param('insys_maintenance.authorized.ips'))
        ->arg(4, param('insys_maintenance.authorized.query'))
        ->arg(5, param('insys_maintenance.authorized.cookie'))
        ->arg(6, param('insys_maintenance.authorized.route'))
        ->arg(7, param('insys_maintenance.authorized.attributes'))
        ->arg(8, param('insys_maintenance.response.http_code'))
        ->arg(9, param('insys_maintenance.response.http_status'))
        ->arg(10, param('insys_maintenance.response.exception_message'))
        ->arg(11, param('kernel.debug'));

    $services
        ->set(DriverLockCommand::class)
        ->arg(0, service('insys_maintenance.driver.factory'))
        ->tag('console.command');

    $services
        ->set(DriverUnlockCommand::class)
        ->arg(0, service('insys_maintenance.driver.factory'))
        ->tag('console.command');
};
