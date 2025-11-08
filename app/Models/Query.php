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
}
