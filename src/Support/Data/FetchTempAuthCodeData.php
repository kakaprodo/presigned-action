<?php

namespace Kakaprodo\PresignedAction\Support\Data;

use Kakaprodo\PresignedAction\Exceptions\PresignedActionException;
use Kakaprodo\PresignedAction\Models\TempAuthCode;

/**
 * Validated identifiers used to retrieve a temporary authentication code.
 *
 * @property string $state_key Client-generated authentication state.
 * @property string $uuid Temporary authentication-code UUID.
 */
class FetchTempAuthCodeData extends TempAuthCodeData
{
    /** @return array<string, mixed> */
    protected function expectedProperties(): array
    {
        return [
            'state_key' => $this->property()->string()->rules(['required', 'string', 'max:500']),
            'uuid' => $this->property()->string()->rules(['required', 'string', 'max:255']),
        ];
    }

    /** Validate that the state and UUID identify an active code. */
    public function boot(): void
    {
        $code = TempAuthCode::query()
            ->where('state_key', $this->state_key)
            ->where('uuid', $this->uuid)
            ->first();

        if (! $code) {
            throw new PresignedActionException('The temporary authentication code was not found.', 404);
        }

        if ($code->isExpired()) {
            $code->delete();
            throw new PresignedActionException('The temporary authentication code has expired.', 410);
        }
    }
}
