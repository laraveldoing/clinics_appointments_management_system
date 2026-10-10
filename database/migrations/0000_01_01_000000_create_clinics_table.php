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
        Schema::create('clinics', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->string('email', 150);
            $table->string('phone', 50)->nullable();
            $table->text('address')->nullable();
            $table->jsonb('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('deleted_at', 'idx_clinic_deleted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinics');
    }
};