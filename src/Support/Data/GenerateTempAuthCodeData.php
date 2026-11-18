<?php

namespace Kakaprodo\PresignedAction\Support\Data;

use Kakaprodo\PresignedAction\Exceptions\PresignedActionException;
use Kakaprodo\PresignedAction\Models\TempAuthCode;

/**
 * Validated input for generating a temporary authentication code.
 *
 * @property string $state_key Client-generated unique authentication state.
 * @property array<string, mixed> $metadata Optional metadata created with the authentication request.
 */
class GenerateTempAuthCodeData extends TempAuthCodeData
{
    /** @return array<string, mixed> */
    protected function expectedProperties(): array
    {
        return [
            'state_key' => $this->property()->string()->rules(['required', 'string', 'max:500']),
            'metadata?' => $this->property()->array([]),
        ];
    }

    /** Reject a state key that has already been used. */
    public function boot(): void
    {
        if (TempAuthCode::query()->where('state_key', $this->state_key)->exists()) {
            throw new PresignedActionException('The provided auth identifier state has already been used.', 409);
        }
    }
}
