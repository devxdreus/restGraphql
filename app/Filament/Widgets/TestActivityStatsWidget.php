<?php

namespace App\Filament\Widgets;

use App\Models\ApiTest;
use App\Models\ApiTestResult;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TestActivityStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalTests = ApiTest::count();
        $completedTests = ApiTest::whereNotNull('completed_at')->count();
        $runningTests = ApiTest::whereNull('completed_at')->count();

        // Calculate average test duration
        $avgDuration = ApiTest::whereNotNull('completed_at')
            ->get()
            ->avg('duration');

        // Tests in last 7 days
        $testsLast7Days = ApiTest::where('created_at', '>=', Carbon::now()->subDays(7))->count();

        // Tests in last 30 days
        $testsLast30Days = ApiTest::where('created_at', '>=', Carbon::now()->subDays(30))->count();

        // Latest test
        $latestTest = ApiTest::latest()->first();
        $latestTestStatus = $latestTest?->status?->value ?? 'N/A';

        return [
            Stat::make('Total API Tests', $totalTests)
                ->description($completedTests . ' completed, ' . $runningTests . ' running')
                ->descriptionIcon('heroicon-o-beaker')
                ->color('primary'),

            Stat::make('Average Test Duration', $avgDuration ? number_format($avgDuration, 2) . 's' : 'N/A')
                ->description('Average time per test')
                ->descriptionIcon('heroicon-o-clock')
                ->color('info'),

            Stat::make('Latest Test Status', ucfirst($latestTestStatus))
                ->description('Test ID: ' . ($latestTest?->id ?? 'N/A'))
                ->descriptionIcon($latestTestStatus === 'success' ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                ->color($latestTestStatus === 'success' ? 'success' : 'danger'),

            Stat::make('Tests (Last 7 Days)', $testsLast7Days)
                ->description($testsLast30Days . ' tests in last 30 days')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('warning'),
        ];
    }
}
