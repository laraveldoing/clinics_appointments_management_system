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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->enum('status', ['pending', 'paid', 'cancelled', 'refunded'])->default('pending');
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('clinic_id', 'idx_invoice_clinic');
            $table->index('patient_id', 'idx_invoice_patient');
            $table->index('status', 'idx_invoice_status');
            $table->index('deleted_at', 'idx_invoice_deleted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};