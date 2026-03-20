<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Threadable\StateMachine\Providers\StateMachineServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            StateMachineServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        File::deleteDirectory(resource_path('statemachines'));
        foreach (File::glob(database_path('migrations/*_create_tsm_*_table.php')) as $publishedMigration) {
            File::delete($publishedMigration);
        }

        foreach (File::glob(database_path('migrations/*_create_tsm_tables.php')) as $publishedMigration) {
            File::delete($publishedMigration);
        }

        $this->artisan('migrate')->run();

        if (! Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table): void {
                $table->id();
                $table->string('name')->nullable();
                $table->integer('priority')->nullable();
                $table->unsignedBigInteger('organization_id')->nullable();
                $table->unsignedBigInteger('tsm_current_state_id')->nullable();
                $table->timestamps();
            });
        }
    }
}
