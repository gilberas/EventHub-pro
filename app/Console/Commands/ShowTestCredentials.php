<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Database\Seeders\UserSeeder;
use Illuminate\Console\Command;

class ShowTestCredentials extends Command
{
    protected $signature = 'users:test-credentials';

    protected $description = 'Print the seeded test user credentials table';

    public function handle(): int
    {
        $this->info('Test credentials:');
        $this->newLine();
        $this->line(UserSeeder::credentialsTable());

        return self::SUCCESS;
    }
}
