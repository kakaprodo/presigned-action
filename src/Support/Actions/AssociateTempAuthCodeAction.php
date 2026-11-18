<?php

namespace Kakaprodo\PresignedAction\Support\Actions;

use Kakaprodo\CustomData\Helpers\CustomActionBuilder;
use Kakaprodo\PresignedAction\Models\TempAuthCode;
use Kakaprodo\PresignedAction\Support\Data\AssociateTempAuthCodeData;

/** Associate subscriber information with a scanned authentication code. */
class AssociateTempAuthCodeAction extends CustomActionBuilder
{
    /** @param AssociateTempAuthCodeData $data */
    public function handle(AssociateTempAuthCodeData $data): TempAuthCode
    {
        $code = TempAuthCode::query()->where('uuid', $data->uuid)->first();

        $code->update([
            'subscriber_id' => $data->subscriber_id,
            'subscriber_type' => $data->subscriber_type,
            'metadata' => $data->metadata ?? [],
        ]);

        return $code->refresh();
    }
}
