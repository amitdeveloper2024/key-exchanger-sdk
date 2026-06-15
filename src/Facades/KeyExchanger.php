<?php
namespace amitdeveloper2024\KeyExchanger\Facades;

use Illuminate\Support\Facades\Facade;

class KeyExchanger extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \amitdeveloper2024\KeyExchanger\KeyExchangerManager::class;
    }
}