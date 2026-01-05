<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('api_test_results', function (Blueprint $table) {
            $table->integer('payload_size')->nullable()->change();
            $table->integer('response_time')->nullable()->change();
            $table->integer('mem_usage')->nullable()->change();
            $table->decimal('cpu_usage')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('api_test_results', function (Blueprint $table) {
            //
        });
    }
};
