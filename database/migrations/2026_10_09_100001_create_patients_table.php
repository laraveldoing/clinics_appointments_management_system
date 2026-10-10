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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 150)->nullable();
            $table->string('phone', 50);
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->text('address')->nullable();
            $table->text('medical_history_summary')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('clinic_id', 'idx_patient_clinic');
            $table->index('phone', 'idx_patient_phone');
            $table->index('deleted_at', 'idx_patient_deleted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};