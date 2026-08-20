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
        Schema::create('bill_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained('bills')->cascadeOnDelete();
            $table->foreignId('meter_reading_id')->nullable()->constrained('meter_readings')->nullOnDelete();
            $table->decimal('old_current_reading', 12, 2)->default(0);
            $table->decimal('new_current_reading', 12, 2)->default(0);
            $table->decimal('old_units', 12, 2)->default(0);
            $table->decimal('new_units', 12, 2)->default(0);
            $table->decimal('old_total_amount', 12, 2)->default(0);
            $table->decimal('new_total_amount', 12, 2)->default(0);
            $table->decimal('old_due_amount', 12, 2)->default(0);
            $table->decimal('new_due_amount', 12, 2)->default(0);
            // A bill reprinted at a different amount needs a stated reason.
            $table->string('reason');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamps();

            $table->index('bill_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_revisions');
    }
};
