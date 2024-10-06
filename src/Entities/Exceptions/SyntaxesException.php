<?php

namespace Kerroline\PhpGoExcel\Entities\Exceptions;

use Exception;

class SyntaxesException extends Exception
{
    public function __construct(string $message) {
        parent::__construct($message, 0, null);
    }
}