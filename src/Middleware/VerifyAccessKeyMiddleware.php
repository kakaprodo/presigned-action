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

        // The decrypted key is either:
        // {uuid}-tempo-{accessible_id}::{accessible_type}
        // or {uuid}-independent-{whoami}.
        if (! is_string($decryptedKey)) {
            $this->fireError();
        }

        if (preg_match('/^(.+)-independent-(.+)$/', $decryptedKey, $matches)) {
            $accessKey = TemporaryAccessKey::query()
                ->where('uuid', $matches[1])
                ->where('is_independent', true)
                ->first();
        } elseif (preg_match('/^(.+)-tempo-(\d+)::(.+)$/', $decryptedKey, $matches)) {
            $accessKey = TemporaryAccessKey::query()
                ->with('accessible')
                ->where('uuid', $matches[1])
                ->where('is_independent', false)
                ->where('accessible_id', (int) $matches[2])
                ->where('accessible_type', $matches[3])
                ->first();
        } else {
            $this->fireError();
        }

        if (! $accessKey) {
            $this->fireError();
        }


        if (! $accessKey->revalidateWhoami($whoAmiValue)) {
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
