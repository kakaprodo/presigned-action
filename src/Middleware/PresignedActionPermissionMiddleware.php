<?php

namespace Kakaprodo\PresignedAction\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kakaprodo\PresignedAction\Exceptions\PresignedActionException;
use Kakaprodo\PresignedAction\Facades\PresignedAction;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PresignedActionPermissionMiddleware
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $temporaryAccessKey = PresignedAction::temporaryAccessKey();

        if (! $temporaryAccessKey) {
            throw new PresignedActionException('Unauthorized - invalid temporary access key', 403);
        }

        $requiredPermissions = collect($permissions)
            ->flatMap(fn(string $permission) => preg_split('/[|,]/', $permission))
            ->map(fn(string $permission) => trim($permission))
            ->filter()
            ->unique()
            ->values();

        if ($requiredPermissions->isNotEmpty() && ! $temporaryAccessKey->can($requiredPermissions->all())) {
            throw new PresignedActionException('Unauthorized - insufficient permission', 403);
        }

        return $next($request);
    }
}
