<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('expense_type')->default('one_time');
            $table->string('expense_status')->default('draft');
            $table->decimal('expense_amount', 10, 2);
            $table->string('currency')->default('USD');
            $table->string('receipt_path')->nullable();
            $table->text('description')->nullable();
            $table->date('expensed_at');
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('expense_categories')->onDelete('set null');
            $table->index(['user_id', 'expensed_at', 'expense_status']);
            $table->index(['category_id', 'customer_id', 'expense_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
