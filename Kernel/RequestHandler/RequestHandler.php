<?php

namespace Sherpa\Kernel\RequestHandler;

use Sherpa\Exception\NoResponseException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Description of RequestHandler
 *
 * @author cevantime
 */
class RequestHandler implements RequestHandlerInterface
{
    private $middlewares;
    
    public function __construct($middlewares)
    {
        $this->middlewares = $middlewares;
    }
    
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = current($this->middlewares);
        if( ! $middleware) {
            throw new NoResponseException();
        }
        next($this->middlewares);
        return $middleware->process($request, $this);
        
    }

}
