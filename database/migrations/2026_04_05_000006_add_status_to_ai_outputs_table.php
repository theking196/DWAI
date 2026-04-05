<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_outputs', function (Blueprint $table) {
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])
                ->default('pending')
                ->after('result');
            $table->text('error_message')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('ai_outputs', function (Blueprint $table) {
            $table->dropColumn(['status', 'error_message']);
        });
    }
};