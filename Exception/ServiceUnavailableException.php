<?php

namespace INSYS\Bundle\MaintenanceBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * The server is currently unavailable (because it is overloaded or down for maintenance)
 *
 * @package INSYSMaintenanceBundle
 * @author  Gilles Gauthier <g.gauthier@lexik.fr>
 */
class ServiceUnavailableException extends HttpException
{
    /**
     * Constructor.
     *
     * @param string $message The internal exception message
     * @param \Exception|null $previous The previous exception
     * @param integer $code The internal exception code
     */
    public function __construct(string $message = '', ?\Throwable $previous = null, int $code = 0)
    {
        parent::__construct(503, $message, $previous, array(), $code);
    }
}
