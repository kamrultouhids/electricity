<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            // Marks the carry-forward entry that opens a migrated customer's
            // ledger. It is not a billed month: no units, no charges, and it
            // never goes through BillCalculator.
            $table->boolean('is_opening')->default(false)->after('status');

            $table->index(['customer_id', 'is_opening']);
        });
    }

    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropIndex(['customer_id', 'is_opening']);
            $table->dropColumn('is_opening');
        });
    }
};
