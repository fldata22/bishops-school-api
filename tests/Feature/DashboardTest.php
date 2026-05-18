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
use App\Models\TeacherModuleAssignment;
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

    public function test_teacher_target_is_pooled_lesson_coverage(): void
    {
        $class = SchoolClass::create(['name' => 'Makarios']);
        $teacher = Teacher::create(['name' => 'Pastor Emmanuel']);
        $module = Module::create(['name' => 'Loyalty', 'code' => 'L']);
        $book = Book::create(['module_id' => $module->id, 'name' => 'B1', 'chapters' => ['c0', 'c1', 'c2', 'c3'], 'position' => 0]);

        TeacherModuleAssignment::create(['teacher_id' => $teacher->id, 'module_id' => $module->id, 'class_id' => $class->id]);

        foreach ([0, 1] as $idx) {
            Session::create(['class_id' => $class->id, 'module_id' => $module->id, 'book_id' => $book->id, 'chapter_index' => $idx, 'teacher_id' => $teacher->id, 'date' => now()->toDateString()]);
        }

        $response = $this->getJson('/api/dashboard');
        $response->assertOk()
            ->assertJsonPath('data.teacher_targets.0.name', 'Pastor Emmanuel')
            ->assertJsonPath('data.teacher_targets.0.rate', 50.0);
    }

    public function test_teacher_target_pools_across_modules_weighted_by_chapter_count(): void
    {
        $class = SchoolClass::create(['name' => 'Makarios']);
        $teacher = Teacher::create(['name' => 'Pastor Emmanuel']);

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

        $response = $this->getJson('/api/dashboard');
        $response->assertJsonPath('data.teacher_targets.0.rate', 58.3);
    }

    public function test_teacher_with_no_assignments_has_zero_rate(): void
    {
        Teacher::create(['name' => 'Idle Teacher']);

        $response = $this->getJson('/api/dashboard');
        $response->assertJsonPath('data.teacher_targets.0.rate', 0.0);
    }

    public function test_chapter_taught_in_two_classes_counts_once(): void
    {
        $classA = SchoolClass::create(['name' => 'A']);
        $classB = SchoolClass::create(['name' => 'B']);
        $teacher = Teacher::create(['name' => 'T']);
        $module = Module::create(['name' => 'M', 'code' => 'M']);
        $book = Book::create(['module_id' => $module->id, 'name' => 'B', 'chapters' => ['c0', 'c1'], 'position' => 0]);

        TeacherModuleAssignment::create(['teacher_id' => $teacher->id, 'module_id' => $module->id, 'class_id' => $classA->id]);

        Session::create(['class_id' => $classA->id, 'module_id' => $module->id, 'book_id' => $book->id, 'chapter_index' => 0, 'teacher_id' => $teacher->id, 'date' => now()->toDateString()]);
        Session::create(['class_id' => $classB->id, 'module_id' => $module->id, 'book_id' => $book->id, 'chapter_index' => 0, 'teacher_id' => $teacher->id, 'date' => now()->toDateString()]);

        $response = $this->getJson('/api/dashboard');
        $response->assertJsonPath('data.teacher_targets.0.rate', 50.0);
    }

    public function test_stale_chapter_index_is_excluded(): void
    {
        $class = SchoolClass::create(['name' => 'A']);
        $teacher = Teacher::create(['name' => 'T']);
        $module = Module::create(['name' => 'M', 'code' => 'M']);
        $book = Book::create(['module_id' => $module->id, 'name' => 'B', 'chapters' => ['c0', 'c1'], 'position' => 0]);

        TeacherModuleAssignment::create(['teacher_id' => $teacher->id, 'module_id' => $module->id, 'class_id' => $class->id]);

        Session::create(['class_id' => $class->id, 'module_id' => $module->id, 'book_id' => $book->id, 'chapter_index' => 0, 'teacher_id' => $teacher->id, 'date' => now()->toDateString()]);
        Session::create(['class_id' => $class->id, 'module_id' => $module->id, 'book_id' => $book->id, 'chapter_index' => 5, 'teacher_id' => $teacher->id, 'date' => now()->toDateString()]);

        $response = $this->getJson('/api/dashboard');
        $response->assertJsonPath('data.teacher_targets.0.rate', 50.0);
    }

    public function test_teacher_targets_do_not_include_rating(): void
    {
        $class = SchoolClass::create(['name' => 'A']);
        $teacher = Teacher::create(['name' => 'T']);
        $module = Module::create(['name' => 'M', 'code' => 'M']);
        Book::create(['module_id' => $module->id, 'name' => 'B', 'chapters' => ['c0'], 'position' => 0]);
        TeacherModuleAssignment::create(['teacher_id' => $teacher->id, 'module_id' => $module->id, 'class_id' => $class->id]);

        $response = $this->getJson('/api/dashboard');
        $data = $response->json('data.teacher_targets.0');
        $this->assertArrayNotHasKey('rating', $data);
        $this->assertEqualsCanonicalizing(['id', 'name', 'rate'], array_keys($data));
    }
}
