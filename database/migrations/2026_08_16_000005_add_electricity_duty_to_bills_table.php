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
        Schema::table('bills', function (Blueprint $table) {
            // The rate is snapshotted alongside the amount it produced, the same
            // way per_unit_rate sits next to energy_charge.
            $table->decimal('electricity_duty_rate', 5, 2)->default(0)->after('demand_charge');
            $table->decimal('electricity_duty', 12, 2)->default(0)->after('electricity_duty_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn(['electricity_duty_rate', 'electricity_duty']);
        });
    }
};
