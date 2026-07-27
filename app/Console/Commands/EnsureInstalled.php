<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Installer\Helpers\DatabaseManager;

class EnsureInstalled extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bagisto:ensure-installed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the full unattended Bagisto install on a fresh database, or just migrate if already installed. Safe to run on every deploy — intended for the platform pre-deploy hook.';

    /**
     * Execute the console command.
     */
    public function handle(DatabaseManager $databaseManager): int
    {
        if ($databaseManager->isInstalled()) {
            $this->info('Bagisto is already installed — running incremental migrations only.');

            $this->call('migrate', ['--force' => true]);

            return self::SUCCESS;
        }

        $this->warn('Bagisto is not installed yet — running the full unattended installer...');

        $this->call('bagisto:install', ['--no-interaction' => true]);

        return self::SUCCESS;
    }
}
