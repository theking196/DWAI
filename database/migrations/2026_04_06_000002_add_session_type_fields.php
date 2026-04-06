<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->string('session_type', 50)->default('normal')->after('type');
            $table->json('build_steps')->nullable()->after('assistant_music_prompt');
            $table->integer('current_step_index')->default(0)->after('build_steps');
            $table->json('build_outputs')->nullable()->after('current_step_index');
        });
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn(['session_type', 'build_steps', 'current_step_index', 'build_outputs']);
        });
    }
};
