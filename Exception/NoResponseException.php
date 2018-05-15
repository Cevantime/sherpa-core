<?php

namespace Sherpa\Exception;
/**
 * Description of NoResponseException
 *
 * @author cevantime
 */
class NoResponseException extends \Exception
{
    public function __construct(\Throwable $previous = null)
    {
        parent::__construct("No response at the end of the middleware stack", Exceptions::NO_RESPONSE_EXCPETION, $previous);
    }
}
