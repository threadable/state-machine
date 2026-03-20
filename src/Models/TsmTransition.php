<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Threadable\StateMachine\Components\StateableModelInterface;

/**
 * @property int $id
 * @property string $name
 * @property int $from_states_id
 * @property int $to_states_id
 * @property int $tsm_transition_configs_id
 * @property-read TsmTransitionConfig $transitionConfig
 * @property-read TsmState $from_state
 * @property-read TsmState $to_state
 */
class TsmTransition extends Model
{
    use SoftDeletes;

    protected $table = 'tsm_transitions';

    protected $guarded = [];

    /**
     * @return BelongsTo<TsmTransitionConfig, $this>
     */
    public function transitionConfig(): BelongsTo
    {
        return $this->belongsTo(TsmTransitionConfig::class, 'tsm_transition_configs_id', 'id');
    }

    public function getConfig(StateableModelInterface $stateableModelInterface): TsmTransitionConfig
    {
        return $this->getConfigForScopes($stateableModelInterface->getTransitionOverrideScopes());
    }

    /**
     * @param  array<int, array{type: string, id: int}>  $scopes
     */
    public function getConfigForScopes(array $scopes): TsmTransitionConfig
    {
        foreach ($scopes as $scope) {
            $customTransition = TsmCustomTransition::query()
                ->where('tsm_transitions_id', $this->id)
                ->where('scope_type', $scope['type'])
                ->where('scope_id', $scope['id'])
                ->first();

            if ($customTransition !== null) {
                return $customTransition->transitionConfig;
            }
        }

        return $this->transitionConfig;
    }

    /**
     * @return BelongsTo<TsmState, $this>
     */
    public function fromState(): BelongsTo
    {
        return $this->belongsTo(TsmState::class, 'from_states_id', 'id');
    }

    /**
     * @return BelongsTo<TsmState, $this>
     */
    public function toState(): BelongsTo
    {
        return $this->belongsTo(TsmState::class, 'to_states_id', 'id');
    }

    public function getFromStateAttribute(): TsmState
    {
        return $this->fromState()->getResults();
    }

    public function getToStateAttribute(): TsmState
    {
        return $this->toState()->getResults();
    }
}
