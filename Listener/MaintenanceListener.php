<?php

namespace INSYS\Bundle\MaintenanceBundle\Listener;

use INSYS\Bundle\MaintenanceBundle\Drivers\DriverFactory;
use INSYS\Bundle\MaintenanceBundle\Exception\ServiceUnavailableException;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;

/**
 * Listener to decide if user can access to the site
 *
 * @package INSYSMaintenanceBundle
 * @author  Gilles Gauthier <g.gauthier@lexik.fr>
 */
class MaintenanceListener
{
    /**
     * Service driver factory
     *
     * @var \INSYS\Bundle\MaintenanceBundle\Drivers\DriverFactory
     */
    protected $driverFactory;

    /**
     * @var null|String
     */
    protected $path;

    /**
     * @var null|String
     */
    protected $host;

    /**
     * @var array|null
     */
    protected $ips;

    /**
     * @var array
     */
    protected $query;

    /**
     * @var array
     */
    protected $cookie;

    /**
     * @var null|String
     */
    protected $route;

    /**
     * @var array
     */
    protected $attributes;

    /**
     * @var Int|null
     */
    protected $http_code;

    /**
     * @var null|String
     */
    protected $http_status;

    /**
     * @var String
     */
    protected $http_exception_message;

    /**
     * @var bool
     */
    protected $handleResponse = false;

    /**
     * @var bool
     */
    protected $debug;

    /**
     * Constructor Listener
     *
     * Accepts a driver factory, and several arguments to be compared against the
     * incoming request.
     * When the maintenance mode is enabled, the request will be allowed to bypass
     * it if at least one of the provided arguments is not empty and matches the
     *  incoming request.
     *
     * @param DriverFactory $driverFactory The driver factory
     * @param String $path A regex for the path
     * @param String $host A regex for the host
     * @param array $ips The list of IP addresses
     * @param array $query Query arguments
     * @param array $cookie Cookies
     * @param String $route Route name
     * @param array $attributes Attributes
     * @param Int $http_code http status code for response
     * @param String $http_status http status message for response
     * @param String $http_exception_message http response page exception message
     * @param bool $debug
     */
    public function __construct(
        DriverFactory $driverFactory,
        $path = null,
        $host = null,
        $ips = null,
        $query = array(),
        $cookie = array(),
        $route = null,
        $attributes = array(),
        $http_code = null,
        $http_status = null,
        $http_exception_message = '',
        $debug = false
    ) {
        $this->driverFactory = $driverFactory;
        $this->path = $path;
        $this->host = $host;
        $this->ips = $ips;
        $this->query = (array) $query;
        $this->cookie = (array) $cookie;
        $this->route = $route;
        $this->attributes = (array) $attributes;
        $this->http_code = $http_code;
        $this->http_status = $http_status;
        $this->http_exception_message = $http_exception_message;
        $this->debug = $debug;
    }

    /**
     * @param RequestEvent $event RequestEvent
     *
     * @return void
     *
     * @throws ServiceUnavailableException
     */
    public function onKernelRequest(RequestEvent $event)
    {
        if(!$event->isMainRequest()){
            return;
        }

        $request = $event->getRequest();

        foreach ($this->query as $key => $pattern) {
            if (!empty($pattern) && preg_match('{'.$pattern.'}', (string) $this->getRequestValue($request, $key))) {
                return;
            }
        }

        foreach ($this->cookie as $key => $pattern) {
            if (!empty($pattern) && preg_match('{'.$pattern.'}', (string) $request->cookies->get($key))) {
                return;
            }
        }

        foreach ($this->attributes as $key => $pattern) {
            if (!empty($pattern) && preg_match('{'.$pattern.'}', (string) $request->attributes->get($key))) {
                return;
            }
        }

        if (null !== $this->path && !empty($this->path) && preg_match('{'.$this->path.'}', rawurldecode($request->getPathInfo()))) {
            return;
        }

        if (null !== $this->host && !empty($this->host) && preg_match('{'.$this->host.'}i', $request->getHost())) {
            return;
        }

        if (count((array) $this->ips) !== 0 && $this->checkIps($request->getClientIp(), $this->ips)) {
            return;
        }

        $route = $request->attributes->get('_route');
        if ((null !== $this->route && null !== $route && preg_match('{'.$this->route.'}', $route)) || (true === $this->debug && null !== $route && '_' === $route[0])) {
            return;
        }

        // Get driver class defined in your configuration
        $driver = $this->driverFactory->getDriver();

        if ($driver->decide()) {
            $this->handleResponse = true;
            throw new ServiceUnavailableException($this->http_exception_message);
        }
    }

    /**
     * Rewrites the http code of the response
     *
     * @param ResponseEvent $event ResponseEvent
     * @return void
     */
    public function onKernelResponse(ResponseEvent $event)
    {
        if ($this->handleResponse && $this->http_code !== null) {
            $response = $event->getResponse();
            $response->setStatusCode($this->http_code, $this->http_status);
        }
    }

    /**
     * Checks if the requested ip is valid.
     *
     * @param string       $requestedIp
     * @param string|array $ips
     * @return boolean
     */
    protected function checkIps($requestedIp, $ips)
    {
        $ips = (array) $ips;

        $valid = false;
        $i = 0;

        while ($i<count($ips) && !$valid) {
            $valid = IpUtils::checkIp($requestedIp, $ips[$i]);
            $i++;
        }

        return $valid;
    }

    private function getRequestValue(Request $request, string $key)
    {
        if ($request->attributes->has($key)) {
            return $request->attributes->get($key);
        }

        if ($request->query->has($key)) {
            return $request->query->get($key);
        }

        if ($request->request->has($key)) {
            return $request->request->get($key);
        }

        return null;
    }
}
