<?php

namespace App\Exception;

class NotAbleToReadFileException extends \Exception
{
    public function __construct(string $file = '', int $code = 0, ?\Throwable $previous = null)
    {
        $message = 'Not able to read file "'.$file.'"';
        parent::__construct($message, $code, $previous);
    }
}
