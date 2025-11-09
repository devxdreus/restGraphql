<?php

namespace App\Models;

use App\Enums\ApiStatusType;
use App\Enums\ApiType;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiTestResult extends Model
{
    use HasFactory;

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

    #[Scope]
    public function success($query): void
    {
        $query->where('status', ApiStatusType::Success);
    }

    #[Scope]
    public function rest($query): void
    {
        $query->where('api_type', ApiType::Rest);
    }

    #[Scope]
    public function graphql($query): void
    {
        $query->where('api_type', ApiType::Graphql);
    }

    #[Scope]
    public function integrated($query): void
    {
        $query->where('api_type', ApiType::Integrated);
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
}
