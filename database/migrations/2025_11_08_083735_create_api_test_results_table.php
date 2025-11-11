<?php

use App\Enums\ApiStatusType;
use App\Enums\ApiType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('api_test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_test_id');
            $table->foreignId('query_id');
            $table->foreignId('preset_id');
            $table->enum('api_type', ApiType::values());
            $table->enum('request_type', ApiType::values())->nullable();
            $table->enum('status', ApiStatusType::values())->default(ApiStatusType::Processing->value);
            $table->json('response')->nullable();
            $table->integer('payload_size')->default(0);
            $table->integer('response_time')->default(0);
            $table->integer('mem_usage')->default(0);
            $table->decimal('cpu_usage')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_test_results');
    }
};
