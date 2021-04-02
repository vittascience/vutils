<?php

namespace Utils\Backend;

class Errors implements \JsonSerializable
{
    private $errorMessage;
    private $timestamp;

    public static function createError($errorMessage = "error")
    {
        return new Errors($errorMessage, new \DateTime());
    }

    private function __construct($errorMessage, $timestamp)
    {
        $this->errorMessage = $errorMessage;
        $this->timestamp = $timestamp;
    }

    public function jsonSerialize()
    {
        return [
            'error_message' => $this->errorMessage,
            'time' => $this->timestamp
        ];
    }
}
