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

class BookTest extends TestCase
{
    use RefreshDatabase;

    private Module $module;

    protected function setUp(): void
    {
        parent::setUp();
        $this->module = Module::create(['name' => 'Loyalty', 'code' => 'L']);
    }

    public function test_can_create_book_on_module(): void
    {
        $response = $this->postJson("/api/modules/{$this->module->id}/books", [
            'name' => 'Book One',
            'chapters' => ['Introduction', 'What is Loyalty?', 'Loyalty to God'],
        ]);
        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Book One')
            ->assertJsonPath('data.module_id', $this->module->id)
            ->assertJsonPath('data.chapters.0', 'Introduction');
        $this->assertDatabaseHas('books', ['name' => 'Book One', 'module_id' => $this->module->id]);
    }

    public function test_create_book_validates_name_required(): void
    {
        $response = $this->postJson("/api/modules/{$this->module->id}/books", [
            'chapters' => ['Ch1'],
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    public function test_create_book_validates_chapters_required(): void
    {
        $response = $this->postJson("/api/modules/{$this->module->id}/books", [
            'name' => 'Book One',
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['chapters']);
    }

    public function test_create_book_validates_chapters_not_empty(): void
    {
        $response = $this->postJson("/api/modules/{$this->module->id}/books", [
            'name' => 'Book One',
            'chapters' => [],
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['chapters']);
    }

    public function test_can_update_book_name(): void
    {
        $book = Book::create(['module_id' => $this->module->id, 'name' => 'Old Name', 'chapters' => ['Ch1'], 'position' => 0]);
        $response = $this->putJson("/api/books/{$book->id}", ['name' => 'New Name']);
        $response->assertOk()->assertJsonPath('data.name', 'New Name');
        $this->assertDatabaseHas('books', ['id' => $book->id, 'name' => 'New Name']);
    }

    public function test_can_update_book_chapters(): void
    {
        $book = Book::create(['module_id' => $this->module->id, 'name' => 'Book One', 'chapters' => ['Ch1'], 'position' => 0]);
        $response = $this->putJson("/api/books/{$book->id}", ['chapters' => ['Ch1', 'Ch2', 'Ch3']]);
        $response->assertOk()->assertJsonCount(3, 'data.chapters');
    }

    public function test_can_update_book_position(): void
    {
        $book = Book::create(['module_id' => $this->module->id, 'name' => 'Book One', 'chapters' => ['Ch1'], 'position' => 0]);
        $response = $this->putJson("/api/books/{$book->id}", ['position' => 5]);
        $response->assertOk()->assertJsonPath('data.position', 5);
    }

    public function test_can_delete_book(): void
    {
        $book = Book::create(['module_id' => $this->module->id, 'name' => 'Book One', 'chapters' => ['Ch1'], 'position' => 0]);
        $response = $this->deleteJson("/api/books/{$book->id}");
        $response->assertStatus(204);
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    public function test_deleting_book_cascades_to_sessions(): void
    {
        $class = SchoolClass::create(['name' => 'Makarios']);
        $teacher = Teacher::create(['name' => 'Pastor Emmanuel']);
        $denomination = Denomination::create(['name' => 'QFC', 'abbreviation' => 'QFC']);
        $church = Church::create(['name' => 'Main', 'denomination_id' => $denomination->id]);
        $student = Student::create(['name' => 'A', 'class_id' => $class->id, 'church_id' => $church->id, 'gender' => 'male']);

        $book = Book::create(['module_id' => $this->module->id, 'name' => 'Book One', 'chapters' => ['Ch1'], 'position' => 0]);
        $session = Session::create([
            'class_id' => $class->id,
            'module_id' => $this->module->id,
            'book_id' => $book->id,
            'chapter_index' => 0,
            'teacher_id' => $teacher->id,
            'date' => '2026-04-09',
        ]);
        Attendance::create(['session_id' => $session->id, 'student_id' => $student->id, 'status' => 'present']);

        $this->assertDatabaseCount('sessions', 1);
        $this->deleteJson("/api/books/{$book->id}")->assertStatus(204);
        $this->assertDatabaseCount('sessions', 0);
        $this->assertDatabaseCount('attendance', 0);
    }

    public function test_returns_404_for_nonexistent_module(): void
    {
        $response = $this->postJson('/api/modules/999/books', ['name' => 'Book', 'chapters' => ['Ch1']]);
        $response->assertStatus(404);
    }

    public function test_returns_404_for_nonexistent_book(): void
    {
        $response = $this->putJson('/api/books/999', ['name' => 'Updated']);
        $response->assertStatus(404);
    }

    public function test_auto_assigns_position_when_not_provided(): void
    {
        Book::create(['module_id' => $this->module->id, 'name' => 'Book One', 'chapters' => ['Ch1'], 'position' => 0]);
        Book::create(['module_id' => $this->module->id, 'name' => 'Book Two', 'chapters' => ['Ch1'], 'position' => 1]);

        $response = $this->postJson("/api/modules/{$this->module->id}/books", [
            'name' => 'Book Three',
            'chapters' => ['Ch1'],
        ]);
        $response->assertStatus(201)->assertJsonPath('data.position', 2);
    }
}
