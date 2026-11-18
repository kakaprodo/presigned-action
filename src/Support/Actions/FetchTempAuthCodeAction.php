<?php

namespace Kakaprodo\PresignedAction\Support\Actions;

use Kakaprodo\CustomData\Helpers\CustomActionBuilder;
use Kakaprodo\PresignedAction\Models\TempAuthCode;
use Kakaprodo\PresignedAction\Support\Data\FetchTempAuthCodeData;

/** Fetch pending or completed details and consume completed codes once. */
class FetchTempAuthCodeAction extends CustomActionBuilder
{
    /** @param FetchTempAuthCodeData $data @return array<string, mixed> */
    public function handle(FetchTempAuthCodeData $data): array
    {
        return (new TempAuthCode())->getConnection()->transaction(function () use ($data): array {
            $code = TempAuthCode::query()->where('state_key', $data->state_key)
                ->where('uuid', $data->uuid)->lockForUpdate()->first();

            if ($code->subscriber_type === null) {
                return ['status' => 'pending', 'expires_at' => $code->expires_at->timestamp];
            }

            $result = [
                'status' => 'completed',
                'subscriber_id' => $code->subscriber_id,
                'subscriber_type' => $code->subscriber_type,
                'metadata' => $code->metadata ?? [],
            ];

            $code->delete();

            return $result;
        });
    }
}
