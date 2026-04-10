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

class TeacherStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_teacher_stats(): void
    {
        $class = SchoolClass::create(['name' => 'Makarios']);
        $module = Module::create(['name' => 'Loyalty', 'code' => 'L']);
        $book = Book::create(['module_id' => $module->id, 'name' => 'Book 1', 'chapters' => ['Intro'], 'position' => 0]);
        $teacher = Teacher::create(['name' => 'Pastor Emmanuel']);
        $denomination = Denomination::create(['name' => 'QFC', 'abbreviation' => 'QFC']);
        $church = Church::create(['name' => 'Main', 'denomination_id' => $denomination->id]);
        $student = Student::create(['name' => 'A', 'class_id' => $class->id, 'church_id' => $church->id, 'gender' => 'male']);

        $session = Session::create(['class_id' => $class->id, 'module_id' => $module->id, 'book_id' => $book->id, 'chapter_index' => 0, 'teacher_id' => $teacher->id, 'date' => '2026-04-09']);
        Attendance::create(['session_id' => $session->id, 'student_id' => $student->id, 'status' => 'present']);

        $response = $this->getJson("/api/teachers/{$teacher->id}/stats");
        $response->assertOk()
            ->assertJsonStructure(['data' => ['teacher', 'total_sessions', 'classes', 'monthly_breakdown']])
            ->assertJsonPath('data.teacher.name', 'Pastor Emmanuel')
            ->assertJsonPath('data.total_sessions', 1)
            ->assertJsonPath('data.classes.0.class_name', 'Makarios')
            ->assertJsonPath('data.classes.0.attendance_rate', 100.0);
    }

    public function test_returns_404_for_nonexistent_teacher(): void
    {
        $response = $this->getJson('/api/teachers/999/stats');
        $response->assertStatus(404);
    }
}
