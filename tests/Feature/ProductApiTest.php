<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    private function authenticate(): array
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth-token')->plainTextToken;

        return ['Authorization' => 'Bearer '.$token];
    }

    public function test_authenticated_user_can_list_products(): void
    {
        Product::factory()->count(3)->create();

        $response = $this->withHeaders($this->authenticate())->getJson('/api/products');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Products retrieved successfully.')
            ->assertJsonCount(3, 'data');
    }

    public function test_authenticated_user_can_create_a_product(): void
    {
        $payload = [
            'name' => 'Wireless Mouse',
            'sku' => 'WM-1000',
            'description' => 'Ergonomic wireless mouse',
            'price' => 49.99,
            'stock' => 25,
            'is_active' => true,
        ];

        $response = $this->withHeaders($this->authenticate())->postJson('/api/products', $payload);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Product created successfully.')
            ->assertJsonPath('data.name', 'Wireless Mouse')
            ->assertJsonPath('data.sku', 'WM-1000')
            ->assertJsonPath('data.price', '49.99');

        $this->assertDatabaseHas('products', [
            'sku' => 'WM-1000',
            'stock' => 25,
        ]);
    }

    public function test_authenticated_user_can_view_a_product(): void
    {
        $product = Product::factory()->create([
            'name' => 'Mechanical Keyboard',
            'sku' => 'MK-2000',
        ]);

        $response = $this->withHeaders($this->authenticate())->getJson("/api/products/{$product->id}");

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Product retrieved successfully.')
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.sku', 'MK-2000');
    }

    public function test_authenticated_user_can_update_a_product(): void
    {
        $product = Product::factory()->create([
            'name' => 'Old Name',
            'sku' => 'SKU-OLD',
            'price' => 19.99,
            'stock' => 5,
            'is_active' => true,
        ]);

        $response = $this->withHeaders($this->authenticate())->putJson("/api/products/{$product->id}", [
            'name' => 'New Name',
            'sku' => 'SKU-NEW',
            'price' => 29.99,
            'stock' => 10,
            'is_active' => false,
            'description' => 'Updated description',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Product updated successfully.')
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.sku', 'SKU-NEW')
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'New Name',
            'sku' => 'SKU-NEW',
            'stock' => 10,
            'is_active' => 0,
        ]);
    }

    public function test_authenticated_user_can_delete_a_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->withHeaders($this->authenticate())->deleteJson("/api/products/{$product->id}");

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Product deleted successfully.');

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    public function test_product_creation_requires_unique_sku(): void
    {
        Product::factory()->create([
            'sku' => 'SKU-EXISTS',
        ]);

        $response = $this->withHeaders($this->authenticate())->postJson('/api/products', [
            'name' => 'Duplicate SKU Product',
            'sku' => 'SKU-EXISTS',
            'description' => 'Duplicate sku',
            'price' => 10.00,
            'stock' => 1,
            'is_active' => true,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sku']);
    }

    public function test_products_endpoints_require_authentication(): void
    {
        $product = Product::factory()->create();

        $this->getJson('/api/products')->assertUnauthorized();
        $this->postJson('/api/products', [])->assertUnauthorized();
        $this->getJson("/api/products/{$product->id}")->assertUnauthorized();
        $this->putJson("/api/products/{$product->id}", [])->assertUnauthorized();
        $this->deleteJson("/api/products/{$product->id}")->assertUnauthorized();
    }
}
