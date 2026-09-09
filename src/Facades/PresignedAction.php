<?php

namespace Kakaprodo\PresignedAction\Facades;

use Illuminate\Support\Facades\Facade;
use Kakaprodo\PresignedAction\Models\TemporaryAccessKey;
use Kakaprodo\PresignedAction\PresignedActionGate;

/**
 * @method static TemporaryAccessKey generateAccessKey(array{
 *     accessible: \Illuminate\Database\Eloquent\Model,
 *     whoami: string,
 *     expires_at: null|\Illuminate\Support\Carbon,
 *     settings: array|null,
 *     scopes: array|null,
 *     permissions: array|null,
 *     origin: string|null,
 * } $options)
 * @method static ?TemporaryAccessKey temporaryAccessKey()
 */
class PresignedAction extends Facade
{

    protected static function getFacadeAccessor()
    {
        return PresignedActionGate::class;
    }
}
