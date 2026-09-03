<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('expense_number')->after('id')->unique();
            $table->unsignedBigInteger('category_id')->after('user_id')->nullable();
            $table->unsignedBigInteger('customer_id')->after('category_id')->nullable();
            $table->string('expense_type')->after('currency')->default('one_time');
            $table->string('expense_status')->after('expense_type')->default('draft');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->renameColumn('amount', 'expense_amount');
            $table->renameColumn('expense_date', 'expensed_at');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreign('category_id')->references('id')->on('expense_categories')->onDelete('set null');
            $table->index(['category_id', 'customer_id', 'expense_type', 'expense_status']);
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropIndex(['category_id', 'customer_id', 'expense_type', 'expense_status']);
            $table->dropColumn(['expense_number', 'category_id', 'customer_id', 'expense_type', 'expense_status']);
            $table->renameColumn('expense_amount', 'amount');
            $table->renameColumn('expensed_at', 'expense_date');
        });
    }
};
