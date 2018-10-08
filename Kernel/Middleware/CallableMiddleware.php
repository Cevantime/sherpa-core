<?php

namespace Sherpa\Kernel\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Description of CallableMiddleware
 *
 * @author cevantime
 */
final class CallableMiddleware implements MiddlewareInterface
{
    
    private $callable;
    
    public function __construct($callable, $container)
    {
        $this->callable = $callable;
        $this->container = $container;
    }
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $callable = $this->callable;
        return $callable($request, $handler, $this->container);
    }

}
