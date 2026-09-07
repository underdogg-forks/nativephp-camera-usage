<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('expense_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('product_units')->nullOnDelete();
            $table->string('item_name')->nullable();
            $table->decimal('quantity', 20, 4)->default(1.00);
            $table->decimal('price', 20, 4)->default(0.00);
            $table->decimal('discount', 20, 4)->nullable()->default(0.00);
            $table->decimal('subtotal', 20, 4)->default(0.00);
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->foreignId('tax_rate_2_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->decimal('tax_1', 20, 4)->nullable()->default(0.00);
            $table->decimal('tax_2', 20, 4)->nullable()->default(0.00);
            $table->decimal('tax_total', 20, 4)->nullable()->default(0.00);
            $table->decimal('total', 20, 4)->nullable()->default(0.00);
            $table->unsignedMediumInteger('display_order')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_items');
    }
};
