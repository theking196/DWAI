<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            // Archive fields
            $table->timestamp('archived_at')->nullable()->after('context_updated_at');
            $table->text('archive_reason')->nullable()->after('archived_at');
            
            // History tracking
            $table->json('activity_history')->nullable()->after('archive_reason');
        });
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn(['archived_at', 'archive_reason', 'activity_history']);
        });
    }
};
