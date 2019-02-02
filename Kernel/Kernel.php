<?php

namespace Sherpa\Kernel;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sherpa\Exception\NotAMiddlewareException;
use Sherpa\Kernel\Event\Events;
use Sherpa\Kernel\Middleware\CallableMiddleware;
use Sherpa\Kernel\Middleware\MiddlewareGroup;
use Sherpa\Kernel\Request\RequestStack;
use Sherpa\Kernel\RequestHandler\RequestHandler;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\ServerRequestFactory;

/**
 * Description of HttpKernel
 *
 * @author cevantime
 */
class Kernel implements RequestHandlerInterface, ContainerInterface
{

    /**
     *
     * @var MiddlewareGroup
     */
    protected $middlewareGroup;

    /**
     *
     * @var EventDispatcherInterface
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

    /**
     *
     * @var ContainerBuilder
     */
    protected $containerBuilder;

    /**
     * @var RequestStack
     */
    protected $requestStack;
    /**
     * @var ContainerInterface
     */
    protected $container;
    protected $storage;
    protected $booted = false;
    protected $currentHandler;

    protected $middlewareClasses;

    public function __construct()
    {
        $this->middlewareGroup = new MiddlewareGroup();
        $this->containerBuilder = new ContainerBuilder();
        $this->storage = [
            'container.builder' => $this->containerBuilder,
        ];
        $this->delayed = [];
        $this->dispatcher = new EventDispatcher();
        $this->requestStack = new RequestStack();
        $this->set(EventDispatcherInterface::class, $this->dispatcher);
        $this->set(RequestStack::class, $this->requestStack);
    }

    /**
     * add a middleware, with optionnal priority.
     * If a class is given as third argument, the middleware will be added <i>after</i>
     * the middleware that has this class.
     * @param mixed $middleware
     * @param int $priority
     * @param string $after
     * @param string $before
     */
    public function pipe($middleware, ?int $priority = 1, ?string $before = null)
    {
        if(is_object($middleware)) {
            $class = get_class($middleware);
        } else if(is_string($middleware) && class_exists($middleware)) {
            $class = $middleware;
        }
        if(isset($class) && ! isset($this->middlewareClasses[$class])) {
            $this->middlewareClasses[$class] = $middleware;
        }

        $this->getMiddlewareGroup()->addMiddleware($middleware, $priority, $before);
    }

    public function createMiddlewareGroup($max = 2147483647, $min = -2147483648)
    {
        return new MiddlewareGroup($this->getMiddlewareGroup()->getMiddlewareGroups($max, $min));
    }

    public function on($eventName, $listener)
    {
        $this->addListener($eventName, $listener);
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
            $this->setOriginalRequest($request);
            $this->boot();
        }
        return $this->run($request);
    }

    public function setOriginalRequest(ServerRequestInterface $request)
    {
        $this->originalRequest = $request;
        $this->containerBuilder->addDefinitions([
            'original_request' => $request
        ]);
    }

    public function run($request, $max = 2147483647, $min = -2147483648)
    {
        $middlewares = $this->createMiddlewareGroup($max, $min);
        $handler = new RequestHandler($middlewares, $this->getContainer());
        $this->set('current_handler', $handler);
        $this->requestStack->push($request);
        $response = $handler->handle($request);
        $this->requestStack->pop();
        return $response;
    }

    public function boot()
    {
        if ($this->originalRequest === null) {
            $this->setOriginalRequest(ServerRequestFactory::fromGlobals());
        }
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
        $this->dispatch(Events::TERMINATE);
    }

    protected function getMiddlewareGroup()
    {
        return $this->middlewareGroup;
    }

    public function getMiddlewares(int $min = -2147483647, int $max = 2147483647)
    {
        return $this->getMiddlewareGroup()->getMiddlewareGroups($max, $min);
    }

    public function delayed($callable)
    {
        $this->delayed[] = $callable;
    }

    function getOriginalRequest(): ServerRequest
    {
        return $this->originalRequest;
    }

    /**
     *
     * @return ContainerInterface
     */
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
