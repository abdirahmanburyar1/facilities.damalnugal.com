<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_reorder_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('facility_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('amc', 15, 2)->default(0);
            $table->unsignedInteger('lead_time')->default(1); // days
            $table->decimal('reorder_level', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->unique(['facility_id', 'product_id'], 'facility_product_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_reorder_levels');
    }
};


