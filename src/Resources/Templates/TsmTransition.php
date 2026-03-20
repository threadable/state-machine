<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Threadable\StateMachine\Components\StateableModelInterface;

class TsmTransition extends Model
{
    use SoftDeletes;

    protected $table = 'tsm_transitions';

    public function transition_config(): BelongsTo
    {
        return $this->belongsTo(TsmTransitionConfig::class, 'tsm_transition_configs_id', 'id');
    }

    public function getConfig(StateableModelInterface $stateableModelInterface): TsmTransitionConfig
    {
        foreach ($stateableModelInterface->getTransitionOverrideScopes() as $scope) {
            $customTransition = TsmCustomTransition::query()
                ->where('tsm_transitions_id', $this->id)
                ->where('scope_type', $scope['type'])
                ->where('scope_id', $scope['id'])
                ->first();

            if ($customTransition) {
                return $customTransition->transition_config;
            }
        }

        return $this->transition_config;
    }

    public function from_state(): BelongsTo
    {
        return $this->belongsTo(TsmState::class, 'from_states_id', 'id');
    }

    public function to_state(): BelongsTo
    {
        return $this->belongsTo(TsmState::class, 'to_states_id', 'id');
    }
}
