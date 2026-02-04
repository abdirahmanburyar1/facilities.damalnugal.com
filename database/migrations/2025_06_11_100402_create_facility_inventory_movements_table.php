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
        Schema::create('facility_inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->enum('movement_type', ['facility_received', 'facility_issued']);
            $table->enum('source_type', ['transfer', 'order', 'dispense', 'moh_dispense']);
            $table->unsignedBigInteger('source_id'); // ID of the source record
            $table->unsignedBigInteger('source_item_id'); // ID of the source item
            $table->decimal('facility_received_quantity', 10, 2)->default(0);
            $table->decimal('facility_issued_quantity', 10, 2)->default(0);
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('barcode')->nullable();
            $table->string('uom')->nullable();
            $table->datetime('movement_date');
            $table->string('reference_number')->nullable(); // transfer number, order number, dispence number
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes for better performance
            $table->index(['facility_id', 'product_id']);
            $table->index(['movement_type', 'movement_date']);
            $table->index(['source_type', 'source_id']);
            $table->index('movement_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facility_inventory_movements');
    }
};
