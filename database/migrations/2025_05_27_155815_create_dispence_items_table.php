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
        Schema::create('dispence_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispence_id')->constrained('dispences')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->integer('dose');
            $table->integer('frequency');
            $table->integer('duration');
            $table->integer('quantity');
            $table->string('barcode')->nullable();
            $table->string('batch_number');
            $table->date('expiry_date');
            $table->string('uom');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispence_items');
    }
};
