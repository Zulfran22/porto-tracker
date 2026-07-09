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
        Storage::fake('local');
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
        Storage::disk('local')->assertExists($kontrak->file_kontrak);

        $this->actingAs($user)->delete("/kontrak-cicilan/{$kontrak->id}");
        Storage::disk('local')->assertMissing($kontrak->file_kontrak);
    }

    public function test_file_upload_rejects_disallowed_mime_types(): void
    {
        Storage::fake('local');
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
        Storage::fake('local');
        $user = User::factory()->create();
        $oldFile = UploadedFile::fake()->create('old.pdf', 100, 'application/pdf');
        $newFile = UploadedFile::fake()->create('new.pdf', 100, 'application/pdf');

        $oldPath = $oldFile->store('kontrak', 'local');

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

        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($kontrak->refresh()->file_kontrak);
    }

    public function test_kontrak_can_be_updated_with_file_via_post_method_spoof(): void
    {
        // Frontend sends file uploads as POST + _method=put spoof (a real PUT with a multipart
        // body is unreliably parsed by PHP/Laravel) — this locks in that path stays working.
        Storage::fake('local');
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
        Storage::disk('local')->assertExists($kontrak->file_kontrak);
    }

    public function test_gram_terbayar_proporsional_dengan_angsuran_berjalan(): void
    {
        $user = User::factory()->create();

        // Mulai 6 bulan lalu, tenor 12, total 4g → 7 angsuran terbayar
        // (angsuran pertama dibayar saat kontrak dimulai) → 4 × 7/12 ≈ 2.3333g.
        $kontrak = KontrakCicilanEmas::create([
            'user_id' => $user->id,
            'nomor_kontrak' => 'GT-1',
            'tanggal_mulai' => now()->subMonths(6)->toDateString(),
            'tanggal_selesai' => now()->addMonths(6)->toDateString(),
            'tenor_bulan' => 12,
            'total_gram' => 4,
            'angsuran_bulan' => 1000000,
            'status' => 'aktif',
        ]);

        $this->assertEqualsWithDelta(2.3333, $kontrak->gram_terbayar, 0.0001);

        // Melewati tenor → dibatasi total gram kontrak, tidak lebih.
        $lunas = KontrakCicilanEmas::create([
            'user_id' => $user->id,
            'nomor_kontrak' => 'GT-2',
            'tanggal_mulai' => now()->subMonths(24)->toDateString(),
            'tanggal_selesai' => now()->subMonths(12)->toDateString(),
            'tenor_bulan' => 12,
            'total_gram' => 4,
            'angsuran_bulan' => 1000000,
            'status' => 'aktif',
        ]);

        $this->assertEqualsWithDelta(4.0, $lunas->gram_terbayar, 0.0001);
    }

    public function test_file_kontrak_mengikuti_disk_upload_yang_dikonfigurasi(): void
    {
        // Di produksi (Render) UPLOADS_DISK=s3 (Backblaze B2) karena filesystem
        // container ephemeral — pastikan seluruh siklus (upload, download,
        // hapus) benar-benar memakai disk dari config, bukan 'local' hardcoded.
        config(['filesystems.uploads' => 's3']);
        Storage::fake('s3');
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('kontrak.pdf', 100, 'application/pdf');

        $this->actingAs($user)->post('/kontrak-cicilan', [
            'nomor_kontrak' => 'R2-1',
            'tanggal_mulai' => '2026-06-04',
            'tenor_bulan' => 12,
            'total_gram' => 5,
            'angsuran_bulan' => 1032662,
            'file_kontrak' => $file,
        ]);

        $kontrak = KontrakCicilanEmas::where('user_id', $user->id)->first();
        $this->assertNotNull($kontrak->file_kontrak);
        Storage::disk('s3')->assertExists($kontrak->file_kontrak);

        $this->actingAs($user)->get(route('kontrak-cicilan.file', $kontrak->id))->assertOk();

        $this->actingAs($user)->delete("/kontrak-cicilan/{$kontrak->id}");
        Storage::disk('s3')->assertMissing($kontrak->file_kontrak);
    }

    public function test_file_kontrak_is_stored_privately_and_only_accessible_by_owner(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $file = UploadedFile::fake()->create('kontrak.pdf', 100, 'application/pdf');

        $this->actingAs($owner)->post('/kontrak-cicilan', [
            'nomor_kontrak' => '17805391142154415301',
            'tanggal_mulai' => '2026-06-04',
            'tenor_bulan' => 12,
            'total_gram' => 5,
            'angsuran_bulan' => 1032662,
            'file_kontrak' => $file,
        ]);

        $kontrak = KontrakCicilanEmas::where('user_id', $owner->id)->first();

        // Not on the public disk, so it's not reachable via the old /storage/... URL at all.
        Storage::disk('public')->assertMissing($kontrak->file_kontrak);
        Storage::disk('local')->assertExists($kontrak->file_kontrak);

        $this->actingAs($other)->get(route('kontrak-cicilan.file', $kontrak->id))->assertForbidden();
        $this->actingAs($owner)->get(route('kontrak-cicilan.file', $kontrak->id))->assertOk();
    }
}
