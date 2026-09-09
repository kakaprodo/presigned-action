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
            ->when($data->origin === null,
                fn($query) => $query->whereNull('origin'),
                fn($query) => $query->where('origin', $data->origin)
            )
            ->first();

        if ($data->accessKeyIsGeneratedWithSameValues($accessKey)) {
            if ($accessKey->expires_at?->isFuture()) {
                return $accessKey;
            }

            $uuid = ((string) Str::uuid()) . '' . $accessibleId;

            $accessKey->update([
                'uuid' => $uuid,
                'expires_at' => $data->expires_at ?? now()->addMinutes($expireAfterMinutes),
                'settings' => $data->settings,
                'scopes' => $data->scopes,
                'permissions' => $data->permissions,
                'origin' => $data->origin,
            ]);

            return $accessKey->refresh();
        }

        $uuid = ((string) Str::uuid()) . '' . $accessibleId;

        return TemporaryAccessKey::create([
            'uuid' => $uuid,
            'whoami' => $data->whoami,
            'expires_at' => $data->expires_at ?? now()->addMinutes($expireAfterMinutes),
            'accessible_id' => $accessibleId,
            'accessible_type' => $accessibleClass,
            'settings' => $data->settings,
            'scopes' => $data->scopes,
            'permissions' => $data->permissions,
            'origin' => $data->origin,
        ]);
    }
}
