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
        $accessibleId = $data->accessible?->getKey();
        $accessibleClass = $data->accessible ? get_class($data->accessible) : null;
        $isIndependent = $data->accessible === null;
        $expireAfterMinutes = config('presigned-action.key_expires_after', 30);

        $accessKey = TemporaryAccessKey::query()
            ->where('is_independent', $isIndependent)
            ->when(
                $isIndependent,
                fn($query) => $query->whereNull('accessible_id')->whereNull('accessible_type'),
                fn($query) => $query->where('accessible_id', $accessibleId)->where('accessible_type', $accessibleClass)
            )
            ->where('expires_at', '>', now())
            ->where('reference_text', $data->dataKey())
            ->first();

        if ($accessKey) {
            if ($accessKey->expires_at?->isFuture()) {
                return $accessKey;
            }

            $uuid = (string) Str::uuid() . (string) ($accessibleId ?? '');

            $accessKey->update([
                'uuid' => $uuid, // we always change the uuid because is part of the dynamic key to return in response of access key and we want it to always change
                'expires_at' => $data->expires_at ?? now()->addMinutes($expireAfterMinutes)
            ]);

            return $accessKey->refresh();
        }

        $uuid = (string) Str::uuid() . (string) ($accessibleId ?? '');

        return TemporaryAccessKey::create([
            'uuid' => $uuid,
            'reference_text' => $data->dataKey(),
            'whoami' => $data->whoami,
            'expires_at' => $data->expires_at ?? now()->addMinutes($expireAfterMinutes),
            'accessible_id' => $accessibleId,
            'accessible_type' => $accessibleClass,
            'is_independent' => $isIndependent,
            'settings' => $data->settings,
            'scopes' => $data->scopes,
            'permissions' => $data->permissions,
            'origin' => $data->origin,
        ]);
    }
}
