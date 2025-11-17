<?php

namespace App\Console\Commands;

use App\Services\ApiTestService;
use Illuminate\Console\Command;

class RunApiTestCommand extends Command
{
    protected $signature = 'api:run {--count=1 : Number of times to run the test}';

    protected $description = 'Run API performance tests comparing REST, GraphQL, and Integrated approaches';

    public function handle(): int
    {
        $count = (int)$this->option('count');
        $this->info("Running API test with {$count} iterations");

        $this->call('github:limit');

        $this->line('Dispatching Tests...');

        ApiTestService::make()->dispatchTests($count);

        $this->info("API test completed successfully");

        return self::SUCCESS;
    }

}
