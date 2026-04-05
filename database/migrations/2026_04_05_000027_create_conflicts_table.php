<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conflicts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('session_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->string('type', 50);
            $table->text('description');
            $table->enum('severity', ['error', 'warning', 'info'])->default('warning');
            $table->enum('status', ['detected', 'acknowledged', 'resolved', 'ignored'])->default('detected');
            
            // Source info
            $table->string('source_type', 50)->nullable();  // canon, timeline, reference, etc.
            $table->unsignedBigInteger('source_id')->nullable();
            
            // Suggested fix
            $table->text('suggested_fix')->nullable();
            
            $table->timestamps();
            
            $table->index(['project_id', 'status']);
            $table->index(['severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conflicts');
    }
};
