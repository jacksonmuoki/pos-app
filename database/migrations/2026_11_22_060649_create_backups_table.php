<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->string('path')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('status', 20)->default('running'); // running, completed, failed
            $table->text('error')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};