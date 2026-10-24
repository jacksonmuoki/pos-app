<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class CompanyStats
{
    /** Per-request cache so a page that asks twice does not query twice. */
    private static array $cache = [];

    /** @return array<string, array> keyed by tenant id */
    public function all(Collection $tenants): array
    {
        $out = [];

        foreach ($tenants as $tenant) {
            $out[$tenant->getTenantKey()] = $this->forTenant($tenant);
        }

        return $out;
    }

    public function forTenant(Tenant $tenant): array
    {
        $key = (string) $tenant->getTenantKey();

        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        try {
            $data = $tenant->run(function () {
                return [
                    'users' => DB::table('users')->count(),
                    'products' => DB::table('products')->count(),
                    'low_stock' => DB::table('products')->where('active', true)->whereColumn('stock', '<=', 'reorder_level')->count(),
                    'orders' => DB::table('orders')->count(),
                    'revenue' => (float) DB::table('orders')->where('status', 'paid')->sum('total'),
                    'today' => (float) DB::table('orders')->where('status', 'paid')->where('created_at', '>=', now()->startOfDay())->sum('total'),
                    'last_order' => DB::table('orders')->max('created_at'),
                ];
            });
            $data['ok'] = true;
            $data['error'] = null;
        } catch (Throwable $e) {
            $data = [
                'users' => 0, 'products' => 0, 'low_stock' => 0, 'orders' => 0,
                'revenue' => 0.0, 'today' => 0.0, 'last_order' => null,
                'ok' => false, 'error' => $e->getMessage(),
            ];
        }

        $data['db_size'] = $this->dbSize($tenant);

        return self::$cache[$key] = $data;
    }

    public function dbSize(Tenant $tenant): int
    {
        try {
            $row = DB::connection(config('tenancy.database.central_connection'))->selectOne(
                'SELECT COALESCE(SUM(data_length + index_length), 0) AS size FROM information_schema.tables WHERE table_schema = ?',
                [$tenant->database()->getName()]
            );

            return (int) ($row->size ?? 0);
        } catch (Throwable) {
            return 0;
        }
    }
}