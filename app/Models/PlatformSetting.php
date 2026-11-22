<?php // app/Models/PlatformSetting.php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class PlatformSetting extends Model
{
    use CentralConnection;

    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    public const SECRETS = ['support_smtp_password'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::find($key);
        if (! $row || $row->value === null) {
            return $default;
        }
        return in_array($key, self::SECRETS, true) ? Crypt::decryptString($row->value) : $row->value;
    }

    public static function put(string $key, mixed $value): void
    {
        $stored = $value === null ? null : (string) $value;
        if ($stored !== null && in_array($key, self::SECRETS, true)) {
            $stored = Crypt::encryptString($stored);
        }
        static::updateOrCreate(['key' => $key], ['value' => $stored]);
    }
}