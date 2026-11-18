<?php

namespace Kakaprodo\PresignedAction;

use Illuminate\Http\Request;
use Kakaprodo\PresignedAction\Models\TempAuthCode;
use Kakaprodo\PresignedAction\Models\TemporaryAccessKey;
use Kakaprodo\PresignedAction\Plugins\ActionValidator;
use Kakaprodo\PresignedAction\Support\Actions\AssociateTempAuthCodeAction;
use Kakaprodo\PresignedAction\Support\Actions\FetchTempAuthCodeAction;
use Kakaprodo\PresignedAction\Support\Actions\GenerateTempAuthCodeAction;
use Kakaprodo\PresignedAction\Support\Actions\GenerateTemporaryAccessKeyAction;

class PresignedActionGate
{
    /**
     * Generate a temporary access key
     * 
     * @param array{
     *   accessible: \Illuminate\Database\Eloquent\Model,
     *   whoami: string,
     *   expires_at: null|\Illuminate\Support\Carbon,
     *   settings: array|null,
     *   scopes: array|null,
     *   permissions: array|null,
     *   origin: string|null,
     * } $options
     * @return TemporaryAccessKey
     */
    public function generateAccessKey(array $options): TemporaryAccessKey
    {
        return GenerateTemporaryAccessKeyAction::process($options);
    }

    /**
     * Generate a temporary authentication code.
     *
     * @param array{state_key: string} $options
     */
    public function generateTempAuthCode(array $options): TempAuthCode
    {
        return GenerateTempAuthCodeAction::process($options);
    }

    /**
     * Associate subscriber information with a scanned authentication code.
     *
     * @param array{uuid: string, subscriber_id?: string|null, subscriber_type: string, metadata?: array<string, mixed>} $options
     */
    public function associateTempAuthCode(array $options): TempAuthCode
    {
        return AssociateTempAuthCodeAction::process($options);
    }

    /**
     * Fetch temporary authentication-code details and consume completed codes.
     *
     * @param string $stateKey
     * @param string $uuid
     * @return array{status: string, expires_at?: int, subscriber_id?: string|null, subscriber_type?: string, metadata?: array<string, mixed>}
     */
    public function fetchTempAuthCode(string $stateKey, string $uuid): array
    {
        return FetchTempAuthCodeAction::process([
            'state_key' => $stateKey,
            'uuid' => $uuid,
        ]);
    }

    /**
     * Get the temporary access key model from the one loaded on the
     * request after middleware check has passed.
     */
    public function temporaryAccessKey(): ?TemporaryAccessKey
    {
        if (!Request::hasMacro('temporaryAccessKey')) {
            return null;
        }

        return request()->temporaryAccessKey();
    }

    /**
     * The action validator gate
     */
    public function validator(): ActionValidator
    {
        return new ActionValidator();
    }
}
