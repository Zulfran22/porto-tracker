<?php

namespace Tests\Feature;

use App\Models\InvestmentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestmentTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_a_rupiah_type_scoped_to_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/investasi/tipe', ['name' => 'Kripto'])->assertRedirect();

        $this->assertDatabaseHas('investment_types', [
            'user_id' => $user->id, 'name' => 'Kripto', 'unit' => 'rupiah', 'is_default' => false,
        ]);
    }

    public function test_store_dedupes_on_repeat_submit_of_same_name(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/investasi/tipe', ['name' => 'Kripto']);
        $this->actingAs($user)->post('/investasi/tipe', ['name' => 'Kripto']);

        $this->assertSame(1, InvestmentType::where('user_id', $user->id)->where('name', 'Kripto')->count());
    }

    public function test_store_requires_a_name(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/investasi/tipe', ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_destroy_removes_an_owned_rupiah_type(): void
    {
        $user = User::factory()->create();
        InvestmentType::ensureDefaultsFor($user->id);
        $sbn = InvestmentType::where('user_id', $user->id)->where('name', 'SBN')->first();

        $this->actingAs($user)->delete("/investasi/tipe/{$sbn->id}")->assertRedirect();

        $this->assertDatabaseMissing('investment_types', ['id' => $sbn->id]);
    }

    public function test_destroy_is_forbidden_for_another_users_type(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        InvestmentType::ensureDefaultsFor($owner->id);
        $type = InvestmentType::where('user_id', $owner->id)->where('unit', 'rupiah')->first();

        $this->actingAs($intruder)->delete("/investasi/tipe/{$type->id}")->assertForbidden();
        $this->assertDatabaseHas('investment_types', ['id' => $type->id]);
    }

    public function test_destroy_is_forbidden_for_the_gram_type_even_when_owned(): void
    {
        $user = User::factory()->create();
        InvestmentType::ensureDefaultsFor($user->id);
        $emas = InvestmentType::where('user_id', $user->id)->where('unit', 'gram')->first();

        $this->actingAs($user)->delete("/investasi/tipe/{$emas->id}")->assertForbidden();
        $this->assertDatabaseHas('investment_types', ['id' => $emas->id]);
    }

    public function test_ensure_defaults_seeds_exactly_four_types_with_correct_shape(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard');

        $types = InvestmentType::where('user_id', $user->id)->orderBy('urutan')->get();
        $this->assertCount(4, $types);
        $this->assertEquals(['Emas Tunai', 'Dana Darurat', 'Reksa Dana', 'SBN'], $types->pluck('name')->all());
        $this->assertEquals(['gram', 'rupiah', 'rupiah', 'rupiah'], $types->pluck('unit')->all());
        $this->assertTrue($types->every(fn ($t) => $t->is_default));
    }

    public function test_ensure_defaults_is_idempotent_across_multiple_entry_points(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard');
        $this->actingAs($user)->get('/catat');
        $this->actingAs($user)->get('/target');

        $this->assertSame(4, InvestmentType::where('user_id', $user->id)->count());
    }
}
