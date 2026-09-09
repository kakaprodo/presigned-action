<?php

namespace Kakaprodo\PresignedAction;

use Kakaprodo\PresignedAction\Models\TemporaryAccessKey;
use Kakaprodo\PresignedAction\Support\Actions\GenerateTemporaryAccessKeyAction;

class PresignedActionGate
{

    /**
     * Generate a temporary access key
     * 
     * @param array{
     *   accessible: \Illuminate\Database\Eloquent\Model,
     *   whoami: string,
     *   expires_at: null|\Illuminate\Support\Carbon,
     *   settings: array|null,
     *   scopes: array|null,
     *   permissions: array|null,
     *   origin: string|null,
     * } $options
     * @return TemporaryAccessKey
     */
    public function generateAccessKey(array $options): TemporaryAccessKey
    {
        return GenerateTemporaryAccessKeyAction::process($options);
    }

    /**
     * Get the temporary access key model from the one loaded on the
     * request after middleware check has passed.
     */
    public function temporaryAccessKey(): ?TemporaryAccessKey
    {
        return request()?->temporaryAccessKey();
    }
}
