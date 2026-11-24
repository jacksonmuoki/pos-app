<?php // app/Services/WebhookDispatcher.php

declare(strict_types=1);

namespace App\Services;

use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Support\Facades\Http;
use Throwable;

class WebhookDispatcher
{
    /** Fire an event to every active endpoint subscribed to it. */
    public static function dispatch(string $event, array $data = []): void
    {
        $endpoints = WebhookEndpoint::where('active', true)->get()
            ->filter(fn ($e) => in_array($event, $e->events ?? [], true));

        foreach ($endpoints as $endpoint) {
            $payload = [
                'event'      => $event,
                'data'       => $data,
                'created_at' => now()->toIso8601String(),
            ];
            $delivery = WebhookDelivery::create([
                'webhook_endpoint_id' => $endpoint->id,
                'event'    => $event,
                'payload'  => $payload,
                'attempts' => 0,
            ]);
            static::send($endpoint, $delivery);
        }
    }

    public static function send(WebhookEndpoint $endpoint, WebhookDelivery $delivery): void
    {
        $body = json_encode($delivery->payload, JSON_UNESCAPED_SLASHES);
        $delivery->attempts++;

        try {
            if (! static::isSafeUrl($endpoint->url)) {
                throw new \RuntimeException('URL blocked (must be public and https).');
            }

            $response = Http::timeout(10)
                ->withoutRedirecting()
                ->withHeaders([
                    'X-Webhook-Event' => $delivery->event,
                    'X-Signature'     => hash_hmac('sha256', $body, $endpoint->secret),
                ])
                ->withBody($body, 'application/json')
                ->post($endpoint->url);

            $delivery->status_code = $response->status();
            $delivery->response    = substr($response->body(), 0, 2000);
            $delivery->success     = $response->successful();
        } catch (Throwable $e) {
            $delivery->status_code = null;
            $delivery->response    = substr($e->getMessage(), 0, 2000);
            $delivery->success     = false;
        }

        $delivery->save();
    }

    /** Basic SSRF guard: https only (http allowed locally), no private/reserved IPs. */
    public static function isSafeUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (! $parts || empty($parts['host']) || empty($parts['scheme'])) {
            return false;
        }
        if ($parts['scheme'] !== 'https' && ! (app()->isLocal() && $parts['scheme'] === 'http')) {
            return false;
        }
        if (app()->isLocal()) {
            return true;
        }
        $ip = gethostbyname($parts['host']);
        return (bool) filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}