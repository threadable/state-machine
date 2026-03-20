<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Tests\Feature;

use Illuminate\Support\Facades\File;
use Threadable\StateMachine\Tests\TestCase;

class MakeSpecificationCommandTest extends TestCase
{
    public function test_it_creates_a_versioned_specification_file(): void
    {
        $path = resource_path('statemachines/Order_1.php');

        File::ensureDirectoryExists(dirname($path));

        $this->artisan('state-machine:spec', [
            'name' => 'Order',
        ])->assertExitCode(0);

        $this->assertFileExists($path);
        $spec = require $path;

        $this->assertSame(1, $spec['version']);
        $this->assertSame('Order', $spec['stateable_class']);
        $this->assertIsArray($spec['states']);
        $this->assertIsArray($spec['transitions']);
    }

    public function test_it_refuses_to_overwrite_existing_spec_without_force(): void
    {
        $path = resource_path('statemachines/Order_1.php');

        File::ensureDirectoryExists(dirname($path));
        File::put($path, <<<'PHP'
<?php

return ['version' => 1, 'stateable_class' => 'Order', 'states' => [], 'transitions' => []];
PHP);

        $this->artisan('state-machine:spec', [
            'name' => 'Order',
            '--spec' => 1,
        ])->assertExitCode(1);
    }

    public function test_it_overwrites_existing_spec_with_force(): void
    {
        $path = resource_path('statemachines/Order_1.php');

        File::ensureDirectoryExists(dirname($path));
        File::put($path, "<?php\n\nreturn ['version' => 999];\n");

        $this->artisan('state-machine:spec', [
            'name' => 'Order',
            '--spec' => 1,
            '--force' => true,
        ])->assertExitCode(0);

        $spec = require $path;

        $this->assertSame(1, $spec['version']);
        $this->assertSame('Order', $spec['stateable_class']);
    }
}
