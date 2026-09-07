<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->after('customer_id')->constrained('relations')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->after('vendor_id')->constrained('invoices')->nullOnDelete();
        });

        Schema::table('expenses', function (Blueprint $table): void {
            $table->foreign('customer_id')->references('id')->on('relations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropForeign(['customer_id']);
            $table->dropConstrainedForeignId('invoice_id');
            $table->dropConstrainedForeignId('vendor_id');
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
