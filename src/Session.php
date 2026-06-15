<?php

namespace amitdeveloper2024\KeyExchanger;

class Session
{
    public function __construct(
        public string $aesKey,
        public string $hmacKey,
        public string $keyId,
        public string $keyVersion,
        public ?string $clientId = null,
        public ?string $apiKey = null,
    ) {}
}