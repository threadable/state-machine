<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TsmState extends Model
{
    use SoftDeletes;

    protected $table = 'tsm_states';

    public function transitions(): HasMany
    {
        return $this->hasMany(TsmTransition::class, 'from_states_id', 'id')->orderBy('id');
    }
}
