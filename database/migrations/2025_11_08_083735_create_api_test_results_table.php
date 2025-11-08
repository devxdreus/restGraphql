<?php

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
            $table->json('response');
            $table->integer('payload');
            $table->decimal('cpu_usage');
            $table->decimal('mem_usage');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_test_results');
    }
};
