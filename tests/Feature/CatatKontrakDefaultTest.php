<?php

namespace Tests\Feature;

use App\Models\KontrakCicilanEmas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CatatKontrakDefaultTest extends TestCase
{
    use RefreshDatabase;

    public function test_catat_page_receives_active_kontrak_as_default(): void
    {
        $user = User::factory()->create();

        $kontrak = KontrakCicilanEmas::create([
            'user_id' => $user->id,
            'nomor_kontrak' => '17805391142154415301',
            'tanggal_mulai' => '2026-06-04',
            'tanggal_selesai' => '2027-06-04',
            'tenor_bulan' => 12,
            'total_gram' => 5,
            'angsuran_bulan' => 1032662,
            'status' => 'aktif',
        ]);

        $this->actingAs($user)
            ->get('/catat')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catat')
                ->where('aktifKontrak.nomor_kontrak', '17805391142154415301')
                ->where('aktifKontrak.angsuran_bulan', 1032662)
            );
    }

    public function test_catat_page_has_null_kontrak_when_none_active(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/catat')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catat')
                ->where('aktifKontrak', null)
            );
    }
}
