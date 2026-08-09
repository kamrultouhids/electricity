<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Records how a row was created: entered by hand or brought in by a CSV import.
        // Existing rows predate the CSV importers, so the default backfills them to manual.
        Schema::table('customers', function (Blueprint $table) {
            $table->string('source', 20)->default('manual')->after('status');
        });

        Schema::table('meter_readings', function (Blueprint $table) {
            $table->string('source', 20)->default('manual')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('source');
        });

        Schema::table('meter_readings', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
