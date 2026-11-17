<?php

use App\Models\PrivateDataAccessRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;

uses(RefreshDatabase::class);

function privateEntry(User $owner): int
{
    return $owner->personalVaultEntries()->create([
        'encrypted_label' => 'ciphertext',
        'category' => 'other',
        'encrypted_fields' => '{}',
    ])->id;
}

it('requires authentication to request or decide access', function (): void {
    $this->get(route('private-access.index'))->assertRedirect(route('login'));
    $this->post(route('private-access.store'))->assertRedirect(route('login'));
});

it('lets a user request a specific private entry and only its owner approve it with a protected key', function (): void {
    $owner = User::factory()->create();
    $requester = User::factory()->create();
    $entryId = privateEntry($owner);

    $this->actingAs($requester)->post(route('private-access.store'), [
        'owner_email' => $owner->email,
        'resource_type' => 'personal_data',
        'resource_id' => $entryId,
    ])->assertRedirect(route('private-access.index'));

    $accessRequest = PrivateDataAccessRequest::query()->firstOrFail();
    expect($accessRequest->status)->toBe('pending')
        ->and($accessRequest->encrypted_symmetric_key)->toBeNull();

    $this->actingAs($requester)->post(route('private-access.approve', $accessRequest))->assertNotFound();
    $this->actingAs($owner)->post(route('private-access.approve', $accessRequest))->assertRedirect();

    $accessRequest->refresh();
    expect($accessRequest->status)->toBe('approved')
        ->and($accessRequest->encrypted_symmetric_key)->not->toBeNull()
        ->and(strlen(base64_decode(Crypt::decryptString($accessRequest->encrypted_symmetric_key), true)))->toBe(32);

    $this->actingAs($requester)->get(route('private-access.index'))->assertOk()->assertDontSee($accessRequest->encrypted_symmetric_key);
    $this->actingAs($requester)->get(route('personal-vault.edit', $entryId))->assertNotFound();

    $this->actingAs($owner)->post(route('private-access.revoke', $accessRequest))->assertRedirect();
    expect($accessRequest->fresh()->status)->toBe('revoked')
        ->and($accessRequest->fresh()->encrypted_symmetric_key)->toBeNull();
});

it('handles file requests and decline without generating a key', function (): void {
    $owner = User::factory()->create();
    $requester = User::factory()->create();
    $file = $owner->vaultFiles()->create([
        'original_name' => 'scan.png', 'stored_name' => 'scan.vault', 'category' => 'identity',
        'cipher' => 'aes', 'mime_type' => 'image/png', 'original_size' => 4,
        'original_hash' => hash('sha256', 'scan'),
    ]);

    $this->actingAs($requester)->post(route('private-access.store'), [
        'owner_email' => $owner->email, 'resource_type' => 'vault_file', 'resource_id' => $file->id,
    ])->assertRedirect(route('private-access.index'));

    $accessRequest = PrivateDataAccessRequest::query()->firstOrFail();
    $this->actingAs($owner)->post(route('private-access.decline', $accessRequest))->assertRedirect();
    expect($accessRequest->fresh()->status)->toBe('declined')
        ->and($accessRequest->fresh()->encrypted_symmetric_key)->toBeNull();
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

it('revokes a request and erases its key when the private entry is deleted', function (): void {
    $owner = User::factory()->create();
    $requester = User::factory()->create();
    $entryId = privateEntry($owner);
    $accessRequest = PrivateDataAccessRequest::query()->create([
        'owner_id' => $owner->id, 'requester_id' => $requester->id,
        'resource_type' => 'personal_data', 'resource_id' => $entryId,
        'status' => 'approved', 'encrypted_symmetric_key' => Crypt::encryptString('secret'),
    ]);

    $this->actingAs($owner)->delete(route('personal-vault.destroy', $entryId))->assertRedirect();

    expect($accessRequest->fresh()->status)->toBe('revoked')
        ->and($accessRequest->fresh()->encrypted_symmetric_key)->toBeNull();
});
