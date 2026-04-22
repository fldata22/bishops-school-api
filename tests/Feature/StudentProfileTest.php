<?php
namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Book;
use App\Models\Church;
use App\Models\Denomination;
use App\Models\Module;
use App\Models\Participation;
use App\Models\SchoolClass;
use App\Models\Session;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_student_profile_with_stats(): void
    {
        $class = SchoolClass::create(['name' => 'Makarios']);
        $module = Module::create(['name' => 'Loyalty', 'code' => 'L']);
        $book = Book::create(['module_id' => $module->id, 'name' => 'Book 1', 'chapters' => ['Intro', 'Ch1'], 'position' => 0]);
        $teacher = Teacher::create(['name' => 'Pastor Emmanuel']);
        $denomination = Denomination::create(['name' => 'QFC', 'abbreviation' => 'QFC']);
        $church = Church::create(['name' => 'Main', 'denomination_id' => $denomination->id]);
        $student = Student::create(['name' => 'Kwame', 'class_id' => $class->id, 'church_id' => $church->id, 'gender' => 'male']);

        $s1 = Session::create(['class_id' => $class->id, 'module_id' => $module->id, 'book_id' => $book->id, 'chapter_index' => 0, 'teacher_id' => $teacher->id, 'date' => '2026-04-07']);
        $s2 = Session::create(['class_id' => $class->id, 'module_id' => $module->id, 'book_id' => $book->id, 'chapter_index' => 1, 'teacher_id' => $teacher->id, 'date' => '2026-04-08']);

        Attendance::create(['session_id' => $s1->id, 'student_id' => $student->id, 'status' => 'present', 'participation_level' => 3]);
        Attendance::create(['session_id' => $s2->id, 'student_id' => $student->id, 'status' => 'absent']);

        $response = $this->getJson("/api/students/{$student->id}/profile");
        $response->assertOk()
            ->assertJsonStructure(['data' => ['student', 'attendance_rate', 'present_count', 'absent_count', 'participation_average', 'module_breakdown']])
            ->assertJsonPath('data.attendance_rate', 50.0)
            ->assertJsonPath('data.present_count', 1)
            ->assertJsonPath('data.absent_count', 1)
            ->assertJsonPath('data.participation_average', 3.0)
            ->assertJsonPath('data.module_breakdown.0.module_name', 'Loyalty');
    }

    public function test_participation_average_includes_participation_table_records(): void
    {
        $class = SchoolClass::create(['name' => 'BRA-UD AFRICA']);
        $teacher = Teacher::create(['name' => 'Bishop Richard']);
        $student = Student::create(['name' => 'Paul Mbah', 'class_id' => $class->id]);

        Participation::create([
            'class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'date' => '2026-04-15',
            'records' => [
                ['student_id' => $student->id, 'participation_level' => 4],
            ],
        ]);
        Participation::create([
            'class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'date' => '2026-04-16',
            'records' => [
                ['student_id' => $student->id, 'participation_level' => 2],
            ],
        ]);

        $response = $this->getJson("/api/students/{$student->id}/profile");

        $response->assertOk()
            ->assertJsonPath('data.participation_average', 3.0);
    }

    public function test_participation_average_combines_attendance_and_participation_sources(): void
    {
        $class = SchoolClass::create(['name' => 'Makarios']);
        $module = Module::create(['name' => 'Loyalty', 'code' => 'L']);
        $book = Book::create(['module_id' => $module->id, 'name' => 'Book 1', 'chapters' => ['Intro'], 'position' => 0]);
        $teacher = Teacher::create(['name' => 'Pastor Emmanuel']);
        $student = Student::create(['name' => 'Kwame', 'class_id' => $class->id]);

        $session = Session::create(['class_id' => $class->id, 'module_id' => $module->id, 'book_id' => $book->id, 'chapter_index' => 0, 'teacher_id' => $teacher->id, 'date' => '2026-04-07']);
        Attendance::create(['session_id' => $session->id, 'student_id' => $student->id, 'status' => 'present', 'participation_level' => 4]);

        Participation::create([
            'class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'date' => '2026-04-09',
            'records' => [
                ['student_id' => $student->id, 'participation_level' => 2],
            ],
        ]);

        $response = $this->getJson("/api/students/{$student->id}/profile");

        $response->assertOk()
            ->assertJsonPath('data.participation_average', 3.0);
    }

    public function test_returns_404_for_nonexistent_student(): void
    {
        $response = $this->getJson('/api/students/999/profile');
        $response->assertStatus(404);
    }

    public function test_returns_profile_when_student_has_no_church(): void
    {
        $class = SchoolClass::create(['name' => 'APJ-UNITED CITIES']);
        $student = Student::create(['name' => 'Kwame', 'class_id' => $class->id]);

        $response = $this->getJson("/api/students/{$student->id}/profile");

        $response->assertOk()
            ->assertJsonPath('data.student.name', 'Kwame')
            ->assertJsonPath('data.student.class', 'APJ-UNITED CITIES')
            ->assertJsonPath('data.student.church', null)
            ->assertJsonPath('data.student.gender', null)
            ->assertJsonPath('data.attendance_rate', 0.0)
            ->assertJsonPath('data.module_breakdown', []);
    }
}
