<?php

namespace Tests\Feature\Api;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test_token')->plainTextToken;
    }

    private function authHeaders(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ];
    }

    public function test_unauthenticated_request_to_items_returns_401(): void
    {
        $response = $this->getJson('/api/items');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_authenticated_user_can_list_items(): void
    {
        Item::factory()->count(3)->create();

        $response = $this->withHeaders($this->authHeaders())->getJson('/api/items');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'amount',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_authenticated_user_can_create_item(): void
    {
        $payload = [
            'title' => 'Cold Brew Bottle 250ml',
            'description' => 'Ready to drink bottled cold brew coffee',
            'amount' => 45,
        ];

        $response = $this->withHeaders($this->authHeaders())->postJson('/api/items', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Item created successfully.',
                'data' => [
                    'title' => 'Cold Brew Bottle 250ml',
                    'description' => 'Ready to drink bottled cold brew coffee',
                    'amount' => 45,
                ],
            ]);

        $this->assertDatabaseHas('items', [
            'title' => 'Cold Brew Bottle 250ml',
            'amount' => 45,
        ]);
    }

    public function test_create_item_validation_fails_for_missing_required_fields(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/items', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'amount']);
    }

    public function test_create_item_validation_fails_for_title_exceeding_max_length(): void
    {
        $longTitle = str_repeat('a', 51);

        $response = $this->withHeaders($this->authHeaders())->postJson('/api/items', [
            'title' => $longTitle,
            'amount' => 10,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_create_item_validation_fails_for_negative_amount(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/items', [
            'title' => 'Syrup Vanilla',
            'amount' => -5,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_authenticated_user_can_view_single_item(): void
    {
        $item = Item::factory()->create([
            'title' => 'Matcha Powder 100g',
            'description' => 'Ceremonial grade matcha',
            'amount' => 15,
        ]);

        $response = $this->withHeaders($this->authHeaders())->getJson('/api/items/'.$item->id);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $item->id,
                    'title' => 'Matcha Powder 100g',
                    'description' => 'Ceremonial grade matcha',
                    'amount' => 15,
                ],
            ]);
    }

    public function test_authenticated_user_can_update_item(): void
    {
        $item = Item::factory()->create([
            'title' => 'Caramel Syrup',
            'amount' => 20,
        ]);

        $updatePayload = [
            'title' => 'Salted Caramel Syrup',
            'amount' => 35,
        ];

        $response = $this->withHeaders($this->authHeaders())->putJson('/api/items/'.$item->id, $updatePayload);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Item updated successfully.',
                'data' => [
                    'id' => $item->id,
                    'title' => 'Salted Caramel Syrup',
                    'amount' => 35,
                ],
            ]);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'title' => 'Salted Caramel Syrup',
            'amount' => 35,
        ]);
    }

    public function test_authenticated_user_can_delete_item(): void
    {
        $item = Item::factory()->create();

        $response = $this->withHeaders($this->authHeaders())->deleteJson('/api/items/'.$item->id);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Item deleted successfully.',
            ]);

        $this->assertDatabaseMissing('items', [
            'id' => $item->id,
        ]);
    }

    public function test_accessing_non_existent_item_returns_404(): void
    {
        $response = $this->withHeaders($this->authHeaders())->getJson('/api/items/99999');

        $response->assertStatus(404);
    }
}
