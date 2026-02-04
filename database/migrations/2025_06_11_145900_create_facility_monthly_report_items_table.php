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
        Schema::create('facility_monthly_report_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id');
            $table->unsignedBigInteger('product_id');
            
            // Report data fields matching LMIS format
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('stock_received', 15, 2)->default(0);
            $table->decimal('stock_issued', 15, 2)->default(0);
            $table->decimal('positive_adjustments', 15, 2)->default(0);
            $table->decimal('negative_adjustments', 15, 2)->default(0);
            $table->decimal('closing_balance', 15, 2)->default(0);
            $table->integer('stockout_days')->default(0);
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('parent_id')->references('id')->on('facility_monthly_reports')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            
            // Unique constraint to prevent duplicate items per report
            $table->unique(['parent_id', 'product_id'], 'unique_report_product');
            
            // Indexes for performance
            $table->index(['parent_id'], 'idx_parent_id');
            $table->index(['product_id'], 'idx_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facility_monthly_report_items');
    }
};
