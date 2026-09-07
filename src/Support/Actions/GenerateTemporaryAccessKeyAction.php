<?php

namespace Kakaprodo\PresignedAction\Support\Actions;

use Illuminate\Support\Str;
use Kakaprodo\CustomData\Helpers\CustomActionBuilder;
use Kakaprodo\PresignedAction\Models\TemporaryAccessKey;
use Kakaprodo\PresignedAction\Support\Data\GenerateTemporaryAccessKeyData;

/**
 * Generate or reuse a temporary access key for an accessible model.
 */
class GenerateTemporaryAccessKeyAction extends CustomActionBuilder
{
    public function handle(GenerateTemporaryAccessKeyData $data): TemporaryAccessKey
    {
        $accessibleId = $data->accessible->getKey();
        $accessibleClass = get_class($data->accessible);
        $expireAfterMinutes = config('presigned-action.key_expires_after', 3600);

        $accessKey = TemporaryAccessKey::query()
            ->where('accessible_id', $accessibleId)
            ->where('accessible_type', $accessibleClass)
            ->where('whoami', $data->whoami)
            ->first();

        if (
            $accessKey
            && $accessKey?->expires_at?->isFuture()
            && $accessKey?->expires_at?->isAfter(now()->addMinutes($expireAfterMinutes / 4))
        ) {
            return $accessKey;
        }

        $uuid = ((string) Str::uuid()) . '' . $accessibleId;

        // When access key is expired , update its uuid, then extends its validity period
        if ($accessKey) {
            $accessKey->update([
                'uuid' => $uuid,
                'expires_at' => $data->expires_at,
                'scopes' => [
                    ...($accessKey->scopes ?? []),
                    ...($data->scopes ?? [])
                ],
            ]);

            return $accessKey->refresh();
        }

        return TemporaryAccessKey::create([
            'uuid' => $uuid,
            'whoami' => $data->whoami,
            'expires_at' => $data->expires_at ?? now()->addMinutes($expireAfterMinutes),
            'accessible_id' => $accessibleId,
            'accessible_type' => $accessibleClass,
            'settings' => $data->settings,
            'scopes' => $data->scopes
        ]);
    }
}
