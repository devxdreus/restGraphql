<?php

namespace App\Services;

use App\Models\QueryPreset;
use Illuminate\Support\Facades\Cache;

class CacheManager
{
    private const CACHE_KEY_PATTERN = 'api_query_%d_preset_%d_%s';

    public function getCachedResult(QueryPreset $query, string $type): ?array
    {
        $key = sprintf(self::CACHE_KEY_PATTERN, $query->query_id, $query->id, $type);
        return Cache::get($key);
    }

    public function putCachedResult(QueryPreset $query, string $type, array $value): bool
    {
        $key = sprintf(self::CACHE_KEY_PATTERN, $query->query_id, $query->id, $type);
        return Cache::put($key, $value);
    }
}
