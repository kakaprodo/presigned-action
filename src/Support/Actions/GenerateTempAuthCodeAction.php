<?php

namespace Kakaprodo\PresignedAction\Support\Actions;

use Kakaprodo\CustomData\Helpers\CustomActionBuilder;
use Kakaprodo\PresignedAction\Models\TempAuthCode;
use Kakaprodo\PresignedAction\Support\Data\GenerateTempAuthCodeData;

/** Generate a unique, short-lived authentication code. */
class GenerateTempAuthCodeAction extends CustomActionBuilder
{
    /** @param GenerateTempAuthCodeData $data */
    public function handle(GenerateTempAuthCodeData $data): TempAuthCode
    {
        return TempAuthCode::create([
            'uuid' => $data->generateUniqueCode(),
            'state_key' => $data->state_key,
            'metadata' => $data->metadata ?? [],
            'expires_at' => now()->addSeconds((int) config('presigned-action.temp_auth_code.expires_after', 20)),
        ]);
    }
}
