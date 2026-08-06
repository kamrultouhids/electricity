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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('serial_no')->nullable();
            $table->text('photo')->nullable();
            $table->string('name');
            $table->string('father_or_husband_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('mobile_number');
            $table->text('address');
            $table->text('educational_qualification')->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->text('occupation')->nullable();
            $table->text('religion')->nullable();
            $table->string('national_id')->nullable();
            $table->text('guardian_name')->nullable();
            $table->text('guardian_relationship')->nullable();
            $table->text('guardian_address')->nullable();
            $table->string('meter_number')->nullable();
            $table->string('connection_type')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
