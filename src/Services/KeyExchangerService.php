<?php
namespace AmitDeveloper2024\KeyExchanger\Services;

class KeyExchangerService
{
    public function send(array $data)
    {
        return [
            'status' => 'sent',
            'payload' => $data
        ];
    }
}