<?php

namespace Modules\Scenarios\Services\_Exception;

use Throwable;

class AppServiceException extends \Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
