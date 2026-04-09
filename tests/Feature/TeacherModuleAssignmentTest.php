<?php
namespace Tests\Feature;

use App\Models\Module;
use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\TeacherModuleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherModuleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private Teacher $teacher;
    private Module $module;
    private SchoolClass $class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacher = Teacher::create(['name' => 'Pastor Emmanuel']);
        $this->module = Module::create(['name' => 'Loyalty', 'code' => 'L', 'topics' => ['Intro']]);
        $this->class = SchoolClass::create(['name' => 'Makarios']);
    }

    public function test_can_list_assignments(): void
    {
        TeacherModuleAssignment::create(['teacher_id' => $this->teacher->id, 'module_id' => $this->module->id, 'class_id' => $this->class->id]);
        $response = $this->getJson('/api/teacher-module-assignments');
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_filter_by_teacher(): void
    {
        $other = Teacher::create(['name' => 'Other']);
        TeacherModuleAssignment::create(['teacher_id' => $this->teacher->id, 'module_id' => $this->module->id, 'class_id' => $this->class->id]);
        TeacherModuleAssignment::create(['teacher_id' => $other->id, 'module_id' => $this->module->id, 'class_id' => $this->class->id]);
        $response = $this->getJson("/api/teacher-module-assignments?teacher_id={$this->teacher->id}");
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_create_assignment(): void
    {
        $response = $this->postJson('/api/teacher-module-assignments', [
            'teacher_id' => $this->teacher->id, 'module_id' => $this->module->id, 'class_id' => $this->class->id,
        ]);
        $response->assertStatus(201);
    }

    public function test_cannot_create_duplicate_assignment(): void
    {
        TeacherModuleAssignment::create(['teacher_id' => $this->teacher->id, 'module_id' => $this->module->id, 'class_id' => $this->class->id]);
        $response = $this->postJson('/api/teacher-module-assignments', [
            'teacher_id' => $this->teacher->id, 'module_id' => $this->module->id, 'class_id' => $this->class->id,
        ]);
        $response->assertStatus(422);
    }

    public function test_can_delete_assignment(): void
    {
        $assignment = TeacherModuleAssignment::create(['teacher_id' => $this->teacher->id, 'module_id' => $this->module->id, 'class_id' => $this->class->id]);
        $response = $this->deleteJson("/api/teacher-module-assignments/{$assignment->id}");
        $response->assertStatus(204);
    }
}
