<?php

namespace INSYS\Bundle\MaintenanceBundle\Drivers\Query;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Default Class for handle database with a doctrine connection
 *
 * @package INSYSMaintenanceBundle
 * @author  Gilles Gauthier <g.gauthier@lexik.fr>
 */
class DefaultQuery extends PdoQuery
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    const NAME_TABLE   = 'insys_maintenance';

    /**
     * @param EntityManagerInterface $em Entity Manager
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function initDb()
    {
        if (null === $this->db) {
            $db = $this->em->getConnection();
            $this->db = $db;
            $this->createTableQuery();
        }

        return $this->db;
    }

    /**
     * {@inheritdoc}
     */
    public function createTableQuery()
    {
        $platform = $this->em->getConnection()->getDatabasePlatform();
        $type = $platform instanceof \Doctrine\DBAL\Platforms\MySQLPlatform ? 'datetime' : 'timestamp';

        $this->db->exec(
            sprintf('CREATE TABLE IF NOT EXISTS %s (ttl %s DEFAULT NULL)', self::NAME_TABLE, $type)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function deleteQuery($db)
    {
        return $this->exec($db, sprintf('DELETE FROM %s', self::NAME_TABLE));
    }

    /**
     * {@inheritdoc}
     */
    public function selectQuery($db)
    {
        return $this->fetch($db, sprintf('SELECT ttl FROM %s', self::NAME_TABLE));
    }

    /**
     * {@inheritdoc}
     */
    public function insertQuery($ttl, $db)
    {
        return $this->exec(
            $db, sprintf('INSERT INTO %s (ttl) VALUES (:ttl)',
            self::NAME_TABLE),
            array(':ttl' => $ttl)
        );
    }
}
