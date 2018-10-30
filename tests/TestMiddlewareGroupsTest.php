<?php

use Codeception\Test\Unit;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sherpa\Kernel\Kernel;
use Zend\Diactoros\Response\HtmlResponse;

class MiddleWareSampleA implements MiddlewareInterface
{
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return new HtmlResponse('Sample A');
    }

}

class MiddleWareSampleB implements MiddlewareInterface
{
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return new HtmlResponse('Sample B');
    }

}

class MiddleWareSampleC implements MiddlewareInterface
{
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return new HtmlResponse('Sample C');
    }

}

class TestMiddlewareGroupsTest extends Unit
{

    private $kernel;

    protected function _before()
    {
        $this->kernel = new Kernel();
    }

    protected function _after()
    {
        
    }

    public function testMiddlewareIspipeed()
    {
        $middleWareSampleA = new MiddleWareSampleA();
        
        $this->kernel->pipe($middleWareSampleA);
        
        $this->assertContains($middleWareSampleA, $this->kernel->getMiddlewares());
    }
    
    public function testMiddlewareIsAppended()
    {
        $middleWareSampleA = new MiddleWareSampleA();
        $middleWareSampleB = new MiddleWareSampleB();
        
        $this->kernel->pipe($middleWareSampleA);
        $this->kernel->pipe($middleWareSampleB);
        
        $middlewares = $this->kernel->getMiddlewares();
        
        $this->assertCount(2, $middlewares);
        $this->assertEquals($middleWareSampleA, $middlewares[0]);
        $this->assertEquals($middleWareSampleB, $middlewares[1]);
    }
    
    public function testMiddlewareIsAppendedToGroup()
    {
        $middleWareSampleA = new MiddleWareSampleA();
        $middleWareSampleB = new MiddleWareSampleB();
        
        $this->kernel->pipe($middleWareSampleA, 1);
        $this->kernel->pipe($middleWareSampleB, 2);
        
        $subset = $this->kernel->getMiddlewares(1,2);
        
        $this->assertCount(2, $subset);
        $this->assertArraySubset([$middleWareSampleB, $middleWareSampleA], $subset);
        $this->assertContains($middleWareSampleA, $this->kernel->getMiddlewares(1,1));
        $this->assertNotContains($middleWareSampleB, $this->kernel->getMiddlewares(-1,1));
        $this->assertContains($middleWareSampleB, $this->kernel->getMiddlewares(2,2));
        $this->assertNotContains($middleWareSampleA, $this->kernel->getMiddlewares(2));
    }
    
    public function testMiddlewareIsInserted()
    {
        $middleWareSampleA = new MiddleWareSampleA();
        $middleWareSampleB = new MiddleWareSampleB();
        $middleWareSampleC = new MiddleWareSampleC();
        
        $this->kernel->pipe($middleWareSampleA);
        $this->kernel->pipe($middleWareSampleC);
        $this->kernel->pipe($middleWareSampleB, 1, MiddleWareSampleC::class);
        
        $this->assertArraySubset([$middleWareSampleA, $middleWareSampleB, $middleWareSampleC], $this->kernel->getMiddlewares());
        
    }

}
