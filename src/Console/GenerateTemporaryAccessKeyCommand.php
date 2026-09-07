<?php

namespace Kakaprodo\PresignedAction\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Kakaprodo\PresignedAction\Facades\PresignedAction;
use Throwable;

class GenerateTemporaryAccessKeyCommand extends Command
{
    protected $signature = 'presigned-action:generate
        {whoami? : Identifier of the person or system receiving access}
        {--accessible-id= : Primary key of the accessible entity}
        {--accessible-type= : Model class or morph-map alias of the accessible entity}
        {--scopes=* : Optional scopes, supplied repeatedly or as a comma/pipe-separated value}';

    protected $description = 'Generate a temporary access token for an accessible entity';

    public function handle(): int
    {
        $accessibleId = $this->option('accessible-id')
            ?? $this->ask('Accessible entity ID');
        $accessibleType = $this->option('accessible-type')
            ?? $this->ask('Accessible entity type (model class or morph-map alias)');
        $whoami = $this->argument('whoami')
            ?: $this->ask('Whoami');
        $scopes = $this->option('scopes');

        if (empty($scopes)) {
            $scopes = $this->ask('Scopes (optional, comma- or pipe-separated)');
        }

        if ($accessibleId === null || $accessibleType === null || $whoami === null) {
            $this->error('Accessible ID, accessible type, and whoami are required.');

            return self::FAILURE;
        }

        try {
            $modelClass = Relation::getMorphedModel($accessibleType) ?? $accessibleType;

            if (! class_exists($modelClass) || ! is_a($modelClass, Model::class, true)) {
                throw new \InvalidArgumentException("Accessible type [{$accessibleType}] is not an Eloquent model.");
            }

            $accessible = (new $modelClass)->newQuery()->find($accessibleId);

            if (! $accessible) {
                throw new \InvalidArgumentException("Accessible entity [{$accessibleId}] was not found for type [{$accessibleType}].");
            }

            $scopes = collect(is_array($scopes) ? $scopes : [$scopes])
                ->filter(fn($scope) => is_string($scope))
                ->flatMap(fn(string $scope) => preg_split('/[|,]/', $scope))
                ->map(fn(string $scope) => trim($scope))
                ->filter()
                ->unique()
                ->values()
                ->all();

            $accessKey = PresignedAction::generateAccessKey([
                'accessible' => $accessible,
                'whoami' => $whoami,
                'scopes' => $scopes,
            ]);

            $this->line(json_encode($accessKey->formatPublicTempKey(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
