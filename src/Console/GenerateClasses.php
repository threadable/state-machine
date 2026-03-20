<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Threadable\StateMachine\Support\StateMachineSpecification;
use Throwable;

class GenerateClasses extends Command
{
    protected $aliases = [
        'threadstate:generate',
        'threadable:statemachine:generate:classes',
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'state-machine:generate {name : State machine name} {--spec=1 : Specification version} {--force : Replace any existing generated scaffolding directory}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Threadable\'s State Machine Classes Generator';

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        $version = (int) $this->option('spec');

        /**
         * Processing specifications file
         */
        $statemachinesFolder = resource_path('statemachines');
        $statemachineSpec = $statemachinesFolder.'/'.Str::studly($name).'_'.$version.'.php';

        if (! File::exists($statemachineSpec)) {
            $this->error("No specification file found for {$name} version {$version}.");

            return self::FAILURE;
        }

        try {
            $spec = StateMachineSpecification::load($statemachineSpec);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ((int) $spec['version'] !== (int) $version) {
            $this->error("Version mismatch. Expected {$version}, found {$spec['version']}.");

            return self::FAILURE;
        }

        $stateable_class = $spec['stateable_class'];

        /**
         * Finished processing file
         */

        /**
         * Creating folders structure
         */
        $outputFolder = $statemachinesFolder.'/'.$stateable_class.'_'.$version;

        if (File::exists($outputFolder)) {
            if (! $this->option('force')) {
                $this->error("Generated scaffolding already exists at {$outputFolder}. Use --force to replace it.");

                return self::FAILURE;
            }

            File::deleteDirectory($outputFolder);
        }

        // Destination folder structure
        File::ensureDirectoryExists($outputFolder);
        File::put($outputFolder.'/.gitignore', "/*\n");

        $outputApp = $outputFolder.'/app';
        $outputModels = $this->namespacePath($outputApp, (string) config('threadstate.namespaces.models', 'App\\Models\\'));
        $outputActions = $this->namespacePath($outputApp, (string) config('threadstate.namespaces.actions', 'App\\Actions\\'));
        $outputConditions = $this->namespacePath($outputApp, (string) config('threadstate.namespaces.conditions', 'App\\Conditions\\'));
        $outputObservers = $this->namespacePath($outputApp, (string) config('threadstate.namespaces.observers', 'App\\Observers\\'));
        $outputDatabase = $outputFolder.'/database';
        $outputMigrations = $outputDatabase.'/migrations';
        $outputSeeders = $outputDatabase.'/seeders';

        foreach ([$outputApp, $outputModels, $outputActions, $outputConditions, $outputObservers, $outputDatabase, $outputMigrations, $outputSeeders] as $directory) {
            File::ensureDirectoryExists($directory);
        }

        $this->info("Folders structure created at {$outputFolder}");

        /**
         * Finished folders structure
         */

        /**
         * Classes generation
         */
        $templatesFolder = dirname(__DIR__).'/Resources/Templates';

        /**
         * Creating Stateable Class file
         */
        $destinationModel = $outputModels.'/'.$stateable_class.'.php';
        $this->copyTemplate(
            $templatesFolder.'/StateableClassTemplate.php',
            $destinationModel,
            [
                '__STATEABLE_CLASS__' => $stateable_class,
                '__MODEL_NAMESPACE__' => $this->trimNamespace((string) config('threadstate.namespaces.models', 'App\\Models\\')),
            ]
        );

        $destinationObserver = $outputObservers.'/'.$stateable_class.'Observer.php';
        $this->copyTemplate(
            $templatesFolder.'/ObserverTemplate.php',
            $destinationObserver,
            [
                '__STATEABLE_CLASS__' => $stateable_class.'Observer',
                '__OBSERVER_NAMESPACE__' => $this->trimNamespace((string) config('threadstate.namespaces.observers', 'App\\Observers\\')),
            ]
        );

        foreach ($this->uniquePhpRefs($spec['transitions'], 'conditions') as $conditionRef) {
            $destinationCondition = $outputConditions.'/'.$conditionRef.'.php';
            $this->copyTemplate(
                $templatesFolder.'/ConditionTemplate.php',
                $destinationCondition,
                [
                    '__CONDITION_NAME__' => $conditionRef,
                    '__CONDITION_NAMESPACE__' => $this->trimNamespace((string) config('threadstate.namespaces.conditions', 'App\\Conditions\\')),
                ]
            );
        }

        foreach ($this->uniquePhpRefs($spec['transitions'], 'actions') as $actionRef) {
            $destinationAction = $outputActions.'/'.$actionRef.'.php';
            $this->copyTemplate(
                $templatesFolder.'/ActionTemplate.php',
                $destinationAction,
                [
                    '__ACTION_NAME__' => $actionRef,
                    '__ACTION_NAMESPACE__' => $this->trimNamespace((string) config('threadstate.namespaces.actions', 'App\\Actions\\')),
                ]
            );
        }

        // Migration
        $migration_name = str_replace('__STATEABLE_CLASS__', Str::plural($stateable_class), 'AddTsmCurrentStateTo__STATEABLE_CLASS__Table');
        $stMigrationFile = $outputMigrations.'/'.date('Y_m_d_His_').Str::snake($migration_name).'.php';
        File::copy($templatesFolder.'/MigrationTemplate.php', $stMigrationFile);
        $migration_str = File::get($stMigrationFile);
        $migration_str = str_replace('__MIGRATION_NAME__', $migration_name, $migration_str);
        $migration_str = str_replace('__STATEABLE_CLASS_TABLE__', Str::plural(Str::snake($stateable_class)), $migration_str);
        File::put($stMigrationFile, $migration_str);

        $seeder_name = str_replace('__STATEABLE_CLASS__', Str::plural($stateable_class), 'Seed__STATEABLE_CLASS__Statemachine');
        $stSeederFile = $outputSeeders.'/'.$seeder_name.'.php';
        File::copy($templatesFolder.'/SeederTemplate.php', $stSeederFile);
        $seeder_str = File::get($stSeederFile);
        $seeder_str = str_replace('__SEEDER_NAME__', $seeder_name, $seeder_str);
        $seeder_str = str_replace('__STATEMACHINE_SPEC__', $statemachineSpec, $seeder_str);
        File::put($stSeederFile, $seeder_str);

        return self::SUCCESS;
    }

    /**
     * Copy a template and replace simple placeholder tokens.
     *
     * @param  array<string, string>  $replacements
     */
    protected function copyTemplate(string $source, string $destination, array $replacements = []): void
    {
        $contents = File::get($source);

        foreach ($replacements as $search => $replace) {
            $contents = str_replace($search, $replace, $contents);
        }

        File::put($destination, $contents);
    }

    protected function namespacePath(string $root, string $namespace): string
    {
        $segments = explode('\\', trim($namespace, '\\'));

        if ($segments[0] === 'App') {
            array_shift($segments);
        }

        return $root.($segments === [] ? '' : '/'.implode('/', $segments));
    }

    protected function trimNamespace(string $namespace): string
    {
        return trim($namespace, '\\');
    }

    /**
     * @param  array<int, array<string, mixed>>  $transitions
     * @return array<int, string>
     */
    protected function uniquePhpRefs(array $transitions, string $key): array
    {
        $refs = [];

        foreach ($transitions as $transition) {
            foreach (($transition[$key] ?? []) as $entry) {
                if (is_string($entry['ref'] ?? null) && $entry['ref'] !== '') {
                    $refs[] = $entry['ref'];
                }
            }
        }

        return array_values(array_unique($refs));
    }
}
