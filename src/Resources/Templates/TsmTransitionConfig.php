<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TsmTransitionConfig extends Model
{
    use SoftDeletes;

    protected $table = 'tsm_transition_configs';

    public const string TRIGGER_AUTO = 'AUTO';

    const TRIGGER_COMMAND = 'COMMAND';

    public const string  CONDITION_ALWAYS = 'ALWAYS';

    public const string  CONDITION_ACTION = 'ACTION';

    public const string  CONDITION_OTHERWISE = 'OTHERWISE';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'condition' => 'array',
        'action' => 'array',
        'validation' => 'array',
    ];
}
