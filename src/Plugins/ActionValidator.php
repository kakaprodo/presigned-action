<?php

namespace Kakaprodo\PresignedAction\Plugins;

use Illuminate\Support\Arr;
use Kakaprodo\PresignedAction\Exceptions\PresignedActionException;
use Kakaprodo\PresignedAction\Facades\PresignedAction;

class ActionValidator
{
    /** @var array<string, callable|array<string, callable>> */
    protected static array $registeredValidators = [];

    /** Register one validator or a named group of validators. */
    public function register(string $name, callable|array $validator): void
    {
        static::$registeredValidators[$name] = $validator;
    }

    /**
     * Resolve and execute a validator with the current temporary access key.
     *
     * A failed validator throws a PresignedActionException.
     */
    public function check(string $validatorActionHandler, array $arguments = [], ?string $errorMessage = null): mixed
    {
        $handler = Arr::get(static::$registeredValidators, $validatorActionHandler);

        if (! $handler) {
            throw new \InvalidArgumentException("Validator [{$validatorActionHandler}] is not defined.");
        }

        $hasPassed = $handler(PresignedAction::temporaryAccessKey(), ...$arguments);


        if (! $hasPassed) {
            throw new PresignedActionException($errorMessage ?? 'Permission denied', 403);
        }

        return $hasPassed;
    }

    /** Reset registered validators, primarily useful for isolated application lifecycles. */
    public function flush(): void
    {
        static::$registeredValidators = [];
    }
}
