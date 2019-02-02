<?php

namespace Sherpa\Kernel\Middleware;

use Sherpa\Kernel\Kernel;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Description of EventGroup
 *
 * @author cevantime
 */
class MiddlewareGroup
{

    /**
     *
     * @var calllable[]
     */
    protected $middlewareSubGroups;
    protected $currentGroup;

    public function __construct($middlewareSubGroups = [])
    {
        $this->middlewareSubGroups = $middlewareSubGroups;
        $this->currentGroup = current($middlewareSubGroups);
    }

    public function addMiddleware($middleware, ?int $priority = 1, ?string $before = null)
    {
        if ($before === null) {
            $this->middlewareSubGroups[$priority][] = $middleware;
            $this->currentGroup = current($this->middlewareSubGroups);
            return;
        }

        $this->middlewareSubGroups[$priority] = $this->middlewareSubGroups[$priority] ?? [];

        foreach ($this->middlewareSubGroups[$priority] as $key => $m) {
            $classname = is_string($m) ? $m : get_class($m);
            if ($classname === $before) {
                $this->middlewareSubGroups[$priority][$key] = $middleware;
                $this->middlewareSubGroups[$priority][++$key] = $m;
                $this->currentGroup = current($this->middlewareSubGroups);
                return;
            }
        }

        $this->middlewareSubGroups[$priority][] = $middleware;
        $this->currentGroup = current($this->middlewareSubGroups);
    }

    public function getMiddlewareSubGroups()
    {
        krsort($this->middlewareSubGroups);
        return $this->middlewareSubGroups;
    }

    public function getMiddlewareGroups(int $max = 2147483647, int $min = -2147483648)
    {
        $middlewareGroups = [];
        foreach ($this->getMiddlewareSubGroups() as $key => $middlewareGroup) {
            if ($key >= $min && $key <= $max) {
                $middlewareGroups[$key] = $middlewareGroup;
            }
        }
        return $middlewareGroups;
    }

    public function currentMiddleware()
    {
        if($this->currentGroup) {
            return current($this->currentGroup);
        }
        return false;
    }

    public function nextMiddleware()
    {
        if( ! next($this->currentGroup)) {
            $this->currentGroup = next($this->middlewareSubGroups);

        }
        return $this->currentMiddleware();
    }

}
