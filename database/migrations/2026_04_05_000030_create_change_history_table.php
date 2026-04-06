<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('entity_id');
            $table->string('field_name', 50);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('change_type', 20)->default('update'); // create, update, delete
            $table->timestamps();
            
            $table->index(['entity_type', 'entity_id']);
            $table->index(['entity_type', 'entity_id', 'field_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_history');
    }
};
