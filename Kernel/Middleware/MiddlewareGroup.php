<?php

namespace Sherpa\Kernel\Middleware;

use Sherpa\Kernel\Kernel;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Description of EventGroup
 *
 * @author cevantime
 */
class MiddlewareGroup implements MiddlewareGroupInterface
{

    /**
     *
     * @var calllable[]
     */
    protected $middlewareGroups = array();

    /**
     *
     * @var Kernel
     */
    protected $kernel;

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    public function addMiddleware(MiddlewareInterface $middleware, int $priority = 0)
    {
        $this->middlewareGroups[$priority][] = $middleware;
    }

    public function getMiddlewareGroups()
    {
        krsort($this->middlewareGroups);
        return $this->middlewareGroups;
    }

    public function getMiddlewares($max = 2147483647, $min = -2147483648)
    {
        $middlewares = [];
        foreach ($this->getMiddlewareGroups() as $key => $middlewareGroup) {
            if ($key >= $min && $key <= $max) {
                foreach ($middlewareGroup as $middleware) {
                    $middlewares[] = $middleware;
                }
            }
        }
        return $middlewares;
    }

}
