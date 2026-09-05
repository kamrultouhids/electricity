<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A revision may now also correct the reading it started from and the
     * balance carried into the bill, so both need their own before/after trail.
     */
    public function up(): void
    {
        Schema::table('bill_revisions', function (Blueprint $table) {
            $table->decimal('old_previous_reading', 12, 2)->default(0)->after('meter_reading_id');
            $table->decimal('new_previous_reading', 12, 2)->default(0)->after('old_previous_reading');
            $table->decimal('old_previous_outstanding', 12, 2)->default(0)->after('new_units');
            $table->decimal('new_previous_outstanding', 12, 2)->default(0)->after('old_previous_outstanding');
            $table->decimal('old_late_fee', 12, 2)->default(0)->after('new_previous_outstanding');
            $table->decimal('new_late_fee', 12, 2)->default(0)->after('old_late_fee');
        });
    }

    public function down(): void
    {
        Schema::table('bill_revisions', function (Blueprint $table) {
            $table->dropColumn([
                'old_previous_reading',
                'new_previous_reading',
                'old_previous_outstanding',
                'new_previous_outstanding',
                'old_late_fee',
                'new_late_fee',
            ]);
        });
    }
};
