<?php
namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Book;
use App\Models\Church;
use App\Models\Denomination;
use App\Models\Module;
use App\Models\SchoolClass;
use App\Models\Session;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_module_progress(): void
    {
        $class = SchoolClass::create(['name' => 'Makarios']);
        $module = Module::create(['name' => 'Loyalty', 'code' => 'L']);
        // Book 1: 2 chapters, Book 2: 3 chapters = 5 total chapters
        $book1 = Book::create(['module_id' => $module->id, 'name' => 'Book One', 'chapters' => ['Intro', 'Ch1'], 'position' => 0]);
        $book2 = Book::create(['module_id' => $module->id, 'name' => 'Book Two', 'chapters' => ['Ch2', 'Ch3', 'Ch4'], 'position' => 1]);
        $teacher = Teacher::create(['name' => 'Pastor Emmanuel']);
        $denomination = Denomination::create(['name' => 'QFC', 'abbreviation' => 'QFC']);
        $church = Church::create(['name' => 'Main', 'denomination_id' => $denomination->id]);
        $student = Student::create(['name' => 'A', 'class_id' => $class->id, 'church_id' => $church->id, 'gender' => 'male']);

        // Teach chapter 0 of book1 and chapter 0 of book2 = 2 distinct chapters taught
        $s1 = Session::create(['class_id' => $class->id, 'module_id' => $module->id, 'book_id' => $book1->id, 'chapter_index' => 0, 'teacher_id' => $teacher->id, 'date' => '2026-04-07']);
        $s2 = Session::create(['class_id' => $class->id, 'module_id' => $module->id, 'book_id' => $book2->id, 'chapter_index' => 0, 'teacher_id' => $teacher->id, 'date' => '2026-04-08']);

        Attendance::create(['session_id' => $s1->id, 'student_id' => $student->id, 'status' => 'present']);
        Attendance::create(['session_id' => $s2->id, 'student_id' => $student->id, 'status' => 'present']);

        $response = $this->getJson("/api/modules/{$module->id}/progress");
        $response->assertOk()
            ->assertJsonStructure(['data' => ['module', 'completion_rate', 'chapters_taught', 'attendance_rate', 'class_breakdown', 'book_breakdown', 'chapter_attendance']])
            ->assertJsonPath('data.module.total_chapters', 5)
            ->assertJsonPath('data.completion_rate', 40.0)
            ->assertJsonPath('data.chapters_taught', 2)
            ->assertJsonPath('data.attendance_rate', 100.0)
            ->assertJsonPath('data.class_breakdown.0.class_name', 'Makarios')
            ->assertJsonPath('data.book_breakdown.0.book_name', 'Book One')
            ->assertJsonPath('data.book_breakdown.0.total_chapters', 2)
            ->assertJsonPath('data.book_breakdown.0.chapters_taught', 1);
    }

    public function test_returns_404_for_nonexistent_module(): void
    {
        $response = $this->getJson('/api/modules/999/progress');
        $response->assertStatus(404);
    }
}
