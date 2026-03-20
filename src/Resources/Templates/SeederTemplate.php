<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Threadable\StateMachine\Models\TsmState;
use Threadable\StateMachine\Models\TsmTransition;
use Threadable\StateMachine\Models\TsmTransitionConfig;

class __SEEDER_NAME__ extends Seeder
{
    public function run(): void
    {
        $statemachineSpec = '__STATEMACHINE_SPEC__';
        $spec = require $statemachineSpec;
        $states = [];
        DB::beginTransaction();
        try {
            $existingStateIds = TsmState::query()->where('class', $spec['stateable_class'])->pluck('id');
            $existingTransitionConfigIds = TsmTransition::query()
                ->whereIn('from_states_id', $existingStateIds)
                ->pluck('tsm_transition_configs_id');

            TsmTransition::query()->whereIn('from_states_id', $existingStateIds)->delete();
            TsmTransitionConfig::query()->whereIn('id', $existingTransitionConfigIds)->delete();

            TsmState::query()->where('class', $spec['stateable_class'])->delete();

            foreach ($spec['states'] as $stateDefinition) {
                $state = TsmState::query()->create([
                    'class' => $spec['stateable_class'],
                    'name' => $stateDefinition['name'],
                    'initial' => (bool) ($stateDefinition['initial'] ?? false),
                    'final' => (bool) ($stateDefinition['final'] ?? false),
                ]);

                $states[$stateDefinition['name']] = $state->id;
            }

            foreach ($spec['transitions'] as $transitionDefinition) {
                $transitionConfig = TsmTransitionConfig::query()->create([
                    'trigger' => $transitionDefinition['trigger'],
                    'condition_type' => $transitionDefinition['condition_type'],
                    'condition' => $transitionDefinition['conditions'] ?? null,
                    'action' => $transitionDefinition['actions'] ?? null,
                    'validation' => $transitionDefinition['validation'] ?? null,
                ]);

                TsmTransition::query()->create([
                    'name' => $transitionDefinition['name'],
                    'from_states_id' => $states[$transitionDefinition['from']],
                    'to_states_id' => $states[$transitionDefinition['to']],
                    'tsm_transition_configs_id' => $transitionConfig->id,
                ]);
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
