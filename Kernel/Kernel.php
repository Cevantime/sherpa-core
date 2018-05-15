<?php

namespace Sherpa\Kernel;

use DI\ContainerBuilder;
use Sherpa\Exception\NotAMiddleWareException;
use Sherpa\Kernel\Event\Events;
use Sherpa\Kernel\Middleware\CallableMiddleware;
use Sherpa\Kernel\Middleware\MiddlewareGroup;
use Sherpa\Kernel\RequestHandler\RequestHandler;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Zend\Diactoros\ServerRequest;

/**
 * Description of HttpKernel
 *
 * @author cevantime
 */
class Kernel implements RequestHandlerInterface, ContainerInterface
{

    /**
     *
     * @var MiddlewareGroup[]
     */
    protected $middlewareGroup;

    /**
     *
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     *
     * @var callable[]
     */
    protected $delayed;
    
    /**
     *
     * @var ServerRequest
     */
    protected $originalRequest;
    
    protected $containerBuilder;
    protected $container;
    protected $storage;
    
    protected $booted = false;

    public function __construct()
    {
        $this->dispatcher = new EventDispatcher();
        $this->middlewareGroup = new MiddlewareGroup($this);
        $this->containerBuilder = new ContainerBuilder();
        $this->storage = [
            'container.builder' => $this->containerBuilder,
        ];
    }

    public function add($callable = null, $priority = 0)
    {
        $middleware = $this->toMiddleWare($callable);

        $this->getMiddlewareGroup()->addMiddleware($middleware, $priority);
    }

    public function createMiddlewareChain($max = 2147483647, $min = -2147483648)
    {
        return $this->getMiddlewareGroup()->getMiddlewares($max, $min);
    }

    public function toMiddleWare($callable)
    {
        if (is_callable($callable)) {
            $callable = new CallableMiddleware($callable, $this);
        } else if (!($callable instanceof MiddlewareInterface)) {
            throw new NotAMiddleWareException($callable);
        }
        return $callable;
    }

    public function on($eventName, $listener)
    {
        $this->addListener($eventName, $listener);
    }

    public function onFinish($listener)
    {
        $this->on(Events::FINISH_REQUEST, $listener);
    }

    public function onTerminate($listener)
    {
        $this->on(Events::TERMINATE, $listener);
    }

    public function dispatch($eventName)
    {
        $this->dispatcher->dispatch($eventName);
    }

    public function addListener($eventName, $listener)
    {
        $this->dispatcher->addListener($eventName, $listener);
    }

    public function addSubscriber($subscriber)
    {
        $this->dispatcher->addSubscriber($subscriber);
    }

    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->booted) {
            $this->originalRequest = $request;
            $this->boot();
        }
        return $this->run($request);
    }

    public function run($request, $max = 2147483647, $min = -2147483648)
    {
        $middlewares = $this->createMiddlewareChain($max, $min);
        return (new RequestHandler($middlewares))->handle($request);
    }

    protected function boot()
    {
        $this->container = $this->getContainerBuilder()->build();
        $this->storage = null;
        foreach ($this->delayed as $delayed) {
            $delayed($this);
        }
        $this->booted = true;
    }

    public function reboot()
    {
        $this->boot();
    }

    public function terminate()
    {
        // TODO : do it properly ! ie in the right callback in order for it to
        // run after the response is sent
        $this->dispatcher->dispatch(Events::FINISH_REQUEST);
        $this->dispatcher->dispatch(Events::TERMINATE);
    }

    protected function getMiddlewareGroup()
    {
        return $this->middlewareGroup;
    }

    public function delayed($callable)
    {
        $this->delayed[] = $callable;
    }
    
    function getOriginalRequest(): ServerRequest
    {
        return $this->originalRequest;
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function getContainerBuilder()
    {
        return $this->containerBuilder;
    }
    
    public function set($id, $value)
    {
        if ($this->container) {
            $this->container->set($id, $value);
        } else {
            $this->storage[$id] = $value;
            $this->containerBuilder->addDefinitions([
                $id => $value
            ]);
        }
    }

    public function get($id)
    {
        if (!$this->container) {
            return $this->storage[$id];
        }
        return $this->container->get($id);
    }

    public function has($id): bool
    {
        if (!$this->container) {
            return isset($this->storage[$id]);
        }
        return $this->container->has($id);
    }

}
