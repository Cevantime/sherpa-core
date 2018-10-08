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
    protected $middlewareSubGroups = array();

    public function addMiddleware($middleware, int $priority = 0, string $before = null)
    {
        if ($before === null) {
            $this->middlewareSubGroups[$priority][] = $middleware;
            return;
        }

        $this->middlewareSubGroups[$priority] = $this->middlewareSubGroups[$priority] ?? [];

        foreach ($this->middlewareSubGroups[$priority] as $key => $m) {
            if (get_class($m) === $before) {
                $this->middlewareSubGroups[$priority][$key] = $middleware;
                $this->middlewareSubGroups[$priority][++$key] = $m;
                return;
            }
        }

        $this->middlewareSubGroups[$priority][] = $middleware;
    }

    public function getMiddlewareSubGroups()
    {
        krsort($this->middlewareSubGroups);
        return $this->middlewareSubGroups;
    }

    public function getMiddlewares(int $max = 2147483647, int $min = -2147483648)
    {
        $middlewares = [];
        foreach ($this->getMiddlewareSubGroups() as $key => $middlewareGroup) {
            if ($key >= $min && $key <= $max) {
                foreach ($middlewareGroup as $middleware) {
                    $middlewares[] = $middleware;
                }
            }
        }
        return $middlewares;
    }

}
