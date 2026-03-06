<?php

namespace INSYS\Bundle\MaintenanceBundle\Drivers\Query;

/**
 * Abstract class to handle PDO connection
 *
 * @package INSYSMaintenanceBundle
 * @author  Gilles Gauthier <g.gauthier@lexik.fr>
 */
abstract class PdoQuery
{
    /**
     * @var \PDO|\Doctrine\DBAL\Connection|null
     */
    protected $db;

    /**
     * @var array
     */
    protected $options;

    /**
     * Constructor PdoDriver
     *
     * @param array $options Options driver
     */
    public function __construct(array $options = array())
    {
        $this->options = $options;
    }

    /**
     * Execute create query
     *
     * @return void
     */
    abstract function createTableQuery();

    /**
     * Result of delete query
     *
     * @param \PDO|\Doctrine\DBAL\Connection $db PDO or DBAL connection instance
     *
     * @return boolean
     */
    abstract function deleteQuery($db);

    /**
     * Result of select query
     *
     * @param \PDO|\Doctrine\DBAL\Connection $db PDO or DBAL connection instance
     *
     * @return array
     */
    abstract function selectQuery($db);

    /**
     * Result of insert query
     *
     * @param string|null                   $ttl ttl value
     * @param \PDO|\Doctrine\DBAL\Connection $db  PDO or DBAL connection instance
     *
     * @return boolean
     */
    abstract function insertQuery($ttl, $db);

    /**
     * Initialize pdo connection
     *
     * @return \PDO|\Doctrine\DBAL\Connection
     */
    abstract function initDb();

    /**
     * Execute sql
     *
     * @param \PDO|\Doctrine\DBAL\Connection $db    PDO or DBAL connection instance
     * @param string                         $query Query
     * @param array                          $args  Arguments
     *
     * @return boolean
     *
     * @throws \RuntimeException
     */
    protected function exec($db, $query, array $args = array())
    {
        if ($db instanceof \Doctrine\DBAL\Connection) {
            return $db->executeStatement($query, $args) > 0;
        }

        $stmt = $this->prepareStatement($db, $query);

        foreach ($args as $arg => $val) {
            $stmt->bindValue($arg, $val, $this->getPdoParamType($val));
        }

        $success = $stmt->execute();

        if (!$success) {
            throw new \RuntimeException(sprintf('Error executing query "%s"', $query));
        }

        return $success;
    }

    /**
     * @param \PDO $db PDO instance
     * @param string $query Query
     *
     * @return \PDOStatement
     *
     * @throws \RuntimeException
     */
    protected function prepareStatement($db, $query)
    {
        try {
            $stmt = $db->prepare($query);
        } catch (\Exception $e) {
            $stmt = false;
        }

        if (false === $stmt) {
            throw new \RuntimeException('The database cannot successfully prepare the statement');
        }

        return $stmt;
    }

    /**
     * Fetch All
     *
     * @param \PDO|\Doctrine\DBAL\Connection $db    PDO or DBAL connection instance
     * @param string                         $query Query
     * @param array                          $args  Arguments
     *
     * @return array
     */
    protected function fetch($db, $query, array $args = array())
    {
        if ($db instanceof \Doctrine\DBAL\Connection) {
            return $db->executeQuery($query, $args)->fetchAllAssociative();
        }

        $stmt = $this->prepareStatement($db, $query);

        foreach ($args as $arg => $val) {
            $stmt->bindValue($arg, $val, $this->getPdoParamType($val));
        }

        $stmt->execute();
        $return = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $return;
    }

    /**
     * @param mixed $value
     */
    private function getPdoParamType($value)
    {
        if ($value === null) {
            return \PDO::PARAM_NULL;
        }

        return is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
    }
}
