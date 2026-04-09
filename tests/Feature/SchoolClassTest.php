<?php
namespace Tests\Feature;

use App\Models\SchoolClass;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolClassTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_classes(): void
    {
        SchoolClass::create(['name' => 'Makarios']);
        SchoolClass::create(['name' => 'Poimen']);
        $response = $this->getJson('/api/classes');
        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_can_create_class(): void
    {
        $response = $this->postJson('/api/classes', ['name' => 'Makarios']);
        $response->assertStatus(201)->assertJsonPath('data.name', 'Makarios');
    }

    public function test_can_show_class(): void
    {
        $class = SchoolClass::create(['name' => 'Makarios']);
        $response = $this->getJson("/api/classes/{$class->id}");
        $response->assertOk()->assertJsonPath('data.name', 'Makarios');
    }

    public function test_can_update_class(): void
    {
        $class = SchoolClass::create(['name' => 'Makarios']);
        $response = $this->putJson("/api/classes/{$class->id}", ['name' => 'Makarios Updated']);
        $response->assertOk()->assertJsonPath('data.name', 'Makarios Updated');
    }

    public function test_can_delete_class(): void
    {
        $class = SchoolClass::create(['name' => 'Makarios']);
        $response = $this->deleteJson("/api/classes/{$class->id}");
        $response->assertStatus(204);
    }
}
