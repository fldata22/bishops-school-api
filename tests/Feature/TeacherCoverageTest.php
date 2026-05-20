<?php
namespace Tests\Feature;

use App\Models\Book;
use App\Models\Module;
use App\Models\SchoolClass;
use App\Models\Session;
use App\Models\Teacher;
use App\Models\TeacherModuleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_correct_structure(): void
    {
        $teacher = Teacher::create(['name' => 'T']);
        $class = SchoolClass::create(['name' => 'A']);
        $module = Module::create(['name' => 'M', 'code' => 'M']);
        Book::create(['module_id' => $module->id, 'name' => 'B', 'chapters' => ['c0'], 'position' => 0]);
        TeacherModuleAssignment::create(['teacher_id' => $teacher->id, 'module_id' => $module->id, 'class_id' => $class->id]);

        $response = $this->getJson("/api/teachers/{$teacher->id}/coverage");
        $response->assertOk()
            ->assertJsonStructure(['data' => [
                'teacher' => ['id', 'name'],
                'rate',
                'taught_chapters',
                'total_chapters',
                'modules' => [
                    '*' => ['id', 'name', 'code', 'rate', 'taught_chapters', 'total_chapters', 'classes', 'books'],
                ],
            ]]);
    }

    public function test_overall_rate_matches_dashboard(): void
    {
        $teacher = Teacher::create(['name' => 'T']);
        $class = SchoolClass::create(['name' => 'A']);
        $module = Module::create(['name' => 'M', 'code' => 'M']);
        $book = Book::create(['module_id' => $module->id, 'name' => 'B', 'chapters' => ['c0', 'c1', 'c2', 'c3'], 'position' => 0]);
        TeacherModuleAssignment::create(['teacher_id' => $teacher->id, 'module_id' => $module->id, 'class_id' => $class->id]);

        foreach ([0, 1] as $idx) {
            Session::create(['class_id' => $class->id, 'module_id' => $module->id, 'book_id' => $book->id, 'chapter_index' => $idx, 'teacher_id' => $teacher->id, 'date' => now()->toDateString()]);
        }

        $dashboard = $this->getJson('/api/dashboard')->json('data.teacher_targets.0.rate');
        $coverage = $this->getJson("/api/teachers/{$teacher->id}/coverage")->json('data.rate');

        $this->assertSame($dashboard, $coverage);
        $this->assertSame(50.0, $coverage);
    }

    public function test_module_breakdown_with_multiple_modules(): void
    {
        $teacher = Teacher::create(['name' => 'T']);
        $class = SchoolClass::create(['name' => 'A']);

        $big = Module::create(['name' => 'Big', 'code' => 'BIG']);
        $bigBook = Book::create(['module_id' => $big->id, 'name' => 'BB', 'chapters' => ['0','1','2','3','4','5','6','7','8','9'], 'position' => 0]);
        $small = Module::create(['name' => 'Small', 'code' => 'SML']);
        $smallBook = Book::create(['module_id' => $small->id, 'name' => 'SB', 'chapters' => ['0','1'], 'position' => 0]);

        TeacherModuleAssignment::create(['teacher_id' => $teacher->id, 'module_id' => $big->id, 'class_id' => $class->id]);
        TeacherModuleAssignment::create(['teacher_id' => $teacher->id, 'module_id' => $small->id, 'class_id' => $class->id]);

        foreach ([0,1,2,3,4] as $idx) {
            Session::create(['class_id' => $class->id, 'module_id' => $big->id, 'book_id' => $bigBook->id, 'chapter_index' => $idx, 'teacher_id' => $teacher->id, 'date' => now()->toDateString()]);
        }
        foreach ([0,1] as $idx) {
            Session::create(['class_id' => $class->id, 'module_id' => $small->id, 'book_id' => $smallBook->id, 'chapter_index' => $idx, 'teacher_id' => $teacher->id, 'date' => now()->toDateString()]);
        }

        $response = $this->getJson("/api/teachers/{$teacher->id}/coverage");
        $response->assertOk()
            ->assertJsonPath('data.rate', 58.3)
            ->assertJsonPath('data.taught_chapters', 7)
            ->assertJsonPath('data.total_chapters', 12)
            ->assertJsonPath('data.modules.0.id', $big->id)
            ->assertJsonPath('data.modules.0.taught_chapters', 5)
            ->assertJsonPath('data.modules.0.total_chapters', 10)
            ->assertJsonPath('data.modules.0.rate', 50.0)
            ->assertJsonPath('data.modules.1.id', $small->id)
            ->assertJsonPath('data.modules.1.taught_chapters', 2)
            ->assertJsonPath('data.modules.1.total_chapters', 2)
            ->assertJsonPath('data.modules.1.rate', 100.0);
    }

    public function test_chapter_taught_flag_correct(): void
    {
        $teacher = Teacher::create(['name' => 'T']);
        $class = SchoolClass::create(['name' => 'A']);
        $module = Module::create(['name' => 'M', 'code' => 'M']);
        $book = Book::create(['module_id' => $module->id, 'name' => 'B', 'chapters' => ['ch0', 'ch1', 'ch2'], 'position' => 0]);
        TeacherModuleAssignment::create(['teacher_id' => $teacher->id, 'module_id' => $module->id, 'class_id' => $class->id]);

        Session::create(['class_id' => $class->id, 'module_id' => $module->id, 'book_id' => $book->id, 'chapter_index' => 0, 'teacher_id' => $teacher->id, 'date' => '2026-04-16']);
        Session::create(['class_id' => $class->id, 'module_id' => $module->id, 'book_id' => $book->id, 'chapter_index' => 2, 'teacher_id' => $teacher->id, 'date' => '2026-04-20']);

        $response = $this->getJson("/api/teachers/{$teacher->id}/coverage");
        $response->assertOk()
            ->assertJsonPath('data.modules.0.books.0.chapters.0.taught', true)
            ->assertJsonPath('data.modules.0.books.0.chapters.0.title', 'ch0')
            ->assertJsonPath('data.modules.0.books.0.chapters.1.taught', false)
            ->assertJsonPath('data.modules.0.books.0.chapters.1.title', 'ch1')
            ->assertJsonPath('data.modules.0.books.0.chapters.1.last_taught_date', null)
            ->assertJsonPath('data.modules.0.books.0.chapters.2.taught', true)
            ->assertJsonPath('data.modules.0.books.0.chapters.2.title', 'ch2');
    }

    public function test_last_taught_date_is_max_date(): void
    {
        $teacher = Teacher::create(['name' => 'T']);
        $class = SchoolClass::create(['name' => 'A']);
        $module = Module::create(['name' => 'M', 'code' => 'M']);
        $book = Book::create(['module_id' => $module->id, 'name' => 'B', 'chapters' => ['c0'], 'position' => 0]);
        TeacherModuleAssignment::create(['teacher_id' => $teacher->id, 'module_id' => $module->id, 'class_id' => $class->id]);

        Session::create(['class_id' => $class->id, 'module_id' => $module->id, 'book_id' => $book->id, 'chapter_index' => 0, 'teacher_id' => $teacher->id, 'date' => '2026-04-16']);
        Session::create(['class_id' => $class->id, 'module_id' => $module->id, 'book_id' => $book->id, 'chapter_index' => 0, 'teacher_id' => $teacher->id, 'date' => '2026-04-20']);
        Session::create(['class_id' => $class->id, 'module_id' => $module->id, 'book_id' => $book->id, 'chapter_index' => 0, 'teacher_id' => $teacher->id, 'date' => '2026-04-18']);

        $response = $this->getJson("/api/teachers/{$teacher->id}/coverage");
        $response->assertJsonPath('data.modules.0.books.0.chapters.0.last_taught_date', '2026-04-20');
    }

    public function test_stale_chapter_index_excluded_from_counts_and_list(): void
    {
        $teacher = Teacher::create(['name' => 'T']);
        $class = SchoolClass::create(['name' => 'A']);
        $module = Module::create(['name' => 'M', 'code' => 'M']);
        $book = Book::create(['module_id' => $module->id, 'name' => 'B', 'chapters' => ['c0', 'c1'], 'position' => 0]);
        TeacherModuleAssignment::create(['teacher_id' => $teacher->id, 'module_id' => $module->id, 'class_id' => $class->id]);

        Session::create(['class_id' => $class->id, 'module_id' => $module->id, 'book_id' => $book->id, 'chapter_index' => 0, 'teacher_id' => $teacher->id, 'date' => '2026-04-16']);
        Session::create(['class_id' => $class->id, 'module_id' => $module->id, 'book_id' => $book->id, 'chapter_index' => 5, 'teacher_id' => $teacher->id, 'date' => '2026-04-17']);

        $response = $this->getJson("/api/teachers/{$teacher->id}/coverage");
        $response->assertJsonPath('data.taught_chapters', 1)
            ->assertJsonPath('data.total_chapters', 2)
            ->assertJsonPath('data.modules.0.taught_chapters', 1)
            ->assertJsonCount(2, 'data.modules.0.books.0.chapters');
    }

    public function test_classes_list_deduped_and_sorted_by_id(): void
    {
        $teacher = Teacher::create(['name' => 'T']);
        $classB = SchoolClass::create(['name' => 'B']);
        $classA = SchoolClass::create(['name' => 'A']);
        $module = Module::create(['name' => 'M', 'code' => 'M']);
        $book = Book::create(['module_id' => $module->id, 'name' => 'B', 'chapters' => ['c0', 'c1'], 'position' => 0]);

        TeacherModuleAssignment::create(['teacher_id' => $teacher->id, 'module_id' => $module->id, 'class_id' => $classA->id]);
        TeacherModuleAssignment::create(['teacher_id' => $teacher->id, 'module_id' => $module->id, 'class_id' => $classB->id]);

        Session::create(['class_id' => $classB->id, 'module_id' => $module->id, 'book_id' => $book->id, 'chapter_index' => 0, 'teacher_id' => $teacher->id, 'date' => '2026-04-16']);
        Session::create(['class_id' => $classB->id, 'module_id' => $module->id, 'book_id' => $book->id, 'chapter_index' => 1, 'teacher_id' => $teacher->id, 'date' => '2026-04-17']);
        Session::create(['class_id' => $classA->id, 'module_id' => $module->id, 'book_id' => $book->id, 'chapter_index' => 0, 'teacher_id' => $teacher->id, 'date' => '2026-04-18']);

        $response = $this->getJson("/api/teachers/{$teacher->id}/coverage");
        $classes = $response->json('data.modules.0.classes');
        $this->assertCount(2, $classes);
        $this->assertSame($classB->id, $classes[0]['id']);
        $this->assertSame($classA->id, $classes[1]['id']);
        $this->assertLessThan($classes[1]['id'], $classes[0]['id']);
    }

    public function test_chapter_taught_in_two_classes_counts_once(): void
    {
        $teacher = Teacher::create(['name' => 'T']);
        $classA = SchoolClass::create(['name' => 'A']);
        $classB = SchoolClass::create(['name' => 'B']);
        $module = Module::create(['name' => 'M', 'code' => 'M']);
        $book = Book::create(['module_id' => $module->id, 'name' => 'B', 'chapters' => ['c0', 'c1'], 'position' => 0]);
        TeacherModuleAssignment::create(['teacher_id' => $teacher->id, 'module_id' => $module->id, 'class_id' => $classA->id]);

        Session::create(['class_id' => $classA->id, 'module_id' => $module->id, 'book_id' => $book->id, 'chapter_index' => 0, 'teacher_id' => $teacher->id, 'date' => '2026-04-16']);
        Session::create(['class_id' => $classB->id, 'module_id' => $module->id, 'book_id' => $book->id, 'chapter_index' => 0, 'teacher_id' => $teacher->id, 'date' => '2026-04-17']);

        $response = $this->getJson("/api/teachers/{$teacher->id}/coverage");
        $response->assertJsonPath('data.taught_chapters', 1)
            ->assertJsonPath('data.rate', 50.0);
    }

    public function test_zero_assignment_teacher_returns_empty_modules(): void
    {
        $teacher = Teacher::create(['name' => 'Idle']);
        $response = $this->getJson("/api/teachers/{$teacher->id}/coverage");
        $response->assertOk()
            ->assertJsonPath('data.rate', 0.0)
            ->assertJsonPath('data.taught_chapters', 0)
            ->assertJsonPath('data.total_chapters', 0)
            ->assertJsonPath('data.modules', []);
    }

    public function test_returns_404_for_unknown_teacher(): void
    {
        $response = $this->getJson('/api/teachers/9999/coverage');
        $response->assertNotFound();
    }

    public function test_books_sorted_by_position_chapters_by_index(): void
    {
        $teacher = Teacher::create(['name' => 'T']);
        $class = SchoolClass::create(['name' => 'A']);
        $module = Module::create(['name' => 'M', 'code' => 'M']);
        // Create books in reverse position order to verify sort
        $bookSecond = Book::create(['module_id' => $module->id, 'name' => 'Second', 'chapters' => ['s0'], 'position' => 2]);
        $bookFirst = Book::create(['module_id' => $module->id, 'name' => 'First', 'chapters' => ['f0', 'f1'], 'position' => 1]);
        TeacherModuleAssignment::create(['teacher_id' => $teacher->id, 'module_id' => $module->id, 'class_id' => $class->id]);

        $response = $this->getJson("/api/teachers/{$teacher->id}/coverage");
        $response->assertJsonPath('data.modules.0.books.0.id', $bookFirst->id)
            ->assertJsonPath('data.modules.0.books.0.position', 1)
            ->assertJsonPath('data.modules.0.books.1.id', $bookSecond->id)
            ->assertJsonPath('data.modules.0.books.1.position', 2)
            ->assertJsonPath('data.modules.0.books.0.chapters.0.index', 0)
            ->assertJsonPath('data.modules.0.books.0.chapters.1.index', 1);
    }
}
