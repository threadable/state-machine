<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Order;
use Threadable\StateMachine\Contracts\StateableRepository;

class OrderRepository implements StateableRepository
{
    public function getSingle(string $id): Order
    {
        return Order::query()->findOrFail((int) $id);
    }
}
