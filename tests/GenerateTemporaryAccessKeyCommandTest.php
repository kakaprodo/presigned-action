<?php

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Kakaprodo\PresignedAction\Console\GenerateTemporaryAccessKeyCommand;
use Kakaprodo\PresignedAction\Console\PurgeExpiredTemporaryAccessKeysCommand;
use Kakaprodo\PresignedAction\Models\TemporaryAccessKey;
use Symfony\Component\Console\Tester\CommandTester;

class GenerateTemporaryAccessKeyCommandTest extends TestCase
{
    public function test_it_prompts_for_values_and_generates_a_token(): void
    {
        $accessible = CommandAccessibleModel::create();

        $container = app();
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

    public function test_it_purges_expired_access_keys(): void
    {
        TemporaryAccessKey::create([
            'uuid' => 'expired-key', 'whoami' => 'staff-1', 'expires_at' => now()->subMinute(),
            'accessible_id' => 1, 'accessible_type' => CommandAccessibleModel::class,
        ]);
        TemporaryAccessKey::create([
            'uuid' => 'valid-key', 'whoami' => 'staff-1', 'expires_at' => now()->addMinute(),
            'accessible_id' => 1, 'accessible_type' => CommandAccessibleModel::class,
        ]);

        $command = new PurgeExpiredTemporaryAccessKeysCommand();
        $command->setLaravel(app());
        $tester = new CommandTester($command);

        $this->assertSame(0, $tester->execute([]));
        $this->assertSame(1, TemporaryAccessKey::query()->count());
        $this->assertStringContainsString('Purged 1', $tester->getDisplay());
    }
}

class CommandAccessibleModel extends Model
{
    protected $table = 'accessible_models';

    protected $guarded = [];
}
