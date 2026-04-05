<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timeline_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('session_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('order_index')->default(0);
            $table->timestamp('event_timestamp')->nullable();
            
            // Related canon entries
            $table->json('related_canon')->nullable();
            
            $table->timestamps();
            
            $table->index(['project_id', 'order_index']);
            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timeline_events');
    }
};
