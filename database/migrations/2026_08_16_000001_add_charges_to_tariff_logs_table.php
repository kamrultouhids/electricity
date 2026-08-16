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
        Schema::table('tariff_logs', function (Blueprint $table) {
            $table->decimal('old_line_charge', 10, 2)->default(0)->after('new_rate');
            $table->decimal('new_line_charge', 10, 2)->default(0)->after('old_line_charge');
            $table->decimal('old_service_charge', 10, 2)->default(0)->after('new_line_charge');
            $table->decimal('new_service_charge', 10, 2)->default(0)->after('old_service_charge');
            $table->decimal('old_demand_charge', 10, 2)->default(0)->after('new_service_charge');
            $table->decimal('new_demand_charge', 10, 2)->default(0)->after('old_demand_charge');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tariff_logs', function (Blueprint $table) {
            $table->dropColumn([
                'old_line_charge',
                'new_line_charge',
                'old_service_charge',
                'new_service_charge',
                'old_demand_charge',
                'new_demand_charge',
            ]);
        });
    }
};
