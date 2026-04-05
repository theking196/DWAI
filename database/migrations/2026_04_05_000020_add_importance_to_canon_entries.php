<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('canon_entries', function (Blueprint $table) {
            $table->enum('importance', ['critical', 'important', 'minor', 'none'])->default('none')->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('canon_entries', function (Blueprint $table) {
            $table->dropColumn('importance');
        });
    }
};
