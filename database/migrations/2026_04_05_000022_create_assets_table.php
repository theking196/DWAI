<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('session_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('canon_entry_id')->nullable()->constrained()->onDelete('set null');
            
            // File info
            $table->string('file_name');
            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->bigInteger('file_size')->nullable(); // bytes
            $table->string('extension', 20)->nullable();
            
            // Metadata
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->enum('type', ['image', 'audio', 'video', 'document', 'model', 'other'])->default('other');
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable(); // extra metadata
            
            // Organization
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
            
            $table->index(['project_id', 'type']);
            $table->index(['session_id']);
            $table->index(['canon_entry_id']);
            $table->index('file_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
