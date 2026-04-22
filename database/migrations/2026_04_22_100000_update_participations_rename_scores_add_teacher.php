<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participations', function (Blueprint $table) {
            $table->foreignId('teacher_id')
                ->nullable()
                ->after('class_id')
                ->constrained('teachers')
                ->nullOnDelete();
            $table->renameColumn('scores', 'records');
        });
    }

    public function down(): void
    {
        Schema::table('participations', function (Blueprint $table) {
            $table->renameColumn('records', 'scores');
            $table->dropConstrainedForeignId('teacher_id');
        });
    }
};
