<?php

namespace amitdeveloper2024\KeyExchanger;

use Exception;
use Ramsey\Uuid\Uuid;
use WebSocket\Client as WsClient;

class Client
{
    protected ?WsClient $ws = null;

    protected ?Session $session = null;

    protected bool $connected = false;

    protected array $listeners = [];

    protected array $queue = [];

    protected int $retryAttempts = 0;

    public function __construct(
        protected CryptoService $crypto
    ) {}

    public function on(string $event, callable $callback): void
    {
        $this->listeners[$event] = $callback;
    }

    protected function emit(string $event, mixed $data = null): void
    {
        if (isset($this->listeners[$event])) {
            call_user_func($this->listeners[$event], $data);
        }
    }

    public function connect(): void
    {

        while (true) {

            try {

                // echo "Step 1: Enter connect()\n";

                // echo "URL: " . config('key_exchanger.url') . PHP_EOL;

                // echo "Step 2: Creating websocket\n";

                $this->ws = new WsClient(
                    config('key_exchanger.url'),
                    [
                        'timeout' => config('key_exchanger.connection_timeout')
                    ]
                );

                // echo "Step 3: Websocket created\n";

                $this->connected = true;

                $payload = [
                    'application_id' => config('key_exchanger.application_id'),
                    'timestamp' => (int) round(microtime(true) * 1000),
                    'nonce' => Uuid::uuid4()->toString(),
                ];

                // echo "Step 4: Encrypting init payload\n";

                $encrypted = $this->crypto->rsaEncrypt($payload);

                // echo "Step 5: Sending init\n";

                $this->sendRaw([
                    'type' => 'init',
                    'key_id' => config('key_exchanger.application_id'),
                    'data' => $encrypted,
                ]);

                // echo "Step 6: Waiting for server messages\n";

                while (true) {

                    $message = $this->ws->receive();

                    echo "Received: " . $message . PHP_EOL;

                    $data = json_decode($message, true);

                    if (! is_array($data)) {
                        continue;
                    }

                    $this->handle($data);
                }

            } catch (\Throwable $e) {

                echo "ERROR: " . $e->getMessage() . PHP_EOL;

                $this->emit('error', $e);

                $this->reconnect();
            }
        }
    }

    protected function handle(array $data): void
        {
            switch ($data['type']) {

                case 'init_keys':
                case 'rotate_keys':

                    $payload = $this->handleSession($data);

                    $this->emit('session_payload', $payload);

                    if ($data['type'] === 'rotate_keys') {
                        $this->emit('rotated');
                    }

                    break;

                // ...
            }
        }

  protected function handleSession(array $data): void
    {
        $aesKey = $this->crypto->rsaDecrypt(
            $data['encrypted_key']
        );

        $payload = $this->crypto->aesDecrypt(
            $data['data'],
            $aesKey,
            $data['iv'],
            $data['auth_tag']
        );

        echo json_encode($payload, JSON_PRETTY_PRINT) . PHP_EOL;

        $this->setSession(
            new Session(
                hex2bin($payload['aes_key']),
                hex2bin($payload['hmac_key']),
                $data['key_id'],
                $data['key_version'],
                $payload['client_id'] ?? null,
                $payload['api_key'] ?? null,
            )
        );
    }

    protected function handleSecure(array $data): void
    {
        if (! $this->session) {
            return;
        }

        $expected = $this->crypto->hmac(
            $data['payload'] .
            $data['iv'] .
            $data['auth_tag'],
            $this->session->hmacKey
        );

        if (! hash_equals($expected, $data['signature'])) {
            throw new Exception('Invalid signature');
        }

        $payload = $this->crypto->aesDecrypt(
            $data['payload'],
            $this->session->aesKey,
            $data['iv'],
            $data['auth_tag']
        );

        $this->emit('message', $payload);
    }

    public function setSession(Session $session): void
    {
        $this->retryAttempts = 0;

        $this->session = $session;

        $this->sendRaw([
            'type' => 'init_keys_stored',
            'key_id' => $session->keyId,
            'key_version' => $session->keyVersion,
        ]);

        foreach ($this->queue as $message) {
            $this->send($message);
        }

        $this->queue = [];

        $this->emit('connected', $session);
    }

    public function send(array $data): void
    {
        if (! $this->session) {

            if (count($this->queue) > 1000) {
                throw new Exception('Queue overflow');
            }

            $this->queue[] = $data;

            return;
        }

        $encrypted = $this->crypto->aesEncrypt(
            $data,
            $this->session->aesKey
        );

        $signature = $this->crypto->hmac(
            $encrypted['data'] .
            $encrypted['iv'] .
            $encrypted['tag'],
            $this->session->hmacKey
        );

        $this->sendRaw([
            'type' => 'secure',
            'nonce' => Uuid::uuid4()->toString(),
            'timestamp' => (int) round(microtime(true) * 1000),
            'payload' => $encrypted['data'],
            'iv' => $encrypted['iv'],
            'auth_tag' => $encrypted['tag'],
            'signature' => $signature,
        ]);
    }

    public function rotate(): void
    {
        if (! $this->connected || ! $this->session) {
            return;
        }

        $this->sendRaw([
            'type' => 'rotate_request',
            'nonce' => Uuid::uuid4()->toString(),
            'timestamp' => (int) round(microtime(true) * 1000),
        ]);
    }

    protected function sendRaw(array $payload): void
    {
        $this->ws?->send(
            json_encode($payload, JSON_UNESCAPED_SLASHES)
        );
    }

    protected function reconnect(): void
    {
        $this->connected = false;

        $this->emit('disconnected');

        if ($this->retryAttempts >= config('key_exchanger.max_retries')) {
            throw new Exception('Max retries reached');
        }

        $intervals = config('key_exchanger.retry_intervals');

        $delay = $intervals[
            min(
                $this->retryAttempts,
                count($intervals) - 1
            )
        ];

        $delay += mt_rand(0, 1000) / 1000;

        $this->retryAttempts++;

        usleep((int) ($delay * 1000000));
    }

    public function close(): void
    {
        $this->ws?->close();
    }
}