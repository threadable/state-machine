<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $class
 * @property string $name
 * @property bool $initial
 * @property bool $final
 * @property-read Collection<int, TsmTransition> $transitions
 */
class TsmState extends Model
{
    use SoftDeletes;

    protected $table = 'tsm_states';

    protected $guarded = [];

    /**
     * @return HasMany<TsmTransition, $this>
     */
    public function transitions(): HasMany
    {
        return $this->hasMany(TsmTransition::class, 'from_states_id', 'id');
    }
}
