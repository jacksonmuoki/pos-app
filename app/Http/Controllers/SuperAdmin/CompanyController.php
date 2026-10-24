<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CompanyProvisioner;
use App\Services\CompanyStats;
use App\Support\Fmt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class CompanyController extends Controller
{
    private const PLANS = ['free', 'basic', 'pro'];

    public function index(Request $request, CompanyStats $stats)
    {
        $tenants = Tenant::with('domains')->get();
        $all = $stats->all($tenants);

        $q = strtolower(trim((string) $request->query('q', '')));
        $status = $request->query('status', 'all');
        $sort = $request->query('sort', 'newest');

        $rows = $tenants->filter(function ($t) use ($q, $status) {
            if ($q !== '') {
                $hay = strtolower($t->id . ' ' . $t->name . ' ' . $t->contact_email . ' ' . $t->domains->pluck('domain')->implode(' '));
                if (! str_contains($hay, $q)) {
                    return false;
                }
            }
            if ($status === 'active' && $t->suspended) {
                return false;
            }
            if ($status === 'suspended' && ! $t->suspended) {
                return false;
            }

            return true;
        })->map(fn ($t) => ['tenant' => $t, 'stats' => $all[$t->getTenantKey()]]);

        $rows = match ($sort) {
            'name' => $rows->sortBy(fn ($r) => strtolower($r['tenant']->name ?: $r['tenant']->id)),
            'revenue' => $rows->sortByDesc(fn ($r) => $r['stats']['revenue']),
            'orders' => $rows->sortByDesc(fn ($r) => $r['stats']['orders']),
            default => $rows->sortByDesc(fn ($r) => $r['tenant']->created_at),
        };

        return view('super.companies.index', [
            'rows' => $rows->values(),
            'total' => $tenants->count(),
            'q' => $q, 'status' => $status, 'sort' => $sort,
        ]);
    }

    public function create()
    {
        return view('super.companies.create', ['plans' => self::PLANS]);
    }

    public function store(Request $request, CompanyProvisioner $provisioner)
    {
        $request->merge([
            'id' => strtolower(trim((string) $request->input('id'))),
            'domain' => strtolower(trim((string) $request->input('domain'))),
        ]);

        $data = $request->validate([
            'id' => ['required', 'regex:/^[a-z0-9]{2,30}$/', 'unique:tenants,id'],
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'regex:/^[a-z0-9]([a-z0-9.\-]*[a-z0-9])?$/', 'max:255', 'unique:domains,domain'],
            'plan' => ['required', Rule::in(self::PLANS)],
            'contact_email' => ['nullable', 'email'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email'],
            'admin_password' => ['required', 'string', 'min:8'],
        ]);

        try {
            $provisioner->create(
                [
                    'id' => $data['id'],
                    'name' => $data['name'],
                    'domain' => $data['domain'],
                    'plan' => $data['plan'],
                    'contact_email' => $data['contact_email'] ?? null,
                    'contact_phone' => $data['contact_phone'] ?? null,
                ],
                [
                    'name' => $data['admin_name'],
                    'email' => $data['admin_email'],
                    'password' => $data['admin_password'],
                ],
            );
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->withErrors(['id' => $e->getMessage()]);
        }

        ActivityLog::record('company.created', $data['id'], [
            'name' => $data['name'], 'domain' => $data['domain'], 'plan' => $data['plan'],
        ]);

        return redirect()->route('super.companies.show', $data['id'])
            ->with('status', "{$data['name']} is ready. Share the sign-in details with its admin.");
    }

    public function show(string $id, CompanyStats $stats)
    {
        $tenant = Tenant::with('domains')->findOrFail($id);
        $s = $stats->forTenant($tenant);

        $users = collect();
        $orders = collect();
        $topProducts = collect();

        if ($s['ok']) {
            try {
                [$users, $orders, $topProducts] = $tenant->run(function () {
                    return [
                        DB::table('users')->orderBy('id')->get(['id', 'name', 'email', 'role', 'active', 'created_at']),
                        DB::table('orders')->orderByDesc('id')->limit(8)->get(['receipt_no', 'total', 'status', 'created_at']),
                        DB::table('order_items')
                            ->select('product_name', DB::raw('SUM(quantity) as qty'), DB::raw('SUM(line_total) as amount'))
                            ->groupBy('product_name')->orderByDesc('amount')->limit(5)->get(),
                    ];
                });
            } catch (Throwable $e) {
                report($e);
            }
        }

        return view('super.companies.show', [
            'tenant' => $tenant,
            's' => $s,
            'users' => $users,
            'orders' => $orders,
            'topProducts' => $topProducts,
            'plans' => self::PLANS,
            'log' => ActivityLog::where('tenant_id', $id)->latest('id')->limit(10)->get(),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $tenant = Tenant::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'plan' => ['required', Rule::in(self::PLANS)],
            'contact_email' => ['nullable', 'email'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $tenant->update($data);
        ActivityLog::record('company.updated', $id, ['plan' => $data['plan']]);

        return back()->with('status', 'Company details saved.');
    }

    public function suspend(Request $request, string $id)
    {
        $tenant = Tenant::findOrFail($id);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        $tenant->update([
            'suspended' => true,
            'suspended_reason' => $data['reason'] ?? null,
            'suspended_at' => now()->toDateTimeString(),
        ]);
        ActivityLog::record('company.suspended', $id, ['reason' => $data['reason'] ?? null]);

        return back()->with('status', "{$tenant->name} is suspended. Its staff can no longer sign in.");
    }

    public function activate(string $id)
    {
        $tenant = Tenant::findOrFail($id);

        $tenant->update(['suspended' => false, 'suspended_reason' => null, 'suspended_at' => null]);
        ActivityLog::record('company.reactivated', $id);

        return back()->with('status', "{$tenant->name} is active again.");
    }

    public function destroy(Request $request, string $id)
    {
        $tenant = Tenant::findOrFail($id);

        $request->validate(['confirm' => ['required', Rule::in([$tenant->id])]], [
            'confirm.in' => 'Type the company id exactly to confirm deletion.',
        ]);

        try {
            $databaseName = $tenant->database()->getName();
            $name = $tenant->name;

            // Delete the records with model events off, so the package's own
            // "drop database" job does not run on top of ours.
            Tenant::withoutEvents(function () use ($tenant) {
                $tenant->domains()->delete();
                $tenant->delete();
            });

            // Then drop the database. IF EXISTS makes this safe if it is already gone.
            DB::connection(config('tenancy.database.central_connection'))
                ->statement('DROP DATABASE IF EXISTS `' . str_replace('`', '``', $databaseName) . '`');

            ActivityLog::record('company.deleted', $id, ['name' => $name]);
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['delete' => 'Company deletion failed: ' . $e->getMessage()]);
        }

        return redirect()->route('super.companies.index')->with('status', "$id and its database were deleted.");
    }

    public function migrate(string $id)
    {
        $tenant = Tenant::findOrFail($id);

        Artisan::call('tenants:migrate', ['--tenants' => [$tenant->getTenantKey()]]);
        ActivityLog::record('migrations.run', $id);

        return back()->with('status', 'Migrations ran for ' . $tenant->id . '.');
    }

    public function addDomain(Request $request, string $id)
    {
        $tenant = Tenant::findOrFail($id);
        $request->merge(['domain' => strtolower(trim((string) $request->input('domain')))]);

        $data = $request->validate([
            'domain' => ['required', 'regex:/^[a-z0-9]([a-z0-9.\-]*[a-z0-9])?$/', 'max:255', 'unique:domains,domain'],
        ]);

        $tenant->domains()->create(['domain' => $data['domain']]);
        ActivityLog::record('domain.added', $id, ['domain' => $data['domain']]);

        return back()->with('status', 'Domain added.');
    }

    public function removeDomain(string $id, int $domain)
    {
        $tenant = Tenant::with('domains')->findOrFail($id);
        $row = $tenant->domains->firstWhere('id', $domain);

        abort_unless($row, 404);

        if ($tenant->domains->count() < 2) {
            return back()->withErrors(['domain' => 'A company needs at least one domain.']);
        }

        $row->delete();
        ActivityLog::record('domain.removed', $id, ['domain' => $row->domain]);

        return back()->with('status', 'Domain removed.');
    }

    public function addUser(Request $request, string $id)
    {
        $tenant = Tenant::findOrFail($id);

        $data = $request->validate([
            'user_name' => ['required', 'string', 'max:255'],
            'user_email' => ['required', 'email'],
            'user_password' => ['required', 'string', 'min:8'],
            'user_role' => ['required', Rule::in(['admin', 'manager', 'cashier'])],
        ]);

        $created = $tenant->run(function () use ($data) {
            if (DB::table('users')->where('email', $data['user_email'])->exists()) {
                return false;
            }

            User::create([
                'name' => $data['user_name'],
                'email' => $data['user_email'],
                'password' => $data['user_password'],
                'role' => $data['user_role'],
            ]);

            return true;
        });

        if (! $created) {
            return back()->withInput()->withErrors(['user_email' => 'That email already exists in this company.']);
        }

        ActivityLog::record('user.added', $id, ['email' => $data['user_email'], 'role' => $data['user_role']]);

        return back()->with('status', 'Staff account created.');
    }

    public function toggleUser(string $id, int $user)
    {
        $tenant = Tenant::findOrFail($id);

        $active = $tenant->run(function () use ($user) {
            $row = DB::table('users')->where('id', $user)->first();
            if (! $row) {
                return null;
            }
            DB::table('users')->where('id', $user)->update(['active' => ! $row->active]);

            return ! $row->active;
        });

        abort_if($active === null, 404);
        ActivityLog::record($active ? 'user.activated' : 'user.deactivated', $id, ['user_id' => $user]);

        return back()->with('status', $active ? 'Account activated.' : 'Account deactivated.');
    }

    public function resetPassword(string $id, int $user)
    {
        $tenant = Tenant::findOrFail($id);
        $password = Str::password(12, symbols: false);

        $email = $tenant->run(function () use ($user, $password) {
            $row = DB::table('users')->where('id', $user)->first();
            if (! $row) {
                return null;
            }
            DB::table('users')->where('id', $user)->update(['password' => Hash::make($password), 'remember_token' => null]);

            return $row->email;
        });

        abort_if($email === null, 404);
        ActivityLog::record('user.password_reset', $id, ['email' => $email]);

        return back()->with('secret', ['for' => $email, 'password' => $password]);
    }

    public function impersonate(string $id, int $user)
    {
        $tenant = Tenant::with('domains')->findOrFail($id);
        $domain = $tenant->domains->first()?->domain;

        abort_unless($domain, 404);

        if ($tenant->suspended) {
            return back()->withErrors(['domain' => 'Reactivate the company before signing in as one of its users.']);
        }

        $ok = $tenant->run(fn () => DB::table('users')->where('id', $user)->where('active', true)->exists());
        abort_unless($ok, 404);

        // A signed link that only works on the company's own domain for two minutes.
        URL::forceRootUrl(Fmt::url($domain));
        try {
            $link = URL::temporarySignedRoute('tenant.impersonate', now()->addMinutes(2), ['user' => $user]);
        } finally {
            URL::forceRootUrl(null);
        }

        ActivityLog::record('user.impersonated', $id, ['user_id' => $user]);

        return redirect()->away($link);
    }
}