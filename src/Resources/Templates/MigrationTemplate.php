<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class __MIGRATION_NAME__ extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('__STATEABLE_CLASS_TABLE__', function (Blueprint $table) {
            $table->unsignedBigInteger('tsm_current_state_id')->nullable()->index();
            $table->foreign('tsm_current_state_id')
                ->references('id')
                ->on('tsm_states')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('__STATEABLE_CLASS_TABLE__', function (Blueprint $table) {
            $table->dropForeign(['tsm_current_state_id']);
            $table->dropColumn('tsm_current_state_id');
        });
    }
}
