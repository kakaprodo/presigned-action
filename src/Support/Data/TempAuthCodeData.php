<?php

namespace Kakaprodo\PresignedAction\Support\Data;

use Illuminate\Support\Str;
use Kakaprodo\CustomData\CustomData;
use Kakaprodo\PresignedAction\Exceptions\PresignedActionException;
use Kakaprodo\PresignedAction\Models\TempAuthCode;

/** Shared validation and lookup helpers for temporary authentication-code data. */
abstract class TempAuthCodeData extends CustomData
{
    /** Find a temporary authentication code by UUID. */
    protected function findByUuid(string $uuid): ?TempAuthCode
    {
        return TempAuthCode::query()->where('uuid', $uuid)->first();
    }

    /** Throw the standard missing-code error. */
    protected function throwCodeNotFound(): void
    {
        throw new PresignedActionException('The code was not found or  was used.', 404);
    }

    /** Throw the standard expired-code error. */
    protected function throwCodeExpired(): void
    {
        throw new PresignedActionException('The code has expired.', 410);
    }

    /** Generate a UUID that is not already stored in the configured table. */
    public function generateUniqueCode(): string
    {
        do {
            $uuid = (string) Str::uuid();
        } while (TempAuthCode::query()->where('uuid', $uuid)->exists());

        return $uuid;
    }
}
