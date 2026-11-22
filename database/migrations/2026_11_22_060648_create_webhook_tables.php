<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_endpoints', function (Blueprint $table) {
            $table->id();
            $table->string('url', 500);
            $table->text('secret');                 // encrypted
            $table->json('events');                 // e.g. ["company.created","backup.failed"]
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_endpoint_id')->constrained()->cascadeOnDelete();
            $table->string('event', 80)->index();
            $table->json('payload');
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->text('response')->nullable();
            $table->boolean('success')->default(false)->index();
            $table->unsignedSmallInteger('attempts')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Drop the child table first because of the foreign key.
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhook_endpoints');
    }
};