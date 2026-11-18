<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Kakaprodo\PresignedAction\PresignedActionGate;

$routeConfig = config('presigned-action.temp_auth_code.route', []);
$route = Route::prefix($routeConfig['prefix'] ?? 'api/presigned-action');

if (($routeConfig['middleware'] ?? []) !== []) {
    $route->middleware($routeConfig['middleware']);
}

$generationRoute = $route->post($routeConfig['generate_uri'] ?? 'temp-auth-codes', function (Request $request) {
    $validated = $request->validate([
        'state_key' => ['required', 'string', 'max:500'],
        'metadata' => ['sometimes', 'array'],
    ]);

    $code = app(PresignedActionGate::class)->generateTempAuthCode([
        'state_key' => $validated['state_key'],
        'metadata' => $validated['metadata'] ?? [],
    ]);

    return response()->json([
        'uuid' => $code->uuid,
        'state_key' => $code->state_key,
        'expires_at' => $code->expires_at->timestamp,
    ], 201);
});

if (config('presigned-action.temp_auth_code.rate_limit.enabled', true)) {
    $generationRoute->middleware('throttle:presigned-action-temp-auth-code');
}

$fetchRoute = $route->get(
    $routeConfig['fetch_uri'] ?? 'temp-auth-codes/{state_key}/{uuid}',
    function (string $state_key, string $uuid) {
        $result = app(PresignedActionGate::class)->fetchTempAuthCode($state_key, $uuid);

        return response()->json(
            $result,
            $result['status'] === 'pending' ? 202 : 200
        );
    }
);

if (config('presigned-action.temp_auth_code.rate_limit.enabled', true)) {
    $fetchRoute->middleware('throttle:presigned-action-temp-auth-code');
}
