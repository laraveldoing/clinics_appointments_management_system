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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->enum('status', ['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'])
                ->default('scheduled');
            $table->text('reason')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['start_time', 'end_time'], 'idx_appt_dates');
            $table->index('status', 'idx_appt_status');
            $table->index('clinic_id', 'idx_appt_clinic');
            $table->index('patient_id', 'idx_appt_patient');
            $table->index('user_id', 'idx_appt_doctor');
            $table->index('deleted_at', 'idx_appt_deleted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};