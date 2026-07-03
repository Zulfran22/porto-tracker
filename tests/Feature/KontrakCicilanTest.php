<?php

namespace Tests\Feature;

use App\Models\KontrakCicilanEmas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KontrakCicilanTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/kontrak-cicilan');

        $response->assertOk();
    }

    public function test_kontrak_can_be_created(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/kontrak-cicilan', [
            'nomor_kontrak' => '17805391142154415301',
            'cabang' => 'CP Bontang',
            'no_rekening' => '1090 9266 2901 5244',
            'tanggal_mulai' => '2026-06-04',
            'tenor_bulan' => 12,
            'total_gram' => 5,
            'angsuran_bulan' => 1032662,
            'sewa_modal' => 2033750,
            'biaya_admin' => 25000,
            'catatan' => 'PIC Toni Sugianto',
        ]);

        $response->assertRedirect(route('kontrak-cicilan.index'));

        $this->assertDatabaseHas('kontrak_cicilan_emas', [
            'user_id' => $user->id,
            'nomor_kontrak' => '17805391142154415301',
            'angsuran_bulan' => 1032662,
            'status' => 'aktif',
            'tanggal_selesai' => '2027-06-04 00:00:00',
        ]);
    }

    public function test_kontrak_can_be_deleted_only_by_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $kontrak = KontrakCicilanEmas::create([
            'user_id' => $owner->id,
            'nomor_kontrak' => 'X',
            'tanggal_mulai' => '2026-06-04',
            'tanggal_selesai' => '2027-06-04',
            'tenor_bulan' => 12,
            'total_gram' => 5,
            'angsuran_bulan' => 1032662,
        ]);

        $this->actingAs($other)->delete("/kontrak-cicilan/{$kontrak->id}")->assertForbidden();
        $this->assertDatabaseHas('kontrak_cicilan_emas', ['id' => $kontrak->id]);

        $this->actingAs($owner)->delete("/kontrak-cicilan/{$kontrak->id}");
        $this->assertDatabaseMissing('kontrak_cicilan_emas', ['id' => $kontrak->id]);
    }

    public function test_kontrak_can_be_created_with_a_file_and_file_is_removed_on_delete(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('kontrak.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)->post('/kontrak-cicilan', [
            'nomor_kontrak' => '17805391142154415301',
            'tanggal_mulai' => '2026-06-04',
            'tenor_bulan' => 12,
            'total_gram' => 5,
            'angsuran_bulan' => 1032662,
            'file_kontrak' => $file,
        ]);

        $response->assertRedirect(route('kontrak-cicilan.index'));

        $kontrak = KontrakCicilanEmas::where('user_id', $user->id)->first();
        $this->assertNotNull($kontrak->file_kontrak);
        Storage::disk('public')->assertExists($kontrak->file_kontrak);

        $this->actingAs($user)->delete("/kontrak-cicilan/{$kontrak->id}");
        Storage::disk('public')->assertMissing($kontrak->file_kontrak);
    }

    public function test_file_upload_rejects_disallowed_mime_types(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('virus.exe', 100, 'application/x-msdownload');

        $response = $this->actingAs($user)->post('/kontrak-cicilan', [
            'nomor_kontrak' => '17805391142154415301',
            'tanggal_mulai' => '2026-06-04',
            'tenor_bulan' => 12,
            'total_gram' => 5,
            'angsuran_bulan' => 1032662,
            'file_kontrak' => $file,
        ]);

        $response->assertSessionHasErrors('file_kontrak');
        $this->assertDatabaseMissing('kontrak_cicilan_emas', ['nomor_kontrak' => '17805391142154415301']);
    }

    public function test_kontrak_can_be_updated_by_owner(): void
    {
        $user = User::factory()->create();

        $kontrak = KontrakCicilanEmas::create([
            'user_id' => $user->id,
            'nomor_kontrak' => 'OLD-1',
            'tanggal_mulai' => '2026-06-04',
            'tanggal_selesai' => '2027-06-04',
            'tenor_bulan' => 12,
            'total_gram' => 5,
            'angsuran_bulan' => 1032662,
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($user)->put("/kontrak-cicilan/{$kontrak->id}", [
            'nomor_kontrak' => 'NEW-1',
            'tanggal_mulai' => '2026-06-04',
            'tenor_bulan' => 12,
            'total_gram' => 5,
            'angsuran_bulan' => 1100000,
            'status' => 'lunas',
        ]);

        $response->assertRedirect(route('kontrak-cicilan.index'));

        $this->assertDatabaseHas('kontrak_cicilan_emas', [
            'id' => $kontrak->id,
            'nomor_kontrak' => 'NEW-1',
            'angsuran_bulan' => 1100000,
            'status' => 'lunas',
        ]);
    }

    public function test_kontrak_cannot_be_updated_by_non_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $kontrak = KontrakCicilanEmas::create([
            'user_id' => $owner->id,
            'nomor_kontrak' => 'OLD-1',
            'tanggal_mulai' => '2026-06-04',
            'tanggal_selesai' => '2027-06-04',
            'tenor_bulan' => 12,
            'total_gram' => 5,
            'angsuran_bulan' => 1032662,
            'status' => 'aktif',
        ]);

        $this->actingAs($other)->put("/kontrak-cicilan/{$kontrak->id}", [
            'nomor_kontrak' => 'HACKED',
            'tanggal_mulai' => '2026-06-04',
            'tenor_bulan' => 12,
            'total_gram' => 5,
            'angsuran_bulan' => 1,
            'status' => 'aktif',
        ])->assertForbidden();

        $this->assertDatabaseHas('kontrak_cicilan_emas', ['id' => $kontrak->id, 'nomor_kontrak' => 'OLD-1']);
    }

    public function test_updating_kontrak_with_new_file_removes_old_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $oldFile = UploadedFile::fake()->create('old.pdf', 100, 'application/pdf');
        $newFile = UploadedFile::fake()->create('new.pdf', 100, 'application/pdf');

        $oldPath = $oldFile->store('kontrak', 'public');

        $kontrak = KontrakCicilanEmas::create([
            'user_id' => $user->id,
            'nomor_kontrak' => 'X',
            'tanggal_mulai' => '2026-06-04',
            'tanggal_selesai' => '2027-06-04',
            'tenor_bulan' => 12,
            'total_gram' => 5,
            'angsuran_bulan' => 1032662,
            'status' => 'aktif',
            'file_kontrak' => $oldPath,
        ]);

        $this->actingAs($user)->put("/kontrak-cicilan/{$kontrak->id}", [
            'nomor_kontrak' => 'X',
            'tanggal_mulai' => '2026-06-04',
            'tenor_bulan' => 12,
            'total_gram' => 5,
            'angsuran_bulan' => 1032662,
            'status' => 'aktif',
            'file_kontrak' => $newFile,
        ]);

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($kontrak->refresh()->file_kontrak);
    }

    public function test_kontrak_can_be_updated_with_file_via_post_method_spoof(): void
    {
        // Frontend sends file uploads as POST + _method=put spoof (a real PUT with a multipart
        // body is unreliably parsed by PHP/Laravel) — this locks in that path stays working.
        Storage::fake('public');
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('kontrak.pdf', 100, 'application/pdf');

        $kontrak = KontrakCicilanEmas::create([
            'user_id' => $user->id,
            'nomor_kontrak' => 'OLD-1',
            'tanggal_mulai' => '2026-06-04',
            'tanggal_selesai' => '2027-06-04',
            'tenor_bulan' => 12,
            'total_gram' => 5,
            'angsuran_bulan' => 1032662,
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($user)->post("/kontrak-cicilan/{$kontrak->id}", [
            '_method' => 'put',
            'nomor_kontrak' => 'NEW-1',
            'tanggal_mulai' => '2026-06-04',
            'tenor_bulan' => 12,
            'total_gram' => 5,
            'angsuran_bulan' => 1032662,
            'status' => 'lunas',
            'file_kontrak' => $file,
        ]);

        $response->assertRedirect(route('kontrak-cicilan.index'));

        $kontrak->refresh();
        $this->assertSame('NEW-1', $kontrak->nomor_kontrak);
        $this->assertSame('lunas', $kontrak->status);
        Storage::disk('public')->assertExists($kontrak->file_kontrak);
    }
}
