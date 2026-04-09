<?php
namespace Tests\Feature;

use App\Models\Denomination;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DenominationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_denominations(): void
    {
        Denomination::create(['name' => 'Qodesh Family Church', 'abbreviation' => 'QFC']);
        Denomination::create(['name' => 'Loyalty House International', 'abbreviation' => 'LHI']);
        $response = $this->getJson('/api/denominations');
        $response->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.name', 'Qodesh Family Church');
    }

    public function test_can_create_denomination(): void
    {
        $response = $this->postJson('/api/denominations', ['name' => 'Qodesh Family Church', 'abbreviation' => 'QFC']);
        $response->assertStatus(201)->assertJsonPath('data.name', 'Qodesh Family Church');
        $this->assertDatabaseHas('denominations', ['abbreviation' => 'QFC']);
    }

    public function test_create_denomination_validates_required_fields(): void
    {
        $response = $this->postJson('/api/denominations', []);
        $response->assertStatus(422)->assertJsonValidationErrors(['name', 'abbreviation']);
    }

    public function test_can_show_denomination(): void
    {
        $d = Denomination::create(['name' => 'QFC', 'abbreviation' => 'QFC']);
        $response = $this->getJson("/api/denominations/{$d->id}");
        $response->assertOk()->assertJsonPath('data.name', 'QFC');
    }

    public function test_can_update_denomination(): void
    {
        $d = Denomination::create(['name' => 'QFC', 'abbreviation' => 'QFC']);
        $response = $this->putJson("/api/denominations/{$d->id}", ['name' => 'Qodesh Family Church', 'abbreviation' => 'QFC']);
        $response->assertOk()->assertJsonPath('data.name', 'Qodesh Family Church');
    }

    public function test_can_delete_denomination(): void
    {
        $d = Denomination::create(['name' => 'QFC', 'abbreviation' => 'QFC']);
        $response = $this->deleteJson("/api/denominations/{$d->id}");
        $response->assertStatus(204);
        $this->assertDatabaseMissing('denominations', ['id' => $d->id]);
    }
}
