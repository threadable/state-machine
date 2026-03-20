<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeSpecification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'state-machine:spec {name : State machine name} {--spec= : Explicit specification version} {--force : Overwrite an existing specification file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a state machine specification.';

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        $templatePath = dirname(__DIR__).'/Resources/Specification/template.php';
        $statemachinesFolder = resource_path('statemachines');

        $this->info('Creating state machine specification');

        if (! File::exists($statemachinesFolder)) {
            $this->line('Creating state machine specification directory: '.$statemachinesFolder);
            File::ensureDirectoryExists($statemachinesFolder);
        }

        $version = $this->option('spec');
        $version = $version !== null ? (int) $version : 1;

        if ($version < 1) {
            $this->error('The specification version must be a positive integer.');

            return self::FAILURE;
        }

        while ($this->option('spec') === null && File::exists($this->specPath($statemachinesFolder, $name, $version))) {
            $version++;
        }

        $specfile = $this->specPath($statemachinesFolder, $name, $version);

        if (File::exists($specfile) && ! $this->option('force')) {
            $this->error("Specification already exists at {$specfile}. Use --force to overwrite it.");

            return self::FAILURE;
        }

        $contents = File::get($templatePath);
        $contents = str_replace('__VERSION_NUMBER__', (string) $version, $contents);
        $contents = str_replace('__STATEABLE_CLASS__', Str::studly($name), $contents);
        File::put($specfile, $contents);

        $this->info("Specification created at {$specfile}");

        return self::SUCCESS;
    }

    protected function specPath(string $folder, string $name, int $version): string
    {
        return $folder.'/'.Str::studly($name).'_'.$version.'.php';
    }
}
