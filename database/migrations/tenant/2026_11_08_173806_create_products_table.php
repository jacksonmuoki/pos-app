
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Products already exists in the original POS migration.
        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('sku')->nullable()->unique();
                $table->string('barcode')->nullable()->index();
                $table->decimal('price', 12, 2)->default(0);
                $table->decimal('cost', 12, 2)->default(0);
                $table->integer('stock')->default(0);
                $table->integer('reorder_level')->default(0);
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }

        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'brand_id')) {
                $table->foreignId('brand_id')
                    ->nullable()
                    ->constrained('brands')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('products', 'description')) {
                $table->text('description')->nullable();
            }

            if (!Schema::hasColumn('products', 'wholesale_price')) {
                $table->decimal('wholesale_price', 12, 2)->nullable();
            }

            if (!Schema::hasColumn('products', 'unit')) {
                $table->string('unit', 40)->default('piece');
            }

            if (!Schema::hasColumn('products', 'product_type')) {
                $table->string('product_type', 30)->default('goods');
            }

            if (!Schema::hasColumn('products', 'image_path')) {
                $table->string('image_path')->nullable();
            }

            if (!Schema::hasColumn('products', 'track_stock')) {
                $table->boolean('track_stock')->default(true);
            }

            if (!Schema::hasColumn('products', 'is_taxable')) {
                $table->boolean('is_taxable')->default(false);
            }

            if (!Schema::hasColumn('products', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        // Keep the original products table and its POS data.
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'brand_id')) {
                $table->dropForeign(['brand_id']);
            }

            $columns = [
                'brand_id',
                'description',
                'wholesale_price',
                'unit',
                'product_type',
                'image_path',
                'track_stock',
                'is_taxable',
                'tax_rate',
            ];

            $existing = array_filter(
                $columns,
                fn ($column) => Schema::hasColumn('products', $column)
            );

            if ($existing) {
                $table->dropColumn($existing);
            }
        });
    }
};
