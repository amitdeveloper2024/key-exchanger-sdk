<?php

namespace amitdeveloper2024\KeyExchanger;

use RuntimeException;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\PublicKeyLoader;

class CryptoService
{
    public function rsaEncrypt(array $payload): string
    {
        $publicKey = file_get_contents(
            config('key_exchanger.server_public_key')
        );

        $rsa = PublicKeyLoader::load($publicKey)
            ->withPadding(RSA::ENCRYPTION_OAEP)
            ->withHash('sha256')
            ->withMGFHash('sha256');

        $encrypted = $rsa->encrypt(
            json_encode($payload, JSON_UNESCAPED_SLASHES)
        );

        return base64_encode($encrypted);
    }

    public function rsaDecrypt(string $encrypted): string
    {
        $privateKey = file_get_contents(
            config('key_exchanger.private_key')
        );

        $rsa = PublicKeyLoader::load($privateKey)
            ->withPadding(RSA::ENCRYPTION_OAEP)
            ->withHash('sha256')
            ->withMGFHash('sha256');

        return $rsa->decrypt(
            base64_decode($encrypted)
        );
    }

    public function aesEncrypt(array $payload, string $key): array
    {
        $iv = random_bytes(12);

        $encrypted = openssl_encrypt(
            json_encode($payload, JSON_UNESCAPED_SLASHES),
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($encrypted === false) {
            throw new RuntimeException('AES encryption failed');
        }

        return [
            'data' => base64_encode($encrypted),
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
        ];
    }

    public function aesDecrypt(
    string $payload,
    string $key,
    string $iv,
    string $tag
): array {

    $decrypted = openssl_decrypt(
        base64_decode($payload),
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        base64_decode($iv),
        base64_decode($tag)
    );
    // dump($decrypted);
    if ($decrypted === false) {
        throw new RuntimeException(
            'AES decryption failed: ' . openssl_error_string()
        );
    }

    return json_decode($decrypted, true);
}

    public function hmac(string $data, string $key): string
    {
        return hash_hmac(
            'sha256',
            $data,
            $key
        );
    }
}