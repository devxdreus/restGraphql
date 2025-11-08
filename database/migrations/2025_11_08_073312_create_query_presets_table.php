<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('query_presets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('query_id');
            $table->string('name');
            $table->string('rest_query');
            $table->string('graphql_query');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('query_presets');
    }
};
