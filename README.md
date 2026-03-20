# Threadable State Machine

Threadable State Machine is a Laravel state machine package for Eloquent models. It provides:

- package-owned state machine tables and internal models
- a trait and interface for stateful Eloquent models
- transition validation and action hooks
- Artisan commands for publishing migrations and generating application scaffolding from a specification file

## Requirements

- PHP 8.3+
- Laravel 11, 12, or 13

## Installation

Add the package to your application and allow Laravel package discovery to register the service provider.

Install the package:

```bash
composer require threadable/state-machine
```

## Database Setup

The package ships with migrations for:

- `tsm_states`
- `tsm_transition_configs`
- `tsm_transitions`
- `tsm_custom_transitions`
- `tsm_logs`

Publish and run them with the package command:

```bash
php artisan state-machine:install
```

If you only want to publish the migrations:

```bash
php artisan state-machine:install --publish-only
```

Or use standard Laravel publishing:

```bash
php artisan vendor:publish --tag=threadstate-migrations
php artisan migrate
```

Publish the package configuration if you need to change namespace or repository resolution conventions:

```bash
php artisan vendor:publish --tag=threadstate-config
```

The published config controls:

- application namespaces for generated models, actions, conditions, observers, and repositories
- repository contract expectations for loading stateable entities
- package route prefix and middleware
- optional authorization callbacks for package routes

## Model Setup

Your stateful model must:

- implement `Threadable\StateMachine\Components\StateableModelInterface`
- use `Threadable\StateMachine\Components\StateableModelTrait`
- have a nullable `tsm_current_state_id` foreign key column
- return ordered override scopes from `getTransitionOverrideScopes()` when scoped transition overrides are needed

Example:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Threadable\StateMachine\Components\StateableModelInterface;
use Threadable\StateMachine\Components\StateableModelTrait;

class Order extends Model implements StateableModelInterface
{
    use StateableModelTrait;

    protected $guarded = [];

    protected $appends = [
        'tsm_current_state_label',
    ];

    public function getTransitionOverrideScopes(): array
    {
        $scopes = [];

        if ($this->exists) {
            $scopes[] = ['type' => 'order', 'id' => (int) $this->getKey()];
        }

        if ($this->organization_id !== null) {
            $scopes[] = ['type' => 'organization', 'id' => (int) $this->organization_id];
        }

        return $scopes;
    }
}
```

Transition override scopes are evaluated in order. In the example above, an order-specific override wins over an organization-specific override, and both fall back to the default transition config.

## Repository Contract

Stateable entities are resolved through an application repository. Repositories should implement `Threadable\StateMachine\Contracts\StateableRepository`.

Example:

```php
<?php

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
```

## Specification Workflow

Create a specification file:

```bash
php artisan state-machine:spec Order
```

This writes a versioned PHP specification file to `resources/statemachines/Order_1.php`.

The generated file returns a PHP array with a flat structure:

- `version`
- `stateable_class`
- `states`
- `transitions`

Example:

```php
<?php

