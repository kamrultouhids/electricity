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
            $table->decimal('line_charge', 10, 2)->default(0)->after('per_unit_rate');
            $table->decimal('service_charge', 10, 2)->default(0)->after('line_charge');
            $table->decimal('demand_charge', 10, 2)->default(0)->after('service_charge');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            $table->dropColumn(['line_charge', 'service_charge', 'demand_charge']);
        });
    }
};
