<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('base_price', 10, 2)->default(0);
            $table->boolean('is_available')->default(true);
            $table->jsonb('dietary_tags')->nullable();
            $table->jsonb('variants')->nullable();
            $table->jsonb('add_ons')->nullable();
            $table->string('image_url')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
