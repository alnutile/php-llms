<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->longText('body');
            $table->foreignIdFor(\App\Models\Chat::class);
            $table->string('tool_name')->nullable();
            $table->string('tool_id')->nullable();
            $table->json('args')->nullable();
            $table->string('role')->default(\App\Services\LlmServices\Messages\RoleEnum::User->value);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
