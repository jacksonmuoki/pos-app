<?php // app/Http/Controllers/SuperAdmin/WebhookController.php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Services\WebhookDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WebhookController extends Controller
{
    public function index(): View
    {
        return view('super.webhooks.index', [
            'endpoints'  => WebhookEndpoint::latest()->get(),
            'events'     => WebhookEndpoint::EVENTS,
            'deliveries' => WebhookDelivery::with('endpoint')->latest()->paginate(15),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'url'      => ['required', 'url', 'max:500'],
            'events'   => ['required', 'array', 'min:1'],
            'events.*' => ['string', Rule::in(WebhookEndpoint::EVENTS)],
        ]);

        abort_unless(WebhookDispatcher::isSafeUrl($data['url']), 422, 'URL must be public and https.');

        $secret = Str::random(40);
        $endpoint = WebhookEndpoint::create([
            'url' => $data['url'], 'events' => $data['events'], 'secret' => $secret,
        ]);

        ActivityLog::record('webhook.created', null, $data['url']);

        // Show the secret once only.
        return back()->with('status', "Webhook added. Signing secret (copy now, shown once): {$secret}");
    }

    public function toggle(WebhookEndpoint $webhook): RedirectResponse
    {
        $webhook->update(['active' => ! $webhook->active]);
        ActivityLog::record('webhook.toggled', null, $webhook->url, ['active' => $webhook->active]);
        return back();
    }

    public function destroy(WebhookEndpoint $webhook): RedirectResponse
    {
        ActivityLog::record('webhook.deleted', null, $webhook->url);
        $webhook->delete();
        return back()->with('status', 'Webhook deleted.');
    }

    public function test(WebhookEndpoint $webhook): RedirectResponse
    {
        $delivery = WebhookDelivery::create([
            'webhook_endpoint_id' => $webhook->id,
            'event'    => 'webhook.test',
            'payload'  => ['event' => 'webhook.test', 'data' => ['ok' => true], 'created_at' => now()->toIso8601String()],
            'attempts' => 0,
        ]);
        WebhookDispatcher::send($webhook, $delivery);
        return back()->with('status', 'Test sent: ' . ($delivery->success ? 'success' : 'failed') . '.');
    }

    public function resend(WebhookDelivery $delivery): RedirectResponse
    {
        WebhookDispatcher::send($delivery->endpoint, $delivery);
        ActivityLog::record('webhook.resent', null, "Delivery #{$delivery->id}");
        return back()->with('status', $delivery->success ? 'Delivered.' : 'Still failing.');
    }
}