<?php

use App\Models\User;
use App\Services\Encryption\EncryptionManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config()->set('studentvault.encryption.aes.key', base64_encode(str_repeat('a', 32)));
    config()->set('studentvault.encryption.des.key', base64_encode(str_repeat('b', 8)));
    config()->set('studentvault.encryption.rc4.key', base64_encode(str_repeat('c', 16)));
    Storage::fake('local');
});

it('stores and downloads a file larger than one encryption chunk', function () {
    $user = User::factory()->create();
    $content = "%PDF-1.4\n".str_repeat('A', 1100000);
    $file = UploadedFile::fake()->createWithContent('lecture.pdf', $content);

    $this->actingAs($user)->post(route('vault.store'), [
        'category' => 'documents',
        'file' => $file,
    ])->assertRedirect(route('vault.index'));

    $vaultFile = $user->vaultFiles()->sole();
    $ciphertext = Storage::disk('local')->get('vault/'.$vaultFile->stored_name);
    expect($ciphertext)->toStartWith("SV2\n")->not->toContain($content);
    expect($vaultFile->original_hash)->toBe(hash('sha256', $content));

    $response = $this->actingAs($user)->get(route('vault.download', $vaultFile));
    $response->assertOk();
    expect($response->streamedContent())->toBe($content);
});

it('rejects a file whose content or extension does not match its category', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('vault.store'), [
        'category' => 'videos',
        'file' => UploadedFile::fake()->createWithContent('lecture.pdf', "%PDF-1.4\ncontent"),
    ])->assertSessionHasErrors('file');

    expect($user->vaultFiles()->count())->toBe(0);
});

it('rejects a tampered encrypted file before sending a download', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('vault.store'), [
        'category' => 'documents',
        'file' => UploadedFile::fake()->createWithContent('lecture.pdf', "%PDF-1.4\ncontent"),
    ])->assertRedirect(route('vault.index'));
    $vaultFile = $user->vaultFiles()->sole();
    Storage::disk('local')->put('vault/'.$vaultFile->stored_name, "SV2\ninvalid\n");

    $this->actingAs($user)->from(route('vault.index'))->get(route('vault.download', $vaultFile))
        ->assertRedirect(route('vault.index'))->assertSessionHasErrors('file');
});

it('downloads files stored in the original envelope format', function () {
    $user = User::factory()->create();
    $content = 'original file';
    $vaultFile = $user->vaultFiles()->create([
        'original_name' => 'old.txt',
        'stored_name' => 'old.vault',
        'category' => 'documents',
        'cipher' => 'aes',
        'mime_type' => 'text/plain',
        'original_size' => strlen($content),
        'original_hash' => hash('sha256', $content),
    ]);
    Storage::disk('local')->put('vault/old.vault', app(EncryptionManager::class)->encrypt($content));

    $response = $this->actingAs($user)->get(route('vault.download', $vaultFile));
    $response->assertOk();
    expect($response->streamedContent())->toBe($content);
});

it('forbids another user from downloading or deleting a file', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $vaultFile = $owner->vaultFiles()->create([
        'original_name' => 'private.pdf',
        'stored_name' => 'private.vault',
        'category' => 'documents',
        'cipher' => 'aes',
        'mime_type' => 'application/pdf',
        'original_size' => 7,
        'original_hash' => hash('sha256', 'private'),
    ]);

    $this->actingAs($otherUser)->get(route('vault.download', $vaultFile))->assertForbidden();
    $this->actingAs($otherUser)->delete(route('vault.destroy', $vaultFile))->assertForbidden();
    $this->assertModelExists($vaultFile);
});

it('rejects files larger than fifty megabytes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('vault.store'), [
        'category' => 'documents',
        'file' => UploadedFile::fake()->create('large.pdf', 51201, 'application/pdf'),
    ])->assertSessionHasErrors('file');

    expect($user->vaultFiles()->count())->toBe(0);
});