return [
    'version' => 1,
    'stateable_class' => 'Order',
    'states' => [
        [
            'name' => 'Pending',
            'initial' => true,
        ],
        [
            'name' => 'Approved',
            'final' => true,
        ],
        [
            'name' => 'Rejected',
            'final' => true,
        ],
    ],
    'transitions' => [
        [
            'name' => 'Approve',
            'from' => 'Pending',
            'to' => 'Approved',
            'trigger' => 'COMMAND',
            'condition_type' => 'ACTION',
            'conditions' => [
                [
                    'name' => 'Order is payable',
                    'ref' => 'OrderIsPayable',
                ],
            ],
            'actions' => [
                [
                    'name' => 'Send approval email',
                    'ref' => 'SendApprovalEmail',
                ],
            ],
            'validation' => [
                'rules' => [
                    'reason' => ['required', 'string'],
                ],
                'messages' => [
                    'reason.required' => 'A reason is required.',
                ],
            ],
        ],
        [
            'name' => 'Reject',
            'from' => 'Pending',
            'to' => 'Rejected',
            'trigger' => 'COMMAND',
            'condition_type' => 'OTHERWISE',
        ],
    ],
];
```

### Specification Keys

`version`

- integer version number for the specification file
- used by the generator to build versioned output folders such as `Order_1`

`stateable_class`

- the model name the state machine applies to
- this should match the stateable model class name, for example `Order`

`states`

- array of state definitions
- each state must have a unique `name`
- `initial` marks the starting state
- `final` marks an end state

`transitions`

- array of transitions between states
- each transition defines:
  - `name`: human-readable transition name, for example `Approve`
  - `from`: source state name
  - `to`: destination state name
  - `trigger`: when the transition is intended to run
  - `condition_type`: how availability is determined
  - optional `conditions`
  - optional `actions`
  - optional `validation`

### How Transitions Work

A transition moves a model from one state to another.

At runtime the package evaluates a transition in this order:

1. The model's current state is resolved.
2. Available transitions from that state are loaded.
3. The transition's condition logic is evaluated.
4. If the transition is executed through a request, validation rules are applied.
5. The model's state is updated.
6. Transition actions are executed.
7. State change events and logs are recorded.

### Triggers

The package supports these trigger values:

- `COMMAND`
  - the transition is intended to be executed explicitly, for example from a controller or API call
- `AUTO`
  - the transition can be executed automatically by `AutoTransitionService`

### Condition Types

The package supports three condition types:

`ALWAYS`

- the transition is always available from its source state
- no `conditions` array is needed

`ACTION`

- the transition is available only if every configured condition class returns `true`
- `conditions` is required

`OTHERWISE`

- fallback transition
- used only when no other transition from the same state is available
- useful for catch-all flows such as default rejection or default timeout behavior

### Conditions

Conditions decide whether a transition is currently available.

Each condition entry uses:

- `name`: descriptive label
- `ref`: condition class name

Example:

```php
'conditions' => [
    [
        'name' => 'Order is payable',
        'ref' => 'OrderIsPayable',
    ],
],
```

Condition classes are resolved from the configured conditions namespace and should extend `Threadable\StateMachine\Components\StateMachineBaseCondition`.

All configured conditions for an `ACTION` transition must pass for the transition to be available.

### Validation

Validation applies when a transition is executed with input data, typically through the HTTP transition endpoint.

Validation uses normal Laravel validator rules and messages.

Example:

```php
'validation' => [
    'rules' => [
        'reason' => ['required', 'string'],
        'approved_by' => ['required', 'integer'],
    ],
    'messages' => [
        'reason.required' => 'A reason is required.',
    ],
],
```

Validation does not decide whether a transition is available. That is the job of conditions.

The distinction is:

- conditions answer: "may this transition happen right now?"
- validation answers: "is the provided input payload valid for this transition?"

### Actions

Actions run after a successful transition.

Each action entry uses:

- `name`: descriptive label
- `ref`: action class name

Example:

```php
'actions' => [
    [
        'name' => 'Send approval email',
        'ref' => 'SendApprovalEmail',
    ],
],
```

Action classes are resolved from the configured actions namespace and should extend `Threadable\StateMachine\Components\StateMachineBaseAction`.

Typical uses for actions:

- dispatch a job
- send a notification
- call an application service
- update related records

### Practical Example

For an order approval flow:

- `Approve` can move `Pending -> Approved`
- it uses `condition_type = ACTION`
- `OrderIsPayable` decides if the order may be approved
- validation requires a `reason`
- `SendApprovalEmail` runs after the state changes

- `Reject` can move `Pending -> Rejected`
- it uses `condition_type = OTHERWISE`
- it becomes available only when no other transition from `Pending` is available

You can force a specific version or overwrite an existing file:

```bash
php artisan state-machine:spec Order --spec=2 --force
```

Generate application scaffolding from the specification:

```bash
php artisan state-machine:generate Order --spec=1
```

If scaffolding already exists for that version, use `--force` to replace it.

Generated files are written under:

- `resources/statemachines/Order_1/app/Models`
- `resources/statemachines/Order_1/app/Actions`
- `resources/statemachines/Order_1/app/Conditions`
- `resources/statemachines/Order_1/app/Observers`
- `resources/statemachines/Order_1/database/migrations`
- `resources/statemachines/Order_1/database/seeders`

The generator creates application-facing scaffolding only. The `tsm_*` models are provided by the package.

## Runtime Usage

```php
$order = Order::findOrFail($id);

