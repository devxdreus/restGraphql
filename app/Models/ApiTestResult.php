<?php

namespace App\Models;

use App\Enums\ApiType;
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
            'cpu_usage' => 'decimal:2',
            'mem_usage' => 'decimal:2',
        ];
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
