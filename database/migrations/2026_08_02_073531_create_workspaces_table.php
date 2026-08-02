<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('emoji')->default('🍜');
            $table->string('cuisine')->nullable();

            // Address (international)
            $table->string('address')->nullable();
            $table->string('postcode', 16)->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();          // state / province / region
            $table->char('country_code', 2)->nullable();  // ISO 3166-1 alpha-2, e.g. MY, SG, US

            // Localization
            $table->char('currency', 3)->default('USD');  // ISO 4217, e.g. MYR, SGD, USD
            $table->string('timezone')->default('UTC');   // IANA tz, e.g. Asia/Kuala_Lumpur

            // Contact
            $table->string('phone')->nullable();          // store in E.164, e.g. +60123456789

            // Subscription plan (free | pro | business)
            $table->string('plan')->default('free');

            // The user who created / owns the workspace
            $table->foreignId('owner_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspaces');
    }
};
