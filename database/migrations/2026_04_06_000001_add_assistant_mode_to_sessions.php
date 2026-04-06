<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->string('assistant_phase', 50)->default('idea_input')->after('type');
            $table->text('assistant_idea')->nullable()->after('assistant_phase');
            $table->text('assistant_refined_idea')->nullable()->after('assistant_idea');
            $table->json('assistant_structure')->nullable()->after('assistant_refined_idea');
            $table->json('assistant_image_prompts')->nullable()->after('assistant_structure');
            $table->json('assistant_video_prompts')->nullable()->after('assistant_image_prompts');
            $table->text('assistant_music_prompt')->nullable()->after('assistant_video_prompts');
        });
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn([
                'assistant_phase',
                'assistant_idea',
                'assistant_refined_idea',
                'assistant_structure',
                'assistant_image_prompts',
                'assistant_video_prompts',
                'assistant_music_prompt',
            ]);
        });
    }
};
