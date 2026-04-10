<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Module;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ModulesBooksSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = __DIR__.'/data/modules_books.json';
        if (! file_exists($jsonPath)) {
            $this->command?->error("Missing data file: {$jsonPath}");
            return;
        }

        $data = json_decode(file_get_contents($jsonPath), true);
        if (! is_array($data)) {
            $this->command?->error('Invalid JSON in modules_books.json');
            return;
        }

        // Wipe modules + books and anything that references them.
        // Sessions and attendance reference book_id with cascade delete, so
        // truncating books cascades through; we still truncate them explicitly
        // to keep the data set clean and predictable.
        Schema::disableForeignKeyConstraints();
        DB::table('attendance')->truncate();
        DB::table('sessions')->truncate();
        DB::table('teacher_module_assignments')->truncate();
        DB::table('books')->truncate();
        DB::table('modules')->truncate();
        Schema::enableForeignKeyConstraints();

        $totalModules = 0;
        $totalBooks = 0;
        $totalChapters = 0;

        foreach ($data as $modData) {
            $module = Module::create([
                'name' => $modData['name'],
                'code' => $modData['code'],
            ]);
            $totalModules++;

            foreach ($modData['books'] as $position => $bookData) {
                $chapters = $bookData['chapters'] ?? [];
                if (empty($chapters)) {
                    // Schema requires at least one chapter; use a placeholder
                    // for books whose source EPUB wasn't available.
                    $chapters = ['Content to be added'];
                }

                Book::create([
                    'module_id' => $module->id,
                    'name' => $bookData['name'],
                    'chapters' => $chapters,
                    'position' => $position,
                ]);
                $totalBooks++;
                $totalChapters += count($chapters);
            }
        }

        $this->command?->info("Seeded {$totalModules} modules, {$totalBooks} books, {$totalChapters} chapters.");
    }
}
