<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Tests\Feature;

use Illuminate\Support\Facades\File;
use Threadable\StateMachine\Tests\TestCase;

class InstallCommandTest extends TestCase
{
    public function test_it_supports_publish_only_mode(): void
    {
        $this->artisan('state-machine:install', [
            '--publish-only' => true,
        ])->assertExitCode(0);

        $publishedMigrations = File::glob(database_path('migrations/*_create_tsm_*'));

        $this->assertNotEmpty($publishedMigrations);
    }
}
