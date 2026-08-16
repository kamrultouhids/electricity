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
        Schema::table('tariffs', function (Blueprint $table) {
            // Percentage, not an amount — charged on the energy charge.
            $table->decimal('electricity_duty', 5, 2)->default(0)->after('demand_charge');
        });

        Schema::table('tariff_logs', function (Blueprint $table) {
            $table->decimal('old_electricity_duty', 5, 2)->default(0)->after('new_demand_charge');
            $table->decimal('new_electricity_duty', 5, 2)->default(0)->after('old_electricity_duty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            $table->dropColumn('electricity_duty');
        });

        Schema::table('tariff_logs', function (Blueprint $table) {
            $table->dropColumn(['old_electricity_duty', 'new_electricity_duty']);
        });
    }
};
