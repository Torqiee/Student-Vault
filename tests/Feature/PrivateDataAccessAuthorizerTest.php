<?php

use App\Models\PrivateDataAccessRequest;
use App\Models\User;
use App\Services\Vault\PrivateDataAccessAuthorizer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows access only when the request is approved and has an encrypted key', function (): void {
    $owner = User::factory()->create();
    $requester = User::factory()->create();

    $entry = $owner->personalVaultEntries()->create([
        'encrypted_label' => 'ciphertext',
        'category' => 'other',
        'encrypted_fields' => '{}',
    ]);

    $accessRequest = PrivateDataAccessRequest::query()->create([
        'owner_id' => $owner->id,
        'requester_id' => $requester->id,
        'resource_type' => 'personal_data',
        'resource_id' => $entry->id,
        'status' => 'pending',
    ]);

    $authorizer = app(PrivateDataAccessAuthorizer::class);

    expect($authorizer->allowsRequester(
        $requester,
        'personal_data',
        $entry->id
    ))->toBeFalse();

    $accessRequest->update(['status' => 'declined']);

    expect($authorizer->allowsRequester(
        $requester,
        'personal_data',
        $entry->id
    ))->toBeFalse();

    $accessRequest->update(['status' => 'revoked']);

    expect($authorizer->allowsRequester(
        $requester,
        'personal_data',
        $entry->id
    ))->toBeFalse();

    $accessRequest->update([
        'status' => 'approved',
        'encrypted_symmetric_key' => 'encrypted-test-key',
    ]);

    expect($authorizer->allowsRequester(
        $requester,
        'personal_data',
        $entry->id
    ))->toBeTrue();
});

it('denies access when an approved request has no encrypted key', function (): void {
    $owner = User::factory()->create();
    $requester = User::factory()->create();

    $entry = $owner->personalVaultEntries()->create([
        'encrypted_label' => 'ciphertext',
        'category' => 'other',
        'encrypted_fields' => '{}',
    ]);

    PrivateDataAccessRequest::query()->create([
        'owner_id' => $owner->id,
        'requester_id' => $requester->id,
        'resource_type' => 'personal_data',
        'resource_id' => $entry->id,
        'status' => 'approved',
        'encrypted_symmetric_key' => null,
    ]);

    $authorizer = app(PrivateDataAccessAuthorizer::class);

    expect($authorizer->allowsRequester(
        $requester,
        'personal_data',
        $entry->id
    ))->toBeFalse();
});

it('does not allow another user to use someone elses approval', function (): void {
    $owner = User::factory()->create();
    $approvedRequester = User::factory()->create();
    $otherUser = User::factory()->create();

    $entry = $owner->personalVaultEntries()->create([
        'encrypted_label' => 'ciphertext',
        'category' => 'other',
        'encrypted_fields' => '{}',
    ]);

    PrivateDataAccessRequest::query()->create([
        'owner_id' => $owner->id,
        'requester_id' => $approvedRequester->id,
        'resource_type' => 'personal_data',
        'resource_id' => $entry->id,
        'status' => 'approved',
        'encrypted_symmetric_key' => 'encrypted-test-key',
    ]);

    $authorizer = app(PrivateDataAccessAuthorizer::class);

    expect($authorizer->allowsRequester(
        $approvedRequester,
        'personal_data',
        $entry->id
    ))->toBeTrue();

    expect($authorizer->allowsRequester(
        $otherUser,
        'personal_data',
        $entry->id
    ))->toBeFalse();
});

it('does not allow an approval to be reused for another resource', function (): void {
    $owner = User::factory()->create();
    $requester = User::factory()->create();

    $firstEntry = $owner->personalVaultEntries()->create([
        'encrypted_label' => 'first-ciphertext',
        'category' => 'other',
        'encrypted_fields' => '{}',
    ]);

    $secondEntry = $owner->personalVaultEntries()->create([
        'encrypted_label' => 'second-ciphertext',
        'category' => 'other',
        'encrypted_fields' => '{}',
    ]);

    PrivateDataAccessRequest::query()->create([
        'owner_id' => $owner->id,
        'requester_id' => $requester->id,
        'resource_type' => 'personal_data',
        'resource_id' => $firstEntry->id,
        'status' => 'approved',
        'encrypted_symmetric_key' => 'encrypted-test-key',
    ]);

    $authorizer = app(PrivateDataAccessAuthorizer::class);

    expect($authorizer->allowsRequester(
        $requester,
        'personal_data',
        $firstEntry->id
    ))->toBeTrue();

    expect($authorizer->allowsRequester(
        $requester,
        'personal_data',
        $secondEntry->id
    ))->toBeFalse();
});


it('checks approval for a specific vault file and blocks other users', function (): void {
    $owner = User::factory()->create();
    $requester = User::factory()->create();
    $otherUser = User::factory()->create();

    $file = $owner->vaultFiles()->create([
        'original_name' => 'identity-card.png',
        'stored_name' => 'identity-card.vault',
        'category' => 'identity',
        'cipher' => 'aes',
        'mime_type' => 'image/png',
        'original_size' => 4,
        'original_hash' => hash('sha256', 'scan'),
    ]);

    $otherFile = $owner->vaultFiles()->create([
        'original_name' => 'document.pdf',
        'stored_name' => 'document.vault',
        'category' => 'documents',
        'cipher' => 'aes',
        'mime_type' => 'application/pdf',
        'original_size' => 4,
        'original_hash' => hash('sha256', 'document'),
    ]);

    PrivateDataAccessRequest::query()->create([
        'owner_id' => $owner->id,
        'requester_id' => $requester->id,
        'resource_type' => 'vault_file',
        'resource_id' => $file->id,
        'status' => 'approved',
        'encrypted_symmetric_key' => 'encrypted-test-key',
    ]);

    $authorizer = app(PrivateDataAccessAuthorizer::class);

    // The approved requester can access the exact file.
    expect($authorizer->allowsRequester(
        $requester,
        'vault_file',
        $file->id
    ))->toBeTrue();

    // Another user cannot use the same approval.
    expect($authorizer->allowsRequester(
        $otherUser,
        'vault_file',
        $file->id
    ))->toBeFalse();

    // Approval for one file cannot be reused for another file.
    expect($authorizer->allowsRequester(
        $requester,
        'vault_file',
        $otherFile->id
    ))->toBeFalse();

    // The owner does not use the requester access mechanism.
    expect($authorizer->allowsRequester(
        $owner,
        'vault_file',
        $file->id
    ))->toBeFalse();
});