<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Contracts;

use Illuminate\Database\Eloquent\Model;
use Threadable\StateMachine\Components\StateableModelInterface;

interface StateableRepository
{
    public function getSingle(string $id): Model&StateableModelInterface;
}
