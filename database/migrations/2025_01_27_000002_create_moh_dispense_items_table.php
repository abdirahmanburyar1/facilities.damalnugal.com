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
        Schema::create('moh_dispense_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('moh_dispense_id')->constrained('moh_dispenses')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->string('source')->nullable();
            $table->string('batch_no');
            $table->date('expiry_date');
            $table->integer('quantity');
            $table->date('dispense_date');
            $table->string('dispensed_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moh_dispense_items');
    }
};