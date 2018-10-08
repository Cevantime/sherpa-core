<?php

namespace Sherpa\Kernel\Middleware;

use Psr\Http\Server\MiddlewareInterface;

/**
 *
 * @author cevantime
 */
interface MiddlewareGroupInterface
{
    /**
     * 
     * @param MiddlewareInterface $callable
     * @param int $priority
     */
    public function addMiddleware($callable, int $priority = 10);
    
    /**
     * @return MiddlewareInterface[]
     */
    public function getMiddlewares(int $min = -2147483648, int $max = 2147483647);
    
}
