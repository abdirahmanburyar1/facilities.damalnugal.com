<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Differences from order back orders (create backorder modal) are tied to
     * inventory_allocations, not packing list items, so packing_list_item_id must be nullable.
     */
    public function up(): void
    {
        Schema::table('packing_list_differences', function (Blueprint $table) {
            $table->dropForeign(['packing_list_item_id']);
        });

        DB::statement('ALTER TABLE packing_list_differences MODIFY packing_list_item_id BIGINT UNSIGNED NULL');

        Schema::table('packing_list_differences', function (Blueprint $table) {
            $table->foreign('packing_list_item_id')->references('id')->on('packing_list_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packing_list_differences', function (Blueprint $table) {
            $table->dropForeign(['packing_list_item_id']);
        });

        DB::statement('ALTER TABLE packing_list_differences MODIFY packing_list_item_id BIGINT UNSIGNED NOT NULL');

        Schema::table('packing_list_differences', function (Blueprint $table) {
            $table->foreign('packing_list_item_id')->references('id')->on('packing_list_items')->onDelete('cascade');
        });
    }
};
