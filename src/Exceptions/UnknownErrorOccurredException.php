<?php

namespace Bkhim\LaravelTurnstile\Exceptions;

use Exception;

class UnknownErrorOccurredException extends Exception
{
    protected int $status = 500;
}
