<?php

namespace Tests\Feature;

use App\Models\CustomCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_category_can_be_created(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/keuangan/kategori', [
            'type' => 'expense',
            'name' => 'Langganan',
        ])->assertRedirect();

        $this->assertDatabaseHas('custom_categories', [
            'user_id' => $user->id,
            'type' => 'expense',
            'name' => 'Langganan',
        ]);
    }

    public function test_duplicate_category_for_the_same_user_is_not_created_twice(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/keuangan/kategori', ['type' => 'expense', 'name' => 'Langganan']);
        $this->actingAs($user)->post('/keuangan/kategori', ['type' => 'expense', 'name' => 'Langganan']);

        $this->assertSame(1, CustomCategory::where('user_id', $user->id)->count());
    }

    public function test_custom_category_can_be_deleted_only_by_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $category = CustomCategory::create(['user_id' => $owner->id, 'type' => 'expense', 'name' => 'Langganan']);

        $this->actingAs($other)->delete("/keuangan/kategori/{$category->id}")->assertForbidden();
        $this->assertDatabaseHas('custom_categories', ['id' => $category->id]);

        $this->actingAs($owner)->delete("/keuangan/kategori/{$category->id}");
        $this->assertDatabaseMissing('custom_categories', ['id' => $category->id]);
    }
}
