<?php

namespace INSYS\Bundle\MaintenanceBundle\Tests\Maintenance;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use INSYS\Bundle\MaintenanceBundle\Drivers\DatabaseDriver;

class DatabaseDriverTest extends \PHPUnit\Framework\TestCase
{
    public function testLockUsesDbalExecutionApi()
    {
        $executedStatements = array();

        $result = $this->createMock(Result::class);
        $result->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn(array());

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn(new MySQLPlatform());
        $connection->expects($this->exactly(2))
            ->method('executeStatement')
            ->willReturnCallback(function (string $sql, array $params = array()) use (&$executedStatements) {
                $executedStatements[] = array($sql, $params);

                return 1;
            });
        $connection->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT ttl FROM insys_maintenance', array())
            ->willReturn($result);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->exactly(2))
            ->method('getConnection')
            ->willReturn($connection);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->once())
            ->method('getManager')
            ->willReturn($entityManager);

        $driver = new DatabaseDriver($registry);
        $driver->setOptions(array());

        $this->assertTrue($driver->lock());
        $this->assertSame(
            array(
                array('CREATE TABLE IF NOT EXISTS insys_maintenance (ttl datetime DEFAULT NULL)', array()),
                array('INSERT INTO insys_maintenance (ttl) VALUES (:ttl)', array(':ttl' => null)),
            ),
            $executedStatements
        );
    }
}
