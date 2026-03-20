<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Console;

use Illuminate\Console\Command;

class Install extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'state-machine:install {--publish-only : Publish package migrations without running them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install State Machine package migrations.';

    public function handle(): int
    {
        $this->info('Publishing package migrations');

        $this->call('vendor:publish', [
            '--provider' => 'Threadable\\StateMachine\\Providers\\StateMachineServiceProvider',
            '--tag' => 'state-machine-migrations',
        ]);

        if ($this->option('publish-only')) {
            $this->info('Published package migrations.');

            return self::SUCCESS;
        }

        $this->info('Running migrations');
        $this->call('migrate');

        return self::SUCCESS;
    }
}
