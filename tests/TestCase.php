<?php

namespace Tests;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Request::flushMacros();

        $container = new class extends Container {
            public function runningUnitTests(): bool
            {
                return true;
            }
        };
        Container::setInstance($container);
        Facade::setFacadeApplication($container);

        $container->instance('config', new Repository([
            'app' => ['cipher' => 'AES-256-CBC'],
            'presigned-action' => [
                'key_expires_after' => 60,
                'access_key_validation' => [
                    'whoami' => 'X-WHOMAI',
                    'temp_access_key' => 'X-TEMP-ACCESS-KEY',
                ],
                'validation_error_message' => 'Unauthorized - invalid temporary access key',
                'temp_auth_code' => [
                    'table' => 'temp_auth_codes',
                    'expires_after' => 20,
                    'route' => [
                        'enabled' => false,
                        'prefix' => 'api/presigned-action',
                    ],
                    'rate_limit' => [
                        'enabled' => true,
                        'max_attempts' => 20,
                        'window_seconds' => 60,
                    ],
                ],
            ],
        ]));
        $container->instance('encrypter', new Encrypter(str_repeat('a', 32), 'AES-256-CBC'));
        $container->instance('request', Request::create('/'));

        $database = new Capsule($container);
        $database->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $database->setAsGlobal();
        $database->bootEloquent();
        $schema = $database->schema();
        $schema->create('accessible_models', function ($table): void {
            $table->id();
            $table->timestamps();
        });
        $schema->create('temporary_access_keys', function ($table): void {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('reference_text')->nullable();
            $table->string('whoami');
            $table->timestamp('expires_at');
            $table->string('accessible_type')->nullable();
            $table->unsignedBigInteger('accessible_id')->nullable();
            $table->boolean('is_independent')->default(false);
            $table->json('settings')->nullable();
            $table->json('scopes')->nullable();
            $table->json('permissions')->nullable();
            $table->string('origin')->nullable();
            $table->timestamps();
        });
        $schema->create('temp_auth_codes', function ($table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('state_key')->unique();
            $table->string('subscriber_id')->nullable();
            $table->string('subscriber_type')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }
}
