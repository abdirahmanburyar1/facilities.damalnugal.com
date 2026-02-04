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
        Schema::create('facility_monthly_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('facility_id');
            $table->string('report_period', 7); // Format: YYYY-MM (e.g., 2025-06)
            
            // Report metadata
            $table->enum('status', ['draft', 'submitted', 'reviewed', 'approved', 'rejected'])->default('draft');
            $table->text('comments')->nullable();
            
            // Workflow timestamps and user tracking
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            
            $table->timestamp('rejected_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            
            $table->timestamps();
            
            // Unique constraint to prevent duplicate reports
            $table->unique(['facility_id', 'report_period'], 'unique_facility_report');
            
            // Indexes for performance
            $table->index(['facility_id', 'report_period'], 'idx_facility_period');
            $table->index(['status'], 'idx_status');
            $table->index(['report_period'], 'idx_report_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facility_monthly_reports');
    }
};
