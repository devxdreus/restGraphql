<?php

namespace App\Models;

use App\Enums\ApiStatusType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class ApiTest extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'status' => ApiStatusType::class,
        ];
    }

    // duration in second
    protected function duration(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                return $attributes['completed_at'] ?
                    Carbon::parse($attributes['created_at'])->diffInSeconds($attributes['completed_at']) :
                    null;
            }
        );
    }

    public function results(): HasMany
    {
        return $this->hasMany(ApiTestResult::class, 'api_test_id');
    }
}
