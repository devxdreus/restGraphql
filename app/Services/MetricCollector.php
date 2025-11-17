<?php

namespace App\Services;

class MetricCollector
{
    public static function capture(callable $requestCallback): array
    {
        $startTime = microtime(true);
        $memoryBefore = memory_get_usage();
        $cpuBefore = self::getCpuTime();

        $response = $requestCallback();

        $memoryAfter = memory_get_usage();
        $cpuAfter = self::getCpuTime();
        $endTime = microtime(true);

        $responseTime = round(($endTime - $startTime) * 1000);
        $cpuTime = $cpuAfter - $cpuBefore;

        return [
            'response' => $response,
            'response_time' => $responseTime,
            'payload_size' => strlen($response->body()),
            'mem_usage' => $memoryAfter - $memoryBefore,
            'cpu_usage' => $responseTime > 0 ? round(($cpuTime / ($responseTime * 1000)) * 100, 2) : 0,
        ];
    }

    private static function getCpuTime(): int
    {
        $usage = getrusage();

        $userTime = ($usage['ru_utime.tv_sec'] * 1000000) + $usage['ru_utime.tv_usec'];
        $systemTime = ($usage['ru_stime.tv_sec'] * 1000000) + $usage['ru_stime.tv_usec'];

        return $userTime + $systemTime;
    }
}
