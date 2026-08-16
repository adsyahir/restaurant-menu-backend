<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Subscription is per USER account (not per workspace). The plan caps how
     * many restaurants (workspaces) a user may own:
     *   free = 1 (with a time-limited trial), pro = 5, business = unlimited.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('plan')->default('free');                 // free | pro | business
            $table->string('subscription_status')->default('trialing'); // trialing | active | past_due | canceled
            $table->timestamp('trial_ends_at')->nullable();
            $table->date('renews_on')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['plan', 'subscription_status', 'trial_ends_at', 'renews_on']);
        });
    }
};
