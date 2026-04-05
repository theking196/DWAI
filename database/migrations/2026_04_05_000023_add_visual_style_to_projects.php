<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Main visual style
            $table->string('style_image_path')->nullable()->after('description');
            $table->string('style_image_title')->nullable()->after('style_image_path');
            
            // Supporting style images (JSON array)
            $table->json('style_images')->nullable()->after('style_image_title');
            
            // Style notes
            $table->text('style_notes')->nullable()->after('style_images');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['style_image_path', 'style_image_title', 'style_images', 'style_notes']);
        });
    }
};
