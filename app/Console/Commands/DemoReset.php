<?php

namespace App\Console\Commands;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;

/**
 * Puts the public demo back to a known state.
 *
 * A demo that anyone can open is a demo anyone can empty, and a portfolio piece
 * showing three deleted clients and no projects is worse than no demo at all.
 */
class DemoReset extends Command
{
    protected $signature = 'demo:reset';

    protected $description = 'Wipe the database and reseed the demo data';

    public function handle(): int
    {
        if (app()->isProduction() && ! $this->option('no-interaction')) {
            $this->warn('This will delete every row in the database.');
        }

        // prohibitDestructiveCommands is enabled in production by
        // AppServiceProvider, so the demo database is the deliberate exception
        // and --force is what says so out loud.
        $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);

        $this->newLine();
        $this->info('Demo reset. Sign in with '.DatabaseSeeder::DEMO_EMAIL.' / '.DatabaseSeeder::DEMO_PASSWORD);

        return self::SUCCESS;
    }
}
