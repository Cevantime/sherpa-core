
# Sherpa Core

A basic core class to simply build psr compliant applications. The code is intentionnally minimal. For a more integrated experience, you can use [Sherpa Framework](https://github.com/Cevantime/sherpa-framework).

## Installation

Sherpa Core is installable via composer

```bash
composer require cevantime/sherpa @dev
```

## Getting Started

Sherpa use psr-7 for HTTP messages. You can use Zend Diactoros implementation to build your Request in your index file. 

```php
// index.php
use Zend\Diactoros\ServerRequestFactory;
 
$request = ServerRequestFactory::fromGlobals();
```
Now, you can init the Sherpa Kernel 

```php
use Sherpa\Kernel\Kernel;
// ...
$app = new Kernel();
```

Now, you can add a little middleware to display a simple 'Hello Sherpa' :

```php
use Zend\Diactoros\Response\HtmlResponse;
// ...
$app->pipe(function(){
    return new HtmlResponse("Hello Sherpa !");
});
```

The Kernel will handle the response for the request 

```php
$response = $app->handle($request);
```


When the response is ready, it needs to be emitted !  You can use a SapiEmitter for example

```php
use Zend\Diactoros\Response\SapiEmitter;
// ...
(new SapiEmitter)->emit($response);
```
Now visit your index file. You should see "Hello Sherpa !"

## Middlewares

Sherpa Core do his best to be compliant with psr7 and psr-15 recommandations. Those recommendations introduce the usage of **Message Interface** and **Middlewares**.
Piping middlewares in the Kernel is pretty simple : 
```php
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

$kernel->pipe(function(ServerRequestInterface $request, RequestHandlerInterface $handler) {
	// ...
});
```
Accordingly to the specifications, middlewares must return an instance of `Psr\Http\Message\ResponseInterface`. It can do so by :
 - Delegating the Response creation to the next middleware by calling `$handler->handle($request)` and eventually modify the response.
 - Generate his own Response and bypass the next Middlewares.

Here is a (imaginary) example :

```php
$kernel->pipe(function(ServerRequestInterface $request, RequestHandlerInterface $handler) {
	// check if user tries to access an admin page and check if he is admin
	if(preg_match($request->getUri()->getPath(), '~^/admin~') 
		&& ! $request->getAttribute('user')->isAdmin()) {
		// if not, send an error
		return (new Response('php://memory'))
			->withStatus(403, 'You are not allowed to stay here !';
	}
	// otherwise, let the process continue and the next middleware proceed
	return $handler->handle($request);
});
```

## Container and Autowiring

Sherpa Core use [PHP DI Container](http://php-di.org/) as  a Dependency Container _and_ Dependency Injector. This package lets you inject _any_ class of your project (even your _vendor_) with minimal configuration. **Every type hinted parameter can be injected**. You only need to configure parameters than can't be guessed by type hinting. Classes that are defined using class/interface names as keys will become injectable : 
```php
use function DI\get;  
use function DI\create;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Psr\Container\ContainerInterface;
//... 
$builder = $kernel->getContainerBuilder();
$builder->addDefinitions([
        // create definition directly...
	'log.folder' => 'var/log',
	// ... or using a callback function...
	StreamHandler::class => function(ContainerInterface $container) {
		return new StreamHandler($container->get('log.folder');
	},
	// ... or using configuration methods that come with php di
	LoggerInterface::class => create(Logger::class)
		 ->constructor('mylogger')
		 ->method('pushHandler', get(StreamHandler::class))
]);
```

Now a logger is injectable anywhere, including a class middleware : 

```php
use App\UserRepository;  
use Psr\Log\LoggerInterface;  
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ResponseInterface as Response;
  
class UserMiddleware implements MiddlewareInterface
{
	protected $userRepository;
	protected $logger;
	
	/**
	 * @param UserRepository $userRepository is auto-injected !
	 * @param LoggerInterface $logger hase been configure to inject an instance of Logger
	 */
	public function __construct(UserRepository $userRepository, LoggerInterface $logger)
	{
		$this->userRepository = $userRepository;
		$this->logger = $logger;
	}
	public function process(Request $request, Handler $handler): Response 
	{
		$user = $this->userRepository->findBy(['id' => $_SESSION['user_id'] ?? 0]);
		if($user) {
			$request->setAttribute('user', $user);
		} else {
			$this->logger->log('info', 'No user found in session');
		}
		return $handler->handle($request);
	}
}
```

The Kernel will use di container to inject the middleware in the middleware stack. Thus you can pipe middlewares by only providing their class names.
```php
$kernel->pipe(UserMiddleware::class);
```
