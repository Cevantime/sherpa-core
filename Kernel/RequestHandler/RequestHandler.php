<?php

namespace Sherpa\Kernel\RequestHandler;

use Psr\Http\Server\MiddlewareInterface;
use Sherpa\Exception\NoResponseException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sherpa\Exception\NotAMiddlewareException;
use Sherpa\Kernel\Middleware\CallableMiddleware;
use Sherpa\Kernel\Middleware\MiddlewareGroup;

/**
 * Description of RequestHandler
 *
 * @author cevantime
 */
class RequestHandler implements RequestHandlerInterface
{
    /**
     * @var MiddlewareGroup
     */
    private $middlewareGroup;
    private $container;
    
    public function __construct($middlewares, $container)
    {
        $this->middlewareGroup = $middlewares;
        $this->container = $container;
    }
    
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = $this->middlewareGroup->currentMiddleware();
        if( ! $middleware) {
            throw new NoResponseException();
        }
        $middleware = $this->toMiddleWare($middleware);
        $this->middlewareGroup->nextMiddleware();
        return $middleware->process($request, $this);
    }

    public function toMiddleWare($callable)
    {
        if (is_callable($callable)) {
            return new CallableMiddleware($callable, $this);
        }

        if (is_string($callable) && class_exists($callable)) {
            return $this->container->get($callable);
        }

        if (!($callable instanceof MiddlewareInterface)) {
            throw new NotAMiddlewareException($callable);
        }

        return $callable;
    }

    /**
     * @return MiddlewareGroup
     */
    public function getMiddlewareGroup()
    {
        return $this->middlewareGroup;
    }
}
