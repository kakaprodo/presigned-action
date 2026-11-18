<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Kakaprodo\PresignedAction\PresignedActionGate;

$routeConfig = config('presigned-action.temp_auth_code.route', []);
$route = Route::prefix($routeConfig['prefix'] ?? 'api/presigned-action');

if (($routeConfig['middleware'] ?? []) !== []) {
    $route->middleware($routeConfig['middleware']);
}

if (config('presigned-action.temp_auth_code.rate_limit.enabled', true)) {
    $route->middleware('throttle:presigned-action-temp-auth-code');
}

$route->post($routeConfig['generate_uri'] ?? 'temp-auth-codes', function (Request $request) {
    $validated = $request->validate([
        'state_key' => ['required', 'string', 'max:500'],
    ]);

    $code = app(PresignedActionGate::class)->generateTempAuthCode([
        'state_key' => $validated['state_key'],
    ]);

    return response()->json([
        'uuid' => $code->uuid,
        'state_key' => $code->state_key,
        'expires_at' => $code->expires_at->timestamp,
    ], 201);
});
