<?php

namespace Tests\Feature;

use App\Models\InvestmentType;
use App\Models\KontrakCicilanEmas;
use App\Models\Portofolio;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PortofolioTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'bulan' => '2026-06',
            'harga_emas' => 2500000,
            'cicilan' => 1032662,
            'catatan' => 'test',
            'items' => [
                ['type_name' => 'Emas Tunai', 'unit' => 'gram', 'gram' => 0.5],
                ['type_name' => 'Dana Darurat', 'unit' => 'rupiah', 'jumlah' => 1000000],
                ['type_name' => 'Reksa Dana', 'unit' => 'rupiah', 'jumlah' => 500000],
                ['type_name' => 'SBN', 'unit' => 'rupiah', 'jumlah' => 0],
            ],
        ], $overrides);
    }

    private function createPortofolio(User $user, array $overrides = []): Portofolio
    {
        $data = $this->payload($overrides);
        $p = Portofolio::create([
            'user_id' => $user->id,
            'bulan' => $data['bulan'],
            'harga_emas' => $data['harga_emas'],
            'cicilan' => $data['cicilan'],
            'catatan' => $data['catatan'],
        ]);
        foreach ($data['items'] as $item) {
            $p->items()->create([
                'type_name' => $item['type_name'],
                'unit' => $item['unit'],
                'gram' => $item['unit'] === 'gram' ? ($item['gram'] ?? 0) : null,
                'jumlah' => $item['unit'] === 'rupiah' ? ($item['jumlah'] ?? 0) : null,
            ]);
        }

        return $p;
    }

    public function test_dashboard_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    // cicilanPaid mengontrol notif jatuh tempo di dashboard: hilang begitu data
    // bulan berjalan tersimpan dengan field cicilan terisi (lewat halaman Catat
    // yang dituju tombol "Catat pembayaran" di notif).
    public function test_total_menghitung_gram_kontrak_proporsional_pada_bulan_snapshot(): void
    {
        $user = User::factory()->create();

        // Kontrak mulai 6 bulan lalu, tenor 12, 4g.
        KontrakCicilanEmas::create([
            'user_id' => $user->id,
            'nomor_kontrak' => 'TOT-1',
            'tanggal_mulai' => now()->subMonths(6)->toDateString(),
            'tanggal_selesai' => now()->addMonths(6)->toDateString(),
            'tenor_bulan' => 12,
            'total_gram' => 4,
            'angsuran_bulan' => 1000000,
            'status' => 'aktif',
        ]);

        // Snapshot bulan lalu dinilai dengan angsuran yang berjalan PADA bulan
        // itu (6 dari 12 → 2g), bukan kondisi hari ini — riwayat tidak boleh
        // ditulis ulang tiap angsuran bertambah.
        $bulanLalu = Portofolio::create([
            'user_id' => $user->id,
            'bulan' => now()->subMonth()->format('Y-m'),
            'harga_emas' => 1000000,
            'cicilan' => 0,
        ]);

        $this->assertSame(2000000, $bulanLalu->fresh()->total);

        // Snapshot SEBELUM kontrak ada tidak kebagian gram kontrak sama sekali.
        $praKontrak = Portofolio::create([
            'user_id' => $user->id,
            'bulan' => now()->subMonths(8)->format('Y-m'),
            'harga_emas' => 1000000,
            'cicilan' => 0,
        ]);

        $this->assertSame(0, $praKontrak->fresh()->total);
    }

    public function test_menyimpan_cicilan_menyinkronkan_transaksi_pengeluaran(): void
    {
        $user = User::factory()->create();

        // Simpan bulan dengan cicilan → transaksi expense "Cicilan Emas" ikut
        // tercipta supaya cashflow/saving rate memuat pengeluaran ini.
        $this->actingAs($user)->post('/portofolio', $this->payload(['cicilan' => 1032662]));

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'expense',
            'kategori' => Transaction::KATEGORI_CICILAN_EMAS,
            'jumlah' => 1032662,
        ]);

        // Simpan ulang bulan sama dengan nilai berbeda → di-update, bukan dobel.
        $this->actingAs($user)->post('/portofolio', $this->payload(['cicilan' => 2000000]));

        $this->assertSame(1, Transaction::where('user_id', $user->id)
            ->where('kategori', Transaction::KATEGORI_CICILAN_EMAS)->count());
        $this->assertDatabaseHas('transactions', [
            'kategori' => Transaction::KATEGORI_CICILAN_EMAS,
            'jumlah' => 2000000,
        ]);

        // Cicilan dikosongkan → transaksi sinkronannya ikut hilang.
        $this->actingAs($user)->post('/portofolio', $this->payload(['cicilan' => 0]));

        $this->assertSame(0, Transaction::where('user_id', $user->id)
            ->where('kategori', Transaction::KATEGORI_CICILAN_EMAS)->count());
    }

    public function test_dashboard_cicilan_paid_mengikuti_cicilan_bulan_berjalan(): void
    {
        $user = User::factory()->create();

        // Belum ada data sama sekali → belum dianggap bayar.
        $this->actingAs($user)->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->where('cicilanPaid', false));

        // Data bulan lain (walau cicilan terisi) tidak dihitung.
        $this->createPortofolio($user, ['bulan' => '2020-01']);
        $this->actingAs($user)->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->where('cicilanPaid', false));

        // Data bulan berjalan dengan cicilan terisi → notif dianggap lunas.
        $this->createPortofolio($user, ['bulan' => now()->format('Y-m')]);
        $this->actingAs($user)->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->where('cicilanPaid', true));
    }

    public function test_dashboard_cicilan_paid_false_ketika_cicilan_bulan_berjalan_nol(): void
    {
        $user = User::factory()->create();
        $this->createPortofolio($user, ['bulan' => now()->format('Y-m'), 'cicilan' => 0]);

        $this->actingAs($user)->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->where('cicilanPaid', false));
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
        $portofolio = Portofolio::where('user_id', $user->id)->where('bulan', '2026-06')->first();
        $this->assertDatabaseHas('portfolio_items', [
            'portofolio_id' => $portofolio->id,
            'type_name' => 'Emas Tunai',
            'gram' => 0.5,
        ]);
    }

    public function test_store_updates_the_same_month_instead_of_duplicating(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/portofolio', $this->payload([
            'items' => [['type_name' => 'Emas Tunai', 'unit' => 'gram', 'gram' => 0.5]],
        ]));
        $this->actingAs($user)->post('/portofolio', $this->payload([
            'items' => [['type_name' => 'Emas Tunai', 'unit' => 'gram', 'gram' => 0.75]],
        ]));

        $this->assertSame(1, Portofolio::where('user_id', $user->id)->where('bulan', '2026-06')->count());
        $portofolio = Portofolio::where('user_id', $user->id)->where('bulan', '2026-06')->first();
        $this->assertSame(1, $portofolio->items()->count());
        $this->assertDatabaseHas('portfolio_items', ['portofolio_id' => $portofolio->id, 'gram' => 0.75]);
    }

    public function test_deleting_a_month_soft_deletes_it(): void
    {
        $user = User::factory()->create();
        $portofolio = $this->createPortofolio($user);

        $this->actingAs($user)->delete("/portofolio/{$portofolio->id}");

        $this->assertSoftDeleted('portofolios', ['id' => $portofolio->id]);
    }

    public function test_re_catat_of_a_deleted_month_restores_it_instead_of_duplicating(): void
    {
        $user = User::factory()->create();
        $portofolio = $this->createPortofolio($user, [
            'items' => [['type_name' => 'Emas Tunai', 'unit' => 'gram', 'gram' => 0.5]],
        ]);
        $portofolio->delete();

        $this->actingAs($user)->post('/portofolio', $this->payload([
            'items' => [['type_name' => 'Emas Tunai', 'unit' => 'gram', 'gram' => 1.25]],
        ]));

        $this->assertSame(1, Portofolio::withTrashed()->where('user_id', $user->id)->where('bulan', '2026-06')->count());
        $this->assertDatabaseHas('portofolios', ['id' => $portofolio->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('portfolio_items', ['portofolio_id' => $portofolio->id, 'gram' => 1.25]);
    }

    public function test_portofolio_can_be_updated_only_by_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $portofolio = $this->createPortofolio($owner);

        $this->actingAs($other)->put("/portofolio/{$portofolio->id}", $this->payload())
            ->assertForbidden();

        $this->actingAs($owner)->put("/portofolio/{$portofolio->id}", $this->payload([
            'items' => [['type_name' => 'Emas Tunai', 'unit' => 'gram', 'gram' => 9]],
        ]))->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('portfolio_items', ['portofolio_id' => $portofolio->id, 'gram' => 9]);
    }

    public function test_update_rejects_retargeting_bulan_to_a_month_that_already_exists(): void
    {
        $user = User::factory()->create();
        $this->createPortofolio($user, ['bulan' => '2026-05']);
        $june = $this->createPortofolio($user, ['bulan' => '2026-06']);

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

    public function test_harga_emas_optional_without_gram_item_and_no_kontrak(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/portofolio', $this->payload([
            'harga_emas' => null,
            'items' => [
                ['type_name' => 'Emas Tunai', 'unit' => 'gram', 'gram' => 0],
                ['type_name' => 'Dana Darurat', 'unit' => 'rupiah', 'jumlah' => 500000],
            ],
        ]));

        $response->assertSessionDoesntHaveErrors('harga_emas');
        $response->assertRedirect(route('dashboard'));
    }

    public function test_harga_emas_required_when_gram_item_has_value(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/portofolio', $this->payload([
            'harga_emas' => null,
            'items' => [['type_name' => 'Emas Tunai', 'unit' => 'gram', 'gram' => 2]],
        ]));

        $response->assertSessionHasErrors('harga_emas');
    }

    public function test_harga_emas_required_when_active_kontrak_exists_even_without_gram(): void
    {
        $user = User::factory()->create();
        KontrakCicilanEmas::create([
            'user_id' => $user->id, 'nomor_kontrak' => 'X-1',
            'tanggal_mulai' => '2026-01-04', 'tanggal_selesai' => '2027-01-04',
            'tenor_bulan' => 12, 'total_gram' => 3, 'angsuran_bulan' => 500000,
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($user)->post('/portofolio', $this->payload([
            'harga_emas' => null,
            'items' => [['type_name' => 'Emas Tunai', 'unit' => 'gram', 'gram' => 0]],
        ]));

        $response->assertSessionHasErrors('harga_emas');
    }

    public function test_total_attribute_includes_custom_investment_type(): void
    {
        $user = User::factory()->create();
        InvestmentType::ensureDefaultsFor($user->id);
        InvestmentType::create(['user_id' => $user->id, 'name' => 'Kripto', 'unit' => 'rupiah', 'is_default' => false, 'urutan' => 5]);

        $portofolio = $this->createPortofolio($user, [
            'items' => [
                ['type_name' => 'Emas Tunai', 'unit' => 'gram', 'gram' => 1],
                ['type_name' => 'Kripto', 'unit' => 'rupiah', 'jumlah' => 250000],
            ],
        ]);

        $fresh = Portofolio::with('items')->find($portofolio->id);
        $this->assertEquals((1 * 2500000) + 250000, $fresh->total);
    }

    public function test_catat_context_returns_current_month_defaults_when_none_exists(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/catat-context');

        $response->assertOk()->assertJson(['bulan' => now()->format('Y-m'), 'existing' => null]);
    }

    public function test_catat_context_seeds_default_investment_types_for_fresh_user(): void
    {
        // Akun baru bisa membuka modal Catat (FAB) sebelum pernah ke dashboard —
        // endpoint ini harus ikut men-seed jenis investasi default, kalau tidak
        // form modalnya kosong tanpa field sama sekali.
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/catat-context');

        $response->assertOk();
        $this->assertCount(4, $response->json('investmentTypes'));
    }

    public function test_catat_context_returns_401_json_for_guests(): void
    {
        // Kontrak yang diandalkan modal Catat: sesi kedaluwarsa dijawab 401 JSON
        // (bukan redirect HTML) supaya frontend bisa memuat ulang ke halaman login.
        $this->getJson('/api/catat-context')->assertUnauthorized();
    }

    public function test_catat_context_with_id_returns_that_specific_month_for_editing(): void
    {
        $user = User::factory()->create();
        $portofolio = $this->createPortofolio($user, ['bulan' => '2026-03']);

        $response = $this->actingAs($user)->get('/api/catat-context?id='.$portofolio->id);

        $response->assertOk()->assertJsonPath('bulan', '2026-03')->assertJsonPath('existing.id', $portofolio->id);
    }

    public function test_catat_context_with_id_cannot_leak_another_users_portofolio(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $portofolio = $this->createPortofolio($owner);

        $this->actingAs($other)->get('/api/catat-context?id='.$portofolio->id)->assertNotFound();
    }
}
