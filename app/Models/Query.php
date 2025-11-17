<?php

namespace App\Models;

use App\Enums\QueryType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Query extends Model
{
    use HasFactory;

    protected $casts = [
        'type' => QueryType::class,
    ];

    protected function activePreset(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => $this->presets()->active()->first()
        );
    }

    protected function avgRestResponseTime(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => $this->testResults()->success()->rest()->avg('response_time')
        );
    }

    protected function avgGraphqlResponseTime(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => $this->testResults()->success()->graphql()->avg('response_time')
        );
    }

    protected function avgIntegratedResponseTime(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => $this->testResults()->success()->integrated()->avg('response_time')
        );
    }

    protected function avgRestPayloadSize(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => $this->testResults()->success()->rest()->avg('payload_size')
        );
    }

    protected function avgGraphqlPayloadSize(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => $this->testResults()->success()->graphql()->avg('payload_size')
        );
    }

    protected function avgIntegratedPayloadSize(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => $this->testResults()->success()->integrated()->avg('payload_size')
        );
    }

    protected function avgRestCpuUsage(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => $this->testResults()->success()->rest()->avg('cpu_usage')
        );
    }

    protected function avgGraphqlCpuUsage(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => $this->testResults()->success()->graphql()->avg('cpu_usage')
        );
    }

    protected function avgIntegratedCpuUsage(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => $this->testResults()->success()->integrated()->avg('cpu_usage')
        );
    }

    protected function avgRestMemUsage(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => $this->testResults()->success()->rest()->avg('mem_usage')
        );
    }

    protected function avgGraphqlMemUsage(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => $this->testResults()->success()->graphql()->avg('mem_usage')
        );
    }

    protected function avgIntegratedMemUsage(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => $this->testResults()->success()->integrated()->avg('mem_usage')
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

    public function average(string $column): ?float
    {
        return $this->testResults()->avg($column);
    }

    public function averageByColumnAndApiType(string $column, string $apiType): ?float
    {
        return $this->testResults()->where('api_type', $apiType)->avg($column);
    }

    public function averageByApiTypeAndQueryType(string $column, string $apiType, string $queryType): ?float
    {
        return $this->testResults()->where('api_type', $apiType)->where('query_type', $queryType)->avg($column);
    }
}
