<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            // Short-term memory fields
            $table->text('temp_notes')->nullable()->after('notes');
            $table->text('ai_reasoning')->nullable()->after('temp_notes');
            $table->text('draft_text')->nullable()->after('ai_reasoning');
            
            // Session references (temporary image refs for this session)
            $table->json('session_references')->nullable()->after('draft_text');
            
            // Last context update
            $table->timestamp('context_updated_at')->nullable()->after('session_references');
        });
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn([
                'temp_notes',
                'ai_reasoning',
                'draft_text',
                'session_references',
                'context_updated_at',
            ]);
        });
    }
};
