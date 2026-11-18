<?php

namespace Kakaprodo\PresignedAction\Facades;

use Illuminate\Support\Facades\Facade;
use Kakaprodo\PresignedAction\Models\TempAuthCode;
use Kakaprodo\PresignedAction\Models\TemporaryAccessKey;
use Kakaprodo\PresignedAction\Plugins\ActionValidator;
use Kakaprodo\PresignedAction\PresignedActionGate;

/**
 * @method static TemporaryAccessKey generateAccessKey(array{
 *     accessible?: \Illuminate\Database\Eloquent\Model|null,
 *     whoami: string,
 *     expires_at?: null|\Illuminate\Support\Carbon,
 *     settings?: array<string, mixed>|null,
 *     scopes?: array<int, string>|null,
 *     permissions?: array<int, string>|null,
 *     origin?: string|null,
 *     is_independent?: bool,
 * } $options) Generate or reuse a temporary access key.
 * @method static ?TemporaryAccessKey temporaryAccessKey() Return the access key loaded by request middleware.
 * @method static TempAuthCode generateTempAuthCode(array{state_key: string, metadata?: array<string, mixed>} $options) Generate a short-lived temporary authentication code.
 * @method static TempAuthCode associateTempAuthCode(array{
 *     uuid: string,
 *     subscriber_id?: string|null,
 *     subscriber_type: string,
 *     metadata?: array<string, mixed>,
 * } $options) Associate subscriber information with a scanned authentication code.
 * @method static array{
 *     status: string,
 *     expires_at?: int,
 *     subscriber_id?: string|null,
 *     subscriber_type?: string,
 *     metadata?: array<string, mixed>,
 * } fetchTempAuthCode(string $stateKey, string $uuid) Fetch and consume completed authentication-code details.
 * @method static ActionValidator validator() Return the action-validator gate.
 */
class PresignedAction extends Facade
{
    public static function validator(): ActionValidator
    {
        return static::getFacadeRoot()->validator();
    }

    protected static function getFacadeAccessor()
    {
        return PresignedActionGate::class;
    }
}
