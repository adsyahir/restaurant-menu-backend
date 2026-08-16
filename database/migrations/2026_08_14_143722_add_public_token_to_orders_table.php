<?php

use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('public_token')->nullable()->unique()->after('order_number');
        });

        // Backfill any existing rows with a unique token. New rows get one
        // automatically from the Order model's creating hook.
        Order::query()->whereNull('public_token')->get()->each(function (Order $order) {
            $order->update(['public_token' => (string) Str::uuid()]);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('public_token');
        });
    }
};
