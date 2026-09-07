<?php

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Kakaprodo\PresignedAction\PresignedActionGate;
use Kakaprodo\PresignedAction\Console\GenerateTemporaryAccessKeyCommand;
use Symfony\Component\Console\Tester\CommandTester;

class GenerateTemporaryAccessKeyCommandTest extends TestCase
{
    public function test_it_prompts_for_values_and_generates_a_token(): void
    {
        $accessible = CommandAccessibleModel::create();

        $container = app();
        $container->singleton(PresignedActionGate::class, fn () => new PresignedActionGate());

        $command = new GenerateTemporaryAccessKeyCommand();
        $command->setLaravel($container);

        $tester = new CommandTester($command);
        $tester->setInputs([
            (string) $accessible->getKey(),
            CommandAccessibleModel::class,
            'staff-1',
            'orders.read|orders.download',
        ]);

        $exitCode = $tester->execute([]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('temp_access_key', $tester->getDisplay());

        $accessKey = \Kakaprodo\PresignedAction\Models\TemporaryAccessKey::query()->first();

        $this->assertNotNull($accessKey);
        $this->assertSame('staff-1', $accessKey->whoami);
        $this->assertSame($accessible->getKey(), $accessKey->accessible_id);
        $this->assertSame(CommandAccessibleModel::class, $accessKey->accessible_type);
        $this->assertSame(['orders.read', 'orders.download'], $accessKey->scopes);
    }
}

class CommandAccessibleModel extends Model
{
    protected $table = 'accessible_models';

    protected $guarded = [];
}
