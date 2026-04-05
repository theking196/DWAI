<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_outputs', function (Blueprint $table) {
            $table->integer('version')->default(1)->after('type');
            $table->unsignedBigInteger('parent_output_id')->nullable()->after('version');
            $table->boolean('is_current')->default(true)->after('parent_output_id');
        });
    }

    public function down(): void
    {
        Schema::table('ai_outputs', function (Blueprint $table) {
            $table->dropColumn(['version', 'parent_output_id', 'is_current']);
        });
    }
};
