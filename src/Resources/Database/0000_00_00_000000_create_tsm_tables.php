<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Threadable\StateMachine\Models\TsmTransitionConfig;

class CreateTsmTables extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::beginTransaction();

        try {
            Schema::create('tsm_states', function (Blueprint $table) {
                $table->bigInteger('id', true)->unsigned();
                $table->string('class');
                $table->string('name');
                $table->tinyInteger('initial')->default(0);
                $table->tinyInteger('final')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });

            Schema::create('tsm_transition_configs', function (Blueprint $table) {
                $table->bigInteger('id', true)->unsigned();
                $table->enum('trigger', [TsmTransitionConfig::TRIGGER_AUTO, TsmTransitionConfig::TRIGGER_COMMAND])->default(TsmTransitionConfig::TRIGGER_COMMAND);
                $table->enum('condition_type', [TsmTransitionConfig::CONDITION_ALWAYS, TsmTransitionConfig::CONDITION_ACTION, TsmTransitionConfig::CONDITION_OTHERWISE])->default(TsmTransitionConfig::CONDITION_ALWAYS);
                $table->json('condition')->nullable();
                $table->json('action')->nullable();
                $table->json('validation')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });

            Schema::create('tsm_transitions', function (Blueprint $table) {
                $table->bigInteger('id', true)->unsigned();
                $table->string('name');
                $table->bigInteger('from_states_id')->unsigned()->index('from_states_id_foreign');
                $table->bigInteger('to_states_id')->unsigned()->index('to_states_id_foreign');
                $table->bigInteger('tsm_transition_configs_id')->unsigned()->index('tsm_transition_configs_id_foreign');
                $table->timestamps();
                $table->foreign('from_states_id')->references('id')->on('tsm_states')->onUpdate('no action')->onDelete('no action');
                $table->foreign('to_states_id')->references('id')->on('tsm_states')->onUpdate('no action')->onDelete('no action');
                $table->foreign('tsm_transition_configs_id')->references('id')->on('tsm_transition_configs')->onUpdate('no action')->onDelete('no action');
                $table->softDeletes();
            });

            Schema::create('tsm_custom_transitions', function (Blueprint $table) {
                $table->bigInteger('id', true)->unsigned();
                $table->bigInteger('tsm_transitions_id')->unsigned()->index('tsm_transitions_id_foreign');
                $table->bigInteger('tsm_transition_configs_id')->unsigned()->index('tsm_transitions_config_id_foreign');
                $table->string('scope_type');
                $table->bigInteger('scope_id')->unsigned();
                $table->timestamps();
                $table->softDeletes();
                $table->foreign('tsm_transitions_id')->references('id')->on('tsm_transitions')->onUpdate('no action')->onDelete('no action');
                $table->foreign('tsm_transition_configs_id')->references('id')->on('tsm_transition_configs')->onUpdate('no action')->onDelete('no action');
                $table->unique(['tsm_transitions_id', 'scope_type', 'scope_id'], 'tsm_custom_transitions_scope_unique');
                $table->index(['scope_type', 'scope_id'], 'tsm_custom_transitions_scope_index');
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
            Schema::dropIfExists('tsm_custom_transitions');
            Schema::dropIfExists('tsm_transitions');
            Schema::dropIfExists('tsm_transition_configs');
            Schema::dropIfExists('tsm_states');
            DB::commit();
        } catch (Exception $e) {

            DB::rollBack();
            throw $e;
        }
    }
}
