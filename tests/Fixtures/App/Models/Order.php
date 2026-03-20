<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Threadable\StateMachine\Components\StateableModelInterface;
use Threadable\StateMachine\Components\StateableModelTrait;

/**
 * @property int $id
 * @property string|null $name
 * @property int|null $priority
 * @property int|null $organization_id
 * @property int|null $tsm_current_state_id
 * @property-read string $tsm_current_state_label
 */
class Order extends Model implements StateableModelInterface
{
    use StateableModelTrait;

    protected $table = 'orders';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'organization_id' => 'integer',
            'tsm_current_state_id' => 'integer',
        ];
    }

    protected $appends = [
        'tsm_current_state_label',
    ];

    public function getTransitionOverrideScopes(): array
    {
        $scopes = [];

        if ($this->exists) {
            $scopes[] = [
                'type' => 'order',
                'id' => (int) $this->getKey(),
            ];
        }

        if ($this->organization_id !== null) {
            $scopes[] = [
                'type' => 'organization',
                'id' => (int) $this->organization_id,
            ];
        }

        return $scopes;
    }
}
