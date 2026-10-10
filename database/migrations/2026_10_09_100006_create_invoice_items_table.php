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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description', 255);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
            $table->softDeletes();

            $table->index('invoice_id', 'idx_invoice_item_invoice');
            $table->index('deleted_at', 'idx_invoice_item_deleted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};