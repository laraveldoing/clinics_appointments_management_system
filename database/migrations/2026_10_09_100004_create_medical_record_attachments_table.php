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
        Schema::create('medical_record_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_record_id')->constrained()->cascadeOnDelete();
            $table->string('file_name', 255);
            $table->string('file_path', 255);
            $table->string('file_type', 100);
            $table->unsignedInteger('file_size');
            $table->timestamps();
            $table->softDeletes();

            $table->index('medical_record_id', 'idx_attachment_record');
            $table->index('deleted_at', 'idx_attachment_deleted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_record_attachments');
    }
};