<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create books table
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('chapters');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        // Update sessions table: drop topic_index, add book_id and chapter_index
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn('topic_index');
            $table->foreignId('book_id')->after('module_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('chapter_index')->after('book_id');
        });

        // Update modules table: drop topics column
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn('topics');
        });
    }

    public function down(): void
    {
        // Restore topics column on modules
        Schema::table('modules', function (Blueprint $table) {
            $table->json('topics')->nullable()->after('code');
        });

        // Restore sessions table: drop book_id and chapter_index, add topic_index back
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropForeign(['book_id']);
            $table->dropColumn(['book_id', 'chapter_index']);
            $table->unsignedInteger('topic_index')->after('module_id')->default(0);
        });

        // Drop books table
        Schema::dropIfExists('books');
    }
};
