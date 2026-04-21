<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Module;
use Illuminate\Database\Seeder;

class ChurchHistoryModuleSeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::firstOrCreate(
            ['code' => 'CH'],
            ['name' => 'Church History']
        );

        $books = [
            'The Living History Ghana Missions',
            'The Living History Diverse Missions',
            'The Living History Worldwide Missions',
        ];

        $added = 0;
        foreach ($books as $position => $name) {
            $book = Book::firstOrCreate(
                ['module_id' => $module->id, 'name' => $name],
                ['chapters' => ['Content to be added'], 'position' => $position]
            );
            if ($book->wasRecentlyCreated) {
                $added++;
            }
        }

        $this->command?->info("Church History module ready. Added {$added} new book(s).");
    }
}