$availableTransitions = $order->getAvailableTransitionsWithIdentifiers();

$order->transit('Approve');
```

Useful methods on stateable models:

- `getTsmState()`
- `getAvailableTransitions()`
- `getAvailableTransitionsWithIdentifiers()`
- `transitionExists(string $transition)`
- `transit(string $transition)`
- `getValidator(string $transition)`

## Transition Overrides

Transition overrides let you replace the default condition, action, or validation for a particular scope without changing the base state machine.

The package stores overrides in `tsm_custom_transitions` using:

- `scope_type`
- `scope_id`
- `tsm_transitions_id`

This supports patterns like:

- `organization:42`
- `order:100123`

For a multi-tenant orders system, that means one organization can override the `Approve` transition while every other organization continues using the default config.

Override precedence comes from `getTransitionOverrideScopes()` on the model. If you return:

```php
[
    ['type' => 'order', 'id' => 100123],
    ['type' => 'organization', 'id' => 42],
]
```

Then the package resolves config in this order:

1. order-specific override
2. organization-specific override
3. default transition config

You can manage overrides through the package routes:

```bash
GET    /threadstate/{stateableClass}/transitions/{transitionId}/overrides
POST   /threadstate/{stateableClass}/transitions/{transitionId}/overrides/{scopeType}/{scopeId}
DELETE /threadstate/{stateableClass}/transitions/{transitionId}/overrides/{scopeType}/{scopeId}
GET    /threadstate/{stateableClass}/{uuid}/transitions/{transitionId}/config
```

Override payloads use the same shape as transition configuration records:

```json
{
  "trigger": "COMMAND",
  "condition_type": "ALWAYS",
  "condition": [],
  "action": [],
  "validation": {
    "rules": [
      {"key": "reason", "value": ["required", "string"]}
    ],
    "messages": [
      {"key": "reason.required", "value": "Reason is required."}
    ]
  }
}
```

Example:

- create an organization override for transition `15`
  - `POST /threadstate/Order/transitions/15/overrides/organization/42`
- create a one-off order override
  - `POST /threadstate/Order/transitions/15/overrides/order/100123`
- inspect the effective transition config for order `100123`
  - `GET /threadstate/Order/100123/transitions/15/config`

## Authorization

Package routes support optional authorization callbacks through `config/threadstate.php`.

Available callbacks:

- `view_state_machine`
- `view_object_transitions`
- `transit`
- `view_logs`
- `manage_overrides`

Each callback receives:

1. the authenticated user
2. the current request
3. a context array with route-specific values

Example:

```php
'authorization' => [
    'manage_overrides' => static function ($user, $request, array $context): bool {
        return $user !== null && $user->can('manage-threadstate-overrides');
    },
],
```

For models that use automatic transitions, the package includes `Threadable\StateMachine\Components\AutoTransitionService`. Generated observers use this service so application observers can combine package-managed auto transitions with app-specific jobs or side effects.

## Testing

The package includes a Testbench-based test suite.

Run it with:

```bash
composer ci
```

If you only want to auto-format PHP files locally:

```bash
composer format
```

## Contributing

See [CONTRIBUTING.md](/Users/padraig/Projects/threadable/threadstate/CONTRIBUTING.md) for contribution expectations, local checks, and the pull request checklist.

## Changelog

See [CHANGELOG.md](/Users/padraig/Projects/threadable/threadstate/CHANGELOG.md) for release history.

## License

This package is open-sourced software licensed under the [MIT license](/Users/padraig/Projects/threadable/threadstate/LICENSE).

## Current Constraints

The package bootstrapping, migrations, commands, and internal models are package-owned, but some higher-level integration classes still assume host-application conventions:

- `StateMachineRunnerController` is a generic controller wrapper, not a published route package

Application-facing conventions are configurable through `config/threadstate.php`, including model, repository, action, and condition namespaces.
