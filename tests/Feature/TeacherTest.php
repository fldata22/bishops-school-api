<?php
namespace Tests\Feature;

use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_teachers(): void
    {
        Teacher::create(['name' => 'Pastor Emmanuel Asante']);
        Teacher::create(['name' => 'Deaconess Grace Mensah']);
        $response = $this->getJson('/api/teachers');
        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_can_create_teacher(): void
    {
        $response = $this->postJson('/api/teachers', ['name' => 'Pastor Emmanuel Asante']);
        $response->assertStatus(201)->assertJsonPath('data.name', 'Pastor Emmanuel Asante');
    }

    public function test_can_show_teacher(): void
    {
        $teacher = Teacher::create(['name' => 'Pastor Emmanuel']);
        $response = $this->getJson("/api/teachers/{$teacher->id}");
        $response->assertOk()->assertJsonPath('data.name', 'Pastor Emmanuel');
    }

    public function test_can_update_teacher(): void
    {
        $teacher = Teacher::create(['name' => 'Pastor Emmanuel']);
        $response = $this->putJson("/api/teachers/{$teacher->id}", ['name' => 'Pastor Emmanuel Asante']);
        $response->assertOk()->assertJsonPath('data.name', 'Pastor Emmanuel Asante');
    }

    public function test_can_delete_teacher(): void
    {
        $teacher = Teacher::create(['name' => 'Pastor Emmanuel']);
        $response = $this->deleteJson("/api/teachers/{$teacher->id}");
        $response->assertStatus(204);
    }
}
