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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('meter_reading_id')->nullable()->constrained('meter_readings')->nullOnDelete();
            $table->date('billing_month');
            $table->decimal('units', 12, 2)->default(0);
            $table->decimal('per_unit_rate', 10, 2)->default(0);
            $table->decimal('energy_charge', 12, 2)->default(0);
            $table->decimal('fixed_charge', 12, 2)->default(0);
            $table->decimal('meter_rent', 12, 2)->default(0);
            $table->decimal('previous_outstanding', 12, 2)->default(0);
            $table->decimal('late_fee', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('due_amount', 12, 2)->default(0);
            $table->unsignedTinyInteger('status')->default(1); // 1=unpaid, 2=partial, 3=paid
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['customer_id', 'billing_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
