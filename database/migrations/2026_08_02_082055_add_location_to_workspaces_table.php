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
        Schema::table('workspaces', function (Blueprint $table) {
            // Structured references used when country is Malaysia (chosen from
            // the lookup tables). For other countries these stay null and the
            // free-text `state` / `city` / `postcode` columns are used instead.
            $table->foreignId('state_id')->nullable()->after('state')->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->after('city')->constrained()->nullOnDelete();
            $table->foreignId('postcode_id')->nullable()->after('postcode')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropConstrainedForeignId('state_id');
            $table->dropConstrainedForeignId('city_id');
            $table->dropConstrainedForeignId('postcode_id');
        });
    }
};
