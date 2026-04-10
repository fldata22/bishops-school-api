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

class SessionTest extends TestCase
{
    use RefreshDatabase;

    private SchoolClass $class;
    private Module $module;
    private Book $book;
    private Teacher $teacher;
    private Student $student1;
    private Student $student2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->class = SchoolClass::create(['name' => 'Makarios']);
        $this->module = Module::create(['name' => 'Loyalty', 'code' => 'L']);
        $this->book = Book::create(['module_id' => $this->module->id, 'name' => 'Book 1', 'chapters' => ['Intro', 'Chapter 1'], 'position' => 0]);
        $this->teacher = Teacher::create(['name' => 'Pastor Emmanuel']);
        $denomination = Denomination::create(['name' => 'QFC', 'abbreviation' => 'QFC']);
        $church = Church::create(['name' => 'Main', 'denomination_id' => $denomination->id]);
        $this->student1 = Student::create(['name' => 'Student A', 'class_id' => $this->class->id, 'church_id' => $church->id, 'gender' => 'male']);
        $this->student2 = Student::create(['name' => 'Student B', 'class_id' => $this->class->id, 'church_id' => $church->id, 'gender' => 'female']);
    }

    public function test_can_list_sessions(): void
    {
        Session::create(['class_id' => $this->class->id, 'module_id' => $this->module->id, 'book_id' => $this->book->id, 'chapter_index' => 0, 'teacher_id' => $this->teacher->id, 'date' => '2026-04-09']);
        $response = $this->getJson('/api/sessions');
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_filter_sessions_by_class(): void
    {
        $other = SchoolClass::create(['name' => 'Poimen']);
        Session::create(['class_id' => $this->class->id, 'module_id' => $this->module->id, 'book_id' => $this->book->id, 'chapter_index' => 0, 'teacher_id' => $this->teacher->id, 'date' => '2026-04-09']);
        Session::create(['class_id' => $other->id, 'module_id' => $this->module->id, 'book_id' => $this->book->id, 'chapter_index' => 0, 'teacher_id' => $this->teacher->id, 'date' => '2026-04-09']);
        $response = $this->getJson("/api/sessions?class_id={$this->class->id}");
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_create_session_with_attendance(): void
    {
        $response = $this->postJson('/api/sessions', [
            'class_id' => $this->class->id,
            'module_id' => $this->module->id,
            'book_id' => $this->book->id,
            'chapter_index' => 0,
            'teacher_id' => $this->teacher->id,
            'date' => '2026-04-09',
            'attendance' => [
                ['student_id' => $this->student1->id, 'status' => 'present', 'participation_level' => 3],
                ['student_id' => $this->student2->id, 'status' => 'absent'],
            ],
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseCount('sessions', 1);
        $this->assertDatabaseCount('attendance', 2);
        $this->assertDatabaseHas('attendance', ['student_id' => $this->student1->id, 'status' => 'present', 'participation_level' => 3]);
        $this->assertDatabaseHas('attendance', ['student_id' => $this->student2->id, 'status' => 'absent', 'participation_level' => null]);
    }

    public function test_create_session_rejects_book_from_different_module(): void
    {
        $otherModule = Module::create(['name' => 'Faith', 'code' => 'F']);
        $otherBook = Book::create(['module_id' => $otherModule->id, 'name' => 'Other Book', 'chapters' => ['Ch1'], 'position' => 0]);

        $response = $this->postJson('/api/sessions', [
            'class_id' => $this->class->id,
            'module_id' => $this->module->id,
            'book_id' => $otherBook->id,
            'chapter_index' => 0,
            'teacher_id' => $this->teacher->id,
            'date' => '2026-04-09',
            'attendance' => [['student_id' => $this->student1->id, 'status' => 'present']],
        ]);
        $response->assertStatus(422)->assertJsonPath('message', 'Book does not belong to the specified module');
    }

    public function test_create_session_validates_attendance_students_belong_to_class(): void
    {
        $otherClass = SchoolClass::create(['name' => 'Poimen']);
        $church = Church::first();
        $wrongStudent = Student::create(['name' => 'Wrong', 'class_id' => $otherClass->id, 'church_id' => $church->id, 'gender' => 'male']);
        $response = $this->postJson('/api/sessions', [
            'class_id' => $this->class->id,
            'module_id' => $this->module->id,
            'book_id' => $this->book->id,
            'chapter_index' => 0,
            'teacher_id' => $this->teacher->id,
            'date' => '2026-04-09',
            'attendance' => [['student_id' => $wrongStudent->id, 'status' => 'present']],
        ]);
        $response->assertStatus(422);
    }

    public function test_can_show_session_with_attendance(): void
    {
        $session = Session::create(['class_id' => $this->class->id, 'module_id' => $this->module->id, 'book_id' => $this->book->id, 'chapter_index' => 0, 'teacher_id' => $this->teacher->id, 'date' => '2026-04-09']);
        Attendance::create(['session_id' => $session->id, 'student_id' => $this->student1->id, 'status' => 'present', 'participation_level' => 2]);
        $response = $this->getJson("/api/sessions/{$session->id}");
        $response->assertOk()->assertJsonPath('data.id', $session->id)->assertJsonCount(1, 'data.attendance_records');
    }

    public function test_can_delete_session_cascades_attendance(): void
    {
        $session = Session::create(['class_id' => $this->class->id, 'module_id' => $this->module->id, 'book_id' => $this->book->id, 'chapter_index' => 0, 'teacher_id' => $this->teacher->id, 'date' => '2026-04-09']);
        Attendance::create(['session_id' => $session->id, 'student_id' => $this->student1->id, 'status' => 'present']);
        $response = $this->deleteJson("/api/sessions/{$session->id}");
        $response->assertStatus(204);
        $this->assertDatabaseCount('attendance', 0);
    }
}
