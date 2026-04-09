<?php
namespace Tests\Feature;

use App\Models\Church;
use App\Models\Denomination;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChurchTest extends TestCase
{
    use RefreshDatabase;

    private Denomination $denomination;

    protected function setUp(): void
    {
        parent::setUp();
        $this->denomination = Denomination::create(['name' => 'QFC', 'abbreviation' => 'QFC']);
    }

    public function test_can_list_churches(): void
    {
        Church::create(['name' => 'Church A', 'denomination_id' => $this->denomination->id]);
        Church::create(['name' => 'Church B', 'denomination_id' => $this->denomination->id]);
        $response = $this->getJson('/api/churches');
        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_can_filter_churches_by_denomination(): void
    {
        $other = Denomination::create(['name' => 'LHI', 'abbreviation' => 'LHI']);
        Church::create(['name' => 'Church A', 'denomination_id' => $this->denomination->id]);
        Church::create(['name' => 'Church B', 'denomination_id' => $other->id]);
        $response = $this->getJson("/api/churches?denomination_id={$this->denomination->id}");
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_can_create_church(): void
    {
        $response = $this->postJson('/api/churches', ['name' => 'Main Branch', 'denomination_id' => $this->denomination->id]);
        $response->assertStatus(201)->assertJsonPath('data.name', 'Main Branch');
    }

    public function test_create_church_validates_required_fields(): void
    {
        $response = $this->postJson('/api/churches', []);
        $response->assertStatus(422)->assertJsonValidationErrors(['name', 'denomination_id']);
    }

    public function test_can_show_church(): void
    {
        $church = Church::create(['name' => 'Main', 'denomination_id' => $this->denomination->id]);
        $response = $this->getJson("/api/churches/{$church->id}");
        $response->assertOk()->assertJsonPath('data.name', 'Main');
    }

    public function test_can_update_church(): void
    {
        $church = Church::create(['name' => 'Main', 'denomination_id' => $this->denomination->id]);
        $response = $this->putJson("/api/churches/{$church->id}", ['name' => 'Main Updated', 'denomination_id' => $this->denomination->id]);
        $response->assertOk()->assertJsonPath('data.name', 'Main Updated');
    }

    public function test_can_delete_church(): void
    {
        $church = Church::create(['name' => 'Main', 'denomination_id' => $this->denomination->id]);
        $response = $this->deleteJson("/api/churches/{$church->id}");
        $response->assertStatus(204);
    }
}
