<?php declare(strict_types=1);

namespace Sanjos\Exception;

class ErrorSocketException extends \Exception {

    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function show(string $message) : self
    {
        return new ErrorSocketException($message);
    }
}