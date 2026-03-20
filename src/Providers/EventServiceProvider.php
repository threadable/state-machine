<?php

namespace Threadable\StateMachine\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Threadable\StateMachine\Events\StmStateableObjectStateChangeEvent;
use Threadable\StateMachine\Listeners\TransitionLogger;

class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<string, array<int, class-string>>
     */
    protected $listen = [
        StmStateableObjectStateChangeEvent::class => [
            TransitionLogger::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();
    }
}
