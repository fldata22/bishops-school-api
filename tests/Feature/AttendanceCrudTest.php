<?php
namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Book;
use App\Models\Module;
use App\Models\SchoolClass;
use App\Models\Session;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCrudTest extends TestCase
{
    use RefreshDatabase;

    private function makeAttendance(string $status = 'present', ?int $level = null): Attendance
    {
        $class = SchoolClass::create(['name' => 'C1']);
        $module = Module::create(['name' => 'M', 'code' => 'M']);
        $book = Book::create(['module_id' => $module->id, 'name' => 'B', 'chapters' => ['C1'], 'position' => 0]);
        $teacher = Teacher::create(['name' => 'T']);
        $student = Student::create(['name' => 'S', 'class_id' => $class->id]);
        $session = Session::create([
            'class_id' => $class->id,
            'module_id' => $module->id,
            'teacher_id' => $teacher->id,
            'date' => '2026-04-10',
            'book_id' => $book->id,
            'chapter_index' => 0,
        ]);
        return Attendance::create([
            'session_id' => $session->id,
            'student_id' => $student->id,
            'status' => $status,
            'participation_level' => $level,
        ]);
    }

    public function test_can_update_attendance_status(): void
    {
        $att = $this->makeAttendance('present', 3);

        $response = $this->patchJson("/api/attendance/{$att->id}", ['status' => 'absent']);

        $response->assertOk()
            ->assertJsonPath('data.status', 'absent')
            ->assertJsonPath('data.participation_level', null);
    }

    public function test_can_update_participation_level(): void
    {
        $att = $this->makeAttendance('present', 1);

        $response = $this->patchJson("/api/attendance/{$att->id}", ['participation_level' => 4]);

        $response->assertOk()->assertJsonPath('data.participation_level', 4);
    }

    public function test_update_validates_status(): void
    {
        $att = $this->makeAttendance();

        $response = $this->patchJson("/api/attendance/{$att->id}", ['status' => 'maybe']);

        $response->assertStatus(422)->assertJsonValidationErrors(['status']);
    }

    public function test_update_validates_participation_range(): void
    {
        $att = $this->makeAttendance();

        $response = $this->patchJson("/api/attendance/{$att->id}", ['participation_level' => 9]);

        $response->assertStatus(422)->assertJsonValidationErrors(['participation_level']);
    }

    public function test_can_delete_attendance(): void
    {
        $att = $this->makeAttendance();

        $response = $this->deleteJson("/api/attendance/{$att->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('attendance', ['id' => $att->id]);
    }
}
