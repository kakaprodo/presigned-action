<?php

namespace Kakaprodo\PresignedAction\Exceptions;

use Exception;

class PresignedActionException extends Exception
{
    protected int $status;

    public function __construct(string $message, $status = 403)
    {
        parent::__construct($message, $status);

        $this->status = $status;
    }

    public function render()
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], $this->status);
    }
}
