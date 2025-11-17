<?php

namespace App\Console\Commands;

use App\Services\TestDispatcher;
use Illuminate\Console\Command;

class RunApiTestCommand extends Command
{
    protected $signature = 'api:run {--count=1 : Number of times to run the test}';

    protected $description = 'Run API performance tests comparing REST, GraphQL, and Integrated approaches';

    public function handle(): int
    {
        $this->info('Checking github limit...');
        $this->call('github:limit');

        $count = (int)$this->option('count');
        $this->info("Running API test with {$count} iterations");
        $this->line('Dispatching Tests...');

        (new TestDispatcher())->dispatchTests($count);

        $this->info("API test completed successfully");

        return self::SUCCESS;
    }
}
