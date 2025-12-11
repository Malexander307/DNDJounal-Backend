<?php

namespace App\Exceptions;

use Exception;

class ApiException extends Exception
{
    public function __construct(
        string $message,
        public int $status = 400,
        public ?string $codeStr = null,
        public array $details = []
    ) {
        parent::__construct($message, $status);
    }
}
