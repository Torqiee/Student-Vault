<?php

use App\Mail\PrivateDataKeyMail;
use App\Models\PersonalVaultEntry;
use App\Models\PrivateDataAccessRequest;
use App\Models\PrivateResourceKey;
use App\Models\User;
use App\Services\Encryption\EncryptionManager;
use App\Services\Encryption\PrivateDataKeyService;
use App\Services\Vault\FileVaultService;
use App\Services\Vault\PersonalVaultService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('studentvault.encryption.aes.key', base64_encode(str_repeat('a', 32)));
    config()->set('studentvault.encryption.des.key', base64_encode(str_repeat('b', 8)));
    config()->set('studentvault.encryption.rc4.key', base64_encode(str_repeat('c', 16)));
    Storage::fake('local');
    Mail::fake();
});

function privateEntry(User $owner, string $label = 'Owner profile', string $studentId = 'STUDENT-123'): int
{
    return app(PersonalVaultService::class)->create($owner, [
        'label' => $label,
        'category' => 'student_profile',
        'fields' => ['student_id' => $studentId],
    ])->id;
}

function requestAccess(User $requester, User $owner, string $type, int $id): PrivateDataAccessRequest
{
    test()->actingAs($requester)->post(route('private-access.store'), [
        'owner_email' => $owner->email,
        'resource_type' => $type,
        'resource_id' => $id,
    ])->assertRedirect(route('private-access.index'));

    return PrivateDataAccessRequest::query()->latest('id')->firstOrFail();
}

it('requires authentication to request or decide access', function (): void {
    $this->get(route('private-access.index'))->assertRedirect(route('login'));
    $this->post(route('private-access.store'))->assertRedirect(route('login'));
});

it('lets only the owner approve and wraps the resource key with the requesters public key', function (): void {
    $owner = User::factory()->create();
    $requester = User::factory()->create();
    $entryId = privateEntry($owner);
    $accessRequest = requestAccess($requester, $owner, 'personal_data', $entryId);

    expect($accessRequest->status)->toBe('pending')
        ->and($accessRequest->encrypted_symmetric_key)->toBeNull();

    $this->actingAs($requester)->post(route('private-access.approve', $accessRequest))->assertNotFound();
    $this->actingAs($owner)->post(route('private-access.approve', $accessRequest))->assertRedirect();

    $accessRequest->refresh();
    $keys = app(PrivateDataKeyService::class);
    $resourceKey = $keys->findResourceKey($owner->id, 'personal_data', $entryId);
    $wrappedKey = $accessRequest->encrypted_symmetric_key;

    expect($accessRequest->status)->toBe('approved')
        ->and($requester->fresh()->cryptoKey->algorithm)->toBe('RSA-OAEP-SHA256')
        ->and(strlen(base64_decode($wrappedKey, true)))->toBe(384)
        ->and($wrappedKey)->not->toContain(base64_encode($resourceKey))
        ->and($keys->decryptForRequester($accessRequest, $requester))->toBe($resourceKey);

    $entry = PersonalVaultEntry::query()->findOrFail($entryId);
    expect(app(EncryptionManager::class)->decryptWithKey($entry->encrypted_label, $resourceKey))->toBe('Owner profile');

    Mail::assertSent(PrivateDataKeyMail::class, fn (PrivateDataKeyMail $mail): bool => $mail->hasTo($requester->email)
        && $mail->encryptedKey === $wrappedKey
        && ! str_contains($mail->render(), base64_encode($resourceKey)));

    $this->actingAs($owner)->get(route('private-access.index'))->assertOk()->assertSee($wrappedKey);
    $this->actingAs($requester)->get(route('private-access.index'))->assertOk()->assertDontSee($wrappedKey);
    $this->actingAs($requester)->get(route('personal-vault.edit', $entryId))->assertNotFound();
});

it('does not let another user unwrap an approved key', function (): void {
    $owner = User::factory()->create();
    $requester = User::factory()->create();
    $intruder = User::factory()->create();
    $accessRequest = requestAccess($requester, $owner, 'personal_data', privateEntry($owner));
    $this->actingAs($owner)->post(route('private-access.approve', $accessRequest));

    app(PrivateDataKeyService::class)->decryptForRequester($accessRequest->fresh(), $intruder);
})->throws(RuntimeException::class, 'Access to this key is not authorized.');

it('stores resource keys and private keys encrypted at rest', function (): void {
    $owner = User::factory()->create();
    $requester = User::factory()->create();
    $entryId = privateEntry($owner);
    $this->actingAs($owner)->post(route('private-access.approve', requestAccess($requester, $owner, 'personal_data', $entryId)));

    $stored = PrivateResourceKey::query()->sole();
    $rawKey = app(PrivateDataKeyService::class)->findResourceKey($owner->id, 'personal_data', $entryId);
    $privateKey = $requester->fresh()->cryptoKey;

    expect($stored->encrypted_symmetric_key)->not->toContain(base64_encode($rawKey))
        ->and(base64_decode(Crypt::decryptString($stored->encrypted_symmetric_key), true))->toBe($rawKey)
        ->and($privateKey->encrypted_private_key)->not->toContain('PRIVATE KEY')
        ->and(Crypt::decryptString($privateKey->encrypted_private_key))->toContain('PRIVATE KEY')
        ->and($stored->toArray())->not->toHaveKey('encrypted_symmetric_key')
        ->and($privateKey->toArray())->not->toHaveKey('encrypted_private_key');
});

