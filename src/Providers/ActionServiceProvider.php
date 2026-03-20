<?php

namespace Threadable\StateMachine\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Threadable\StateMachine\Events\TransitionActionEvent;
use Threadable\StateMachine\Listeners\ActionListener;

class ActionServiceProvider extends ServiceProvider
{
    /**
     * @var array<string, array<int, class-string>>
     */
    protected $listen = [
        TransitionActionEvent::class => [
            ActionListener::class,
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
