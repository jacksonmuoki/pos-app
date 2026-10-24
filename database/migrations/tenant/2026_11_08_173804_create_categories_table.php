<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Categories already exists in the original POS migration.
        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->timestamps();
            });
        }

        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'slug')) {
                $table->string('slug', 180)->nullable()->unique();
            }

            if (!Schema::hasColumn('categories', 'description')) {
                $table->text('description')->nullable();
            }

            if (!Schema::hasColumn('categories', 'parent_id')) {
                $table->foreignId('parent_id')
                    ->nullable()
                    ->constrained('categories')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('categories', 'image_path')) {
                $table->string('image_path')->nullable();
            }

            if (!Schema::hasColumn('categories', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }

            if (!Schema::hasColumn('categories', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Populate unique slugs for existing categories.
        DB::table('categories')
            ->whereNull('slug')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->each(function ($category) {
                $slug = Str::slug($category->name) ?: 'category';

                DB::table('categories')
                    ->where('id', $category->id)
                    ->update([
                        'slug' => $slug . '-' . $category->id,
                    ]);
            });
    }

    public function down(): void
    {
        // Do not delete the original categories table.
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'parent_id')) {
                $table->dropForeign(['parent_id']);
            }

            $columns = [
                'parent_id',
                'slug',
                'description',
                'image_path',
                'is_active',
                'deleted_at',
            ];

            $existing = array_filter(
                $columns,
                fn ($column) => Schema::hasColumn('categories', $column)
            );

            if ($existing) {
                $table->dropColumn($existing);
            }
        });
    }
};
