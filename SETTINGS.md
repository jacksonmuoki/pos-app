# Company Settings and Tenant Linking

How per-company settings (currency, M-Pesa/PayHero, SMS, maintenance) are stored, resolved and used in the multi-company POS.

## 1. Where each setting lives

| Setting | Stored in | Edited by |
|---|---|---|
| Platform name, default currency and timezone, signup, trial days | Central `platform_settings` | Super admin |
| Platform SMS, SMTP and PayHero defaults | Central `integrations` (encrypted) | Super admin |
| Business name, currency, timezone, tax, receipt footer | Company `settings` table | Company admin, or super admin on their behalf |
| M-Pesa (PayHero) credentials and on/off switch | Company `settings` table (password encrypted) | Company admin or super admin |
| SMS on/off, sender ID, own API key | Company `settings` table (key encrypted) | Company admin or super admin |
| Maintenance mode and message | Company `settings`, or platform-wide in `platform_settings` | Super admin only |

The link to the tenant is the database itself. Each company's values sit in its own `tenant<id>` database, so nothing can leak between companies. The super admin reaches them with `$tenant->run(...)`, which switches the connection to that company, reads or writes the `settings` table, then switches back.

## 2. How values are resolved

- **Currency and timezone:** company value, else platform default.
- **M-Pesa/PayHero:** company credentials only. There is deliberately no fallback to the platform account, so one company's payments can never land in another's. If username, password or channel ID is missing, `CompanySettings::payhero()` returns `null`.
- **SMS:** works only when the company has `sms_enabled = 1`. API key is the company's own, else the platform SMS integration. Sender ID is the company's, else the platform's.
- **Maintenance:** blocks a company if either the platform switch or that company's switch is on. Payment callbacks (`payhero/callback*`) are exempt.

## 3. Using settings in tenant code

Always read through `App\Support\CompanySettings`, never from `.env`:

```php
use App\Support\CompanySettings;

// Money display (per-company currency)
CompanySettings::money($order->total);   // "KES 1,234.00" or "USD 1,234.00"

// Is M-Pesa usable right now?
$mpesaOn = CompanySettings::get('mpesa_enabled') === '1'
    && CompanySettings::payhero() !== null;

// STK push with THIS company's account
$creds = CompanySettings::payhero();     // ['username','password','channel_id'] or null

// SMS
if ($sms = CompanySettings::sms()) {
    // $sms['api_key'], $sms['sender_id'], $sms['provider']
}
```

In Blade: `@money($amount)`.

## 4. Files involved

- `app/Support/CompanySettings.php`: read, write, encrypt and resolve company settings.
- `app/Http/Controllers/CompanySettingsController.php`: company admin edit screen.
- `app/Http/Controllers/SuperAdmin/CompanySettingsController.php`: super admin edit of any company.
- `app/Http/Controllers/SuperAdmin/SettingsController.php`: platform settings and company overview.
- `app/Http/Middleware/CheckMaintenance.php` and `ApplyCompanyTimezone.php`: tenant middleware.
- `resources/views/settings/edit.blade.php`, `resources/views/partials/company-settings-fields.blade.php`, `resources/views/super/settings.blade.php`, `resources/views/super/company-settings.blade.php`.
- Tenant migrations: `ensure_settings_table`, `add_manage_settings_permission`.

## 5. Routes

- Company: `GET/PUT /settings` (`settings.edit`, `settings.update`), protected by the `manage settings` permission.
- Super admin: `GET/PUT /super/company-settings/{company}` (`super.company-settings.edit`, `super.company-settings.update`).

## 6. Security rules

- Credentials are encrypted with the `APP_KEY`. Never change `APP_KEY` after saving credentials, or they become unreadable.
- Blank secret fields keep the saved value. Secrets are never displayed back.
- The activity log records changed field names only, never values.
- M-Pesa cannot be enabled without complete credentials.
- Only the super admin can set maintenance mode.

## 7. Status

**Built and committed:** storage, helper, both edit screens, validation, encryption, maintenance and timezone middleware, sidebar links.

**Not yet connected:**

1. Cashier screen: show the M-Pesa button only when `$mpesaOn` is true and use `CompanySettings::payhero()` for the STK push.
2. PayHero callback route: must sit outside the `auth`, suspended and maintenance middleware, verify the payment with PayHero's API, and be safe to run twice.
3. SMS sending service: nothing calls `CompanySettings::sms()` yet.
4. Currency in older views: replace hard-coded "KES" or `number_format` with `@money(...)`.
5. Run `php artisan tenants:migrate` so every company has the `manage settings` permission.

## 8. Verification checklist

1. Set `acme` to KES with PayHero details and `zawadi` to USD with different details.
2. Open `/settings` on each company: each sees only its own values, password fields blank.
3. Confirm `@money(100)` shows a different currency per company.
4. Put only `acme` in maintenance from the super admin: `zawadi` keeps working.
5. Check encryption: `SELECT key, LEFT(value,30) FROM settings WHERE key LIKE '%password%';` shows ciphertext.