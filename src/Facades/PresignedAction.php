<?php

namespace Kakaprodo\PresignedAction\Facades;

use Illuminate\Support\Facades\Facade;
use Kakaprodo\PresignedAction\Models\TemporaryAccessKey;
use Kakaprodo\PresignedAction\PresignedActionGate;

/**
 * @method static TemporaryAccessKey generateAccessKey()
 * @method static ?TemporaryAccessKey temporaryAccessKey()
 */
class PresignedAction extends Facade
{

    protected static function getFacadeAccessor()
    {
        return PresignedActionGate::class;
    }
}
