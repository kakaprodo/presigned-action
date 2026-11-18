<?php

namespace Kakaprodo\PresignedAction\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kakaprodo\PresignedAction\Exceptions\PresignedActionException;
use Kakaprodo\PresignedAction\Facades\PresignedAction;
use Symfony\Component\HttpFoundation\Response;

class PresignedActionOriginMiddleware
{
    public function handle(Request $request, Closure $next, string ...$origins): Response
    {
        $temporaryAccessKey = PresignedAction::temporaryAccessKey();

        if (! $temporaryAccessKey) {
            throw new PresignedActionException('Unauthorized - invalid temporary access key', 403);
        }

        $requiredOrigins = collect($origins)
            ->flatMap(fn(string $origin) => preg_split('/[|,]/', $origin))
            ->map(fn(string $origin) => trim($origin))
            ->filter()
            ->unique();

        if ($requiredOrigins->isEmpty() || ! $requiredOrigins->contains($temporaryAccessKey->origin)) {
            throw new PresignedActionException('Unauthorized - invalid origin', 403);
        }

        return $next($request);
    }
}
