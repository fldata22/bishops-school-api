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

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_correct_structure(): void
    {
        $class = SchoolClass::create(['name' => 'Makarios']);
        $module = Module::create(['name' => 'Loyalty', 'code' => 'L']);
        $book = Book::create(['module_id' => $module->id, 'name' => 'Book 1', 'chapters' => ['Intro'], 'position' => 0]);
        $teacher = Teacher::create(['name' => 'Pastor Emmanuel']);
        $denomination = Denomination::create(['name' => 'QFC', 'abbreviation' => 'QFC']);
        $church = Church::create(['name' => 'Main', 'denomination_id' => $denomination->id]);
        $student = Student::create(['name' => 'Student A', 'class_id' => $class->id, 'church_id' => $church->id, 'gender' => 'male']);

        $session = Session::create(['class_id' => $class->id, 'module_id' => $module->id, 'book_id' => $book->id, 'chapter_index' => 0, 'teacher_id' => $teacher->id, 'date' => now()->toDateString()]);
        Attendance::create(['session_id' => $session->id, 'student_id' => $student->id, 'status' => 'present', 'participation_level' => 3]);

        $response = $this->getJson('/api/dashboard');
        $response->assertOk()
            ->assertJsonStructure(['data' => ['overall_class_attendance', 'overall_module_attendance', 'students_enrolled', 'teacher_count', 'teacher_targets']])
            ->assertJsonPath('data.students_enrolled', 1)
            ->assertJsonPath('data.teacher_count', 1)
            ->assertJsonPath('data.overall_class_attendance', 100.0);
    }

    public function test_dashboard_returns_zeros_with_no_data(): void
    {
        $response = $this->getJson('/api/dashboard');
        $response->assertOk()
            ->assertJsonPath('data.overall_class_attendance', 0)
            ->assertJsonPath('data.overall_module_attendance', 0)
            ->assertJsonPath('data.students_enrolled', 0)
            ->assertJsonPath('data.teacher_count', 0)
            ->assertJsonPath('data.teacher_targets', []);
    }

    public function test_teacher_targets_include_rating(): void
    {
        $class = SchoolClass::create(['name' => 'Makarios']);
        $module = Module::create(['name' => 'Loyalty', 'code' => 'L']);
        $book = Book::create(['module_id' => $module->id, 'name' => 'Book 1', 'chapters' => ['Intro'], 'position' => 0]);
        $teacher = Teacher::create(['name' => 'Pastor Emmanuel']);
        $denomination = Denomination::create(['name' => 'QFC', 'abbreviation' => 'QFC']);
        $church = Church::create(['name' => 'Main', 'denomination_id' => $denomination->id]);
        $student = Student::create(['name' => 'A', 'class_id' => $class->id, 'church_id' => $church->id, 'gender' => 'male']);

        $session = Session::create(['class_id' => $class->id, 'module_id' => $module->id, 'book_id' => $book->id, 'chapter_index' => 0, 'teacher_id' => $teacher->id, 'date' => now()->toDateString()]);
        Attendance::create(['session_id' => $session->id, 'student_id' => $student->id, 'status' => 'present']);

        $response = $this->getJson('/api/dashboard');
        $response->assertJsonPath('data.teacher_targets.0.name', 'Pastor Emmanuel')
            ->assertJsonPath('data.teacher_targets.0.rate', 100.0)
            ->assertJsonPath('data.teacher_targets.0.rating', 'Excellent');
    }
}
