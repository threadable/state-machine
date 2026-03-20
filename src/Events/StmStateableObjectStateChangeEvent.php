<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Events;

use Illuminate\Database\Eloquent\Model;
use Threadable\StateMachine\Components\StateableModelInterface;
use Threadable\StateMachine\Models\TsmTransition;

class StmStateableObjectStateChangeEvent extends AbstractStateChangeEvent
{
    /** @var array<string, mixed> */
    public array $snapshot;

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public function __construct(public Model&StateableModelInterface $stateableObject, public TsmTransition $transition, public bool $result, array $snapshot)
    {
        parent::__construct($stateableObject);
        $this->snapshot = $snapshot;
    }
}
