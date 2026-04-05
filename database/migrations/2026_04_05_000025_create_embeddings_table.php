<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Entity type and ID
            $table->string('entity_type', 50);  // canon, reference, project, session
            $table->unsignedBigInteger('entity_id');
            
            // Embedding data
            $table->text('embedding_vector')->nullable();
            $table->integer('dimensions')->default(1536);
            $table->string('model', 100)->nullable();
            
            // Metadata
            $table->string('chunk_text', 2000)->nullable();
            $table->json('metadata')->nullable();
            
            // Status
            $table->enum('status', ['pending', 'processed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            
            $table->timestamps();
            
            $table->index(['entity_type', 'entity_id']);
            $table->index(['status']);
            $table->index(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('embeddings');
    }
};
