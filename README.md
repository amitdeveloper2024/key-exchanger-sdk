# Key Exchanger SDK

## Installation

```bash
composer require amitdeveloper2024/key-exchanger-sdk
```

## Publish config

```bash
php artisan vendor:publish --tag=key-exchanger-config
```

## Configure

Add to `.env`:

```env
KEY_EXCHANGER_URL=ws://localhost:8080
KEY_EXCHANGER_APPLICATION_ID=10
```

## Usage

```php
use KeyExchanger;

KeyExchanger::on('message', function ($message) {
    print_r($message);
});

KeyExchanger::start();
```