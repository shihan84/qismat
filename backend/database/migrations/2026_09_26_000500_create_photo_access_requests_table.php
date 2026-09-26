<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photo_access_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->unique(['requester_id', 'owner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_access_requests');
    }
};
