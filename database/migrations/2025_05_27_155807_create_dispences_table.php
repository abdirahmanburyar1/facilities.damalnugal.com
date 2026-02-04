<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dispences', function (Blueprint $table) {
            $table->id();
            $table->string('dispence_number');
            $table->date('dispence_date');
            $table->string('patient_name')->nullable();
            $table->integer('patient_age')->nullable();
            $table->enum('patient_gender', ['male', 'female'])->nullable();
            $table->string('patient_phone')->nullable();
            $table->foreignId('facility_id')->constrained('facilities')->onDelete('cascade');
            $table->foreignId('dispenced_by')->constrained('users');
            $table->text('diagnosis')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        Schema::dropIfExists('dispences');
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }
};
