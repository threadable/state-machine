<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TsmCustomTransition extends Model
{
    use SoftDeletes;

    protected $hidden = [
        'id',
        'tsm_transitions_id',
        'tsm_transition_configs_id',
        'scope_type',
        'scope_id',
    ];

    protected $table = 'tsm_custom_transitions';

    public function transition(): BelongsTo
    {
        return $this->belongsTo(TsmTransition::class, 'tsm_transitions_id', 'id');
    }

    public function transitionConfig(): BelongsTo
    {
        return $this->belongsTo(TsmTransitionConfig::class, 'tsm_transition_configs_id', 'id');
    }
}
