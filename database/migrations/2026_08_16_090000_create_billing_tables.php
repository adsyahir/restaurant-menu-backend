<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Subscription metadata lives on the workspace (1:1). No Stripe yet —
        // these are plain local columns the billing page reads/writes.
        Schema::table('workspaces', function (Blueprint $table) {
            $table->string('subscription_status')->default('active'); // active | past_due | trialing
            $table->date('renews_on')->nullable();
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('brand');            // Visa | Mastercard | Amex | ...
            $table->char('last4', 4);
            $table->unsignedTinyInteger('exp_month');  // 1-12
            $table->unsignedSmallInteger('exp_year');  // 2026...
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('workspace_id');
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('number');
            $table->date('issued_on');
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('due'); // paid | due | failed
            $table->timestamps();

            $table->unique(['workspace_id', 'number']);
            $table->index(['workspace_id', 'issued_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('payment_methods');

        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn(['subscription_status', 'renews_on']);
        });
    }
};
