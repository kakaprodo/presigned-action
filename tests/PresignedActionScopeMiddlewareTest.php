<?php

namespace Tests;

use Illuminate\Http\Request;
use Kakaprodo\PresignedAction\Exceptions\PresignedActionException;
use Kakaprodo\PresignedAction\Middleware\PresignedActionScopeMiddleware;
use Kakaprodo\PresignedAction\Models\TemporaryAccessKey;
use Symfony\Component\HttpFoundation\Response;

class PresignedActionScopeMiddlewareTest extends TestCase
{
    public function test_it_allows_a_request_when_at_least_one_requested_scope_is_granted(): void
    {
        $request = Request::create('/');
        $accessKey = $this->createAccessKey($request, ['scope1', 'scope2']);

        $response = (new PresignedActionScopeMiddleware())->handle(
            $request,
            fn() => new Response('ok'),
            'scope1, missing-scope'
        );

        $this->assertSame('ok', $response->getContent());
        $this->assertSame(['scope1', 'scope2'], $accessKey->scopes);
    }

    public function test_it_accepts_pipe_separated_scopes(): void
    {
        $request = Request::create('/');
        $this->createAccessKey($request, ['scope1', 'scope2']);

        $response = (new PresignedActionScopeMiddleware())->handle(
            $request,
            fn() => new Response('ok'),
            'scope1|scope2'
        );

        $this->assertSame('ok', $response->getContent());
    }

    public function test_it_rejects_a_request_when_none_of_the_requested_scopes_are_granted(): void
    {
        $request = Request::create('/');
        $this->createAccessKey($request, ['scope1']);

        $this->expectException(PresignedActionException::class);
        $this->expectExceptionMessage('Unauthorized - insufficient scope');

        (new PresignedActionScopeMiddleware())->handle($request, fn() => new Response(), 'scope3,scope4');
    }

    public function test_it_rejects_a_request_when_no_temporary_access_key_exists(): void
    {
        $request = Request::create('/');
        app()->instance('request', $request);

        $this->expectException(PresignedActionException::class);
        $this->expectExceptionCode(403);

        (new PresignedActionScopeMiddleware())->handle($request, fn() => new Response(), 'scope1');
    }

    private function createAccessKey(Request $request, array $scopes): TemporaryAccessKey
    {
        $accessKey = TemporaryAccessKey::create([
            'uuid' => 'key-123',
            'whoami' => 'staff-1',
            'expires_at' => now()->addHour(),
            'accessible_id' => 1,
            'accessible_type' => AccessibleModel::class,
            'settings' => [],
            'scopes' => $scopes,
        ]);

        Request::macro('temporaryAccessKey', fn() => $accessKey);
        app()->instance('request', $request);

        return $accessKey;
    }
}
