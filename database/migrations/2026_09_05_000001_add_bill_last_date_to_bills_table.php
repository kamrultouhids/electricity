<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The payment deadline printed on the bill (পরিশোধের শেষ তারিখ). Entered by
     * the operator when the bill is generated; nullable so bills raised before
     * this column existed fall back to the derived date.
     */
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->date('bill_last_date')->nullable()->after('billing_month');
        });
    }

    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn('bill_last_date');
        });
    }
};