it('re-encrypts a legacy personal entry with its own key when access is approved', function (): void {
    $owner = User::factory()->create();
    $requester = User::factory()->create();
    $manager = app(EncryptionManager::class);
    $entry = $owner->personalVaultEntries()->create([
        'encrypted_label' => $manager->encrypt('Legacy profile'),
        'category' => 'student_profile',
        'encrypted_fields' => json_encode(['student_id' => $manager->encrypt('LEGACY-1')]),
    ]);

    $this->actingAs($owner)->get(route('personal-vault.index'))->assertOk()->assertSee('Legacy profile')->assertSee('LEGACY-1');

    $accessRequest = requestAccess($requester, $owner, 'personal_data', $entry->id);
    $this->actingAs($owner)->post(route('private-access.approve', $accessRequest))->assertRedirect();

    $resourceKey = app(PrivateDataKeyService::class)->decryptForRequester($accessRequest->fresh(), $requester);
    $entry->refresh();

    expect($manager->usesResourceKey($entry->encrypted_label))->toBeTrue()
        ->and($manager->decryptWithKey($entry->encrypted_label, $resourceKey))->toBe('Legacy profile');

    $this->actingAs($owner)->get(route('personal-vault.index'))->assertOk()->assertSee('Legacy profile')->assertSee('LEGACY-1');
});

it('shares an approved file through a copy encrypted with the distributed key and removes it on revoke', function (): void {
    $owner = User::factory()->create();
    $requester = User::factory()->create();
    $content = str_repeat('identity-scan-bytes', 70000);
    $file = app(FileVaultService::class)->store($owner, UploadedFile::fake()->createWithContent('scan.pdf', $content), 'identity');

    $accessRequest = requestAccess($requester, $owner, 'vault_file', $file->id);
    $this->actingAs($owner)->post(route('private-access.approve', $accessRequest))->assertRedirect();

    $stored = PrivateResourceKey::query()->sole();
    $copyPath = PrivateDataKeyService::SHARED_COPY_DIRECTORY.$stored->shared_stored_name;
    $resourceKey = app(PrivateDataKeyService::class)->decryptForRequester($accessRequest->fresh(), $requester);
    $stream = app(FileVaultService::class)->decryptRecipientCopyToStream($file, $resourceKey);

    expect(Storage::disk('local')->get($copyPath))->toStartWith("SVR1\n")->not->toContain('identity-scan-bytes')
        ->and(stream_get_contents($stream))->toBe($content);
    fclose($stream);

    expect(fn () => app(FileVaultService::class)->decryptRecipientCopyToStream($file, random_bytes(32)))
        ->toThrow(RuntimeException::class);

    $this->actingAs($owner)->post(route('private-access.revoke', $accessRequest))->assertRedirect();

    expect($accessRequest->fresh()->status)->toBe('revoked')
        ->and($accessRequest->fresh()->encrypted_symmetric_key)->toBeNull()
        ->and(PrivateResourceKey::query()->count())->toBe(0);
    Storage::disk('local')->assertMissing($copyPath);
});

it('handles decline without generating a key', function (): void {
    $owner = User::factory()->create();
    $requester = User::factory()->create();
    $accessRequest = requestAccess($requester, $owner, 'personal_data', privateEntry($owner));

    $this->actingAs($owner)->post(route('private-access.decline', $accessRequest))->assertRedirect();

    expect($accessRequest->fresh()->status)->toBe('declined')
        ->and($accessRequest->fresh()->encrypted_symmetric_key)->toBeNull()
        ->and($requester->fresh()->cryptoKey)->toBeNull();
    Mail::assertNothingSent();
});

it('does not create requests for missing or another owners resource', function (): void {
    $owner = User::factory()->create();
    $otherOwner = User::factory()->create();
    $requester = User::factory()->create();
    $entryId = privateEntry($otherOwner);

    $this->actingAs($requester)->post(route('private-access.store'), [
        'owner_email' => $owner->email, 'resource_type' => 'personal_data', 'resource_id' => $entryId,
    ])->assertSessionHasErrors('resource_id');

    expect(PrivateDataAccessRequest::query()->count())->toBe(0);
});

it('revokes requests and erases keys when the private entry is deleted', function (): void {
    $owner = User::factory()->create();
    $requester = User::factory()->create();
    $entryId = privateEntry($owner);
    $accessRequest = requestAccess($requester, $owner, 'personal_data', $entryId);
    $this->actingAs($owner)->post(route('private-access.approve', $accessRequest));

    $this->actingAs($owner)->delete(route('personal-vault.destroy', $entryId))->assertRedirect();

    expect($accessRequest->fresh()->status)->toBe('revoked')
        ->and($accessRequest->fresh()->encrypted_symmetric_key)->toBeNull()
        ->and(PrivateResourceKey::query()->count())->toBe(0);
});
