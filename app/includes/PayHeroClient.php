<?php
/*
 * Thin HTTP client for PayHero. It makes no business decisions.
 *   PayHeroRejected    PayHero answered 4xx (or success=false): the request was not accepted.
 *   PayHeroUnavailable timeout, network error, 5xx: PayHero may have accepted it.
 */

final class PayHeroRejected extends RuntimeException {}
final class PayHeroUnavailable extends RuntimeException {}

final class PayHeroClient
{
    public function __construct(private array $cfg)
    {
        foreach (['api_url', 'base_url', 'auth_header', 'channel_id', 'callback_url', 'callback_token'] as $key) {
            if (empty($cfg[$key])) {
                throw new InvalidArgumentException("PayHero config is missing: {$key}");
            }
        }
    }

    /** Starts an STK Push. VERIFY the body fields against PayHero's docs. */
    public function initiateStk(int $amount, string $phone, string $externalRef, string $customerName): array
    {
        return $this->send('POST', (string) $this->cfg['api_url'], [
            'amount'             => $amount,
            'phone_number'       => $phone,
            'channel_id'         => (int) $this->cfg['channel_id'],
            'provider'           => 'm-pesa',
            'external_reference' => $externalRef,
            'customer_name'      => $customerName,
            'callback_url'       => $this->callbackUrl(),
        ]);
    }

    /** Looks up one transaction. VERIFY the path in PayHero's docs. */
    public function getTransactionStatus(string $reference): array
    {
        $path = str_replace('{ref}', rawurlencode($reference), (string) $this->cfg['status_path']);
        return $this->send('GET', rtrim((string) $this->cfg['base_url'], '/') . $path);
    }

    /** Callback URL with the secret token, so the endpoint can reject outside requests. */
    private function callbackUrl(): string
    {
        return $this->cfg['callback_url'] . '?token=' . rawurlencode((string) $this->cfg['callback_token']);
    }

    private function send(string $method, string $url, ?array $body = null): array
    {
        $ch = curl_init($url);

        $headers = [
            'Accept: application/json',
            'Authorization: ' . $this->cfg['auth_header'],
        ];
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
        }

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => (int) ($this->cfg['timeout'] ?? 20),
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_SLASHES));
        }

        $raw  = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $code === 0) {
            throw new PayHeroUnavailable('No response from PayHero: ' . $err);
        }
        if ($code >= 500) {
            throw new PayHeroUnavailable("PayHero HTTP {$code}: " . substr((string) $raw, 0, 300));
        }

        $data = json_decode((string) $raw, true);

        if ($code >= 400) {
            // Prefer PayHero's own message so the cashier can show the real reason.
            $detail = is_array($data)
                ? ($data['message'] ?? $data['error'] ?? $data['detail'] ?? $data['errors'] ?? null)
                : null;

            if (is_array($detail)) {
                $detail = json_encode($detail, JSON_UNESCAPED_SLASHES);
            }

            $text = is_string($detail) && $detail !== ''
                ? $detail
                : substr((string) $raw, 0, 300);

            throw new PayHeroRejected("PayHero HTTP {$code}: " . $text);
        }

        if (!is_array($data)) {
            throw new PayHeroUnavailable('PayHero returned non-JSON');
        }
        if (isset($data['success']) && $data['success'] === false) {
            $msg = $data['message'] ?? $data['error'] ?? 'reported success=false';
            throw new PayHeroRejected('PayHero: ' . (is_string($msg) ? $msg : json_encode($msg)));
        }

        return $data;
    }
}