<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Throwable;

class CompanySettings
{
    public const FIELDS = [
        'business_name', 'business_phone', 'business_address', 'receipt_footer',
        'currency', 'timezone', 'tax_rate',
        'cash_enabled', 'mpesa_enabled',
        'payhero_username', 'payhero_password', 'payhero_channel_id',
        'sms_enabled', 'sms_sender_id', 'sms_api_key',
    ];

    public const BOOLEANS = ['cash_enabled', 'mpesa_enabled', 'sms_enabled'];
    public const SECRETS  = ['payhero_password', 'sms_api_key'];

    public static function rules(): array
    {
        return [
            'business_name'      => ['required', 'string', 'max:150'],
            'business_phone'     => ['nullable', 'string', 'max:30'],
            'business_address'   => ['nullable', 'string', 'max:255'],
            'receipt_footer'     => ['nullable', 'string', 'max:255'],
            'currency'           => ['required', 'string', 'size:3', 'alpha'],
            'timezone'           => ['required', 'timezone'],
            'tax_rate'           => ['required', 'numeric', 'min:0', 'max:100'],
            'payhero_username'   => ['nullable', 'string', 'max:255'],
            'payhero_password'   => ['nullable', 'string', 'max:255'],
            'payhero_channel_id' => ['nullable', 'string', 'max:50'],
            'sms_sender_id'      => ['nullable', 'string', 'max:20'],
            'sms_api_key'        => ['nullable', 'string', 'max:255'],
        ];
    }

    /** Runs in the CURRENT tenant context. */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = DB::table('settings')->where('key', $key)->value('value');

        if ($value === null || $value === '') {
            return $default;
        }

        if (in_array($key, self::SECRETS, true)) {
            try {
                return Crypt::decryptString($value);
            } catch (Throwable) {
                return $default;
            }
        }

        return $value;
    }

    public static function put(string $key, mixed $value): void
    {
        $stored = $value === null ? null : (string) $value;

        if ($stored !== null && $stored !== '' && in_array($key, self::SECRETS, true)) {
            $stored = Crypt::encryptString($stored);
        }

        DB::table('settings')->updateOrInsert(['key' => $key], ['value' => $stored]);
    }

    /** Values for forms. Secrets are never returned, only whether one is saved. */
    public static function load(): array
    {
        $rows   = DB::table('settings')->pluck('value', 'key');
        $values = [];
        $hasSecret = [];

        foreach (array_merge(self::FIELDS, ['maintenance_mode', 'maintenance_message']) as $key) {
            $v = $rows[$key] ?? null;

            if (in_array($key, self::SECRETS, true)) {
                $hasSecret[$key] = ! empty($v);
                $values[$key] = null;
            } else {
                $values[$key] = $v;
            }
        }

        return [$values, $hasSecret];
    }

    /** Save validated input. Blank secret = keep saved one. Returns names of changed fields. */
    public static function save(Request $request, array $data): array
    {
        $changed = [];

        foreach (self::FIELDS as $key) {
            if (in_array($key, self::BOOLEANS, true)) {
                $value = $request->boolean($key) ? '1' : '0';
            } elseif (in_array($key, self::SECRETS, true)) {
                if (($data[$key] ?? '') === '') {
                    continue;
                }
                $value = $data[$key];
            } else {
                $value = $data[$key] ?? null;
                if ($key === 'currency') {
                    $value = strtoupper((string) $value);
                }
            }

            if (self::rawValue($key) !== (in_array($key, self::SECRETS, true) ? null : $value)) {
                $changed[] = $key;
            }
            self::put($key, $value);
        }

        return $changed;
    }

    private static function rawValue(string $key): ?string
    {
        return DB::table('settings')->where('key', $key)->value('value');
    }

    // ---------- Helpers used by the rest of the app ----------

    public static function currency(): string
    {
        return strtoupper((string) (self::get('currency') ?: PlatformSetting::get('default_currency', 'KES')));
    }

    public static function money(float|int|string $amount): string
    {
        return self::currency() . ' ' . number_format((float) $amount, 2);
    }

    public static function timezone(): string
    {
        return (string) (self::get('timezone') ?: PlatformSetting::get('default_timezone', 'Africa/Nairobi'));
    }

    /** This company's PayHero credentials only. No fallback to the platform account. */
    public static function payhero(): ?array
    {
        $username = self::get('payhero_username');
        $password = self::get('payhero_password');
        $channel  = self::get('payhero_channel_id');

        return ($username && $password && $channel)
            ? ['username' => $username, 'password' => $password, 'channel_id' => $channel]
            : null;
    }

    /** Company SMS settings, falling back to the platform SMS integration. */
    public static function sms(): ?array
    {
        if (self::get('sms_enabled') !== '1') {
            return null;
        }

        $platform = \App\Models\Integration::settings('sms') ?? [];

        $apiKey = self::get('sms_api_key') ?: ($platform['api_key'] ?? null);
        if (! $apiKey) {
            return null;
        }

        return [
            'provider'  => $platform['provider'] ?? null,
            'api_key'   => $apiKey,
            'sender_id' => self::get('sms_sender_id') ?: ($platform['sender_id'] ?? null),
        ];
    }
}