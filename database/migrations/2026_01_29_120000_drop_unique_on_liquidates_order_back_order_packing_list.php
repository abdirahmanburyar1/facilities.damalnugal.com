<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Drop unique indexes on liquidates so multiple rows can share same order_id / back_order_id / packing_list_id.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE liquidates DROP INDEX liquidates_order_id_unique');
        DB::statement('ALTER TABLE liquidates DROP INDEX liquidates_back_order_id_unique');
        DB::statement('ALTER TABLE liquidates DROP INDEX liquidates_packing_list_id_unique');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE liquidates ADD UNIQUE liquidates_order_id_unique (order_id)');
        DB::statement('ALTER TABLE liquidates ADD UNIQUE liquidates_back_order_id_unique (back_order_id)');
        DB::statement('ALTER TABLE liquidates ADD UNIQUE liquidates_packing_list_id_unique (packing_list_id)');
    }
};
