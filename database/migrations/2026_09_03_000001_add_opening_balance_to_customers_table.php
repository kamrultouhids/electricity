<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // What an existing customer brought with them from the old system: the
        // meter's last reading and the balance still owed. Declared once at
        // onboarding; the meter reading and bill rows derived from them are what
        // billing and collection actually work against.
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('opening_reading', 12, 2)->nullable()->after('connection_date');
            $table->decimal('opening_due', 12, 2)->nullable()->after('opening_reading');
            // The last month the old system billed — the new system starts after it.
            $table->date('opening_as_of')->nullable()->after('opening_due');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['opening_reading', 'opening_due', 'opening_as_of']);
        });
    }
};
