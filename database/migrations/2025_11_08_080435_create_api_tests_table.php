<?php

use App\Enums\ApiStatusType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('api_tests', function (Blueprint $table) {
            $table->id();
            $table->string('count');
            $table->enum('status', ApiStatusType::values())->default(ApiStatusType::Processing->value);
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_tests');
    }
};
