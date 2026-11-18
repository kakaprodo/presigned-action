<?php

namespace Tests;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Routing\ResponseFactory as LaravelResponseFactory;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\Router;
use Illuminate\Routing\CallableDispatcher;
use Illuminate\Routing\Contracts\CallableDispatcher as CallableDispatcherContract;
use Illuminate\Support\Facades\Route;
use Kakaprodo\PresignedAction\Models\TempAuthCode;

class TempAuthCodeRouteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('presigned-action.temp_auth_code.route.enabled', true);
        config()->set('presigned-action.temp_auth_code.route.prefix', 'api/presigned-action');
        config()->set('presigned-action.temp_auth_code.rate_limit.enabled', false);

        $router = new Router(new Dispatcher(app()), app());
        app()->instance('router', $router);
        app()->instance(CallableDispatcherContract::class, new CallableDispatcher(app()));
        Route::clearResolvedInstance('router');
        app()->instance(ResponseFactory::class, new LaravelResponseFactory(
            $this->createMock(ViewFactory::class),
            $this->createMock(Redirector::class)
        ));

        Request::macro('validate', function (array $rules): array {
            $stateKey = $this->input('state_key');

            if (! is_string($stateKey) || $stateKey === '') {
                throw new \InvalidArgumentException('The state_key field is required and must be a string.');
            }

            if (strlen($stateKey) > 500) {
                throw new \InvalidArgumentException('The state_key may not be greater than 500 characters.');
            }

            return ['state_key' => $stateKey];
        });

        require dirname(__DIR__) . '/src/routes/api.php';
    }

    public function test_it_generates_a_temp_auth_code_through_the_configured_route(): void
    {
        $request = Request::create(
            '/api/presigned-action/temp-auth-codes',
            'POST',
            ['state_key' => 'request-route-state']
        );

        app()->instance('request', $request);
        app()->instance(Request::class, $request);
        $response = app('router')->dispatch($request);
        $payload = $response->getData(true);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('request-route-state', $payload['state_key']);
        $this->assertNotEmpty($payload['uuid']);
        $this->assertArrayHasKey('expires_at', $payload);
        $this->assertTrue(TempAuthCode::query()->where('uuid', $payload['uuid'])->exists());
    }

    public function test_it_rejects_a_request_without_a_string_state_key(): void
    {
        $request = Request::create(
            '/api/presigned-action/temp-auth-codes',
            'POST',
            ['state_key' => 123]
        );

        app()->instance('request', $request);
        app()->instance(Request::class, $request);
        $this->expectException(\InvalidArgumentException::class);

        app('router')->dispatch($request);
    }

    public function test_it_rejects_a_request_with_a_state_key_longer_than_500_characters(): void
    {
        $request = Request::create(
            '/api/presigned-action/temp-auth-codes',
            'POST',
            ['state_key' => str_repeat('a', 501)]
        );

        app()->instance('request', $request);
        app()->instance(Request::class, $request);
        $this->expectException(\InvalidArgumentException::class);

        app('router')->dispatch($request);
    }
}
