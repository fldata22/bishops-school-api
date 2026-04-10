<?php
namespace Tests\Feature;

use App\Models\Church;
use App\Models\Denomination;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentTest extends TestCase
{
    use RefreshDatabase;

    private SchoolClass $class;
    private Church $church;

    protected function setUp(): void
    {
        parent::setUp();
        $this->class = SchoolClass::create(['name' => 'Makarios']);
        $denomination = Denomination::create(['name' => 'QFC', 'abbreviation' => 'QFC']);
        $this->church = Church::create(['name' => 'Main Branch', 'denomination_id' => $denomination->id]);
    }

    public function test_can_list_students(): void
    {
        Student::create(['name' => 'Kwame Asante', 'class_id' => $this->class->id, 'church_id' => $this->church->id, 'gender' => 'male']);
        $response = $this->getJson('/api/students');
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_filter_students_by_class(): void
    {
        $other = SchoolClass::create(['name' => 'Poimen']);
        Student::create(['name' => 'Student A', 'class_id' => $this->class->id, 'church_id' => $this->church->id, 'gender' => 'male']);
        Student::create(['name' => 'Student B', 'class_id' => $other->id, 'church_id' => $this->church->id, 'gender' => 'female']);
        $response = $this->getJson("/api/students?class_id={$this->class->id}");
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_create_student(): void
    {
        $response = $this->postJson('/api/students', [
            'name' => 'Kwame Asante', 'class_id' => $this->class->id, 'church_id' => $this->church->id, 'gender' => 'male',
        ]);
        $response->assertStatus(201)->assertJsonPath('data.name', 'Kwame Asante');
    }

    public function test_create_student_validates_gender_enum(): void
    {
        $response = $this->postJson('/api/students', [
            'name' => 'Test', 'class_id' => $this->class->id, 'church_id' => $this->church->id, 'gender' => 'invalid',
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['gender']);
    }

    public function test_can_show_student(): void
    {
        $student = Student::create(['name' => 'Kwame', 'class_id' => $this->class->id, 'church_id' => $this->church->id, 'gender' => 'male']);
        $response = $this->getJson("/api/students/{$student->id}");
        $response->assertOk()->assertJsonPath('data.name', 'Kwame');
    }

    public function test_can_update_student(): void
    {
        $student = Student::create(['name' => 'Kwame', 'class_id' => $this->class->id, 'church_id' => $this->church->id, 'gender' => 'male']);
        $response = $this->putJson("/api/students/{$student->id}", [
            'name' => 'Kwame Updated', 'class_id' => $this->class->id, 'church_id' => $this->church->id, 'gender' => 'male',
        ]);
        $response->assertOk()->assertJsonPath('data.name', 'Kwame Updated');
    }

    public function test_can_delete_student(): void
    {
        $student = Student::create(['name' => 'Kwame', 'class_id' => $this->class->id, 'church_id' => $this->church->id, 'gender' => 'male']);
        $response = $this->deleteJson("/api/students/{$student->id}");
        $response->assertStatus(204);
    }

    public function test_can_upload_student_image(): void
    {
        Storage::fake('public');
        $student = Student::create(['name' => 'Kwame', 'class_id' => $this->class->id, 'church_id' => $this->church->id, 'gender' => 'male']);
        $file = UploadedFile::fake()->image('photo.jpg', 200, 200);

        $response = $this->postJson("/api/students/{$student->id}/image", ['image' => $file]);

        $response->assertOk()->assertJsonPath('data.id', $student->id);
        $student->refresh();
        $this->assertNotNull($student->image);
        $this->assertStringContainsString('/storage/students/', $student->image);
    }

    public function test_upload_image_rejects_non_image_file(): void
    {
        Storage::fake('public');
        $student = Student::create(['name' => 'Kwame', 'class_id' => $this->class->id, 'church_id' => $this->church->id, 'gender' => 'male']);
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->postJson("/api/students/{$student->id}/image", ['image' => $file]);

        $response->assertStatus(422)->assertJsonValidationErrors(['image']);
    }
}
