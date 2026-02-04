<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Back orders from orders (source_type = 'order') are not linked to a packing list,
     * so packing_list_id must be nullable.
     */
    public function up(): void
    {
        Schema::table('back_orders', function (Blueprint $table) {
            $table->dropForeign(['packing_list_id']);
        });

        DB::statement('ALTER TABLE back_orders MODIFY packing_list_id BIGINT UNSIGNED NULL');

        Schema::table('back_orders', function (Blueprint $table) {
            $table->foreign('packing_list_id')->references('id')->on('packing_lists')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('back_orders', function (Blueprint $table) {
            $table->dropForeign(['packing_list_id']);
        });

        DB::statement('ALTER TABLE back_orders MODIFY packing_list_id BIGINT UNSIGNED NOT NULL');

        Schema::table('back_orders', function (Blueprint $table) {
            $table->foreign('packing_list_id')->references('id')->on('packing_lists')->onDelete('cascade');
        });
    }
};
