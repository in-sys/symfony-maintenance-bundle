<?php

namespace INSYS\Bundle\MaintenanceBundle\Drivers;

/**
 * Class to handle a shared memory driver
 *
 * @package INSYSMaintenanceBundle
 * @author  Audrius Karabanovas <audrius@karabanovas.net>
 */
class ShmDriver extends AbstractDriver
{
    /**
     * Value store in shm
     *
     * @var string
     */
    const VALUE_TO_STORE = "maintenance";

    /**
     * Variable key
     *
     * @var integer
     */
    const VARIABLE_KEY = 1;

    /**
     * The key store in shm
     *
     * @var string keyName
     */
    protected $keyName;


    /**
     * @var \SysvSharedMemory|null
     */
    protected $shmId = null;

    /**
     * Constructor shmDriver
     *
     * @param array $options Options driver
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);

        $key = ftok(__FILE__, 'm');
        $shm = shm_attach($key, 100, 0666);
        if ($shm === false) {
            throw new \RuntimeException('Can\'t allocate shared memory');
        }
        $this->shmId = $shm;
        $this->options = $options;
    }

    /**
     * Detach from shared memory
     */
    public function __destruct()
    {
        if ($this->shmId !== null) {
            shm_detach($this->shmId);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createLock()
    {
        if ($this->shmId === null) {
            return false;
        }

        return shm_put_var($this->shmId, self::VARIABLE_KEY, self::VALUE_TO_STORE);
    }

    /**
     * {@inheritdoc}
     */
    protected function createUnlock()
    {
        if ($this->shmId === null) {
            return false;
        }

        return shm_remove_var($this->shmId, self::VARIABLE_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function isExists()
    {
        if ($this->shmId === null) {
            return false;
        }

        if (!shm_has_var($this->shmId, self::VARIABLE_KEY)) {
            return false;
        }

        $data = shm_get_var($this->shmId, self::VARIABLE_KEY);
        return ($data == self::VALUE_TO_STORE);
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageLock($resultTest)
    {
        $key = $resultTest ? 'insys_maintenance.success_lock_shm' : 'insys_maintenance.not_success_lock';

        return $this->translator->trans($key, array(), 'maintenance');
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageUnlock($resultTest)
    {
        $key = $resultTest ? 'insys_maintenance.success_unlock' : 'insys_maintenance.not_success_unlock';

        return $this->translator->trans($key, array(), 'maintenance');
    }
}
