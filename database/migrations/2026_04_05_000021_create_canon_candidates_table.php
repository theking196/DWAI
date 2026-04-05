<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canon_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('session_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('source_output_id')->nullable()->nullable();
            
            $table->string('title');
            $table->string('type'); // character, location, lore, rule, timeline_event, note
            $table->text('content')->nullable();
            $table->text('ai_reasoning')->nullable();
            $table->json('tags')->nullable();
            $table->string('importance')->default('none');
            
            $table->enum('status', ['pending', 'approved', 'rejected', 'promoted'])->default('pending');
            $table->text('review_notes')->nullable();
            $table->foreignId('reviewer_id')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['project_id', 'status']);
            $table->index(['session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canon_candidates');
    }
};
