<?php
namespace Tests\Feature;

use App\Models\Book;
use App\Models\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_modules(): void
    {
        $module = Module::create(['name' => 'Loyalty', 'code' => 'L']);
        Book::create(['module_id' => $module->id, 'name' => 'Book 1', 'chapters' => ['Intro', 'Chapter 1'], 'position' => 0]);
        $response = $this->getJson('/api/modules');
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_create_module(): void
    {
        $response = $this->postJson('/api/modules', [
            'name' => 'Loyalty',
            'code' => 'L',
            'books' => [
                ['name' => 'Book One', 'chapters' => ['Introduction', 'What is Loyalty?', 'Loyalty to God']],
            ],
        ]);
        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Loyalty')
            ->assertJsonPath('data.books.0.name', 'Book One')
            ->assertJsonPath('data.books.0.chapters.0', 'Introduction');
    }

    public function test_can_create_module_without_books(): void
    {
        $response = $this->postJson('/api/modules', ['name' => 'Loyalty', 'code' => 'L']);
        $response->assertStatus(201)->assertJsonPath('data.name', 'Loyalty');
    }

    public function test_create_module_validates_books_is_array(): void
    {
        $response = $this->postJson('/api/modules', ['name' => 'Loyalty', 'code' => 'L', 'books' => 'not an array']);
        $response->assertStatus(422)->assertJsonValidationErrors(['books']);
    }

    public function test_can_show_module(): void
    {
        $module = Module::create(['name' => 'Loyalty', 'code' => 'L']);
        Book::create(['module_id' => $module->id, 'name' => 'Book 1', 'chapters' => ['Intro'], 'position' => 0]);
        $response = $this->getJson("/api/modules/{$module->id}");
        $response->assertOk()->assertJsonPath('data.code', 'L');
    }

    public function test_can_update_module(): void
    {
        $module = Module::create(['name' => 'Loyalty', 'code' => 'L']);
        $response = $this->putJson("/api/modules/{$module->id}", [
            'name' => 'Loyalty Updated',
            'code' => 'LU',
            'books' => [
                ['name' => 'Book One', 'chapters' => ['Intro', 'New Topic']],
            ],
        ]);
        $response->assertOk()->assertJsonPath('data.name', 'Loyalty Updated');
    }

    public function test_can_delete_module(): void
    {
        $module = Module::create(['name' => 'Loyalty', 'code' => 'L']);
        $response = $this->deleteJson("/api/modules/{$module->id}");
        $response->assertStatus(204);
    }
}
