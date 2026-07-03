<?php

namespace Tests\Feature;

use App\Models\Target;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TargetTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_creates_a_default_target_on_first_visit(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/target')->assertOk();

        $this->assertSame(1, Target::where('user_id', $user->id)->count());
    }

    public function test_index_does_not_duplicate_the_target_on_repeat_visits(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/target');
        $this->actingAs($user)->get('/target');

        $this->assertSame(1, Target::where('user_id', $user->id)->count());
    }

    public function test_update_persists_to_the_existing_row_without_creating_a_second_one(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/target');

        $this->actingAs($user)->put('/target', [
            'target_emas' => 20,
            'target_darurat' => 25000000,
            'target_reksa' => 60000000,
        ])->assertRedirect();

        $this->assertSame(1, Target::where('user_id', $user->id)->count());
        $this->assertDatabaseHas('targets', ['user_id' => $user->id, 'target_emas' => 20]);
    }

    public function test_update_creates_a_target_when_none_exists_yet(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/target', [
            'target_emas' => 15,
            'target_darurat' => 20000000,
            'target_reksa' => 55000000,
        ])->assertRedirect();

        $this->assertDatabaseHas('targets', ['user_id' => $user->id, 'target_emas' => 15]);
    }
}
