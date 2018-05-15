<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Sherpa\Exception;

/**
 * Description of NotAMiddleWareException
 *
 * @author cevantime
 */
class NotAMiddleWareException extends \Exception
{
    public function __construct($var, int $code = 0, \Throwable $previous = null)
    {
        parent::__construct(sprintf("trying to turn object %s of class %s into a middleware !", $var, get_class($var)),
            Exceptions::NO_RESPONSE_EXCPETION, 
            $previous);
    }
}
