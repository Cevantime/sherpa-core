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

    public function addMiddleware(MiddlewareInterface $middleware, int $priority = 0, string $before = null)
    {
        if ($before === null) {
            $this->middlewareGroups[$priority][] = $middleware;
            return;
        }

        $this->middlewareGroups[$priority] = $this->middlewareGroups[$priority] ?? [];

        foreach ($this->middlewareGroups[$priority] as $key => $m) {
            if (get_class($m) === $before) {
                $this->middlewareGroups[$priority][$key] = $middleware;
                $this->middlewareGroups[$priority][++$key] = $m;
                return;
            }
        }

        $this->middlewareGroups[$priority][] = $middleware;
    }

    public function getMiddlewareGroups()
    {
        krsort($this->middlewareGroups);
        return $this->middlewareGroups;
    }

    public function getMiddlewares(int $max = 2147483647, int $min = -2147483648)
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
