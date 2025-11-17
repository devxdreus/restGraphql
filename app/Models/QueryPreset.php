<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QueryPreset extends Model
{
    use HasFactory;

    protected function queryName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->queryModel->name,
        );
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function queryModel(): BelongsTo
    {
        return $this->belongsTo(Query::class, 'query_id');
    }

    public function testResults(): HasMany
    {
        return $this->hasMany(ApiTestResult::class, 'preset_id');
    }
}
