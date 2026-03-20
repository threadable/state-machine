<?php

namespace __MODEL_NAMESPACE__;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Threadable\StateMachine\Components\StateableModelInterface;
use Threadable\StateMachine\Components\StateableModelTrait;

class __STATEABLE_CLASS__ extends Model implements StateableModelInterface
{
    use SoftDeletes, StateableModelTrait;

    /**
     * Appends.
     *
     * @var array
     */
    protected $appends = [
        'tsm_current_state_label',
    ];

    /**
     * @return array<int, array{type: string, id: int}>
     */
    public function getTransitionOverrideScopes(): array
    {
        return [];
    }
}
