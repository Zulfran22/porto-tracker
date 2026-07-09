<?php

namespace Tests\Feature;

use App\Models\InvestmentTarget;
use App\Models\InvestmentType;
use App\Models\Target;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TargetTest extends TestCase
{
    use RefreshDatabase;

    private function targetsPayload(array $extra = []): array
    {
        return ['targets' => [
            ['type_name' => 'Emas Tunai', 'target_value' => 20],
            ['type_name' => 'Dana Darurat', 'target_value' => 25000000],
            ['type_name' => 'Reksa Dana', 'target_value' => 60000000],
            ['type_name' => 'SBN', 'target_value' => 10000000],
            ...$extra,
        ]];
    }

    public function test_index_creates_a_default_target_on_first_visit(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/target')->assertOk();

        $this->assertSame(1, Target::where('user_id', $user->id)->count());
        $this->assertSame(4, InvestmentType::where('user_id', $user->id)->count());
    }

    public function test_index_does_not_duplicate_the_target_on_repeat_visits(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/target');
        $this->actingAs($user)->get('/target');

        $this->assertSame(1, Target::where('user_id', $user->id)->count());
        $this->assertSame(4, InvestmentType::where('user_id', $user->id)->count());
    }

    public function test_update_persists_to_the_existing_row_without_creating_a_second_one(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/target');

        $this->actingAs($user)->put('/target', $this->targetsPayload())->assertRedirect();

        $this->assertSame(4, InvestmentTarget::where('user_id', $user->id)->count());
        $this->assertDatabaseHas('investment_targets', ['user_id' => $user->id, 'type_name' => 'Emas Tunai', 'target_value' => 20]);

        // Repeat save must update, not duplicate
        $this->actingAs($user)->put('/target', ['targets' => [
            ['type_name' => 'Emas Tunai', 'target_value' => 30],
            ['type_name' => 'Dana Darurat', 'target_value' => 25000000],
            ['type_name' => 'Reksa Dana', 'target_value' => 60000000],
            ['type_name' => 'SBN', 'target_value' => 10000000],
        ]]);
        $this->assertSame(4, InvestmentTarget::where('user_id', $user->id)->count());
        $this->assertDatabaseHas('investment_targets', ['user_id' => $user->id, 'type_name' => 'Emas Tunai', 'target_value' => 30]);
    }

    public function test_update_creates_a_target_when_none_exists_yet(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/target', $this->targetsPayload())->assertRedirect();

        $this->assertDatabaseHas('investment_targets', ['user_id' => $user->id, 'type_name' => 'Emas Tunai', 'target_value' => 20]);
    }

    public function test_update_persists_custom_investment_type_target(): void
    {
        $user = User::factory()->create();
        InvestmentType::ensureDefaultsFor($user->id);
        InvestmentType::create(['user_id' => $user->id, 'name' => 'Kripto', 'unit' => 'rupiah', 'is_default' => false, 'urutan' => 5]);

        $this->actingAs($user)->put('/target', $this->targetsPayload([
            ['type_name' => 'Kripto', 'target_value' => 5000000],
        ]))->assertRedirect();

        $this->assertDatabaseHas('investment_targets', ['user_id' => $user->id, 'type_name' => 'Kripto', 'target_value' => 5000000]);
    }

    public function test_budget_bulanan_can_be_updated(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/target');

        $this->actingAs($user)->put('/target/budget', [
            'budget_bulanan' => 5500000,
        ])->assertRedirect();

        $this->assertSame(1, Target::where('user_id', $user->id)->count());
        $this->assertDatabaseHas('targets', ['user_id' => $user->id, 'budget_bulanan' => 5500000]);
    }

    public function test_budget_update_creates_a_target_when_none_exists_yet(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/target/budget', [
            'budget_bulanan' => 4000000,
        ])->assertRedirect();

        $this->assertDatabaseHas('targets', ['user_id' => $user->id, 'budget_bulanan' => 4000000]);
    }

    public function test_budget_below_minimum_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/target/budget', [
            'budget_bulanan' => 500000,
        ])->assertSessionHasErrors('budget_bulanan');
    }

    public function test_info_receives_the_saved_budget(): void
    {
        // Simulator "Rencana & simulasi saving/bulan" pindah dari Dashboard ke
        // Info — budgetBulanan sekarang cuma perlu tersedia di sana.
        $user = User::factory()->create();
        Target::create([
            'user_id' => $user->id,
            'budget_bulanan' => 4500000,
        ]);

        $this->actingAs($user)->get('/info')
            ->assertInertia(fn ($page) => $page->where('budgetBulanan', 4500000));
    }
}
