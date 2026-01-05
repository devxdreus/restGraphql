<?php

namespace App\Models;

use App\Enums\ApiStatusType;
use App\Enums\ApiType;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiTestResult extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (ApiTestResult $result) {
            if ($result->status === ApiStatusType::Failed) {
                $result->response_time = null;
                $result->payload_size = null;
                $result->mem_usage = null;
                $result->cpu_usage = null;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'response' => 'array',
            'api_type' => ApiType::class,
            'request_type' => ApiType::class,
            'status' => ApiStatusType::class,
            'cpu_usage' => 'decimal:2',
            'mem_usage' => 'decimal:2',
        ];
    }

    protected function memUsage(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value < 0 ? null : $value,
        );
    }

    #[Scope]
    public function success(Builder $query): void
    {
        $query->where('status', ApiStatusType::Success);
    }

    #[Scope]
    public function rest(Builder $query): void
    {
        $query->where('api_type', ApiType::Rest);
    }

    #[Scope]
    public function graphql(Builder $query): void
    {
        $query->where('api_type', ApiType::Graphql);
    }

    #[Scope]
    public function integrated(Builder $query): void
    {
        $query->where('api_type', ApiType::Integrated);
    }

    #[Scope]
    public function status(Builder $query, ApiStatusType $status): void
    {
        $query->where('status', $status);
    }

    public function apiTest(): BelongsTo
    {
        return $this->belongsTo(ApiTest::class);
    }

    public function queryModel(): BelongsTo
    {
        return $this->belongsTo(Query::class, 'query_id');
    }

    public function preset(): BelongsTo
    {
        return $this->belongsTo(QueryPreset::class, 'preset_id');
    }

    public static function successRate(?ApiType $apiType = null): float
    {
        $query = static::query();

        if ($apiType) {
            $query->where('api_type', $apiType);
        }

        $total = $query->count();

        if ($total === 0) {
            return 0.0;
        }

        $successful = (clone $query)->success()->count();

        return round(($successful / $total) * 100, 2);
    }
}
