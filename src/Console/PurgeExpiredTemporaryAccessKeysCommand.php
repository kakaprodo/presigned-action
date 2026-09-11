<?php

namespace Kakaprodo\PresignedAction\Console;

use Illuminate\Console\Command;
use Kakaprodo\PresignedAction\Models\TemporaryAccessKey;

class PurgeExpiredTemporaryAccessKeysCommand extends Command
{
    protected $signature = 'presigned-action:purge-expired';

    protected $description = 'Purge expired temporary access keys';

    public function handle(): int
    {
        $deleted = TemporaryAccessKey::query()
            ->where('expires_at', '<=', now())
            ->delete();

        $this->info("Purged {$deleted} expired temporary access key(s).");

        return self::SUCCESS;
    }
}
