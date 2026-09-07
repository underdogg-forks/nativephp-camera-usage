<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nullable rather than InvoicePlane-v2's NOT NULL: this app's pre-existing
     * camera-capture flow creates expense categories outside of any Filament
     * tenant context, and going strictly NOT NULL here would force every one
     * of those call sites to fabricate a company. Filament resources always
     * run inside a tenant, so company_id is set there regardless.
     */
    public function up(): void
    {
        Schema::table('expense_categories', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expense_categories', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
