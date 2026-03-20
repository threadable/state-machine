<?php

declare(strict_types=1);
use Threadable\StateMachine\Contracts\StateableRepository;

return [
    'namespaces' => [
        'models' => 'App\\Models\\',
        'actions' => 'App\\Actions\\',
        'conditions' => 'App\\Conditions\\',
        'observers' => 'App\\Observers\\',
        'repositories' => 'App\\Repositories\\',
    ],

    'repositories' => [
        'suffix' => 'Repository',
        'contract' => StateableRepository::class,
    ],

    'routes' => [
        'enabled' => true,
        'prefix' => 'threadstate',
        'middleware' => ['api'],
    ],

    'authorization' => [
        'view_state_machine' => null,
        'view_object_transitions' => null,
        'transit' => null,
        'view_logs' => null,
        'manage_overrides' => null,
    ],
];
