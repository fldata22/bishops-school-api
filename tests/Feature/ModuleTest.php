<?php
namespace Tests\Feature;

use App\Models\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_modules(): void
    {
        Module::create(['name' => 'Loyalty', 'code' => 'L', 'topics' => ['Intro', 'Chapter 1']]);
        $response = $this->getJson('/api/modules');
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_create_module(): void
    {
        $response = $this->postJson('/api/modules', [
            'name' => 'Loyalty', 'code' => 'L', 'topics' => ['Introduction', 'What is Loyalty?', 'Loyalty to God'],
        ]);
        $response->assertStatus(201)->assertJsonPath('data.name', 'Loyalty')->assertJsonPath('data.topics.0', 'Introduction');
    }

    public function test_create_module_validates_topics_is_array(): void
    {
        $response = $this->postJson('/api/modules', ['name' => 'Loyalty', 'code' => 'L', 'topics' => 'not an array']);
        $response->assertStatus(422)->assertJsonValidationErrors(['topics']);
    }

    public function test_can_show_module(): void
    {
        $module = Module::create(['name' => 'Loyalty', 'code' => 'L', 'topics' => ['Intro']]);
        $response = $this->getJson("/api/modules/{$module->id}");
        $response->assertOk()->assertJsonPath('data.code', 'L');
    }

    public function test_can_update_module(): void
    {
        $module = Module::create(['name' => 'Loyalty', 'code' => 'L', 'topics' => ['Intro']]);
        $response = $this->putJson("/api/modules/{$module->id}", ['name' => 'Loyalty Updated', 'code' => 'LU', 'topics' => ['Intro', 'New Topic']]);
        $response->assertOk()->assertJsonPath('data.name', 'Loyalty Updated');
    }

    public function test_can_delete_module(): void
    {
        $module = Module::create(['name' => 'Loyalty', 'code' => 'L', 'topics' => ['Intro']]);
        $response = $this->deleteJson("/api/modules/{$module->id}");
        $response->assertStatus(204);
    }
}
