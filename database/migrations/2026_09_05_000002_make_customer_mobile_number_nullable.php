<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mobile number is optional on the customer form, so the column has to
     * accept null. (meter_number was already nullable.)
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('mobile_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('mobile_number')->nullable(false)->change();
        });
    }
};
