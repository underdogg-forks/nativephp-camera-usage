<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Publish a test event to the Pusher-compatible server (Vask) so the mobile
 * client (the VibeDemo screen) can receive it. This stands in for "the server"
 * during the POC — no separate Laravel backend needed. It signs the request
 * per the Pusher HTTP API and POSTs directly to the API host.
 *
 *   php artisan vibe:test-broadcast "hello from vask"
 */
class VibeTestBroadcast extends Command
{
    protected $signature = 'vibe:test-broadcast
                            {message=Hello from Vask : The message payload}
                            {--channel=test-channel : Channel to publish on}
                            {--event=TestEvent : Event name (match your #[OnEcho])}';

    protected $description = 'Publish a test broadcast event to the Pusher-compatible server';

    public function handle(): int
    {
        $key = env('PUSHER_APP_KEY');
        $secret = env('PUSHER_APP_SECRET');
        $apiHost = env('PUSHER_API_HOST', 'api.vask.dev');

        if (! $key || ! $secret) {
            $this->error('PUSHER_APP_KEY / PUSHER_APP_SECRET missing in .env');

            return self::FAILURE;
        }

        $channel = $this->option('channel');
        $event = $this->option('event');
        $message = $this->argument('message');

        // Pusher HTTP API: POST /apps/{app_key}/events with the event body,
        // authenticated by a sorted, HMAC-SHA256-signed query string.
        $body = json_encode([
            'name' => $event,
            'channels' => [$channel],
            'data' => json_encode(['message' => $message]),
        ]);

        $path = "/apps/{$key}/events";

        $params = [
            'auth_key' => $key,
            'auth_timestamp' => (string) time(),
            'auth_version' => '1.0',
            'body_md5' => md5($body),
        ];
        ksort($params);

        $queryString = urldecode(http_build_query($params));
        $params['auth_signature'] = hash_hmac('sha256', "POST\n{$path}\n{$queryString}", $secret);

        $url = "https://{$apiHost}{$path}";

        $response = Http::withQueryParameters($params)
            ->withBody($body, 'application/json')
            ->post($url);

        if ($response->successful()) {
            $this->info("Broadcast '{$event}' on '{$channel}': {$message}");

            return self::SUCCESS;
        }

        $this->error("Failed ({$response->status()}): ".$response->body());

        return self::FAILURE;
    }
}
