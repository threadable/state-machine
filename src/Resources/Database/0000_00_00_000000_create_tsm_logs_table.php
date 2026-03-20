<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTsmLogsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::beginTransaction();

        try {
            Schema::create('tsm_logs', function (Blueprint $table) {
                $table->increments('id');
                $table->bigInteger('users_id')->nullable();
                $table->bigInteger('stateable_id');
                $table->text('stateable_class');
                $table->json('stateable_data');
                $table->string('old_state');
                $table->string('new_state');
                $table->string('transition');
                $table->boolean('result');
                $table->timestamps();
            });

            DB::commit();

        } catch (Exception $e) {

            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::beginTransaction();

        try {
            Schema::dropIfExists('tsm_logs');

            DB::commit();

        } catch (Exception $e) {

            DB::rollBack();
            throw $e;
        }
    }
}
