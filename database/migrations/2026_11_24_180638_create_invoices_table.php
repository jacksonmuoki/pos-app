
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->string('invoice_number')->unique();

            // Central tenant/company identifier
            $table->string('tenant_id')->index();

            // Optional subscription reference
            $table->foreignId('subscription_id')
                ->nullable()
                ->constrained('subscriptions')
                ->nullOnDelete();

            $table->date('issue_date');
            $table->date('due_date')->nullable();

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('balance_due', 12, 2)->default(0);

            $table->string('currency', 3)->default('KES');

            // draft, issued, partial, paid, overdue, cancelled
            $table->string('status')->default('draft');

            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['issue_date', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
