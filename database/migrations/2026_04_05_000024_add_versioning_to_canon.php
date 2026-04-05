<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canon_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('canon_entry_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('type', 50);
            $table->string('image')->nullable();
            $table->json('tags')->nullable();
            $table->string('importance')->default('none');
            
            $table->text('change_summary')->nullable();
            $table->json('changes')->nullable();
            
            $table->timestamp('created_at');
            
            $table->index(['canon_entry_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canon_versions');
    }
};
