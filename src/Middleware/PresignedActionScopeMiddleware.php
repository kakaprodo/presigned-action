<?php

namespace Kakaprodo\PresignedAction\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kakaprodo\PresignedAction\Exceptions\PresignedActionException;
use Kakaprodo\PresignedAction\Facades\PresignedAction;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PresignedActionScopeMiddleware
{
    public function handle(Request $request, Closure $next, string ...$scopes): Response
    {
        $temporaryAccessKey = null;

        try {
            $temporaryAccessKey = PresignedAction::temporaryAccessKey();
        } catch (Throwable) {
            $this->fireError();
        }

        if (! $temporaryAccessKey) {
            $this->fireError();
        }

        $requiredScopes = collect($scopes)
            ->flatMap(fn(string $scope) => preg_split('/[|,]/', $scope))
            ->map(fn(string $scope) => trim($scope))
            ->filter()
            ->unique()
            ->values();

        if ($requiredScopes->isEmpty())  return $next($request);

        if ($requiredScopes->intersect($temporaryAccessKey->scopes ?? [])->isEmpty()) {
            $this->fireError('Unauthorized - insufficient scope');
        }

        return $next($request);
    }

    public function fireError(string $message = 'Unauthorized - invalid temporary access key'): void
    {
        throw new PresignedActionException($message, 403);
    }
}
