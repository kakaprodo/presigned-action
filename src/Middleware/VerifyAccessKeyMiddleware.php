<?php

namespace Kakaprodo\PresignedAction\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kakaprodo\PresignedAction\Models\TemporaryAccessKey;
use Kakaprodo\PresignedAction\Exceptions\PresignedActionException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Ensure access incoming encypted temp_access key is valid, then load it on the request
 * */
class VerifyAccessKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $encryptedTempAccessProperty = config('presigned-action.access_key_validation.temp_access_key');
        $whoAmiProperty = config('presigned-action.access_key_validation.whoami');

        $encryptedTempAccessKeyValue = $request->header($encryptedTempAccessProperty);
        $whoAmiValue = $request->header($whoAmiProperty);

        if (!$encryptedTempAccessKeyValue) {
            $this->fireError("The {$encryptedTempAccessProperty} is required");
        }

        if (!$whoAmiValue) {
            $this->fireError("The {$whoAmiProperty} is required");
        }

        try {
            $decryptedKey = decrypt($encryptedTempAccessKeyValue);
        } catch (Throwable) {
            $this->fireError();
        }

        // The decrypted key is: {uuid}-merchant-{accessible_id}::{accessible_type}.
        if (! is_string($decryptedKey) || ! preg_match('/^(.+)-tempo-(\d+)::(.+)$/', $decryptedKey, $matches)) {
            $this->fireError();
        }

        [$uuid, $accessibleId, $accessibleType] = [$matches[1], (int) $matches[2], $matches[3]];

        $accessKey = TemporaryAccessKey::query()
            ->with('accessible')
            ->where('uuid', $uuid)
            ->where('accessible_id', $accessibleId)
            ->where('accessible_type', $accessibleType)
            ->first();

        if (! $accessKey) {
            $this->fireError();
        }


        if ($accessKey->whoami !== $whoAmiValue) {
            $this->fireError('Unauthorized - wrong identifier');
        }

        if ($accessKey->expires_at->isPast()) {
            $this->fireError('Unauthorized - access key has expired');
        }

        Request::macro('temporaryAccessKey', fn() => $accessKey);

        return $next($request);
    }

    public function fireError($message = null)
    {
        throw new PresignedActionException(
            $message ?? config('presigned-action.validation_error_message'),
            403
        );
    }
}
