<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Kafka extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'kafka';
    }
}
