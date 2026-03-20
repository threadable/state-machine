<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $tsm_transitions_id
 * @property int $tsm_transition_configs_id
 * @property string $scope_type
 * @property int $scope_id
 * @property-read TsmTransition $transition
 * @property-read TsmTransitionConfig $transitionConfig
 */
class TsmCustomTransition extends Model
{
    use SoftDeletes;

    protected $table = 'tsm_custom_transitions';

    protected $guarded = [];

    protected $hidden = [
        'id',
        'tsm_transitions_id',
        'tsm_transition_configs_id',
        'scope_type',
        'scope_id',
    ];

    /**
     * @return BelongsTo<TsmTransition, $this>
     */
    public function transition(): BelongsTo
    {
        return $this->belongsTo(TsmTransition::class, 'tsm_transitions_id', 'id');
    }

    /**
     * @return BelongsTo<TsmTransitionConfig, $this>
     */
    public function transitionConfig(): BelongsTo
    {
        return $this->belongsTo(TsmTransitionConfig::class, 'tsm_transition_configs_id', 'id');
    }

    public function getTransitionConfigAttribute(): TsmTransitionConfig
    {
        return $this->transitionConfig()->getResults();
    }
}
