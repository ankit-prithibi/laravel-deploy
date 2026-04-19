<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    private function authenticate(): array
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth-token')->plainTextToken;

        return ['Authorization' => 'Bearer '.$token];
    }

    public function test_authenticated_user_can_list_categories(): void
    {
        Category::factory()->count(3)->create();

        $response = $this->withHeaders($this->authenticate())->getJson('/api/categories');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Categories retrieved successfully.')
            ->assertJsonCount(3, 'data');
    }

    public function test_authenticated_user_can_create_a_category(): void
    {
        $response = $this->withHeaders($this->authenticate())->postJson('/api/categories', [
            'name' => 'Home Appliances',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Category created.')
            ->assertJsonPath('data.name', 'Home Appliances')
            ->assertJsonPath('data.slug', 'home-appliances');

        $this->assertDatabaseHas('categories', [
            'name' => 'Home Appliances',
            'slug' => 'home-appliances',
        ]);
    }

    public function test_authenticated_user_can_view_a_category(): void
    {
        $category = Category::factory()->create([
            'name' => 'Electronics',
            'slug' => 'electronics',
        ]);

        $response = $this->withHeaders($this->authenticate())->getJson("/api/categories/{$category->id}");

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Category retrieved successfully.')
            ->assertJsonPath('data.id', $category->id)
            ->assertJsonPath('data.slug', 'electronics');
    }

    public function test_authenticated_user_can_update_a_category(): void
    {
        $category = Category::factory()->create([
            'name' => 'Old Category',
            'slug' => 'old-category',
        ]);

        $response = $this->withHeaders($this->authenticate())->putJson("/api/categories/{$category->id}", [
            'name' => 'New Category',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Category updated successfully.')
            ->assertJsonPath('data.name', 'New Category')
            ->assertJsonPath('data.slug', 'new-category');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'New Category',
            'slug' => 'new-category',
        ]);
    }

    public function test_authenticated_user_can_delete_a_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->withHeaders($this->authenticate())->deleteJson("/api/categories/{$category->id}");

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Category deleted successfully.');

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_category_creation_requires_a_unique_name(): void
    {
        Category::factory()->create([
            'name' => 'Books',
            'slug' => 'books',
        ]);

        $response = $this->withHeaders($this->authenticate())->postJson('/api/categories', [
            'name' => 'Books',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'slug']);
    }

    public function test_categories_endpoints_require_authentication(): void
    {
        $category = Category::factory()->create();

        $this->getJson('/api/categories')->assertUnauthorized();
        $this->postJson('/api/categories', [])->assertUnauthorized();
        $this->getJson("/api/categories/{$category->id}")->assertUnauthorized();
        $this->putJson("/api/categories/{$category->id}", [])->assertUnauthorized();
        $this->deleteJson("/api/categories/{$category->id}")->assertUnauthorized();
    }
}
