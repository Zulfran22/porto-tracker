<?php

namespace Tests\Feature;

use App\Models\Portofolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortofolioTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'bulan' => '2026-06',
            'emas_gram' => 0.5,
            'harga_emas' => 2500000,
            'cicilan' => 1032662,
            'dana_darurat' => 1000000,
            'reksa_dana' => 500000,
            'sbn' => 0,
            'catatan' => 'test',
        ], $overrides);
    }

    public function test_dashboard_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_store_creates_a_new_month(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/portofolio', $this->payload());

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('portofolios', [
            'user_id' => $user->id,
            'bulan' => '2026-06',
            'catatan' => 'test',
        ]);
    }

    public function test_store_updates_the_same_month_instead_of_duplicating(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/portofolio', $this->payload(['emas_gram' => 0.5]));
        $this->actingAs($user)->post('/portofolio', $this->payload(['emas_gram' => 0.75]));

        $this->assertSame(1, Portofolio::where('user_id', $user->id)->where('bulan', '2026-06')->count());
        $this->assertDatabaseHas('portofolios', ['user_id' => $user->id, 'bulan' => '2026-06', 'emas_gram' => 0.75]);
    }

    public function test_deleting_a_month_soft_deletes_it(): void
    {
        $user = User::factory()->create();
        $portofolio = Portofolio::create(array_merge(['user_id' => $user->id], $this->payload()));

        $this->actingAs($user)->delete("/portofolio/{$portofolio->id}");

        $this->assertSoftDeleted('portofolios', ['id' => $portofolio->id]);
    }

    public function test_re_catat_of_a_deleted_month_restores_it_instead_of_duplicating(): void
    {
        $user = User::factory()->create();
        $portofolio = Portofolio::create(array_merge(['user_id' => $user->id], $this->payload(['emas_gram' => 0.5])));
        $portofolio->delete();

        $this->actingAs($user)->post('/portofolio', $this->payload(['emas_gram' => 1.25]));

        $this->assertSame(1, Portofolio::withTrashed()->where('user_id', $user->id)->where('bulan', '2026-06')->count());
        $this->assertDatabaseHas('portofolios', [
            'id' => $portofolio->id,
            'emas_gram' => 1.25,
            'deleted_at' => null,
        ]);
    }

    public function test_portofolio_can_be_updated_only_by_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $portofolio = Portofolio::create(array_merge(['user_id' => $owner->id], $this->payload()));

        $this->actingAs($other)->put("/portofolio/{$portofolio->id}", $this->payload(['emas_gram' => 9]))
            ->assertForbidden();

        $this->actingAs($owner)->put("/portofolio/{$portofolio->id}", $this->payload(['emas_gram' => 9]))
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('portofolios', ['id' => $portofolio->id, 'emas_gram' => 9]);
    }

    public function test_update_rejects_retargeting_bulan_to_a_month_that_already_exists(): void
    {
        $user = User::factory()->create();
        Portofolio::create(array_merge(['user_id' => $user->id], $this->payload(['bulan' => '2026-05'])));
        $june = Portofolio::create(array_merge(['user_id' => $user->id], $this->payload(['bulan' => '2026-06'])));

        $this->actingAs($user)
            ->put("/portofolio/{$june->id}", $this->payload(['bulan' => '2026-05']))
            ->assertSessionHasErrors('bulan');
    }

    public function test_bulan_must_be_a_valid_year_month_format(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/portofolio', $this->payload(['bulan' => 'not-a-month']))
            ->assertSessionHasErrors('bulan');
    }

    public function test_catat_context_returns_current_month_defaults_when_none_exists(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/catat-context');

        $response->assertOk()->assertJson(['bulan' => now()->format('Y-m'), 'existing' => null]);
    }

    public function test_catat_context_with_id_returns_that_specific_month_for_editing(): void
    {
        $user = User::factory()->create();
        $portofolio = Portofolio::create(array_merge(['user_id' => $user->id], $this->payload(['bulan' => '2026-03'])));

        $response = $this->actingAs($user)->get('/api/catat-context?id='.$portofolio->id);

        $response->assertOk()->assertJsonPath('bulan', '2026-03')->assertJsonPath('existing.id', $portofolio->id);
    }

    public function test_catat_context_with_id_cannot_leak_another_users_portofolio(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $portofolio = Portofolio::create(array_merge(['user_id' => $owner->id], $this->payload()));

        $this->actingAs($other)->get('/api/catat-context?id='.$portofolio->id)->assertNotFound();
    }
}
