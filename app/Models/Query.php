<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Query extends Model
{
    use HasFactory;

    protected function activePreset(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => $this->presets()->active()->first()
        );
    }

    public function presets(): HasMany
    {
        return $this->hasMany(QueryPreset::class, 'query_id');
    }

    public function testResults(): HasMany
    {
        return $this->hasMany(ApiTestResult::class, 'query_id');
    }

    public function average(string $column): float
    {
        return $this->testResults()->avg($column);
    }

    public function averageByColumnAndApiType(string $column, string $apiType): float
    {
        return $this->testResults()->where('api_type', $apiType)->avg($column);
    }

    public function averageByApiTypeAndQueryType(string $column, string $apiType, string $queryType): float
    {
        return $this->testResults()->where('api_type', $apiType)->where('query_type', $queryType)->avg($column);
    }
}
