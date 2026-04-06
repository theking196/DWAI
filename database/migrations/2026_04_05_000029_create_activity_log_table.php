<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('cascade');
            
            $table->string('event_type', 50);
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('entity_id')->nullable();
            
            $table->text('description');
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            $table->index(['project_id', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
            $table->index(['event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
