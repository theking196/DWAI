<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Visual Style
            $table->text('visual_style_description')->nullable()->after('visual_style_image');
            
            // Production State
            $table->boolean('is_archived')->default(false)->after('status');
            $table->timestamp('archived_at')->nullable()->after('is_archived');
            
            // Additional metadata
            $table->json('metadata')->nullable()->after('archived_at');
            
            // Tags for filtering
            $table->json('tags')->nullable()->after('metadata');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'visual_style_description',
                'is_archived',
                'archived_at',
                'metadata',
                'tags',
            ]);
        });
    }
};
