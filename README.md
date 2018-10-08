
# Sherpa Core

A basic core class to simply build psr compliant applications. The code is intentionnally minimal. For a more integrated experience, you can use [Sherpa Framework](https://github.com/Cevantime/sherpa-framework).

## Installation

Sherpa Core is installable via composer

```bash
composer require cevantime/sherpa @dev
```

## Usage

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
$app->add(function(){
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
