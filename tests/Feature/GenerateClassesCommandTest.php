<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Tests\Feature;

use Illuminate\Support\Facades\File;
use Threadable\StateMachine\Models\TsmState;
use Threadable\StateMachine\Models\TsmTransition;
use Threadable\StateMachine\Tests\TestCase;

class GenerateClassesCommandTest extends TestCase
{
    public function test_it_generates_application_scaffolding_in_laravel_friendly_locations(): void
    {
        $specPath = resource_path('statemachines/Order_1.php');

        File::ensureDirectoryExists(dirname($specPath));
        File::put($specPath, <<<'PHP'
<?php

return [
    'version' => 1,
    'stateable_class' => 'Order',
    'states' => [
        [
            'name' => 'Initial',
            'initial' => true,
        ],
        [
            'name' => 'Queued',
            'final' => true,
        ],
    ],
    'transitions' => [
        [
            'name' => 'Queue',
            'from' => 'Initial',
            'to' => 'Queued',
            'trigger' => 'COMMAND',
            'condition_type' => 'ACTION',
            'conditions' => [
                [
                    'name' => 'Always true',
                    'ref' => 'AlwaysTrueCondition',
                ],
            ],
            'actions' => [
                [
                    'name' => 'Record action',
                    'ref' => 'RecordAction',
                ],
            ],
        ],
    ],
];
PHP);

        $this->artisan('state-machine:generate', [
            'name' => 'Order',
            '--spec' => 1,
        ])->assertExitCode(0);

        $basePath = resource_path('statemachines/Order_1');

        $this->assertFileExists($basePath.'/app/Models/Order.php');
        $this->assertFileExists($basePath.'/app/Observers/OrderObserver.php');
        $this->assertFileExists($basePath.'/app/Conditions/AlwaysTrueCondition.php');
        $this->assertFileExists($basePath.'/app/Actions/RecordAction.php');
        $this->assertNotEmpty(File::glob($basePath.'/database/migrations/*_add_tsm_current_state_to_orders_table.php'));
        $this->assertFileExists($basePath.'/database/seeders/SeedOrdersStatemachine.php');
        $this->assertFileDoesNotExist($basePath.'/app/Models/TsmState.php');
    }

    public function test_it_fails_with_an_invalid_specification(): void
    {
        $specPath = resource_path('statemachines/Order_1.php');

        File::ensureDirectoryExists(dirname($specPath));
        File::put($specPath, <<<'PHP'
<?php

return [
    'version' => 1,
    'stateable_class' => 'Order',
    'states' => [
        ['name' => 'Initial'],
    ],
    'transitions' => [
        [
            'name' => 'Queue',
            'from' => 'Initial',
            'to' => 'Missing',
            'trigger' => 'AUTO',
            'condition_type' => 'ALWAYS',
        ],
    ],
];
PHP);

        $this->artisan('state-machine:generate', [
            'name' => 'Order',
            '--spec' => 1,
        ])->assertExitCode(1);
    }

    public function test_it_requires_force_to_replace_existing_generated_output(): void
    {
        $specPath = resource_path('statemachines/Order_1.php');
        $outputPath = resource_path('statemachines/Order_1');

        File::ensureDirectoryExists(dirname($specPath));
        File::put($specPath, <<<'PHP'
<?php

return [
    'version' => 1,
    'stateable_class' => 'Order',
    'states' => [
        ['name' => 'Initial', 'initial' => true],
    ],
    'transitions' => [],
];
PHP);

        File::ensureDirectoryExists($outputPath);

        $this->artisan('state-machine:generate', [
            'name' => 'Order',
            '--spec' => 1,
        ])->assertExitCode(1);
    }

    public function test_generated_seeder_can_be_run_multiple_times_without_creating_duplicates(): void
    {
        $specPath = resource_path('statemachines/Order_1.php');

        File::ensureDirectoryExists(dirname($specPath));
        File::put($specPath, <<<'PHP'
<?php

return [
    'version' => 1,
    'stateable_class' => 'Order',
    'states' => [
        ['name' => 'Initial', 'initial' => true],
        ['name' => 'Queued', 'final' => true],
    ],
    'transitions' => [
        [
            'name' => 'Queue',
            'from' => 'Initial',
            'to' => 'Queued',
            'trigger' => 'AUTO',
            'condition_type' => 'ALWAYS',
        ],
    ],
];
PHP);

        $this->artisan('state-machine:generate', [
            'name' => 'Order',
            '--spec' => 1,
        ])->assertExitCode(0);

        $seederPath = resource_path('statemachines/Order_1/database/seeders/SeedOrdersStatemachine.php');
        require_once $seederPath;

        $this->assertTrue(class_exists('SeedOrdersStatemachine'));

        $seederClass = 'SeedOrdersStatemachine';
        $seeder = new $seederClass;
        $seeder->run();
        $seeder->run();

        $this->assertSame(2, TsmState::query()->where('class', 'Order')->count());
        $this->assertSame(1, TsmTransition::query()->count());
    }
}
