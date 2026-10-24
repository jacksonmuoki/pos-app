<?php

tenancy()->initialize(App\Models\Tenant::find('demo2'));

$catId = DB::table('categories')->where('name', 'Drinks')->value('id')
    ?? DB::table('categories')->insertGetId(['name' => 'Drinks', 'created_at' => now(), 'updated_at' => now()]);

foreach ([['Coca-Cola 500ml', 80, 'CC500'], ['Water 1L', 60, 'WTR1L'], ['Bread Loaf', 65, 'BRD01']] as [$n, $p, $s]) {
    DB::table('products')->updateOrInsert(['sku' => $s], [
        'category_id'   => $catId,
        'name'          => $n,
        'price'         => $p,
        'cost'          => $p * 0.7,
        'stock'         => 50,
        'reorder_level' => 10,
        'active'        => 1,
        'created_at'    => now(),
        'updated_at'    => now(),
    ]);
}

echo 'Products in demo2: ' . DB::table('products')->count();