<?php

namespace Kakaprodo\PresignedAction;

use Kakaprodo\PresignedAction\Models\TemporaryAccessKey;
use Kakaprodo\PresignedAction\Support\Actions\GenerateTemporaryAccessKeyAction;

class PresignedAction
{

    /**
     * Generate a temporary access key
     * 
     * @property array{
     *   accessible: \Illuminate\Database\Eloquent\Model,
     *   whoami: string,
     *   expires_at: null|\Illuminate\Support\Carbon,
     *   settings: array|null
     * } $options
     * 
     */
    public function generateAccessKey(array $options): TemporaryAccessKey
    {
        return GenerateTemporaryAccessKeyAction::process($options);
    }
}
