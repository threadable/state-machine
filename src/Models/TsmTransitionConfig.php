<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $trigger
 * @property string $condition_type
 * @property array<int, array{name?: string, ref: string}>|null $condition
 * @property array<int, array{name?: string, ref?: string}>|null $action
 * @property array{rules?: array<mixed>, messages?: array<mixed>}|null $validation
 */
class TsmTransitionConfig extends Model
{
    use SoftDeletes;

    protected $table = 'tsm_transition_configs';

    public const string TRIGGER_AUTO = 'AUTO';

    public const string TRIGGER_COMMAND = 'COMMAND';

    public const string CONDITION_ALWAYS = 'ALWAYS';

    public const string CONDITION_ACTION = 'ACTION';

    public const string CONDITION_OTHERWISE = 'OTHERWISE';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'condition' => 'array',
            'action' => 'array',
            'validation' => 'array',
        ];
    }
}
